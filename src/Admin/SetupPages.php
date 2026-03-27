<?php

namespace ArvaSeo\Admin;

use ArvaSeo\Helpers\View;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://dajaydigital.com
 * @since      1.0.0
 *
 * @package    Arva_Seo
 * @subpackage Arva_Seo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two example hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Arva_Seo
 * @subpackage Arva_Seo/admin
 * @author     Dajay Digital <aries@dajaydigital.com>
 */
class SetupPages {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private string $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( string $plugin_name, string $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Creates the admin menu for the plugin.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			'Arva SEO',
			'Arva SEO',
			'manage_options',
			'arva-seo',
			array( $this, 'arva_seo_page' ),
			'dashicons-admin-generic',
			100
		);

		add_submenu_page(
			'arva-seo',
			'ArvaSEO Dashboard',
			'Dashboard',
			'manage_options',
			'arva-seo',
			array( $this, 'arva_seo_page' )
		);

		add_submenu_page(
			'arva-seo',
			'Crawl',
			'Crawl',
			'manage_options',
			'arva-seo-crawl',
			array( $this, 'arva_seo_crawl_page' )
		);

		add_submenu_page(
			'arva-seo',
			'Bulk Edit',
			'Bulk Edit',
			'manage_options',
			'arva-seo-bulk-edit',
			array( $this, 'arva_seo_crawl_page' )
		);

		add_submenu_page(
			'arva-seo',
			'Settings',
			'Settings',
			'manage_options',
			'arva-seo-settings',
			array( $this, 'arva_seo_settings_page' )
		);
	}

	public function arva_seo_page(): null {
		return View::render( 'admin.dashboard' );
	}

	public function arva_seo_settings_page(): null {
		return View::render( 'admin.settings' );
	}

	public function arva_seo_crawl_page(): null {
		return View::render( 'admin.crawl' );
	}

}
