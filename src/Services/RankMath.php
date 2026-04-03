<?php

namespace ArvaSeo\Services;

class RankMath extends AbstractSeoProvider {

	public function get_provider_name(): string {
		return 'Rank Math';
	}

	public function is_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'seo-by-rank-math/rank-math.php' )
			|| is_plugin_active( 'seo-by-rank-math-pro/rank-math-pro.php' )
			|| defined( 'RANK_MATH_VERSION' )
			|| class_exists( '\RankMath\Helper' );
	}

	protected function get_title_meta_key(): string {
		return 'rank_math_title';
	}

	protected function get_description_meta_key(): string {
		return 'rank_math_description';
	}

	public function get_post_score( int $post_id ): int {
		$score = get_post_meta( $post_id, 'rank_math_seo_score', true );

		return is_numeric( $score ) ? max( 0, min( 100, (int) $score ) ) : 0;
	}

	public function get_post_canonical_url( int $post_id ): string {
		$canonical = get_post_meta( $post_id, 'rank_math_canonical_url', true );

		return is_string( $canonical ) ? $canonical : '';
	}

	public function is_post_noindex( int $post_id ): bool {
		return in_array( 'noindex', $this->get_robots_meta( $post_id ), true );
	}

	public function is_post_nofollow( int $post_id ): bool {
		return in_array( 'nofollow', $this->get_robots_meta( $post_id ), true );
	}

	protected function update_post_canonical_url( int $post_id, string $value ): void {
		update_post_meta( $post_id, 'rank_math_canonical_url', $value );
	}

	protected function update_post_noindex( int $post_id, bool $value ): void {
		$robots = $this->get_robots_meta( $post_id );
		$robots = $this->replace_robot_directive( $robots, 'noindex', 'index', $value );
		update_post_meta( $post_id, 'rank_math_robots', array_values( array_unique( $robots ) ) );
	}

	protected function update_post_nofollow( int $post_id, bool $value ): void {
		$robots = $this->get_robots_meta( $post_id );
		$robots = $this->replace_robot_directive( $robots, 'nofollow', 'follow', $value );
		update_post_meta( $post_id, 'rank_math_robots', array_values( array_unique( $robots ) ) );
	}

	private function get_robots_meta( int $post_id ): array {
		$robots = get_post_meta( $post_id, 'rank_math_robots', true );

		if ( is_array( $robots ) ) {
			return array_map( 'strval', $robots );
		}

		if ( is_string( $robots ) && '' !== $robots ) {
			return array_map( 'trim', explode( ',', $robots ) );
		}

		return [];
	}

	private function replace_robot_directive( array $robots, string $enabled, string $disabled, bool $value ): array {
		$robots = array_values(
			array_filter(
				$robots,
				static fn( string $robot ): bool => $robot !== $enabled && $robot !== $disabled
			)
		);
		$robots[] = $value ? $enabled : $disabled;

		return $robots;
	}
}
