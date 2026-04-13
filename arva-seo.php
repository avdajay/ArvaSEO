<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://dajaydigital.com
 * @since             2.0.0
 * @package           Arva_Seo
 *
 * @wordpress-plugin
 * Plugin Name:       Arva SEO (formerly Bulk Meta Editor)
 * Plugin URI:        https://dajaydigital.com/
 * Description:       Your Ultimate SEO Assistant. Created for SEO Specialist who do site audits and assist them with the process making SEO fixes more fun and easy!
 * Version:           2.0.1
 * Requires at least: 6.6
 * Requires PHP:      8.2
 * Author:            Dajay Digital
 * Author URI:        https://dajaydigital.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bulk-meta-editor
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Currently plugin version.
 * Start at version 2.0.1 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ARVA_SEO_VERSION', '2.0.1' );
define( 'ARVA_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'ARVA_SEO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Let's load all libraries and files needed for the plugin to work via autoloader.
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use ArvaSeo\Core\Activator;
use ArvaSeo\Core\Deactivator;
use ArvaSeo\Core\Bootstrap;

if ( function_exists( 'arva_seo_fs' ) ) {
	arva_seo_fs()->set_basename( true, __FILE__ );
} else
{
	/**
	 * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
	 * `function_exists` CALL ABOVE TO PROPERLY WORK.
	 */
	if ( ! function_exists( 'arva_seo_fs' ) )
	{

		function arva_seo_fs() {
			global $arva_seo_fs;

			if ( ! isset( $arva_seo_fs ) )
			{
				// Include Freemius SDK.
				// SDK is auto-loaded through Composer

				$arva_seo_fs = fs_dynamic_init( array(
					'id'                  => '27093',
					'slug'                => 'arva-seo',
					'type'                => 'plugin',
					'public_key'          => 'pk_507e765c3b97b9658ad21d6e560e6',
					'is_premium'          => true,
					'premium_suffix'      => 'Professional',
					// If your plugin is a serviceware, set this option to false.
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => true,
					// Automatically removed in the free version. If you're not using the
					// auto-generated free version, delete this line before uploading to wp.org.
					'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
					'menu'                => array(
						'slug' => 'arva-seo',
					),
				) );
			}

			return $arva_seo_fs;
		}
	}

	// Init Freemius.
	arva_seo_fs();
	// Signal that SDK was initiated.
	do_action( 'arva_seo_fs_loaded' );

	function arva_seo_cleanup_data_on_uninstall() {
		$settings = get_option( 'arva_seo_settings', [] );

		if ( empty( $settings['delete_data_on_uninstall'] ) && empty( $settings['delete_data_on_deactivation'] ) ) {
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

	arva_seo_fs()->add_action( 'after_uninstall', 'arva_seo_cleanup_data_on_uninstall' );

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-arva-seo-activator.php
	 */
	function activate_arva_seo() {

		Activator::activate();

	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-arva-seo-deactivator.php
	 */
	function deactivate_arva_seo() {
		Deactivator::deactivate();
	}

	register_activation_hook( __FILE__, 'activate_arva_seo' );
	register_deactivation_hook( __FILE__, 'deactivate_arva_seo' );

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    2.0.0
	 */
	function run_arva_seo() {

		$plugin = new Bootstrap();
		$plugin->run();

	}
	run_arva_seo();

}
