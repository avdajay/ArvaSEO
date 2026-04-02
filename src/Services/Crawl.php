<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;
use ArvaSeo\Repositories\CrawlResultsRepository;
use ArvaSeo\Repositories\CrawlStateRepository;

class Crawl {
	private const DEFAULT_CHUNK_SIZE = 20;

	private SeoService $seo_service;
	private CrawlResultsRepository $repository;
	private CrawlStateRepository $state_repository;

	public function __construct(
		SeoService $seo_service,
		CrawlResultsRepository $repository,
		CrawlStateRepository $state_repository
	) {
		$this->seo_service = $seo_service;
		$this->repository = $repository;
		$this->state_repository = $state_repository;
	}

	public function is_available(): bool {
		return $this->seo_service->is_active();
	}

	public function get_default_chunk_size(): int {
		return self::DEFAULT_CHUNK_SIZE;
	}

	public function get_state(): array {
		return $this->state_repository->get_state();
	}

	public function crawl_batch( ?int $limit = null, bool $start = false ): array {
		$provider = $this->seo_service->get_provider_name();
		$post_ids = $this->get_public_post_ids();
		$total = count( $post_ids );
		$limit = null === $limit ? self::DEFAULT_CHUNK_SIZE : max( 1, $limit );
		$state = $this->state_repository->get_state();

		if ( $start || $provider !== $state['provider'] || ! in_array( $state['status'], [ 'running', 'paused' ], true ) ) {
			$state = $this->state_repository->reset_state( $provider, $total );
		}

		$offset = max( 0, (int) $state['current_offset'] );
		$batch_post_ids = array_slice( $post_ids, $offset, $limit );
		$existing_object_ids = $this->repository->get_existing_object_ids( $provider, 'post', $batch_post_ids );
		$existing_lookup = array_fill_keys( $existing_object_ids, true );
		$crawled_in_batch = 0;
		$skipped_in_batch = 0;
		$error_in_batch = 0;
		$last_error = '';

		foreach ( $batch_post_ids as $post_id ) {
			if ( isset( $existing_lookup[ $post_id ] ) ) {
				$skipped_in_batch++;
				continue;
			}

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

			$this->repository->upsert_result(
				[
					'provider' => $provider,
					'object_type' => 'post',
					'object_id' => $post_id,
					'post_type' => (string) get_post_type( $post_id ),
					'page_title' => $page_title,
					'seo_title' => (string) ( $seo_data['title'] ?? '' ),
					'seo_description' => (string) ( $seo_data['description'] ?? '' ),
					'canonical_url' => $this->seo_service->get_post_canonical_url( $post_id ),
					'robots_noindex' => $this->seo_service->is_post_noindex( $post_id ) ? 1 : 0,
					'robots_nofollow' => $this->seo_service->is_post_nofollow( $post_id ) ? 1 : 0,
					'permalink' => $permalink,
					'score' => $this->seo_service->get_post_score( $post_id ),
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
				'skipped_count' => (int) $state['skipped_count'] + $skipped_in_batch,
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

	private function get_public_post_ids(): array {
		$post_types = get_post_types(
			[
				'public' => true,
			],
			'names'
		);

		unset( $post_types['attachment'] );

		$post_ids = get_posts(
			[
				'post_type' => array_values( $post_types ),
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
}
