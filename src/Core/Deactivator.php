<?php

namespace ArvaSeo\Core;

/**
 * Fired during plugin deactivation
 *
 * @link       https://dajaydigital.com
 * @since      1.0.0
 *
 * @package    Arva_Seo
 * @subpackage Arva_Seo/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Arva_Seo
 * @subpackage Arva_Seo/includes
 * @author     Dajay Digital <aries@dajaydigital.com>
 */
class Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		$settings = get_option( 'arva_seo_settings', [] );

		if ( empty( $settings['delete_data_on_deactivation'] ) && empty( $settings['delete_data_on_uninstall'] ) ) {
			return;
		}

		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}arva_seo_crawl_results" );

		delete_option( 'arva_seo_settings' );
		delete_option( 'arva_seo_no_seo_plugin_notice' );
		delete_option( 'arva_seo_crawl_schema_version' );
		delete_option( 'arva_seo_crawl_state' );
		delete_metadata( 'user', 0, 'arva_seo_bulk_edit_preview', '', true );
		delete_metadata( 'user', 0, 'arva_seo_bulk_edit_state', '', true );
	}

}
