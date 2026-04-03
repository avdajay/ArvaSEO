<?php

namespace ArvaSeo\Repositories;

class BulkEditRepository {

	private const PREVIEW_KEY = 'arva_seo_bulk_edit_preview';
	private const STATE_KEY = 'arva_seo_bulk_edit_state';

	public function get_preview_rows( int $user_id ): array {
		$rows = get_user_meta( $user_id, self::PREVIEW_KEY, true );

		return is_array( $rows ) ? $rows : [];
	}

	public function save_preview_rows( int $user_id, array $rows ): void {
		update_user_meta( $user_id, self::PREVIEW_KEY, array_values( $rows ) );
	}

	public function clear_preview_rows( int $user_id ): void {
		delete_user_meta( $user_id, self::PREVIEW_KEY );
	}

	public function get_state( int $user_id ): array {
		$state = get_user_meta( $user_id, self::STATE_KEY, true );

		if ( ! is_array( $state ) ) {
			$state = [];
		}

		return wp_parse_args(
			$state,
			[
				'status' => 'idle',
				'processed' => 0,
				'total' => 0,
				'updated' => 0,
				'skipped' => 0,
				'errors' => 0,
				'percentage' => 0,
				'last_error' => '',
				'completed_message' => '',
			]
		);
	}

	public function reset_state( int $user_id, int $total ): array {
		$state = [
			'status' => 'running',
			'processed' => 0,
			'total' => $total,
			'updated' => 0,
			'skipped' => 0,
			'errors' => 0,
			'percentage' => 0,
			'last_error' => '',
			'completed_message' => '',
		];
		update_user_meta( $user_id, self::STATE_KEY, $state );

		return $state;
	}

	public function update_state( int $user_id, array $changes ): array {
		$state = array_merge( $this->get_state( $user_id ), $changes );
		update_user_meta( $user_id, self::STATE_KEY, $state );

		return $state;
	}

	public function clear_state( int $user_id ): void {
		delete_user_meta( $user_id, self::STATE_KEY );
	}
}
