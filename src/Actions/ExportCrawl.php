<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Repositories\CrawlResultsRepository;
use ArvaSeo\Services\Licensing;

class ExportCrawl {

	private CrawlResultsRepository $repository;
	private Licensing $licensing;

	public function __construct( CrawlResultsRepository $repository, Licensing $licensing ) {
		$this->repository = $repository;
		$this->licensing = $licensing;
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to export crawl data.', 'bulk-meta-editor' ) );
		}

		check_admin_referer( 'arva_seo_export_crawl', 'arva_seo_export_nonce' );

		if ( function_exists( 'arva_seo_fs' ) && arva_seo_fs()->is__premium_only() ) {
			$this->export__premium_only();
			return;
		}

		wp_die( esc_html( $this->licensing->get_crawl_upgrade_message() ) );
	}

	private function export__premium_only(): void {
		$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$rows = $this->repository->get_results_for_export( $search_query );
		$filename = 'arva-seo-crawl-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to generate export file.', 'bulk-meta-editor' ) );
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

		foreach ( $rows as $row ) {
			fputcsv(
				$output,
				[
					(string) ( $row['permalink'] ?? '' ),
					(string) ( $row['seo_title'] ?? '' ),
					(string) ( $row['seo_description'] ?? '' ),
					(string) ( $row['canonical_url'] ?? '' ),
					! empty( $row['robots_nofollow'] ) ? 'TRUE' : 'FALSE',
					! empty( $row['robots_noindex'] ) ? 'TRUE' : 'FALSE',
				]
			);
		}

		fclose( $output );
		exit;
	}
}
