<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Repositories\BulkEditRepository;
use ArvaSeo\Services\SeoProviderResolver;

class ProcessBulkEdit {

	private const CHUNK_SIZE = 20;

	private SeoProviderResolver $resolver;
	private BulkEditRepository $repository;

	public function __construct( SeoProviderResolver $resolver, BulkEditRepository $repository ) {
		$this->resolver = $resolver;
		$this->repository = $repository;
	}

	public function save_preview(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to process bulk edits.', 'arva-seo' ) ], 403 );
		}

		check_ajax_referer( 'arva_seo_bulk_edit_process', 'nonce' );

		$provider = $this->resolver->resolve();

		if ( ! $provider->is_active() ) {
			wp_send_json_error( [ 'message' => __( 'No supported SEO plugin is active.', 'arva-seo' ) ], 400 );
		}

		$rows = isset( $_POST['rows'] ) ? json_decode( wp_unslash( $_POST['rows'] ), true ) : null;

		if ( ! is_array( $rows ) ) {
			wp_send_json_error( [ 'message' => __( 'Preview data is invalid.', 'arva-seo' ) ], 400 );
		}

		$normalized_rows = array_map( [ $this, 'normalize_preview_row' ], $rows );
		$user_id = get_current_user_id();
		$this->repository->save_preview_rows( $user_id, $normalized_rows );
		$state = $this->repository->reset_state( $user_id, count( $normalized_rows ) );

		wp_send_json_success(
			[
				'message' => __( 'Preview saved. Starting bulk update.', 'arva-seo' ),
				'state' => $state,
			]
		);
	}

	public function process_batch(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to process bulk edits.', 'arva-seo' ) ], 403 );
		}

		check_ajax_referer( 'arva_seo_bulk_edit_process', 'nonce' );

		$provider = $this->resolver->resolve();

		if ( ! $provider->is_active() ) {
			wp_send_json_error( [ 'message' => __( 'No supported SEO plugin is active.', 'arva-seo' ) ], 400 );
		}

		$user_id = get_current_user_id();
		$rows = $this->repository->get_preview_rows( $user_id );
		$state = $this->repository->get_state( $user_id );
		$offset = (int) $state['processed'];
		$batch = array_slice( $rows, $offset, self::CHUNK_SIZE );
		$updated = 0;
		$skipped = 0;
		$errors = 0;
		$last_error = '';

		foreach ( $batch as $row ) {
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
				$provider->update_post_fields( (int) $row['post_id'], $fields );
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
					? __( 'Bulk edit completed. Go back to the Crawl page and re-crawl to verify the updated values.', 'arva-seo' )
					: '',
			]
		);

		wp_send_json_success(
			[
				'message' => $done
					? __( 'Bulk edit completed. Go back to the Crawl page and re-crawl to verify the updated values.', 'arva-seo' )
					: sprintf(
						/* translators: 1: processed rows, 2: total rows */
						__( 'Processed %1$d of %2$d rows.', 'arva-seo' ),
						$processed,
						count( $rows )
					),
				'state' => $state,
				'done' => $done,
			]
		);
	}

	private function normalize_preview_row( array $row ): array {
		return [
			'post_id' => isset( $row['post_id'] ) ? (int) $row['post_id'] : 0,
			'url' => isset( $row['url'] ) ? (string) $row['url'] : '',
			'page_title' => isset( $row['page_title'] ) ? (string) $row['page_title'] : '',
			'old_title' => isset( $row['old_title'] ) ? (string) $row['old_title'] : '',
			'new_title' => $this->empty_to_null( $row['new_title'] ?? null ),
			'old_description' => isset( $row['old_description'] ) ? (string) $row['old_description'] : '',
			'new_description' => $this->empty_to_null( $row['new_description'] ?? null ),
			'old_canonical_url' => isset( $row['old_canonical_url'] ) ? (string) $row['old_canonical_url'] : '',
			'new_canonical_url' => $this->empty_to_null( $row['new_canonical_url'] ?? null ),
			'old_no_follow' => ! empty( $row['old_no_follow'] ),
			'new_no_follow' => $this->normalize_nullable_bool( $row['new_no_follow'] ?? null ),
			'old_no_index' => ! empty( $row['old_no_index'] ),
			'new_no_index' => $this->normalize_nullable_bool( $row['new_no_index'] ?? null ),
		];
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
}
