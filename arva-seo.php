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
 * @since             1.0.0
 * @package           Arva_Seo
 *
 * @wordpress-plugin
 * Plugin Name:       ArvaSEO - Ultimate SEO Assistant
 * Plugin URI:        https://arvaseo.com
 * Description:       Your Ultimate SEO Assistant. Created for SEO Specialist who do site audits and assist them with the process making SEO fixes more fun and easy!
 * Version:           1.0.0
 * Author:            Dajay Digital
 * Author URI:        https://dajaydigital.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       arva-seo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ARVA_SEO_VERSION', '1.0.0' );
define( 'ARVA_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'ARVA_SEO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Let's load all libraries and files needed for the plugin to work via autoloader.
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use ArvaSeo\Core\Activator;
use ArvaSeo\Core\Deactivator;
use ArvaSeo\Core\Bootstrap;

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
 * @since    1.0.0
 */
function run_arva_seo() {

	$plugin = new Bootstrap();
	$plugin->run();

}
run_arva_seo();
