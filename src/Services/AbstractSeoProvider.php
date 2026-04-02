<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;

abstract class AbstractSeoProvider implements SeoService {

	abstract protected function get_title_meta_key(): string;

	abstract protected function get_description_meta_key(): string;

	public function crawl( string $url ): array {
		$post_id = $this->get_post_id( $url );

		if ( $post_id <= 0 ) {
			return [];
		}

		return [
			'provider' => $this->get_provider_name(),
			'type' => 'post',
			'id' => $post_id,
			'title' => $this->get_post_title( $post_id ),
			'description' => $this->get_post_description( $post_id ),
		];
	}

	public function get_post_id( string $url ): int {
		return (int) url_to_postid( $url );
	}

	public function get_post_title( int $post_id ): string {
		$title = get_post_meta( $post_id, $this->get_title_meta_key(), true );

		if ( is_string( $title ) && '' !== $title ) {
			return $title;
		}

		$fallback = get_the_title( $post_id );

		return is_string( $fallback ) ? $fallback : '';
	}

	public function get_post_description( int $post_id ): string {
		$description = get_post_meta( $post_id, $this->get_description_meta_key(), true );

		return is_string( $description ) ? $description : '';
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

	public function get_term_id( string $url ): int {
		$path = parse_url( $url, PHP_URL_PATH );

		if ( ! is_string( $path ) || '' === $path ) {
			return 0;
		}

		$segments = array_values(
			array_filter(
				explode( '/', trim( $path, '/' ) ),
				static fn( string $segment ): bool => '' !== $segment
			)
		);

		if ( [] === $segments ) {
			return 0;
		}

		$term_slug = end( $segments );
		$taxonomy = 'product-category' === $segments[0] ? 'product_cat' : '';
		$term = '' !== $taxonomy ? term_exists( $term_slug, $taxonomy ) : term_exists( $term_slug );

		if ( is_array( $term ) && isset( $term['term_id'] ) ) {
			return (int) $term['term_id'];
		}

		if ( is_int( $term ) ) {
			return $term;
		}

		if ( is_string( $term ) && is_numeric( $term ) ) {
			return (int) $term;
		}

		return 0;
	}

	public function get_term_taxonomy( int $id ): string {
		$term = get_term( $id );

		if ( ! $term || is_wp_error( $term ) ) {
			return '';
		}

		return $term->taxonomy;
	}

	public function is_post_or_term( string $url ): string {
		return $this->get_post_id( $url ) > 0 ? 'post' : 'term';
	}
}
