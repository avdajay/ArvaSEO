<?php

namespace ArvaSeo\Actions;

class DownloadBulkEditTemplate {

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to download the bulk edit template.', 'bulk-meta-editor' ) );
		}

		check_admin_referer( 'arva_seo_download_bulk_edit_template', 'arva_seo_template_nonce' );

		$filename = 'arva-seo-bulk-edit-template-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to generate the template file.', 'bulk-meta-editor' ) );
		}

		fputcsv(
			$output,
			[
				'URL',
				'TITLE',
				'DESCRIPTION',
				'CANONICAL_URL',
				'NO_FOLLOW',
				'NO_INDEX',
			]
		);

		fclose( $output );
		exit;
	}
}
