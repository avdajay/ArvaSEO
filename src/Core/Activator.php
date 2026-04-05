<?php

namespace ArvaSeo\Core;

use ArvaSeo\Repositories\CrawlResultsRepository;
use ArvaSeo\Services\SeoChecker;

/**
 * Fired during plugin activation
 *
 * @link       https://dajaydigital.com
 * @since      2.0.0
 *
 * @package    Arva_Seo
 * @subpackage Arva_Seo/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    Arva_Seo
 * @subpackage Arva_Seo/includes
 * @author     Dajay Digital <aries@dajaydigital.com>
 */
class Activator {

	/**
	 * Runs all actions necessary to activate the plugin.
	 *
	 * Set up the plugin's initial state. And check for required dependencies.
	 *
	 * @since    2.0.0
	 */
	public static function activate(): void
	{
        self::check_seo_plugins();
		( new CrawlResultsRepository() )->ensure_schema();
	}

    public static function check_seo_plugins(): bool {
        $active_seo_plugin = new SeoChecker();

        if ( $active_seo_plugin->check() ) {
            return true;
        }

        update_option( 'arva_seo_no_seo_plugin_notice', true );

        return false;
    }

}
