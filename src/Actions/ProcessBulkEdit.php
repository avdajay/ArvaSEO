<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Repositories\BulkEditRepository;
use ArvaSeo\Repositories\SettingsRepository;
use ArvaSeo\Services\Licensing;
use ArvaSeo\Services\SeoProviderResolver;

class ProcessBulkEdit {
	private SeoProviderResolver $resolver;
	private BulkEditRepository $repository;
	private SettingsRepository $settings_repository;
	private Licensing $licensing;

	public function __construct( SeoProviderResolver $resolver, BulkEditRepository $repository, SettingsRepository $settings_repository, Licensing $licensing ) {
		$this->resolver = $resolver;
		$this->repository = $repository;
		$this->settings_repository = $settings_repository;
		$this->licensing = $licensing;
	}

	public function save_preview(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to process bulk edits.', 'bulk-meta-editor' ) ], 403 );
		}

		check_ajax_referer( 'arva_seo_bulk_edit_process', 'nonce' );

		if ( $this->resolver->detected_provider_requires_premium() ) {
			wp_send_json_error( [ 'message' => __( 'This SEO provider requires ArvaSEO Premium.', 'bulk-meta-editor' ), 'upgrade_url' => $this->licensing->get_upgrade_url() ], 400 );
		}

		$provider = $this->resolver->resolve();

		if ( ! $provider->is_active() ) {
			wp_send_json_error( [ 'message' => __( 'No supported SEO plugin is active.', 'bulk-meta-editor' ), 'upgrade_url' => $this->licensing->get_upgrade_url() ], 400 );
		}

		$rows = isset( $_POST['rows'] ) ? json_decode( wp_unslash( $_POST['rows'] ), true ) : null;

		if ( ! is_array( $rows ) ) {
			wp_send_json_error( [ 'message' => __( 'Preview data is invalid.', 'bulk-meta-editor' ) ], 400 );
		}

		$normalized_rows = [];
		$user_id = get_current_user_id();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$normalized_row = $this->normalize_preview_row( $row, $provider );

			if ( [] === $normalized_row ) {
				continue;
			}

