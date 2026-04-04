<div class="arva-seo-wrapper">
    <div class="arva-seo-opportunities-hero">
        <div>
            <h1 class="arva-seo-text-dark"><?php esc_html_e( 'Opportunities', 'arva-seo' ); ?></h1>
            <p class="arva-seo-summary-meta"><?php esc_html_e( 'Track the site-wide SEO score and focus on the pages that need the most attention.', 'arva-seo' ); ?></p>
        </div>
        <a class="arva-seo-btn-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=arva-seo-crawl' ) ); ?>"><?php esc_html_e( 'Go To Crawl', 'arva-seo' ); ?></a>
    </div>

    <div class="arva-seo-opportunities-grid">
        <div class="arva-seo-op-card arva-seo-bg-lighter arva-seo-rounded">
            <span class="arva-seo-summary-label"><?php esc_html_e( 'Overall SEO Score', 'arva-seo' ); ?></span>
            <div class="arva-seo-op-score-ring" style="--progress: <?php echo esc_attr( $dashboard['average_score'] ); ?>;">
                <span><?php echo esc_html( $dashboard['average_score'] ); ?></span>
            </div>
            <p class="arva-seo-summary-meta">
				<?php
				printf(
					/* translators: %d: pages crawled */
					esc_html__( '%d crawled pages included in this score.', 'arva-seo' ),
					(int) $dashboard['total_pages']
				);
				?>
            </p>
        </div>

        <div class="arva-seo-op-card arva-seo-bg-lighter arva-seo-rounded">
            <span class="arva-seo-summary-label"><?php esc_html_e( 'Score Distribution', 'arva-seo' ); ?></span>
            <div class="arva-seo-op-bars">
				<?php foreach ( $dashboard['score_bands'] as $band => $count ) : ?>
					<?php
					$label_map = [
						'critical' => __( 'Critical', 'arva-seo' ),
						'warning' => __( 'Needs Work', 'arva-seo' ),
						'healthy' => __( 'Healthy', 'arva-seo' ),
					];
					$max_band = max( 1, max( $dashboard['score_bands'] ) );
					$height = (int) max( 18, round( ( $count / $max_band ) * 120 ) );
					?>
                    <div class="arva-seo-op-bar-group">
                        <div class="arva-seo-op-bar arva-seo-op-bar-<?php echo esc_attr( $band ); ?>" style="height: <?php echo esc_attr( $height ); ?>px;"></div>
                        <strong><?php echo esc_html( $count ); ?></strong>
                        <span><?php echo esc_html( $label_map[ $band ] ); ?></span>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="arva-seo-opportunities-list">
        <div class="arva-seo-opportunities-list-header">
            <h2 class="arva-seo-text-dark"><?php esc_html_e( 'Optimization Opportunities', 'arva-seo' ); ?></h2>
            <p class="arva-seo-summary-meta"><?php esc_html_e( 'Click an opportunity to inspect the affected pages on this same screen.', 'arva-seo' ); ?></p>
        </div>
        <div class="arva-seo-opportunity-cards">
			<?php foreach ( $dashboard['opportunities'] as $key => $opportunity ) : ?>
                <a class="arva-seo-opportunity-card arva-seo-rounded <?php echo esc_attr( $selected_opportunity === $key ? 'is-active' : '' ); ?>" href="<?php echo esc_url( add_query_arg( [ 'page' => 'arva-seo', 'opportunity' => $key ], admin_url( 'admin.php' ) ) ); ?>">
                    <span class="arva-seo-summary-label"><?php echo esc_html( $opportunity['label'] ); ?></span>
                    <strong><?php echo esc_html( $opportunity['count'] ); ?></strong>
                    <p><?php echo esc_html( $opportunity['description'] ); ?></p>
                </a>
			<?php endforeach; ?>
        </div>
    </div>

	<?php if ( '' !== $selected_opportunity ) : ?>
        <div class="arva-seo-opportunity-detail arva-seo-bg-lighter arva-seo-rounded">
            <div class="arva-seo-opportunities-list-header">
                <div>
                    <h2 class="arva-seo-text-dark"><?php echo esc_html( $dashboard['opportunities'][ $selected_opportunity ]['label'] ?? __( 'Opportunity Detail', 'arva-seo' ) ); ?></h2>
                    <p class="arva-seo-summary-meta">
						<?php
						printf(
							/* translators: %d: issue count */
							esc_html__( '%d affected pages found.', 'arva-seo' ),
							(int) $opportunity_total
						);
						?>
                    </p>
                </div>
                <a class="arva-seo-search-reset" href="<?php echo esc_url( admin_url( 'admin.php?page=arva-seo' ) ); ?>"><?php esc_html_e( 'Back To Overview', 'arva-seo' ); ?></a>
            </div>
            <div class="arva-seo-opportunity-detail-table">
                <div class="arva-seo-opportunity-detail-head">
                    <div><?php esc_html_e( 'Page', 'arva-seo' ); ?></div>
                    <div><?php esc_html_e( 'Current Value', 'arva-seo' ); ?></div>
                    <div><?php esc_html_e( 'Score', 'arva-seo' ); ?></div>
                </div>
				<?php foreach ( $opportunity_items as $item ) : ?>
                    <div class="arva-seo-opportunity-detail-row">
                        <div>
                            <strong><?php echo esc_html( $item['page_title'] ); ?></strong>
                            <p><a href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['permalink'] ); ?></a></p>
                        </div>
                        <div>
							<?php
							if ( in_array( $selected_opportunity, [ 'title_missing', 'title_length' ], true ) ) {
								$current_value = $item['seo_title'];
							} elseif ( in_array( $selected_opportunity, [ 'description_missing', 'description_length' ], true ) ) {
								$current_value = $item['seo_description'];
							} elseif ( 'image_alt_missing' === $selected_opportunity ) {
								$image_values = json_decode( (string) ( $item['missing_image_alt_details'] ?? '' ), true );
								$image_values = is_array( $image_values ) ? array_filter( array_map( 'strval', $image_values ) ) : [];
								$current_value = [] !== $image_values
									? sprintf(
										/* translators: 1: number of images missing alt text, 2: image sources */
										__( '%1$d missing alt image(s): %2$s', 'arva-seo' ),
										(int) ( $item['missing_image_alt_count'] ?? 0 ),
										implode( ' | ', $image_values )
									)
									: __( 'No missing image alt text found', 'arva-seo' );
							} else {
								$h1_values = json_decode( (string) ( $item['h1_texts'] ?? '' ), true );
								$h1_values = is_array( $h1_values ) ? array_filter( array_map( 'strval', $h1_values ) ) : [];
								$current_value = [] !== $h1_values
									? implode( ' | ', $h1_values )
									: __( 'No H1 found', 'arva-seo' );
							}
							echo esc_html( '' !== $current_value ? $current_value : __( 'Not set', 'arva-seo' ) );
							?>
                        </div>
                        <div>
                            <span class="arva-seo-score-box <?php echo esc_attr( $item['score'] >= 80 ? 'arva-seo-bg-primary-ligher' : ( $item['score'] >= 50 ? 'arva-seo-bg-primary' : 'arva-seo-bg-primary-darker' ) ); ?>"><?php echo esc_html( $item['score'] ); ?></span>
                        </div>
                    </div>
				<?php endforeach; ?>
            </div>
			<?php if ( $opportunity_total_pages > 1 ) : ?>
                <div class="arva-seo-pagination">
					<?php
					echo wp_kses_post(
						paginate_links(
							[
								'base' => add_query_arg(
									[
										'page' => 'arva-seo',
										'opportunity' => $selected_opportunity,
										'paged' => '%#%',
									],
									admin_url( 'admin.php' )
								),
								'format' => '',
								'current' => $opportunity_page,
								'total' => $opportunity_total_pages,
								'prev_text' => __( 'Previous', 'arva-seo' ),
								'next_text' => __( 'Next', 'arva-seo' ),
							]
						)
					);
					?>
                </div>
			<?php endif; ?>
        </div>
	<?php endif; ?>
</div>
