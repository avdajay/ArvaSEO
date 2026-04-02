<?php

namespace ArvaSeo\Admin;

use ArvaSeo\Helpers\View;
use ArvaSeo\Repositories\CrawlResultsRepository;
use ArvaSeo\Repositories\CrawlStateRepository;
use ArvaSeo\Services\SeoProviderResolver;

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
	private CrawlResultsRepository $crawl_results_repository;
	private CrawlStateRepository $crawl_state_repository;
	private SeoProviderResolver $provider_resolver;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct(
		string $plugin_name,
		string $version,
		CrawlResultsRepository $crawl_results_repository,
		CrawlStateRepository $crawl_state_repository,
		SeoProviderResolver $provider_resolver
	) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->crawl_results_repository = $crawl_results_repository;
		$this->crawl_state_repository = $crawl_state_repository;
		$this->provider_resolver = $provider_resolver;

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
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIj48cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTEwIDJMMyAxN2gzbDItNGg0bDIgNGgzTDEwIDJ6bTAgNWwtMS41IDNoM0wxMCA3eiIgZmlsbD0iY3VycmVudENvbG9yIi8+PHBhdGggZD0iTTEzIDNoNXY1bC0yLTItMi41IDIuNS0xLjUtMS41TDE0LjUgNC41IDEzIDN6IiBmaWxsPSJjdXJyZW50Q29sb3IiLz48L3N2Zz4=',
			100
		);

		add_submenu_page(
			'arva-seo',
			'Opportunities',
			'Opportunities',
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
			array( $this, 'arva_seo_bulk_edit_page' )
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

	public function arva_seo_bulk_edit_page(): null {
		return View::render( 'admin.bulk-edit' );
	}

	public function arva_seo_settings_page(): null {
		return View::render( 'admin.settings' );
	}

	public function arva_seo_crawl_page(): null {
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$per_page = 20;
		$total_rows_stored = $this->crawl_results_repository->count_results();
		$search_results_count = $this->crawl_results_repository->count_results_by_search( $search_query );
		$total_pages = max( 1, (int) ceil( $search_results_count / $per_page ) );
		$current_page = min( $current_page, $total_pages );
		$last_crawled_at = $this->crawl_results_repository->get_last_crawled_at();

		return View::render(
			'admin.crawl',
			[
				'active_provider' => $this->provider_resolver->get_active_provider_name(),
				'has_active_provider' => $this->provider_resolver->has_active_provider(),
				'results' => $this->crawl_results_repository->get_paginated_results( $current_page, $per_page, $search_query ),
				'current_page' => $current_page,
				'total_pages' => $total_pages,
				'total_items' => $total_rows_stored,
				'search_results_count' => $search_results_count,
				'last_crawled_at' => $last_crawled_at,
				'crawl_state' => $this->crawl_state_repository->get_state(),
				'search_query' => $search_query,
				'export_url' => wp_nonce_url(
					add_query_arg(
						[
							'action' => 'arva_seo_export_crawl',
							's' => $search_query,
						],
						admin_url( 'admin-post.php' )
					),
					'arva_seo_export_crawl',
					'arva_seo_export_nonce'
				),
			]
		);
	}

}
