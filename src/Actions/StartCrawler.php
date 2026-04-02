<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Services\Crawl;

class StartCrawler {

	private Crawl $crawl;

	public function __construct( Crawl $crawl ) {
		$this->crawl = $crawl;
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You are not allowed to start the crawl.', 'arva-seo' ),
				],
				403
			);
		}

		check_ajax_referer( 'arva_seo_start_crawl', 'nonce' );

		if ( function_exists( 'ignore_user_abort' ) ) {
			ignore_user_abort( true );
		}

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}

		if ( ! $this->crawl->is_available() ) {
			wp_send_json_error(
				[
					'message' => __( 'No supported SEO plugin is active.', 'arva-seo' ),
				],
				400
			);
		}

		$limit = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : $this->crawl->get_default_chunk_size();
		$start = isset( $_POST['start'] ) && '1' === wp_unslash( $_POST['start'] );
		$summary = $this->crawl->crawl_batch( $limit, $start );
		$message = $summary['done']
			? sprintf(
				/* translators: 1: number of crawled items, 2: SEO provider name */
				__( 'Crawled %1$d items using %2$s.', 'arva-seo' ),
				(int) $summary['crawled_count'],
				$summary['provider']
			)
			: sprintf(
				/* translators: 1: processed count, 2: total count, 3: provider name */
				__( 'Processed %1$d of %2$d items using %3$s.', 'arva-seo' ),
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
