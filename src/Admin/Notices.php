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

}
