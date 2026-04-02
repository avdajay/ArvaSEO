<?php

namespace ArvaSeo\Contracts;

interface SeoService {
	public function get_provider_name(): string;

	public function is_active(): bool;

	public function crawl( string $url ): array;

	public function get_post_id( string $url ): int;

	public function get_post_title( int $post_id ): string;

	public function get_post_description( int $post_id ): string;

	public function get_post_score( int $post_id ): int;

	public function get_post_canonical_url( int $post_id ): string;

	public function is_post_noindex( int $post_id ): bool;

	public function is_post_nofollow( int $post_id ): bool;

	public function get_term_id( string $url ): int;

	public function get_term_taxonomy( int $id ): string;

	public function is_post_or_term( string $url ): string;
}
