<?php

namespace ArvaSeo\Repositories;

class CrawlStateRepository {

	private const OPTION_KEY = 'arva_seo_crawl_state';

	public function get_state(): array {
		$state = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $state ) ) {
			$state = [];
		}

		return wp_parse_args(
			$state,
			[
				'provider' => '',
				'status' => 'idle',
				'current_offset' => 0,
				'processed' => 0,
				'total' => 0,
				'percentage' => 0,
				'crawled_count' => 0,
				'skipped_count' => 0,
				'error_count' => 0,
				'last_error' => '',
				'started_at' => null,
				'updated_at' => null,
				'completed_at' => null,
			]
		);
	}

	public function save_state( array $state ): array {
		update_option( self::OPTION_KEY, $state, false );

		return $state;
	}

	public function reset_state( string $provider, int $total ): array {
		$now = current_time( 'mysql' );

		return $this->save_state(
			[
				'provider' => $provider,
				'status' => 'running',
				'current_offset' => 0,
				'processed' => 0,
				'total' => $total,
				'percentage' => 0,
				'crawled_count' => 0,
				'skipped_count' => 0,
				'error_count' => 0,
				'last_error' => '',
				'started_at' => $now,
				'updated_at' => $now,
				'completed_at' => null,
			]
		);
	}

	public function update_progress( array $changes ): array {
		$state = $this->get_state();

		return $this->save_state(
			array_merge(
				$state,
				$changes,
				[
					'updated_at' => current_time( 'mysql' ),
				]
			)
		);
	}

	public function mark_completed( array $state ): array {
		$now = current_time( 'mysql' );

		return $this->save_state(
			array_merge(
				$state,
				[
					'status' => 'completed',
					'percentage' => 100,
					'updated_at' => $now,
					'completed_at' => $now,
				]
			)
		);
	}

	public function clear_state(): void {
		delete_option( self::OPTION_KEY );
	}
}
