<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Repositories\BulkEditRepository;
use ArvaSeo\Services\SeoProviderResolver;

class UploadBulkEdit {

	private SeoProviderResolver $resolver;
	private BulkEditRepository $repository;

	public function __construct( SeoProviderResolver $resolver, BulkEditRepository $repository ) {
		$this->resolver = $resolver;
		$this->repository = $repository;
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to upload bulk edit files.', 'arva-seo' ) );
		}

		check_admin_referer( 'arva_seo_upload_bulk_edit', 'arva_seo_upload_nonce' );

		$provider = $this->resolver->resolve();

		if ( ! $provider->is_active() ) {
			$this->redirect_with_notice( 'no-provider' );
		}

		if ( ! isset( $_FILES['bulk_edit_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['bulk_edit_file']['tmp_name'] ) ) {
			$this->redirect_with_notice( 'missing-file' );
		}

		$handle = fopen( $_FILES['bulk_edit_file']['tmp_name'], 'r' );

		if ( false === $handle ) {
			$this->redirect_with_notice( 'invalid-file' );
		}

		$headers = fgetcsv( $handle );

		if ( ! is_array( $headers ) ) {
			fclose( $handle );
			$this->redirect_with_notice( 'invalid-file' );
		}

		$normalized_headers = array_map( [ $this, 'normalize_header' ], $headers );
		$rows = [];

		while ( false !== ( $data = fgetcsv( $handle ) ) ) {
			$row = $this->map_row( $normalized_headers, $data );
			$url = trim( (string) ( $row['url'] ?? '' ) );

			if ( '' === $url ) {
				continue;
			}

			$post_id = $provider->get_post_id( $url );

			if ( $post_id <= 0 ) {
				continue;
			}

			if ( ! $this->is_supported_post_type( $post_id ) ) {
				continue;
			}

			$new_values = [
				'title' => $this->normalize_text_value( $row['title'] ?? null ),
				'description' => $this->normalize_text_value( $row['description'] ?? null ),
				'canonical_url' => $this->normalize_text_value( $row['canonical_url'] ?? null ),
				'no_follow' => $this->normalize_bool_value( $row['no_follow'] ?? null ),
				'no_index' => $this->normalize_bool_value( $row['no_index'] ?? null ),
			];

			if ( ! $this->has_changes( $new_values ) ) {
				continue;
			}

			$rows[] = [
				'post_id' => $post_id,
				'url' => $url,
				'page_title' => get_the_title( $post_id ),
				'old_title' => $provider->get_post_title( $post_id ),
				'new_title' => $new_values['title'],
				'old_description' => $provider->get_post_description( $post_id ),
				'new_description' => $new_values['description'],
				'old_canonical_url' => $provider->get_post_canonical_url( $post_id ),
				'new_canonical_url' => $new_values['canonical_url'],
				'old_no_follow' => $provider->is_post_nofollow( $post_id ),
				'new_no_follow' => $new_values['no_follow'],
				'old_no_index' => $provider->is_post_noindex( $post_id ),
				'new_no_index' => $new_values['no_index'],
			];
		}

		fclose( $handle );

		$user_id = get_current_user_id();
		$this->repository->save_preview_rows( $user_id, $rows );
		$this->repository->clear_state( $user_id );

		$this->redirect_with_notice( 0 === count( $rows ) ? 'empty-preview' : 'preview-ready' );
	}

	private function normalize_header( string $header ): string {
		$header = strtoupper( trim( $header ) );

		return str_replace( ' ', '_', $header );
	}

	private function map_row( array $headers, array $data ): array {
		$row = [];

		foreach ( $headers as $index => $header ) {
			$row[ strtolower( $header ) ] = $data[ $index ] ?? null;
		}

		return $row;
	}

	private function normalize_text_value( $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );

		return '' === $value ? null : $value;
	}

	private function normalize_bool_value( $value ): ?bool {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = strtolower( trim( $value ) );

		if ( '' === $value ) {
			return null;
		}

		if ( in_array( $value, [ '1', 'true', 'yes', 'y' ], true ) ) {
			return true;
		}

		if ( in_array( $value, [ '0', 'false', 'no', 'n' ], true ) ) {
			return false;
		}

		return null;
	}

	private function has_changes( array $new_values ): bool {
		foreach ( $new_values as $value ) {
			if ( null !== $value ) {
				return true;
			}
		}

		return false;
	}

	private function is_supported_post_type( int $post_id ): bool {
		$post_type = get_post_type( $post_id );

		return in_array( $post_type, [ 'post', 'page', 'product' ], true );
	}

	private function redirect_with_notice( string $notice ): void {
		wp_safe_redirect(
			add_query_arg(
				[
					'page' => 'arva-seo-bulk-edit',
					'arva_seo_bulk_notice' => $notice,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
