<?php

namespace ArvaSeo\Actions;

use ArvaSeo\Repositories\SettingsRepository;

class SaveSettings {

	private SettingsRepository $settings_repository;

	public function __construct( SettingsRepository $settings_repository ) {
		$this->settings_repository = $settings_repository;
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to save settings.', 'bulk-meta-editor' ) );
		}

		check_admin_referer( 'arva_seo_save_settings', 'arva_seo_settings_nonce' );

		$this->settings_repository->save_settings(
			[
				'crawl_batch_size' => isset( $_POST['crawl_batch_size'] ) ? wp_unslash( $_POST['crawl_batch_size'] ) : null,
				'bulk_edit_batch_size' => isset( $_POST['bulk_edit_batch_size'] ) ? wp_unslash( $_POST['bulk_edit_batch_size'] ) : null,
				'delete_data_on_uninstall' => isset( $_POST['delete_data_on_uninstall'] ) ? wp_unslash( $_POST['delete_data_on_uninstall'] ) : null,
			]
		);

		wp_safe_redirect(
			add_query_arg(
				[
					'page' => 'arva-seo-settings',
					'arva_seo_settings_notice' => 'saved',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
