<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;

class NullSeoService implements SeoService {

	public function get_provider_name(): string {
		return 'None';
	}

	public function is_active(): bool {
		return false;
	}

	public function crawl( string $url ): array {
		return [];
	}

	public function get_post_id( string $url ): int {
		return 0;
	}

	public function get_post_title( int $post_id ): string {
		return '';
	}

	public function get_post_description( int $post_id ): string {
		return '';
	}

	public function get_post_score( int $post_id ): int {
		return 0;
	}

	public function get_post_canonical_url( int $post_id ): string {
		return '';
	}

	public function is_post_noindex( int $post_id ): bool {
		return false;
	}

	public function is_post_nofollow( int $post_id ): bool {
		return false;
	}

	public function update_post_fields( int $post_id, array $fields ): void {
	}

	public function get_term_id( string $url ): int {
		return 0;
	}

	public function get_term_taxonomy( int $id ): string {
		return '';
	}

	public function is_post_or_term( string $url ): string {
		return 'none';
	}
}
