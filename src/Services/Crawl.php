<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;
use ArvaSeo\Repositories\CrawlResultsRepository;
use ArvaSeo\Repositories\SettingsRepository;
use ArvaSeo\Repositories\CrawlStateRepository;

class Crawl {
	private SeoService $seo_service;
	private CrawlResultsRepository $repository;
	private SettingsRepository $settings_repository;
	private CrawlStateRepository $state_repository;
	private Licensing $licensing;

	public function __construct(
		SeoService $seo_service,
		CrawlResultsRepository $repository,
		SettingsRepository $settings_repository,
		CrawlStateRepository $state_repository,
		Licensing $licensing
	) {
		$this->seo_service = $seo_service;
		$this->repository = $repository;
		$this->settings_repository = $settings_repository;
		$this->state_repository = $state_repository;
		$this->licensing = $licensing;
	}

	public function is_available(): bool {
		return $this->seo_service->is_active();
	}

	public function get_default_chunk_size(): int {
		return $this->settings_repository->get_crawl_batch_size();
	}

	public function get_state(): array {
		return $this->state_repository->get_state();
	}

	public function crawl_batch( ?int $limit = null, bool $start = false ): array {
		$provider = $this->seo_service->get_provider_name();
		$post_ids = $this->get_crawl_post_ids();
		$total = count( $post_ids );
		$limit = null === $limit ? $this->get_default_chunk_size() : max( 1, $limit );
		$state = $this->state_repository->get_state();

		if ( $start || $provider !== $state['provider'] || ! in_array( $state['status'], [ 'running', 'paused' ], true ) ) {
			$this->repository->delete_unsupported_post_type_results();
			$state = $this->state_repository->reset_state( $provider, $total );
		}

		$offset = max( 0, (int) $state['current_offset'] );
		$batch_post_ids = array_slice( $post_ids, $offset, $limit );
		$crawled_in_batch = 0;
		$error_in_batch = 0;
		$last_error = '';

		foreach ( $batch_post_ids as $post_id ) {
			$permalink = get_permalink( $post_id );

			if ( ! is_string( $permalink ) || '' === $permalink ) {
				$error_in_batch++;
				$last_error = sprintf( 'Missing permalink for post ID %d.', $post_id );
				continue;
			}

			try {
				$seo_data = $this->seo_service->crawl( $permalink );
			} catch ( \Throwable $throwable ) {
				$error_in_batch++;
				$last_error = $throwable->getMessage();
				continue;
			}

			$page_title = get_the_title( $post_id );
			$page_title = is_string( $page_title ) ? $page_title : '';
			$seo_title = (string) ( $seo_data['title'] ?? '' );
			$seo_description = (string) ( $seo_data['description'] ?? '' );
			$canonical_url = $this->seo_service->get_post_canonical_url( $post_id );
			$robots_noindex = $this->seo_service->is_post_noindex( $post_id );
			$robots_nofollow = $this->seo_service->is_post_nofollow( $post_id );
			$content_analysis = $this->analyze_post_content( $post_id );

			$this->repository->upsert_result(
				[
					'provider' => $provider,
					'object_type' => 'post',
					'object_id' => $post_id,
					'post_type' => (string) get_post_type( $post_id ),
					'page_title' => $page_title,
					'seo_title' => $seo_title,
					'seo_description' => $seo_description,
					'canonical_url' => $canonical_url,
					'robots_noindex' => $robots_noindex ? 1 : 0,
					'robots_nofollow' => $robots_nofollow ? 1 : 0,
					'h1_count' => $content_analysis['h1_count'],
					'has_duplicate_h1' => $content_analysis['has_duplicate_h1'] ? 1 : 0,
					'h1_texts' => wp_json_encode( $content_analysis['h1_texts'] ),
					'image_count' => $content_analysis['image_count'],
					'missing_image_alt_count' => $content_analysis['missing_image_alt_count'],
					'missing_image_alt_details' => wp_json_encode( $content_analysis['missing_image_alt_details'] ),
					'permalink' => $permalink,
					'score' => $this->resolve_score( $post_id, $seo_title, $seo_description, $canonical_url, $content_analysis ),
				]
			);

			$crawled_in_batch++;
		}

		$next_offset = min( $offset + count( $batch_post_ids ), $total );
		$processed = $next_offset;
		$updated_state = $this->state_repository->update_progress(
			[
				'provider' => $provider,
				'status' => $next_offset >= $total ? 'completed' : 'running',
				'current_offset' => $next_offset,
				'processed' => $processed,
				'total' => $total,
				'percentage' => $total > 0 ? (int) floor( ( $processed / $total ) * 100 ) : 100,
				'crawled_count' => (int) $state['crawled_count'] + $crawled_in_batch,
				'skipped_count' => (int) $state['skipped_count'],
				'error_count' => (int) $state['error_count'] + $error_in_batch,
				'last_error' => $last_error,
			]
		);

		if ( $next_offset >= $total ) {
			$updated_state = $this->state_repository->mark_completed( $updated_state );
		}

		return [
			'provider' => $provider,
			'count' => $crawled_in_batch,
			'processed' => $processed,
			'total' => $total,
			'remaining' => max( 0, $total - $processed ),
			'next_offset' => $next_offset,
			'done' => $next_offset >= $total,
			'percentage' => $updated_state['percentage'],
			'crawled_count' => $updated_state['crawled_count'],
			'skipped_count' => $updated_state['skipped_count'],
			'error_count' => $updated_state['error_count'],
			'status' => $updated_state['status'],
			'last_error' => $updated_state['last_error'],
		];
	}

