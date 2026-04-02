<?php

namespace ArvaSeo\Services;

class AllInOneSeo extends AbstractSeoProvider {

	public function get_provider_name(): string {
		return 'All in One SEO';
	}

	public function is_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' )
			|| is_plugin_active( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' )
			|| defined( 'AIOSEO_VERSION' )
			|| function_exists( 'aioseo' );
	}

	protected function get_title_meta_key(): string {
		return '_aioseo_title';
	}

	protected function get_description_meta_key(): string {
		return '_aioseo_description';
	}

	public function get_post_canonical_url( int $post_id ): string {
		$row = $this->get_aioseo_row( $post_id );
		$canonical = $row['canonical_url'] ?? '';

		return is_string( $canonical ) ? $canonical : '';
	}

	public function is_post_noindex( int $post_id ): bool {
		$row = $this->get_aioseo_row( $post_id );

		return isset( $row['robots_noindex'] ) && '1' === (string) $row['robots_noindex'];
	}

	public function is_post_nofollow( int $post_id ): bool {
		$row = $this->get_aioseo_row( $post_id );

		return isset( $row['robots_nofollow'] ) && '1' === (string) $row['robots_nofollow'];
	}

	private function get_aioseo_row( int $post_id ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aioseo_posts';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		if ( $table_name !== $table_exists ) {
			return [];
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT canonical_url, robots_noindex, robots_nofollow FROM {$table_name} WHERE post_id = %d LIMIT 1",
				$post_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : [];
	}
}
