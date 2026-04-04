<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Repositories\CrawlResultsRepository;
use ArvaSeo\Repositories\CrawlStateRepository;

class ResetCrawlData {

	private CrawlResultsRepository $results_repository;
	private CrawlStateRepository $state_repository;

	public function __construct( CrawlResultsRepository $results_repository, CrawlStateRepository $state_repository ) {
		$this->results_repository = $results_repository;
		$this->state_repository = $state_repository;
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to reset crawl data.', 'arva-seo' ) );
		}

		check_admin_referer( 'arva_seo_reset_crawl_data', 'arva_seo_reset_nonce' );

		$this->results_repository->clear_all_results();
		$this->state_repository->clear_state();

		wp_safe_redirect(
			add_query_arg(
				[
					'page' => 'arva-seo-settings',
					'arva_seo_settings_notice' => 'crawl-reset',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