	private function get_crawl_post_ids(): array {
		$post_types = array_values(
			array_filter(
				[
					post_type_exists( 'post' ) ? 'post' : null,
					post_type_exists( 'page' ) ? 'page' : null,
					post_type_exists( 'product' ) && $this->licensing->can_crawl_post_type( 'product' ) ? 'product' : null,
				]
			)
		);

		if ( [] === $post_types ) {
			return [];
		}

		$post_ids = get_posts(
			[
				'post_type' => $post_types,
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'orderby' => 'ID',
				'order' => 'ASC',
				'suppress_filters' => false,
			]
		);

		return is_array( $post_ids ) ? array_map( 'intval', $post_ids ) : [];
	}

	private function resolve_score( int $post_id, string $seo_title, string $seo_description, string $canonical_url, array $content_analysis ): int {
		$provider_score = $this->seo_service->get_post_score( $post_id );
		$content_score = $this->calculate_content_quality_score( $seo_title, $seo_description, $canonical_url, $content_analysis );

		if ( $provider_score >= 0 ) {
			return max( 0, min( 100, (int) round( ( $provider_score * 0.7 ) + ( $content_score * 0.3 ) ) ) );
		}

		return $content_score;
	}

	private function calculate_content_quality_score( string $seo_title, string $seo_description, string $canonical_url, array $content_analysis ): int {
		$score = 0;
		$title_length = function_exists( 'mb_strlen' ) ? mb_strlen( trim( $seo_title ) ) : strlen( trim( $seo_title ) );
		$description_length = function_exists( 'mb_strlen' ) ? mb_strlen( trim( $seo_description ) ) : strlen( trim( $seo_description ) );
		$h1_count = isset( $content_analysis['h1_count'] ) ? (int) $content_analysis['h1_count'] : 0;
		$has_duplicate_h1 = ! empty( $content_analysis['has_duplicate_h1'] );
		$image_count = isset( $content_analysis['image_count'] ) ? (int) $content_analysis['image_count'] : 0;
		$missing_image_alt_count = isset( $content_analysis['missing_image_alt_count'] ) ? (int) $content_analysis['missing_image_alt_count'] : 0;

		if ( $title_length > 0 ) {
			$score += 18;
		}

		if ( $title_length >= 30 && $title_length <= 60 ) {
			$score += 12;
		}

		if ( $description_length > 0 ) {
			$score += 18;
		}

		if ( $description_length >= 120 && $description_length <= 160 ) {
			$score += 12;
		}

		if ( '' !== trim( $canonical_url ) ) {
			$score += 10;
		}

		if ( 1 === $h1_count ) {
			$score += 15;
		} elseif ( $h1_count > 1 ) {
			$score += 5;
		}

		if ( $h1_count > 0 && ! $has_duplicate_h1 ) {
			$score += 5;
		}

		if ( $image_count <= 0 ) {
			$score += 10;
		} else {
			$images_with_alt = max( 0, $image_count - $missing_image_alt_count );
			$score += (int) round( min( 10, ( $images_with_alt / max( 1, $image_count ) ) * 10 ) );
		}

		return max( 0, min( 100, $score ) );
	}

