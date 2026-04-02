<?php

namespace ArvaSeo\Services;

class SEOPress extends AbstractSeoProvider {

	public function get_provider_name(): string {
		return 'SEOPress';
	}

	public function is_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'wp-seopress/seopress.php' )
			|| is_plugin_active( 'wp-seopress-pro/seopress-pro.php' )
			|| defined( 'SEOPRESS_VERSION' )
			|| defined( 'SEOPRESS_PRO_VERSION' );
	}

	protected function get_title_meta_key(): string {
		return '_seopress_titles_title';
	}

	protected function get_description_meta_key(): string {
		return '_seopress_titles_desc';
	}

	public function get_post_canonical_url( int $post_id ): string {
		$canonical = get_post_meta( $post_id, '_seopress_robots_canonical', true );

		return is_string( $canonical ) ? $canonical : '';
	}

	public function is_post_noindex( int $post_id ): bool {
		return 'yes' === get_post_meta( $post_id, '_seopress_robots_index', true );
	}

	public function is_post_nofollow( int $post_id ): bool {
		return 'yes' === get_post_meta( $post_id, '_seopress_robots_follow', true );
	}
}
