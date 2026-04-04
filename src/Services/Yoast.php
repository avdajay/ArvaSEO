<?php

namespace ArvaSeo\Services;

class Yoast extends AbstractSeoProvider {

	public function get_provider_name(): string {
		return 'Yoast SEO';
	}

	public function is_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'wordpress-seo/wp-seo.php' )
			|| is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' )
			|| defined( 'WPSEO_VERSION' )
			|| function_exists( 'YoastSEO' );
	}

	protected function get_title_meta_key(): string {
		return '_yoast_wpseo_title';
	}

	protected function get_description_meta_key(): string {
		return '_yoast_wpseo_metadesc';
	}

	public function get_post_score( int $post_id ): int {
		$score = get_post_meta( $post_id, '_yoast_wpseo_linkdex', true );

		if ( ! is_numeric( $score ) ) {
			$score = get_post_meta( $post_id, '_yoast_wpseo_content_score', true );
		}

		return is_numeric( $score ) ? max( 0, min( 100, (int) $score ) ) : -1;
	}

	public function get_post_canonical_url( int $post_id ): string {
		$canonical = get_post_meta( $post_id, '_yoast_wpseo_canonical', true );

		return is_string( $canonical ) ? $canonical : '';
	}

	public function is_post_noindex( int $post_id ): bool {
		$value = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );

		return in_array( (string) $value, [ '1', '2', 'yes', 'true', 'noindex' ], true );
	}

	public function is_post_nofollow( int $post_id ): bool {
		$value = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true );

		return in_array( (string) $value, [ '1', 'yes', 'true', 'nofollow' ], true );
	}

	protected function update_post_canonical_url( int $post_id, string $value ): void {
		update_post_meta( $post_id, '_yoast_wpseo_canonical', $value );
	}

	protected function update_post_noindex( int $post_id, bool $value ): void {
		update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', $value ? '1' : '0' );
	}

	protected function update_post_nofollow( int $post_id, bool $value ): void {
		update_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', $value ? '1' : '0' );
	}

}