			$normalized_rows[] = $normalized_row;
		}

		if ( [] === $normalized_rows ) {
			wp_send_json_error( [ 'message' => __( 'No valid rows were found to process.', 'bulk-meta-editor' ) ], 400 );
		}

		$this->repository->save_preview_rows( $user_id, $normalized_rows );
		$state = $this->repository->reset_state( $user_id, count( $normalized_rows ) );

		wp_send_json_success(
			[
				'message' => __( 'Preview saved. Starting bulk update.', 'bulk-meta-editor' ),
				'state' => $state,
			]
		);
	}

	public function process_batch(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to process bulk edits.', 'bulk-meta-editor' ) ], 403 );
		}

		check_ajax_referer( 'arva_seo_bulk_edit_process', 'nonce' );

		if ( $this->resolver->detected_provider_requires_premium() ) {
			wp_send_json_error( [ 'message' => __( 'This SEO provider requires ArvaSEO Premium.', 'bulk-meta-editor' ), 'upgrade_url' => $this->licensing->get_upgrade_url() ], 400 );
		}

		$provider = $this->resolver->resolve();

		if ( ! $provider->is_active() ) {
			wp_send_json_error( [ 'message' => __( 'No supported SEO plugin is active.', 'bulk-meta-editor' ), 'upgrade_url' => $this->licensing->get_upgrade_url() ], 400 );
		}

		$user_id = get_current_user_id();
		$rows = $this->repository->get_preview_rows( $user_id );
		$state = $this->repository->get_state( $user_id );
		$offset = (int) $state['processed'];
		$batch = array_slice( $rows, $offset, $this->settings_repository->get_bulk_edit_batch_size() );
		$updated = 0;
		$skipped = 0;
		$errors = 0;
		$last_error = '';

		foreach ( $batch as $row ) {
			$post_id = isset( $row['post_id'] ) ? absint( $row['post_id'] ) : 0;

			if ( $post_id <= 0 || ! $this->is_supported_post_type( $post_id ) || ! get_post( $post_id ) ) {
				$errors++;
				$last_error = __( 'A bulk edit row references an invalid post.', 'bulk-meta-editor' );
				continue;
			}

			$fields = [
				'title' => $this->empty_to_null( $row['new_title'] ?? null ),
				'description' => $this->empty_to_null( $row['new_description'] ?? null ),
				'canonical_url' => $this->empty_to_null( $row['new_canonical_url'] ?? null ),
				'no_follow' => $this->normalize_nullable_bool( $row['new_no_follow'] ?? null ),
				'no_index' => $this->normalize_nullable_bool( $row['new_no_index'] ?? null ),
			];

			if ( ! $this->has_changes( $fields ) ) {
				$skipped++;
				continue;
			}

			try {
				$provider->update_post_fields( $post_id, $fields );
				$updated++;
			} catch ( \Throwable $throwable ) {
				$errors++;
				$last_error = $throwable->getMessage();
			}
		}

		$processed = min( $offset + count( $batch ), count( $rows ) );
		$done = $processed >= count( $rows );
		$percentage = count( $rows ) > 0 ? (int) floor( ( $processed / count( $rows ) ) * 100 ) : 100;

		$state = $this->repository->update_state(
			$user_id,
			[
				'status' => $done ? 'completed' : 'running',
				'processed' => $processed,
				'updated' => (int) $state['updated'] + $updated,
				'skipped' => (int) $state['skipped'] + $skipped,
				'errors' => (int) $state['errors'] + $errors,
				'percentage' => $percentage,
				'last_error' => $last_error,
				'completed_message' => $done
					? __( 'Bulk edit completed. Go back to the Crawl page and re-crawl to verify the updated values.', 'bulk-meta-editor' )
					: '',
			]
		);

		wp_send_json_success(
			[
				'message' => $done
					? __( 'Bulk edit completed. Go back to the Crawl page and re-crawl to verify the updated values.', 'bulk-meta-editor' )
					: sprintf(
						/* translator: 1: number of processed rows, 2: total number of rows */
						__( 'Processed %1$d of %2$d rows.', 'bulk-meta-editor' ),
						$processed,
						count( $rows )
					),
				'state' => $state,
				'done' => $done,
			]
		);
	}

	private function normalize_preview_row( array $row, $provider ): array {
		$post_id = isset( $row['post_id'] ) ? absint( $row['post_id'] ) : 0;

		if ( $post_id <= 0 || ! $this->is_supported_post_type( $post_id ) || ! get_post( $post_id ) ) {
			return [];
		}

		$url = get_permalink( $post_id );
		$page_title = get_the_title( $post_id );

		if ( ! is_string( $url ) || '' === $url ) {
			return [];
		}

		return [
			'post_id' => $post_id,
			'url' => $url,
			'page_title' => is_string( $page_title ) ? $page_title : '',
			'old_title' => $provider->get_post_title( $post_id ),
			'new_title' => $this->normalize_title_value( $row['new_title'] ?? null ),
			'old_description' => $provider->get_post_description( $post_id ),
			'new_description' => $this->normalize_description_value( $row['new_description'] ?? null ),
			'old_canonical_url' => $provider->get_post_canonical_url( $post_id ),
			'new_canonical_url' => $this->normalize_canonical_value( $row['new_canonical_url'] ?? null ),
			'old_no_follow' => $provider->is_post_nofollow( $post_id ),
			'new_no_follow' => $this->normalize_nullable_bool( $row['new_no_follow'] ?? null ),
			'old_no_index' => $provider->is_post_noindex( $post_id ),
			'new_no_index' => $this->normalize_nullable_bool( $row['new_no_index'] ?? null ),
		];
	}

	private function normalize_title_value( $value ): ?string {
		$value = $this->empty_to_null( $value );

		return null === $value ? null : sanitize_text_field( $value );
	}

	private function normalize_description_value( $value ): ?string {
		$value = $this->empty_to_null( $value );

		return null === $value ? null : sanitize_textarea_field( $value );
	}

	private function normalize_canonical_value( $value ): ?string {
		$value = $this->empty_to_null( $value );

		if ( null === $value ) {
			return null;
		}

		$value = esc_url_raw( $value );

		return wp_http_validate_url( $value ) ? $value : null;
	}

	private function empty_to_null( $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );

		return '' === $value ? null : $value;
	}

	private function normalize_nullable_bool( $value ): ?bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( null === $value || '' === $value ) {
			return null;
		}

		$value = strtolower( trim( (string) $value ) );

		if ( in_array( $value, [ '1', 'true', 'yes' ], true ) ) {
			return true;
		}

		if ( in_array( $value, [ '0', 'false', 'no' ], true ) ) {
			return false;
		}

		return null;
	}

	private function has_changes( array $fields ): bool {
		foreach ( $fields as $value ) {
			if ( null !== $value ) {
				return true;
			}
		}

		return false;
	}

	private function is_supported_post_type( int $post_id ): bool {
		return in_array( get_post_type( $post_id ), [ 'post', 'page', 'product' ], true );
	}
}
