<?php

namespace ArvaSeo\Admin;

use ArvaSeo\Repositories\SettingsRepository;

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
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Arva_Seo
 * @subpackage Arva_Seo/admin
 * @author     Dajay Digital <aries@dajaydigital.com>
 */
class AdminEnqueue {

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
	private SettingsRepository $settings_repository;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( string $plugin_name, string $version, SettingsRepository $settings_repository )
	{

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->settings_repository = $settings_repository;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
    public function enqueue_styles(): void
    {
        wp_enqueue_style( $this->plugin_name, ARVA_SEO_URL . 'assets/css/arva-seo.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts(): void
    {
		$settings = $this->settings_repository->get_settings();
        wp_enqueue_script( $this->plugin_name, ARVA_SEO_URL . 'assets/js/arva-seo.js', array(), $this->version, true );
		wp_localize_script(
			$this->plugin_name,
			'arvaSeoAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'crawlNonce' => wp_create_nonce( 'arva_seo_start_crawl' ),
				'crawlAction' => 'arva_seo_start_crawl',
				'crawlChunkSize' => (int) $settings['crawl_batch_size'],
				'bulkEditNonce' => wp_create_nonce( 'arva_seo_bulk_edit_process' ),
				'bulkEditPrepareAction' => 'arva_seo_bulk_edit_prepare',
				'bulkEditProcessAction' => 'arva_seo_bulk_edit_process',
			]
		);
    }

}
