<div class="arva-seo-wrapper">
    <h1 class="arva-seo-text-dark"><?php esc_html_e( 'Crawl', 'arva-seo' ); ?></h1>
	<?php if ( ! $can_access_crawl_page ) : ?>
        <div class="arva-seo-premium-lock arva-seo-bg-lighter arva-seo-rounded">
            <div class="arva-seo-premium-lock-badge"><?php esc_html_e( 'Premium Feature', 'arva-seo' ); ?></div>
            <h2 class="arva-seo-text-dark"><?php esc_html_e( 'Unlock The Crawl Workspace', 'arva-seo' ); ?></h2>
            <p><?php echo esc_html( $crawl_upgrade_message ); ?></p>
            <p><?php echo esc_html( $woocommerce_upgrade_message ); ?></p>
			<?php if ( $provider_requires_premium ) : ?>
                <p><?php echo esc_html( $provider_upgrade_message ); ?></p>
			<?php else : ?>
                <p><?php esc_html_e( 'Premium also unlocks WooCommerce product crawling, exports, and recrawl workflows for fresh SEO snapshots.', 'arva-seo' ); ?></p>
			<?php endif; ?>
            <div class="arva-seo-premium-lock-actions">
                <a class="arva-seo-btn-primary" href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade To Premium', 'arva-seo' ); ?></a>
            </div>
        </div>
		<?php return; ?>
	<?php endif; ?>
    <div class="arva-seo-container arva-seo-bg-lighter arva-seo-rounded arva-seo-crawl-toolbar">
        <div class="arva-seo-crawl-toolbar-copy">
            <div class="arva-seo-crawl-toolbar-copy-inner">
                <p class="arva-seo-text-dark">
                    <?php
                    if ( $has_active_provider ) {
                        echo esc_html( sprintf( 'Detected SEO provider: %s', $active_provider ) );
                    } elseif ( $provider_requires_premium ) {
                        echo esc_html( $provider_upgrade_message );
                    } else {
                        echo esc_html__( 'No supported SEO plugin is active. Activate Yoast SEO, All in One SEO, Rank Math, or SEOPress to crawl.', 'arva-seo' );
                    }
                    ?>
                </p>
                <button
                    class="arva-seo-btn-primary"
                    id="arva-seo-start-crawl"
                    <?php disabled( ! $has_active_provider ); ?>
                >
                    Start Crawl
                </button>
                <a class="arva-seo-btn-primary arva-seo-export-btn" href="<?php echo esc_url( $export_url ); ?>">
					<?php esc_html_e( 'Export Data', 'arva-seo' ); ?>
                </a>
            </div>
        </div>
        <div
            class="arva-seo-crawl-controls-left arva-seo-rounded"
            aria-live="polite"
            id="arva-seo-crawl-state"
            data-active="<?php echo esc_attr( in_array( $crawl_state['status'], [ 'running', 'paused' ], true ) ? '1' : '0' ); ?>"
            data-percentage="<?php echo esc_attr( (int) $crawl_state['percentage'] ); ?>"
            data-processed="<?php echo esc_attr( (int) $crawl_state['processed'] ); ?>"
            data-total="<?php echo esc_attr( (int) $crawl_state['total'] ); ?>"
            data-status="<?php echo esc_attr( $crawl_state['status'] ); ?>"
            data-crawled="<?php echo esc_attr( (int) $crawl_state['crawled_count'] ); ?>"
            data-skipped="<?php echo esc_attr( (int) $crawl_state['skipped_count'] ); ?>"
            data-errors="<?php echo esc_attr( (int) $crawl_state['error_count'] ); ?>"
        >
            <div class="arva-seo-crawl-donut-stack">
                <span class="arva-seo-summary-label"><?php esc_html_e( 'Progress', 'arva-seo' ); ?></span>
                <div class="arva-seo-crawl-donut" id="arva-seo-crawl-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (int) $crawl_state['percentage'] ); ?>" style="--progress: <?php echo esc_attr( (int) $crawl_state['percentage'] ); ?>;">
                    <span class="arva-seo-crawl-donut-inner" id="arva-seo-crawl-progress-percent"><?php echo esc_html( (int) $crawl_state['percentage'] ); ?>%</span>
                </div>
                <p class="arva-seo-summary-meta" id="arva-seo-crawl-progress-copy">
					<?php
					if ( in_array( $crawl_state['status'], [ 'running', 'paused' ], true ) ) {
						echo esc_html(
							sprintf(
								/* translators: 1: processed items, 2: total items */
								__( 'Processed %1$d of %2$d items.', 'arva-seo' ),
								(int) $crawl_state['processed'],
								(int) $crawl_state['total']
							)
						);
					} else {
						esc_html_e( 'Waiting to start.', 'arva-seo' );
					}
					?>
                </p>
            </div>
        </div>
        <div class="arva-seo-crawl-summary">
            <span class="arva-seo-summary-label"><?php esc_html_e( 'Crawled Pages', 'arva-seo' ); ?></span>
            <strong class="arva-seo-summary-value"><?php echo esc_html( number_format_i18n( $total_items ) ); ?></strong>
            <p class="arva-seo-summary-meta">
				<?php
				if ( $last_crawled_at ) {
					$last_crawl_datetime = date_create_immutable_from_format( 'Y-m-d H:i:s', $last_crawled_at, wp_timezone() );
					$last_crawl_display = $last_crawl_datetime
						? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_crawl_datetime->getTimestamp(), wp_timezone() )
						: $last_crawled_at;
					printf(
						'%s %s',
						esc_html__( 'Last crawl:', 'arva-seo' ),
						esc_html( $last_crawl_display )
					);
				} else {
					esc_html_e( 'Last crawl: Never', 'arva-seo' );
				}
				?>
            </p>
        </div>
    </div>
    <div class="arva-seo-crawl-results-container">
        <form class="arva-seo-crawl-search arva-seo-rounded" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
            <input type="hidden" name="page" value="arva-seo-crawl">
            <p class="arva-seo-search-results-count">
				<?php
				printf(
					/* translators: %d: number of search results */
					esc_html__( '%d search results', 'arva-seo' ),
					(int) $search_results_count
				);
				?>
            </p>
            <div class="arva-seo-crawl-search-controls">
                <label class="screen-reader-text" for="arva-seo-search"><?php esc_html_e( 'Search crawl results', 'arva-seo' ); ?></label>
                <input
                    class="arva-seo-search-input"
                    id="arva-seo-search"
                    type="search"
                    name="s"
                    value="<?php echo esc_attr( $search_query ); ?>"
                    placeholder="<?php esc_attr_e( 'Search by page title or URL', 'arva-seo' ); ?>"
                >
                <button class="arva-seo-btn-primary" type="submit"><?php esc_html_e( 'Search', 'arva-seo' ); ?></button>
				<?php if ( '' !== $search_query ) : ?>
                    <a class="arva-seo-search-reset" href="<?php echo esc_url( admin_url( 'admin.php?page=arva-seo-crawl' ) ); ?>">
						<?php esc_html_e( 'Clear', 'arva-seo' ); ?>
                    </a>
				<?php endif; ?>
            </div>
        </form>
		<?php if ( [] === $results ) : ?>
            <div class="arva-seo-empty-state arva-seo-bg-lighter arva-seo-rounded">
                <h3>
					<?php
					echo '' === $search_query
						? esc_html__( 'No crawl data yet', 'arva-seo' )
						: esc_html__( 'No matching crawl results', 'arva-seo' );
					?>
                </h3>
                <p>
					<?php
					echo '' === $search_query
						? esc_html__( 'Run the crawler to build your denormalized SEO dataset.', 'arva-seo' )
						: esc_html__( 'Try a different page title or URL.', 'arva-seo' );
					?>
                </p>
            </div>
		<?php else : ?>
            <div class="arva-seo-crawl-headers">
                <div class="arva-seo-crawl-page-info-header">
                    <h3><?php esc_html_e( 'Page Info', 'arva-seo' ); ?></h3>
                </div>
                <div class="arva-seo-crawl-page-score-header">
                    <h3><?php esc_html_e( 'SEO Snapshot', 'arva-seo' ); ?></h3>
                </div>
            </div>
            <div class="arva-seo-crawl-body">
				<?php foreach ( $results as $index => $result ) : ?>
                    <div class="arva-seo-crawl-body-item <?php echo 1 === $index % 2 ? 'arva-seo-bg-lighter' : ''; ?>">
                        <div class="arva-seo-craw-list-page-info">
                            <div class="arva-seo-page-title-row">
                                <h3><?php echo esc_html( $result['page_title'] ); ?></h3>
                                <p class="arva-seo-meta-inline">
                                    <span><?php echo esc_html( ucfirst( $result['post_type'] ) ); ?></span>
                                </p>
                            </div>
                            <p><a href="<?php echo esc_url( $result['permalink'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $result['permalink'] ); ?></a></p>
                        </div>
                        <div class="arva-seo-craw-list-page-score">
                            <div class="arva-seo-score-box <?php echo esc_attr( $result['score'] >= 80 ? 'arva-seo-bg-primary-ligher' : ( $result['score'] >= 50 ? 'arva-seo-bg-primary' : 'arva-seo-bg-primary-darker' ) ); ?>">
								<?php echo esc_html( $result['score'] ); ?>
                            </div>
                            <div class="arva-seo-seo-copy">
                                <p><strong><?php esc_html_e( 'SEO Title:', 'arva-seo' ); ?></strong> <?php echo esc_html( '' !== $result['seo_title'] ? $result['seo_title'] : 'Not set' ); ?></p>
                                <p><strong><?php esc_html_e( 'Meta Description:', 'arva-seo' ); ?></strong> <?php echo esc_html( '' !== $result['seo_description'] ? $result['seo_description'] : 'Not set' ); ?></p>
                            </div>
                        </div>
                    </div>
				<?php endforeach; ?>
            </div>
			<?php if ( $total_pages > 1 ) : ?>
                <div class="arva-seo-pagination">
					<?php
					echo wp_kses_post(
						paginate_links(
							[
								'base' => add_query_arg(
									[
										'page' => 'arva-seo-crawl',
										'paged' => '%#%',
										's' => $search_query,
									],
									admin_url( 'admin.php' )
								),
								'format' => '',
								'current' => $current_page,
								'total' => $total_pages,
								'prev_text' => __( 'Previous', 'arva-seo' ),
								'next_text' => __( 'Next', 'arva-seo' ),
							]
						)
					);
					?>
                </div>
			<?php endif; ?>
		<?php endif; ?>
    </div>
</div>
