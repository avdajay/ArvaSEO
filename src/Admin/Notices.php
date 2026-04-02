<?php

namespace ArvaSeo\Admin;

use ArvaSeo\Services\SeoChecker;
use ArvaSeo\Helpers\View;

class Notices {

    public function no_seo_plugin_notice(): void {
        $checker = new SeoChecker();

        if ( $checker->check() ) {
            // SEO plugin is now active, clean up and stop showing the notice
            delete_option( 'arva_seo_no_seo_plugin_notice' );
            return;
        }

        if ( ! get_option( 'arva_seo_no_seo_plugin_notice' ) ) {
            return;
        }

        View::render( 'notices.no-seo-plugin' );
    }

	public function crawl_complete_notice(): void {
		if ( ! isset( $_GET['page'], $_GET['arva_seo_notice'] ) ) {
			return;
		}

		if ( 'arva-seo-crawl' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( 'crawl-complete' !== sanitize_text_field( wp_unslash( $_GET['arva_seo_notice'] ) ) ) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>' .
			esc_html__( 'Crawl completed successfully.', 'arva-seo' ) .
			'</p></div>';
	}

}
