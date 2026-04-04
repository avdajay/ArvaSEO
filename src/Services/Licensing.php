<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;

class Licensing {

	public function can_use_premium_features(): bool {
		if ( function_exists( 'arva_seo_fs' ) && arva_seo_fs()->is__premium_only() ) {
			return arva_seo_fs()->can_use_premium_code__premium_only();
		}

		return false;
	}

	public function is_free_user(): bool {
		return ! $this->can_use_premium_features();
	}

	public function get_upgrade_url(): string {
		if ( function_exists( 'arva_seo_fs' ) ) {
			return (string) arva_seo_fs()->get_upgrade_url();
		}

		return admin_url( 'admin.php?page=arva-seo-pricing' );
	}

	public function can_access_crawl_page(): bool {
		return $this->can_use_premium_features();
	}

	public function can_use_provider( SeoService $provider ): bool {
		$provider_name = $provider->get_provider_name();

		if ( in_array( $provider_name, [ 'None', 'Yoast SEO' ], true ) ) {
			return true;
		}

		return $this->can_use_premium_features();
	}

	public function provider_requires_premium( SeoService $provider ): bool {
		return ! $this->can_use_provider( $provider ) && 'None' !== $provider->get_provider_name();
	}

	public function can_crawl_post_type( string $post_type ): bool {
		if ( 'product' === $post_type ) {
			return $this->can_use_premium_features();
		}

		return true;
	}

	public function get_provider_upgrade_message( string $provider_name ): string {
		return sprintf(
			/* translators: %s: SEO provider name */
			__( '%s support is available in ArvaSEO Premium. The free version includes Yoast SEO support only.', 'arva-seo' ),
			$provider_name
		);
	}

	public function get_crawl_upgrade_message(): string {
		return __( 'The Crawl workspace is a premium feature. Upgrade to unlock full-site crawling, exports, and recrawling workflows.', 'arva-seo' );
	}

	public function get_woocommerce_upgrade_message(): string {
		return __( 'WooCommerce product crawling is a premium feature. Upgrade to include product SEO data in your crawl dataset.', 'arva-seo' );
	}
}
