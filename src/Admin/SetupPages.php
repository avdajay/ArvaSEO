<?php

namespace ArvaSeo\Admin;

use ArvaSeo\Helpers\View;
use ArvaSeo\Repositories\BulkEditRepository;
use ArvaSeo\Repositories\CrawlResultsRepository;
use ArvaSeo\Repositories\CrawlStateRepository;
use ArvaSeo\Repositories\SettingsRepository;
use ArvaSeo\Services\Licensing;
use ArvaSeo\Services\SeoProviderResolver;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://dajaydigital.com
 * @since      2.0.0
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
	 * @since    2.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private string $version;
	private BulkEditRepository $bulk_edit_repository;
	private CrawlResultsRepository $crawl_results_repository;
	private CrawlStateRepository $crawl_state_repository;
	private SettingsRepository $settings_repository;
	private SeoProviderResolver $provider_resolver;
	private Licensing $licensing;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    2.0.0
	 */
	public function __construct(
		string $plugin_name,
		string $version,
		BulkEditRepository $bulk_edit_repository,
		CrawlResultsRepository $crawl_results_repository,
		CrawlStateRepository $crawl_state_repository,
		SettingsRepository $settings_repository,
		SeoProviderResolver $provider_resolver,
		Licensing $licensing
	) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->bulk_edit_repository = $bulk_edit_repository;
		$this->crawl_results_repository = $crawl_results_repository;
		$this->crawl_state_repository = $crawl_state_repository;
		$this->settings_repository = $settings_repository;
		$this->provider_resolver = $provider_resolver;
		$this->licensing = $licensing;

	}

	/**
	 * Creates the admin menu for the plugin.
	 *
	 * @since    2.0.0
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'Arva SEO', 'arva-seo' ),
			__( 'Arva SEO', 'arva-seo' ),
			'manage_options',
			'arva-seo',
			array( $this, 'arva_seo_page' ),
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNTAgMjUwIj4KICA8ZyBmaWxsPSJub25lIiBzdHJva2U9ImN1cnJlbnRDb2xvciIgc3Ryb2tlLXdpZHRoPSIxMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIj4KICAgIDwhLS0gQ2VudHJhbCBDaXJjbGUgLS0+CiAgICA8Y2lyY2xlIGN4PSIxMjUiIGN5PSIxMjUiIHI9IjQwIi8+CiAgICAKICAgIDwhLS0gUmFkaWFsIFNwb2tlcyAtLT4KICAgIDxsaW5lIHgxPSIxMjUiIHkxPSI4NSIgeDI9IjEyNSIgeTI9IjQwIi8+CiAgICA8bGluZSB4MT0iMTI1IiB5MT0iMTY1IiB4Mj0iMTI1IiB5Mj0iMjEwIi8+CiAgICA8bGluZSB4MT0iMTY1IiB5MT0iMTI1IiB4Mj0iMjEwIiB5Mj0iMTI1Ii8+CiAgICA8bGluZSB4MT0iODUiIHkxPSIxMjUiIHgyPSI0MCIgeTI9IjEyNSIvPgogICAgCiAgICA8IS0tIERpYWdvbmFsIFNwb2tlcyAtLT4KICAgIDxsaW5lIHgxPSI5NyIgeTE9Ijk3IiB4Mj0iNjUiIHkyPSI2NSIvPgogICAgPGxpbmUgeDE9IjE1MyIgeTE9Ijk3IiB4Mj0iMTg1IiB5Mj0iNjUiLz4KICAgIDxsaW5lIHgxPSI5NyIgeTE9IjE1MyIgeDI9IjY1IiB5Mj0iMTg1Ii8+CiAgICA8bGluZSB4MT0iMTUzIiB5MT0iMTUzIiB4Mj0iMTg1IiB5Mj0iMTg1Ii8+CiAgICAKICAgIDwhLS0gT3V0ZXIgQXJjcyAtLT4KICAgIDxwYXRoIGQ9Ik0xNzAgODAgQTcwIDcwIDAgMCAxIDE5MCAxNTAiLz4KICAgIDxwYXRoIGQ9Ik04MCAxNzAgQTcwIDcwIDAgMCAxIDYwIDEwMCIvPgogIDwvZz4KPC9zdmc+',
			100
		);

		add_submenu_page(
			'arva-seo',
			__( 'Opportunities', 'arva-seo' ),
			__( 'Opportunities', 'arva-seo' ),
			'manage_options',
			'arva-seo',
			array( $this, 'arva_seo_page' )
		);

		add_submenu_page(
			'arva-seo',
			__( 'Crawl', 'arva-seo' ),
			__( 'Crawl', 'arva-seo' ),
			'manage_options',
			'arva-seo-crawl',
			array( $this, 'arva_seo_crawl_page' )
		);

		add_submenu_page(
			'arva-seo',
			__( 'Bulk Edit', 'arva-seo' ),
			__( 'Bulk Edit', 'arva-seo' ),
			'manage_options',
			'arva-seo-bulk-edit',
			array( $this, 'arva_seo_bulk_edit_page' )
		);

		add_submenu_page(
			'arva-seo',
			__( 'Settings', 'arva-seo' ),
			__( 'Settings', 'arva-seo' ),
			'manage_options',
			'arva-seo-settings',
			array( $this, 'arva_seo_settings_page' )
		);
	}

	public function arva_seo_page(): null {
		$dashboard = $this->crawl_results_repository->get_opportunities_dashboard();
		$selected_opportunity = isset( $_GET['opportunity'] ) ? sanitize_text_field( wp_unslash( $_GET['opportunity'] ) ) : '';
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page = 20;
		$detail_total = '' !== $selected_opportunity ? $this->crawl_results_repository->count_opportunity_items( $selected_opportunity ) : 0;
		$detail_total_pages = max( 1, (int) ceil( $detail_total / $per_page ) );
		$current_page = min( $current_page, $detail_total_pages );

		return View::render(
			'admin.opportunities',
			[
				'dashboard' => $dashboard,
				'detected_provider' => $this->provider_resolver->get_detected_provider_name(),
				'provider_requires_premium' => $this->provider_resolver->detected_provider_requires_premium(),
				'provider_upgrade_message' => $this->licensing->get_provider_upgrade_message( $this->provider_resolver->get_detected_provider_name() ),
				'upgrade_url' => $this->licensing->get_upgrade_url(),
				'selected_opportunity' => $selected_opportunity,
				'opportunity_items' => '' !== $selected_opportunity
					? $this->crawl_results_repository->get_opportunity_items( $selected_opportunity, $current_page, $per_page )
					: [],
				'opportunity_total' => $detail_total,
				'opportunity_page' => $current_page,
				'opportunity_total_pages' => $detail_total_pages,
			]
		);
	}

	public function arva_seo_bulk_edit_page(): null {
		$user_id = get_current_user_id();
		$bulk_edit_state = $this->bulk_edit_repository->get_state( $user_id );
		$bulk_edit_notice = isset( $_GET['arva_seo_bulk_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['arva_seo_bulk_notice'] ) ) : '';
		$is_preview_load = isset( $_GET['arva_seo_preview'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['arva_seo_preview'] ) );
		$completion_message = '';

		if ( 'completed' === $bulk_edit_state['status'] ) {
			$completion_message = (string) $bulk_edit_state['completed_message'];
			$this->bulk_edit_repository->clear_preview_rows( $user_id );
			$this->bulk_edit_repository->clear_state( $user_id );
			$bulk_edit_state = $this->bulk_edit_repository->get_state( $user_id );
			$bulk_edit_notice = '' !== $completion_message ? $completion_message : $bulk_edit_notice;
		} elseif ( [] !== $this->bulk_edit_repository->get_preview_rows( $user_id ) && ! $is_preview_load ) {
			$this->bulk_edit_repository->clear_preview_rows( $user_id );
			$this->bulk_edit_repository->clear_state( $user_id );
			$bulk_edit_state = $this->bulk_edit_repository->get_state( $user_id );
			$bulk_edit_notice = '';
		}

		$preview_rows = $this->bulk_edit_repository->get_preview_rows( $user_id );

		return View::render(
			'admin.bulk-edit',
			[
				'active_provider' => $this->provider_resolver->get_detected_provider_name(),
				'has_active_provider' => $this->provider_resolver->has_active_provider(),
				'provider_requires_premium' => $this->provider_resolver->detected_provider_requires_premium(),
				'provider_upgrade_message' => $this->licensing->get_provider_upgrade_message( $this->provider_resolver->get_detected_provider_name() ),
				'upgrade_url' => $this->licensing->get_upgrade_url(),
				'template_url' => wp_nonce_url(
					add_query_arg(
						[
							'action' => 'arva_seo_download_bulk_edit_template',
						],
						admin_url( 'admin-post.php' )
					),
					'arva_seo_download_bulk_edit_template',
					'arva_seo_template_nonce'
				),
				'preview_rows' => $preview_rows,
				'bulk_edit_state' => $bulk_edit_state,
				'bulk_edit_notice' => $bulk_edit_notice,
				'is_preview_load' => $is_preview_load,
			]
		);
	}

	public function arva_seo_settings_page(): null {
		return View::render(
			'admin.settings',
			[
				'settings' => $this->settings_repository->get_settings(),
				'min_batch_size' => $this->settings_repository->get_min_batch_size(),
				'max_batch_size' => $this->settings_repository->get_max_batch_size(),
				'settings_notice' => isset( $_GET['arva_seo_settings_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['arva_seo_settings_notice'] ) ) : '',
			]
		);
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
				'active_provider' => $this->provider_resolver->get_detected_provider_name(),
				'has_active_provider' => $this->provider_resolver->has_active_provider(),
				'provider_requires_premium' => $this->provider_resolver->detected_provider_requires_premium(),
				'provider_upgrade_message' => $this->licensing->get_provider_upgrade_message( $this->provider_resolver->get_detected_provider_name() ),
				'can_access_crawl_page' => $this->licensing->can_access_crawl_page(),
				'crawl_upgrade_message' => $this->licensing->get_crawl_upgrade_message(),
				'woocommerce_upgrade_message' => $this->licensing->get_woocommerce_upgrade_message(),
				'upgrade_url' => $this->licensing->get_upgrade_url(),
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
