<?php

namespace ArvaSeo\Core;

use ArvaSeo\Extensions\SeoPluginChecker;

/**
 * Fired during plugin activation
 *
 * @link       https://dajaydigital.com
 * @since      1.0.0
 *
 * @package    Arva_Seo
 * @subpackage Arva_Seo/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
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
	 * @since    1.0.0
	 */
	public static function activate(): void {

	}

	public function check_seo_plugins()
	{
		$active_seo_plugin = new SeoPluginChecker();

		if ( $active_seo_plugin->check() ) {
			return true;
		}
	}

}
