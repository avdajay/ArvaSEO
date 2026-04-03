<?php

namespace ArvaSeo\Repositories;

use wpdb;

class CrawlResultsRepository {

	private const SCHEMA_VERSION = '1.1.0';

	public function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'arva_seo_crawl_results';
	}

	public function ensure_schema(): void {
		if ( self::SCHEMA_VERSION === get_option( 'arva_seo_crawl_schema_version' ) ) {
			return;
		}

		$this->create_table();
		update_option( 'arva_seo_crawl_schema_version', self::SCHEMA_VERSION );
	}

	public function create_table(): void {
		global $wpdb;

		$table_name = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			provider varchar(50) NOT NULL,
			object_type varchar(20) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			post_type varchar(100) NOT NULL DEFAULT '',
			page_title text NOT NULL,
			seo_title text NOT NULL,
			seo_description longtext NOT NULL,
			canonical_url text NOT NULL,
			robots_noindex tinyint(1) unsigned NOT NULL DEFAULT 0,
			robots_nofollow tinyint(1) unsigned NOT NULL DEFAULT 0,
			permalink text NOT NULL,
			score smallint(3) unsigned NOT NULL DEFAULT 0,
			crawled_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY provider_object (provider, object_type, object_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	public function clear_provider_results( string $provider ): void {
		global $wpdb;

		$wpdb->delete(
			$this->get_table_name(),
			[ 'provider' => $provider ],
			[ '%s' ]
		);
	}

	public function clear_all_results(): void {
		global $wpdb;

		$wpdb->query( "TRUNCATE TABLE {$this->get_table_name()}" );
	}

	public function upsert_result( array $data ): void {
		global $wpdb;

		$wpdb->replace(
			$this->get_table_name(),
			[
				'provider' => $data['provider'],
				'object_type' => $data['object_type'],
				'object_id' => $data['object_id'],
				'post_type' => $data['post_type'],
				'page_title' => $data['page_title'],
				'seo_title' => $data['seo_title'],
				'seo_description' => $data['seo_description'],
				'canonical_url' => $data['canonical_url'],
				'robots_noindex' => $data['robots_noindex'],
				'robots_nofollow' => $data['robots_nofollow'],
				'permalink' => $data['permalink'],
				'score' => $data['score'],
				'crawled_at' => current_time( 'mysql' ),
			],
			[
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%d',
				'%s',
			]
		);
	}

	public function count_results(): int {
		return $this->count_results_by_search();
	}

	public function count_results_by_search( string $search = '' ): int {
		global $wpdb;

		$table_name = $this->get_table_name();
		$search = trim( $search );

		if ( '' === $search ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		}

		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE page_title LIKE %s OR permalink LIKE %s",
			$like,
			$like
		);

		return (int) $wpdb->get_var( $sql );
	}

	public function get_last_crawled_at(): ?string {
		global $wpdb;

		$table_name = $this->get_table_name();
		$value = $wpdb->get_var( "SELECT MAX(crawled_at) FROM {$table_name}" );

		return is_string( $value ) && '' !== $value ? $value : null;
	}

	public function get_existing_object_ids( string $provider, string $object_type, array $object_ids ): array {
		global $wpdb;

		$object_ids = array_values( array_filter( array_map( 'intval', $object_ids ) ) );

		if ( [] === $object_ids ) {
			return [];
		}

		$placeholders = implode( ', ', array_fill( 0, count( $object_ids ), '%d' ) );
		$table_name = $this->get_table_name();
		$sql = $wpdb->prepare(
			"SELECT object_id FROM {$table_name} WHERE provider = %s AND object_type = %s AND object_id IN ({$placeholders})",
			array_merge( [ $provider, $object_type ], $object_ids )
		);
		$results = $wpdb->get_col( $sql );

		return is_array( $results ) ? array_map( 'intval', $results ) : [];
	}

	public function get_paginated_results( int $page, int $per_page, string $search = '' ): array {
		global $wpdb;

		$page = max( 1, $page );
		$offset = ( $page - 1 ) * $per_page;
		$table_name = $this->get_table_name();
		$search = trim( $search );

		if ( '' === $search ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY score ASC, crawled_at DESC, object_id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			);
		} else {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE page_title LIKE %s OR permalink LIKE %s ORDER BY score ASC, crawled_at DESC, object_id DESC LIMIT %d OFFSET %d",
				$like,
				$like,
				$per_page,
				$offset
			);
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $results ) ? $results : [];
	}

	public function get_results_for_export( string $search = '' ): array {
		global $wpdb;

		$table_name = $this->get_table_name();
		$search = trim( $search );

		if ( '' === $search ) {
			$sql = "SELECT permalink, seo_title, seo_description, canonical_url, robots_nofollow, robots_noindex FROM {$table_name} ORDER BY object_id ASC";
		} else {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$sql = $wpdb->prepare(
				"SELECT permalink, seo_title, seo_description, canonical_url, robots_nofollow, robots_noindex FROM {$table_name} WHERE page_title LIKE %s OR permalink LIKE %s ORDER BY object_id ASC",
				$like,
				$like
			);
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $results ) ? $results : [];
	}
}
