<?php

namespace ArvaSeo\Core;

use ArvaSeo\Admin\AdminEnqueue;
use ArvaSeo\Admin\Notices;
use ArvaSeo\Admin\SetupPages;
use ArvaSeo\Actions\ExportCrawl;
use ArvaSeo\Actions\ProcessBulkEdit;
use ArvaSeo\Actions\ResetCrawlData;
use ArvaSeo\Actions\SaveSettings;
use ArvaSeo\Actions\StartCrawler;
use ArvaSeo\Actions\UploadBulkEdit;
use ArvaSeo\Repositories\BulkEditRepository;
use ArvaSeo\Repositories\CrawlResultsRepository;
use ArvaSeo\Repositories\CrawlStateRepository;
use ArvaSeo\Repositories\SettingsRepository;
use ArvaSeo\Services\Crawl;
use ArvaSeo\Services\SeoProviderResolver;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://dajaydigital.com
 * @since      1.0.0
 *
 * @package    Arva_Seo
 * @subpackage Arva_Seo/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Arva_Seo
 * @subpackage Arva_Seo/includes
 * @author     Dajay Digital <aries@dajaydigital.com>
 */
class Bootstrap {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Hooks $loader Maintains and registers all hooks for the plugin.
	 */
	protected Hooks $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected string $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected string $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if ( defined( 'ARVA_SEO_VERSION' ) ) {
			$this->version = ARVA_SEO_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'arva-seo';

		$this->init_hooks();
		$this->set_locale();
		$this->load_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Arva_Seo_Loader. Orchestrates the hooks of the plugin.
	 * - Arva_Seo_i18n. Defines internationalization functionality.
	 * - Arva_Seo_Admin. Defines all hooks for the admin area.
	 * - Arva_Seo_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_hooks(): void
	{
		$this->loader = new Hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Arva_Seo_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale(): void
	{
		$plugin_i18n = new Internationalization();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_hooks(): void
	{
		$bulk_edit_repository = new BulkEditRepository();
		$crawl_results_repository = new CrawlResultsRepository();
		$crawl_results_repository->ensure_schema();
		$crawl_state_repository = new CrawlStateRepository();
		$settings_repository = new SettingsRepository();
		$provider_resolver = new SeoProviderResolver();
		$crawl = new Crawl( $provider_resolver->resolve(), $crawl_results_repository, $settings_repository, $crawl_state_repository );
		$admin_enqueue = new AdminEnqueue( $this->get_plugin_name(), $this->get_version(), $settings_repository );
		$setup_pages = new SetupPages(
			$this->get_plugin_name(),
			$this->get_version(),
			$bulk_edit_repository,
			$crawl_results_repository,
			$crawl_state_repository,
			$settings_repository,
			$provider_resolver
		);
		$notices = new Notices();
		$start_crawler = new StartCrawler( $crawl );
		$export_crawl = new ExportCrawl( $crawl_results_repository );
		$upload_bulk_edit = new UploadBulkEdit( $provider_resolver, $bulk_edit_repository );
		$process_bulk_edit = new ProcessBulkEdit( $provider_resolver, $bulk_edit_repository, $settings_repository );
		$save_settings = new SaveSettings( $settings_repository );
		$reset_crawl_data = new ResetCrawlData( $crawl_results_repository, $crawl_state_repository );

		$this->loader->add_action( 'admin_enqueue_scripts', $admin_enqueue, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin_enqueue, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $setup_pages, 'add_admin_menu' );
		$this->loader->add_action( 'admin_notices', $notices, 'no_seo_plugin_notice' );
		$this->loader->add_action( 'admin_notices', $notices, 'crawl_complete_notice' );
		$this->loader->add_action( 'wp_ajax_arva_seo_start_crawl', $start_crawler, 'handle' );
		$this->loader->add_action( 'admin_post_arva_seo_export_crawl', $export_crawl, 'handle' );
		$this->loader->add_action( 'admin_post_arva_seo_upload_bulk_edit', $upload_bulk_edit, 'handle' );
		$this->loader->add_action( 'admin_post_arva_seo_save_settings', $save_settings, 'handle' );
		$this->loader->add_action( 'admin_post_arva_seo_reset_crawl_data', $reset_crawl_data, 'handle' );
		$this->loader->add_action( 'wp_ajax_arva_seo_bulk_edit_prepare', $process_bulk_edit, 'save_preview' );
		$this->loader->add_action( 'wp_ajax_arva_seo_bulk_edit_process', $process_bulk_edit, 'process_batch' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run(): void
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name(): string
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Hooks    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader(): Hooks
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version(): string
	{
		return $this->version;
	}

}
