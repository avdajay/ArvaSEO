<?php

namespace ArvaSeo\Repositories;

class SettingsRepository {

	private const OPTION_KEY = 'arva_seo_settings';
	private const MIN_BATCH_SIZE = 50;
	private const MAX_BATCH_SIZE = 1000;

	public function get_settings(): array {
		$settings = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return wp_parse_args(
			$settings,
			[
				'crawl_batch_size' => self::MIN_BATCH_SIZE,
				'bulk_edit_batch_size' => self::MIN_BATCH_SIZE,
				'delete_data_on_deactivation' => ! empty( $settings['delete_data_on_uninstall'] ),
			]
		);
	}

	public function save_settings( array $settings ): array {
		$normalized = [
			'crawl_batch_size' => $this->normalize_batch_size( $settings['crawl_batch_size'] ?? self::MIN_BATCH_SIZE ),
			'bulk_edit_batch_size' => $this->normalize_batch_size( $settings['bulk_edit_batch_size'] ?? self::MIN_BATCH_SIZE ),
			'delete_data_on_deactivation' => ! empty( $settings['delete_data_on_deactivation'] ?? $settings['delete_data_on_uninstall'] ),
		];

		update_option( self::OPTION_KEY, $normalized, false );

		return $normalized;
	}

	public function get_crawl_batch_size(): int {
		$settings = $this->get_settings();

		return $this->normalize_batch_size( $settings['crawl_batch_size'] ?? self::MIN_BATCH_SIZE );
	}

	public function get_bulk_edit_batch_size(): int {
		$settings = $this->get_settings();

		return $this->normalize_batch_size( $settings['bulk_edit_batch_size'] ?? self::MIN_BATCH_SIZE );
	}

	public function should_delete_on_deactivation(): bool {
		$settings = $this->get_settings();

		return ! empty( $settings['delete_data_on_deactivation'] );
	}

	public function normalize_batch_size( $value ): int {
		return max( self::MIN_BATCH_SIZE, min( self::MAX_BATCH_SIZE, (int) $value ) );
	}

	public function get_min_batch_size(): int {
		return self::MIN_BATCH_SIZE;
	}

	public function get_max_batch_size(): int {
		return self::MAX_BATCH_SIZE;
	}
}