	private function analyze_post_content( int $post_id ): array {
		$html = $this->get_rendered_content_for_analysis( $post_id );

		if ( '' === trim( $html ) ) {
			return [
				'h1_count' => 0,
				'has_duplicate_h1' => false,
				'h1_texts' => [],
				'image_count' => 0,
				'missing_image_alt_count' => 0,
				'missing_image_alt_details' => [],
			];
		}

		$analysis = $this->extract_content_signals( $html );
		$headings = $analysis['h1_texts'];
		$normalized = array_map(
			static fn( string $heading ): string => function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $heading ) ) : strtolower( trim( $heading ) ),
			$headings
		);
		$normalized = array_values( array_filter( $normalized, static fn( string $heading ): bool => '' !== $heading ) );

		return [
			'h1_count' => count( $headings ),
			'has_duplicate_h1' => count( $normalized ) !== count( array_unique( $normalized ) ),
			'h1_texts' => $headings,
			'image_count' => $analysis['image_count'],
			'missing_image_alt_count' => count( $analysis['missing_image_alt_details'] ),
			'missing_image_alt_details' => $analysis['missing_image_alt_details'],
		];
	}

	private function get_rendered_content_for_analysis( int $post_id ): string {
		$elementor_content = $this->get_elementor_content( $post_id );

		if ( '' !== $elementor_content ) {
			return $elementor_content;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$previous_post = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		$content = apply_filters( 'the_content', $post->post_content );
		wp_reset_postdata();

		if ( null !== $previous_post ) {
			$GLOBALS['post'] = $previous_post;
		} else {
			unset( $GLOBALS['post'] );
		}

		return is_string( $content ) ? $content : '';
	}

	private function get_elementor_content( int $post_id ): string {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return '';
		}

		$plugin = \Elementor\Plugin::$instance ?? null;

		if ( ! $plugin || ! isset( $plugin->documents, $plugin->frontend ) ) {
			return '';
		}

		$document = $plugin->documents->get( $post_id );

		if ( ! $document || ! method_exists( $document, 'is_built_with_elementor' ) || ! $document->is_built_with_elementor() ) {
			return '';
		}

		$content = $plugin->frontend->get_builder_content( $post_id, true );

		return is_string( $content ) ? $content : '';
	}

	private function extract_content_signals( string $html ): array {
		$headings = [];
		$missing_image_alts = [];
		$image_count = 0;
		$document = new \DOMDocument();
		$previous_state = libxml_use_internal_errors( true );

		$loaded = $document->loadHTML(
			'<?xml encoding="utf-8" ?>' . $html,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		if ( false !== $loaded ) {
			foreach ( $document->getElementsByTagName( 'h1' ) as $heading ) {
				$text = trim( wp_strip_all_tags( $heading->textContent ) );

				if ( '' !== $text ) {
					$headings[] = $text;
				}
			}

			foreach ( $document->getElementsByTagName( 'img' ) as $image ) {
				$image_count++;
				$alt = trim( (string) $image->getAttribute( 'alt' ) );

				if ( '' !== $alt ) {
					continue;
				}

				$source = trim( (string) $image->getAttribute( 'src' ) );
				$missing_image_alts[] = '' !== $source ? $source : __( 'Image source unavailable', 'arva-seo' );
			}
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		return [
			'h1_texts' => $headings,
			'image_count' => $image_count,
			'missing_image_alt_details' => $missing_image_alts,
		];
	}
}
