<div class="arva-seo-wrapper">
    <h1 class="arva-seo-text-dark"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php if ( '' !== $settings_notice ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
				<?php
				$messages = [
					'saved' => __( 'Settings saved.', 'arva-seo' ),
					'crawl-reset' => __( 'Crawl data has been cleared.', 'arva-seo' ),
				];
				echo esc_html( $messages[ $settings_notice ] ?? $settings_notice );
				?>
            </p>
        </div>
	<?php endif; ?>
    <div class="arva-seo-settings-form arva-seo-bg-lighter arva-seo-rounded">
        <div class="arva-seo-settings-row">
            <div class="arva-seo-settings-label">
                <h3><?php esc_html_e( 'Reset Crawl Data', 'arva-seo' ); ?></h3>
            </div>
            <div class="arva-seo-settings-control">
                <p><?php esc_html_e( 'Clear all stored crawl data and reset crawl progress. This does not affect SEO plugin values.', 'arva-seo' ); ?></p>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                    <input type="hidden" name="action" value="arva_seo_reset_crawl_data">
					<?php wp_nonce_field( 'arva_seo_reset_crawl_data', 'arva_seo_reset_nonce' ); ?>
                    <button class="arva-seo-btn-primary" type="submit"><?php esc_html_e( 'Reset Crawl Data', 'arva-seo' ); ?></button>
                </form>
            </div>
        </div>
    </div>
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="arva-seo-settings-form arva-seo-bg-lighter arva-seo-rounded">
        <input type="hidden" name="action" value="arva_seo_save_settings">
		<?php wp_nonce_field( 'arva_seo_save_settings', 'arva_seo_settings_nonce' ); ?>

        <div class="arva-seo-settings-row">
            <div class="arva-seo-settings-label">
                <h3><?php esc_html_e( 'Crawl Batch Size', 'arva-seo' ); ?></h3>
            </div>
            <div class="arva-seo-settings-control">
                <p><?php esc_html_e( 'Adjust how many crawl rows are processed per batch request.', 'arva-seo' ); ?></p>
                <input type="number" name="crawl_batch_size" min="<?php echo esc_attr( $min_batch_size ); ?>" max="<?php echo esc_attr( $max_batch_size ); ?>" value="<?php echo esc_attr( (int) $settings['crawl_batch_size'] ); ?>">
                <p class="description"><?php echo esc_html( sprintf( __( 'Minimum %1$d, maximum %2$d per batch.', 'arva-seo' ), $min_batch_size, $max_batch_size ) ); ?></p>
            </div>
        </div>

        <div class="arva-seo-settings-row">
            <div class="arva-seo-settings-label">
                <h3><?php esc_html_e( 'Bulk Edit Batch Size', 'arva-seo' ); ?></h3>
            </div>
            <div class="arva-seo-settings-control">
                <p><?php esc_html_e( 'Adjust how many bulk edit rows are updated per batch request.', 'arva-seo' ); ?></p>
                <input type="number" name="bulk_edit_batch_size" min="<?php echo esc_attr( $min_batch_size ); ?>" max="<?php echo esc_attr( $max_batch_size ); ?>" value="<?php echo esc_attr( (int) $settings['bulk_edit_batch_size'] ); ?>">
                <p class="description"><?php echo esc_html( sprintf( __( 'Minimum %1$d, maximum %2$d per batch.', 'arva-seo' ), $min_batch_size, $max_batch_size ) ); ?></p>
            </div>
        </div>

        <div class="arva-seo-settings-row">
            <div class="arva-seo-settings-label">
                <h3><?php esc_html_e( 'Delete Data on Uninstall', 'arva-seo' ); ?></h3>
            </div>
            <div class="arva-seo-settings-control">
                <p><?php esc_html_e( 'Delete plugin crawl data and settings when the plugin is uninstalled.', 'arva-seo' ); ?></p>
                <label>
                    <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( ! empty( $settings['delete_data_on_uninstall'] ) ); ?>>
					<?php esc_html_e( 'Delete all plugin data on uninstall', 'arva-seo' ); ?>
                </label>
            </div>
        </div>

        <div class="arva-seo-settings-actions">
            <button class="arva-seo-btn-primary" type="submit"><?php esc_html_e( 'Save Settings', 'arva-seo' ); ?></button>
        </div>
    </form>
</div>
