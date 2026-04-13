<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Services\Crawl;
use ArvaSeo\Services\Licensing;

class StartCrawler {

	private Crawl $crawl;
	private Licensing $licensing;

	public function __construct( Crawl $crawl, Licensing $licensing ) {
		$this->crawl = $crawl;
		$this->licensing = $licensing;
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You are not allowed to start the crawl.', 'bulk-meta-editor' ),
				],
				403
			);
		}

		check_ajax_referer( 'arva_seo_start_crawl', 'nonce' );

		if ( function_exists( 'arva_seo_fs' ) && arva_seo_fs()->is__premium_only() ) {
			$this->handle_premium__premium_only();
			return;
		}

		wp_send_json_error(
			[
				'message' => $this->licensing->get_crawl_upgrade_message(),
				'upgrade_url' => $this->licensing->get_upgrade_url(),
			],
			403
		);
	}

	private function handle_premium__premium_only(): void {
		if ( function_exists( 'ignore_user_abort' ) ) {
			ignore_user_abort( true );
		}

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}

		if ( ! $this->crawl->is_available() ) {
			$message = __( 'No supported SEO plugin is active.', 'bulk-meta-editor' );

			if ( $this->licensing->is_free_user() ) {
				$message = __( 'The free version supports Yoast SEO only. Upgrade to use Rank Math, All in One SEO, or SEOPress.', 'bulk-meta-editor' );
			}

			wp_send_json_error(
				[
					'message' => $message,
					'upgrade_url' => $this->licensing->get_upgrade_url(),
				],
				400
			);
		}

		$limit = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : $this->crawl->get_default_chunk_size();
		$start = isset( $_POST['start'] ) && '1' === wp_unslash( $_POST['start'] );
		$summary = $this->crawl->crawl_batch( $limit, $start );
		$message = $summary['done']
			? sprintf(
				/* translator: 1: number of crawled items, 2: SEO provider name */
				__( 'Crawled %1$d items using %2$s.', 'bulk-meta-editor' ),
				(int) $summary['crawled_count'],
				$summary['provider']
			)
			: sprintf(
				/* translator: 1: processed count, 2: total count, 3: SEO provider name */
				__( 'Processed %1$d of %2$d items using %3$s.', 'bulk-meta-editor' ),
				(int) $summary['processed'],
				(int) $summary['total'],
				$summary['provider']
			);

		wp_send_json_success(
			[
				'message' => $message,
				'summary' => $summary,
				'state' => $this->crawl->get_state(),
			]
		);
	}
}
