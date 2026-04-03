<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Repositories\CrawlResultsRepository;

class ExportCrawl {

	private CrawlResultsRepository $repository;

	public function __construct( CrawlResultsRepository $repository ) {
		$this->repository = $repository;
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to export crawl data.', 'arva-seo' ) );
		}

		check_admin_referer( 'arva_seo_export_crawl', 'arva_seo_export_nonce' );

		$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$rows = $this->repository->get_results_for_export( $search_query );
		$filename = 'arva-seo-crawl-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to generate export file.', 'arva-seo' ) );
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
