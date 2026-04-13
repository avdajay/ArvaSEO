<div class="arva-seo-wrapper"<?php echo ! empty( $is_preview_load ) ? ' data-clear-preview-url="' . esc_attr( '1' ) . '"' : ''; ?>>
    <h1 class="arva-seo-text-dark"><?php esc_html_e( 'Bulk Edit', 'bulk-meta-editor' ); ?></h1>
    <div class="arva-seo-container arva-seo-bg-lighter arva-seo-rounded arva-seo-bulk-toolbar">
        <div class="arva-seo-bulk-toolbar-copy">
            <h3><?php esc_html_e( 'Upload Crawl Export', 'bulk-meta-editor' ); ?></h3>
            <p class="arva-seo-text-dark">
				<?php
				if ( $has_active_provider ) {
					echo esc_html(
						sprintf(
							/* translator: %s: active SEO provider name */
							__( 'Detected SEO provider: %s', 'bulk-meta-editor' ),
							$active_provider
						)
					);
				} elseif ( $provider_requires_premium ) {
					echo esc_html( $provider_upgrade_message );
				} else {
					echo esc_html__( 'No supported SEO plugin is active. Activate Yoast SEO, All in One SEO, Rank Math, or SEOPress to use bulk edit.', 'bulk-meta-editor' );
				}
				?>
            </p>
			<?php if ( $provider_requires_premium ) : ?>
                <div class="arva-seo-inline-upsell arva-seo-rounded">
                    <p><?php esc_html_e( 'Upgrade to Premium to edit SEO data when the site is powered by Rank Math, All in One SEO, or SEOPress.', 'bulk-meta-editor' ); ?></p>
                    <a class="arva-seo-btn-primary" href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade To Premium', 'bulk-meta-editor' ); ?></a>
                </div>
			<?php endif; ?>
            <form class="arva-seo-bulk-upload-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="arva_seo_upload_bulk_edit">
				<?php wp_nonce_field( 'arva_seo_upload_bulk_edit', 'arva_seo_upload_nonce' ); ?>
                <input type="file" name="bulk_edit_file" accept=".csv" <?php disabled( ! $has_active_provider ); ?>>
                <button class="arva-seo-btn-primary" type="submit" <?php disabled( ! $has_active_provider ); ?>><?php esc_html_e( 'Upload CSV', 'bulk-meta-editor' ); ?></button>
                <a class="arva-seo-btn-primary" href="<?php echo esc_url( $template_url ); ?>"><?php esc_html_e( 'Download Template', 'bulk-meta-editor' ); ?></a>
            </form>
            <p class="arva-seo-summary-meta"><?php esc_html_e( 'Blank values in the CSV are treated as "skip" and will not overwrite existing data. Use the template to match the required import headers.', 'bulk-meta-editor' ); ?></p>
			<?php if ( '' !== $bulk_edit_notice ) : ?>
                <p class="arva-seo-bulk-notice">
					<?php
					$messages = [
						'preview-ready' => __( 'Preview loaded. Review the new values before processing.', 'bulk-meta-editor' ),
						'empty-preview' => __( 'The CSV did not contain any valid rows with changes to process.', 'bulk-meta-editor' ),
						'missing-file' => __( 'Choose a CSV file before uploading.', 'bulk-meta-editor' ),
						'invalid-file' => __( 'The uploaded file could not be read.', 'bulk-meta-editor' ),
						'no-provider' => __( 'No supported SEO plugin is active.', 'bulk-meta-editor' ),
						'provider-premium' => __( 'The free version supports Yoast SEO only. Upgrade to use Rank Math, All in One SEO, or SEOPress.', 'bulk-meta-editor' ),
					];
					echo esc_html( $messages[ $bulk_edit_notice ] ?? $bulk_edit_notice );
					?>
                </p>
			<?php endif; ?>
        </div>
        <div class="arva-seo-bulk-progress" id="arva-seo-bulk-edit-state"
             data-status="<?php echo esc_attr( $bulk_edit_state['status'] ); ?>"
             data-processed="<?php echo esc_attr( (int) $bulk_edit_state['processed'] ); ?>"
             data-total="<?php echo esc_attr( (int) $bulk_edit_state['total'] ); ?>"
             data-percentage="<?php echo esc_attr( (int) $bulk_edit_state['percentage'] ); ?>"
             data-updated="<?php echo esc_attr( (int) $bulk_edit_state['updated'] ); ?>"
             data-skipped="<?php echo esc_attr( (int) $bulk_edit_state['skipped'] ); ?>"
             data-errors="<?php echo esc_attr( (int) $bulk_edit_state['errors'] ); ?>">
            <div class="arva-seo-crawl-donut-stack">
                <span class="arva-seo-summary-label"><?php esc_html_e( 'Progress', 'bulk-meta-editor' ); ?></span>
                <div class="arva-seo-crawl-donut" id="arva-seo-bulk-edit-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (int) $bulk_edit_state['percentage'] ); ?>" style="--progress: <?php echo esc_attr( (int) $bulk_edit_state['percentage'] ); ?>;">
                    <span class="arva-seo-crawl-donut-inner" id="arva-seo-bulk-edit-progress-percent"><?php echo esc_html( (int) $bulk_edit_state['percentage'] ); ?>%</span>
                </div>
                <p class="arva-seo-summary-meta" id="arva-seo-bulk-edit-progress-copy">
					<?php
					echo esc_html(
						'' !== $bulk_edit_state['completed_message']
							? $bulk_edit_state['completed_message']
							: __( 'Upload a CSV file to prepare your bulk edit preview.', 'bulk-meta-editor' )
					);
					?>
                </p>
            </div>
        </div>
    </div>

	<?php if ( [] !== $preview_rows ) : ?>
        <div class="arva-seo-bulk-warning arva-seo-bg-light arva-seo-rounded">
            <strong><?php esc_html_e( 'Warning:', 'bulk-meta-editor' ); ?></strong>
            <span><?php esc_html_e( 'Processing will permanently overwrite the selected SEO values directly in the database for the active SEO plugin.', 'bulk-meta-editor' ); ?></span>
        </div>
        <form id="arva-seo-bulk-edit-form" class="arva-seo-bulk-edit-form">
            <div class="arva-seo-bulk-preview-table">
                <div class="arva-seo-bulk-preview-row arva-seo-bulk-preview-head">
                    <div><?php esc_html_e( 'URL', 'bulk-meta-editor' ); ?></div>
                    <div><?php esc_html_e( 'Title', 'bulk-meta-editor' ); ?></div>
                    <div><?php esc_html_e( 'Description', 'bulk-meta-editor' ); ?></div>
                    <div><?php esc_html_e( 'Canonical URL', 'bulk-meta-editor' ); ?></div>
                    <div><?php esc_html_e( 'No Follow', 'bulk-meta-editor' ); ?></div>
                    <div><?php esc_html_e( 'No Index', 'bulk-meta-editor' ); ?></div>
                </div>
				<?php foreach ( $preview_rows as $index => $row ) : ?>
                    <div class="arva-seo-bulk-preview-row">
                        <div>
                            <strong><?php echo esc_html( $row['page_title'] ); ?></strong>
                            <p><?php echo esc_html( $row['url'] ); ?></p>
                            <input type="hidden" name="rows[<?php echo esc_attr( $index ); ?>][post_id]" value="<?php echo esc_attr( $row['post_id'] ); ?>">
                            <input type="hidden" name="rows[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $row['url'] ); ?>">
                            <input type="hidden" name="rows[<?php echo esc_attr( $index ); ?>][page_title]" value="<?php echo esc_attr( $row['page_title'] ); ?>">
                        </div>
                        <div>
                            <p class="arva-seo-bulk-old-value"><?php echo esc_html( $row['old_title'] ); ?></p>
                            <input class="arva-seo-bulk-input" type="text" name="rows[<?php echo esc_attr( $index ); ?>][new_title]" value="<?php echo esc_attr( (string) ( $row['new_title'] ?? '' ) ); ?>">
                            <input type="hidden" name="rows[<?php echo esc_attr( $index ); ?>][old_title]" value="<?php echo esc_attr( $row['old_title'] ); ?>">
                        </div>
                        <div>
                            <p class="arva-seo-bulk-old-value"><?php echo esc_html( $row['old_description'] ); ?></p>
                            <textarea class="arva-seo-bulk-textarea" name="rows[<?php echo esc_attr( $index ); ?>][new_description]"><?php echo esc_textarea( (string) ( $row['new_description'] ?? '' ) ); ?></textarea>
                            <input type="hidden" name="rows[<?php echo esc_attr( $index ); ?>][old_description]" value="<?php echo esc_attr( $row['old_description'] ); ?>">
                        </div>
                        <div>
                            <p class="arva-seo-bulk-old-value"><?php echo esc_html( $row['old_canonical_url'] ); ?></p>
                            <input class="arva-seo-bulk-input" type="text" name="rows[<?php echo esc_attr( $index ); ?>][new_canonical_url]" value="<?php echo esc_attr( (string) ( $row['new_canonical_url'] ?? '' ) ); ?>">
                            <input type="hidden" name="rows[<?php echo esc_attr( $index ); ?>][old_canonical_url]" value="<?php echo esc_attr( $row['old_canonical_url'] ); ?>">
                        </div>
                        <div>
                            <p class="arva-seo-bulk-old-value"><?php echo $row['old_no_follow'] ? esc_html__( 'Yes', 'bulk-meta-editor' ) : esc_html__( 'No', 'bulk-meta-editor' ); ?></p>
                            <select class="arva-seo-bulk-select" name="rows[<?php echo esc_attr( $index ); ?>][new_no_follow]">
                                <option value=""><?php esc_html_e( 'Skip', 'bulk-meta-editor' ); ?></option>
                                <option value="1" <?php selected( true, $row['new_no_follow'] ); ?>><?php esc_html_e( 'Yes', 'bulk-meta-editor' ); ?></option>
                                <option value="0" <?php selected( false, $row['new_no_follow'] ); ?>><?php esc_html_e( 'No', 'bulk-meta-editor' ); ?></option>
                            </select>
                            <input type="hidden" name="rows[<?php echo esc_attr( $index ); ?>][old_no_follow]" value="<?php echo esc_attr( $row['old_no_follow'] ? '1' : '0' ); ?>">
                        </div>
                        <div>
                            <p class="arva-seo-bulk-old-value"><?php echo $row['old_no_index'] ? esc_html__( 'Yes', 'bulk-meta-editor' ) : esc_html__( 'No', 'bulk-meta-editor' ); ?></p>
                            <select class="arva-seo-bulk-select" name="rows[<?php echo esc_attr( $index ); ?>][new_no_index]">
                                <option value=""><?php esc_html_e( 'Skip', 'bulk-meta-editor' ); ?></option>
                                <option value="1" <?php selected( true, $row['new_no_index'] ); ?>><?php esc_html_e( 'Yes', 'bulk-meta-editor' ); ?></option>
                                <option value="0" <?php selected( false, $row['new_no_index'] ); ?>><?php esc_html_e( 'No', 'bulk-meta-editor' ); ?></option>
                            </select>
                            <input type="hidden" name="rows[<?php echo esc_attr( $index ); ?>][old_no_index]" value="<?php echo esc_attr( $row['old_no_index'] ? '1' : '0' ); ?>">
                        </div>
                    </div>
				<?php endforeach; ?>
            </div>
            <div class="arva-seo-bulk-actions">
                <button class="arva-seo-btn-primary" id="arva-seo-start-bulk-edit" type="button"><?php esc_html_e( 'Start Processing', 'bulk-meta-editor' ); ?></button>
                <p class="arva-seo-summary-meta"><?php esc_html_e( 'This overrides the selected values permanently. Review the new values carefully before processing.', 'bulk-meta-editor' ); ?></p>
            </div>
        </form>
	<?php endif; ?>
</div>
