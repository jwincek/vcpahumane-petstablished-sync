<?php
/**
 * Petstablished Sync Handler
 *
 * Handles synchronization of pets from Petstablished API.
 *
 * @package Petstablished_Sync
 * @since 2.0.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Petstablished_Sync {

	private const API_BASE = 'https://petstablished.com/api/v2/public/pets';

	private array $stats = array(
		'created'   => 0,
		'updated'   => 0,
		'unchanged' => 0,
		'removed'   => 0,
		'errors'    => array(),
	);

	public function __construct() {
		add_action( 'petstablished_scheduled_sync', array( $this, 'run_sync' ) );
		add_action( 'wp_ajax_petstablished_start_sync', array( $this, 'ajax_start_sync' ) );
		add_action( 'wp_ajax_petstablished_process_batch', array( $this, 'ajax_process_batch' ) );
		add_action( 'wp_ajax_petstablished_finish_sync', array( $this, 'ajax_finish_sync' ) );
	}

	// === AJAX Handlers for JS-based sync ===

	public function ajax_start_sync(): void {
		check_ajax_referer( 'petstablished_sync', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$settings = Petstablished_Admin::get_settings();

		if ( empty( $settings['public_key'] ) ) {
			wp_send_json_error( 'Public key not configured' );
		}

		// Fetch all pets from API.
		$pets = $this->fetch_pets( $settings['public_key'] );

		if ( is_wp_error( $pets ) ) {
			wp_send_json_error( $pets->get_error_message() );
		}

		// Store pets in transient for batch processing.
		set_transient( 'petstablished_sync_pets', $pets, HOUR_IN_SECONDS );
		set_transient( 'petstablished_sync_in_progress', true, 10 * MINUTE_IN_SECONDS );

		wp_send_json_success( array(
			'total'     => count( $pets ),
			'batchSize' => $settings['batch_size'],
		) );
	}

	public function ajax_process_batch(): void {
		check_ajax_referer( 'petstablished_sync', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$offset = absint( $_POST['offset'] ?? 0 );
		$limit  = absint( $_POST['limit'] ?? 10 );

		$pets = get_transient( 'petstablished_sync_pets' );

		if ( ! $pets ) {
			wp_send_json_error( 'Sync session expired. Please start again.' );
		}

		$batch = array_slice( $pets, $offset, $limit );
		$stats = array( 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => array() );

		foreach ( $batch as $pet_data ) {
			$result = $this->process_single_pet( $pet_data );
			$stats[ $result ]++;
		}

		wp_send_json_success( array(
			'processed' => count( $batch ),
			'stats'     => $stats,
		) );
	}

	public function ajax_finish_sync(): void {
		check_ajax_referer( 'petstablished_sync', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$pets = get_transient( 'petstablished_sync_pets' );

		if ( $pets ) {
			// Remove stale pets.
			$removed = $this->remove_stale_pets( $pets );
		}

		// Clean up.
		delete_transient( 'petstablished_sync_pets' );
		delete_transient( 'petstablished_sync_in_progress' );

		// Save sync timestamp.
		update_option( 'petstablished_last_sync', time() );

		wp_send_json_success( array(
			'removed' => $removed ?? 0,
		) );
	}

	// === Background sync for cron ===

	public function run_sync(): bool {
		$settings = Petstablished_Admin::get_settings();

		if ( empty( $settings['public_key'] ) ) {
			return false;
		}

		set_transient( 'petstablished_sync_in_progress', true, 10 * MINUTE_IN_SECONDS );

		$pets = $this->fetch_pets( $settings['public_key'] );

		if ( is_wp_error( $pets ) ) {
			delete_transient( 'petstablished_sync_in_progress' );
			return false;
		}

		foreach ( $pets as $pet_data ) {
			$this->process_single_pet( $pet_data );
		}

		$this->remove_stale_pets( $pets );

		update_option( 'petstablished_last_sync', time() );
		update_option( 'petstablished_last_sync_stats', $this->stats );
		delete_transient( 'petstablished_sync_in_progress' );

		return true;
	}

	// === Core Methods ===

	/**
	 * Maximum number of pages to fetch (safety valve against infinite loops).
	 */
	private const MAX_PAGES = 50;

	/**
	 * Fetch all pets from the Petstablished API, paginating automatically.
	 *
	 * The API returns up to 100 pets per page. This method loops through
	 * all pages until `current_page >= total_pages`, collecting every pet
	 * into a single flat array.
	 *
	 * @param string $public_key Petstablished public API key.
	 * @return array|WP_Error All pet records, or WP_Error on failure.
	 */
	private function fetch_pets( string $public_key ): array|WP_Error {
		$all_pets     = [];
		$current_page = 1;
		$total_pages  = 1; // Will be updated after the first response.

		while ( $current_page <= $total_pages && $current_page <= self::MAX_PAGES ) {
			$query_args = [
				'public_key'         => $public_key,
				'search[animal]'     => 'Cat,Dog',
				'pagination[limit]'  => 100,
				'pagination[page]'   => $current_page,
			];

			$url      = add_query_arg( $query_args, self::API_BASE );
			$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( $code !== 200 ) {
				return new WP_Error( 'api_error', sprintf( 'API returned status %d on page %d', $code, $current_page ) );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! isset( $data['collection'] ) || ! is_array( $data['collection'] ) ) {
				return new WP_Error( 'invalid_response', sprintf( 'Invalid API response format on page %d', $current_page ) );
			}

			$all_pets = array_merge( $all_pets, $data['collection'] );

			// Update total_pages from the API's pagination metadata.
			$total_pages = (int) ( $data['pagination']['total_pages'] ?? 1 );

			$current_page++;
		}

		return $all_pets;
	}

	private function process_single_pet( array $data ): string {
		$ps_id = $data['id'] ?? null;

		if ( ! $ps_id ) {
			return 'errors';
		}

		// Compute a hash of the incoming API payload for change detection.
		// We use a stable JSON encoding (keys sorted) so that identical data
		// always produces the same hash regardless of key ordering.
		$api_hash = $this->compute_api_hash( $data );

		// Find existing pet by Petstablished ID.
		$existing = get_posts( array(
			'post_type'   => 'pet',
			'post_status' => 'any',
			'meta_key'    => '_pet_ps_id',
			'meta_value'  => $ps_id,
			'numberposts' => 1,
		) );

		$post_id = $existing ? $existing[0]->ID : 0;

		// Prepare post data.
		$post_data = array(
			'post_type'    => 'pet',
			'post_status'  => 'publish',
			'post_title'   => sanitize_text_field( $data['name'] ?? 'Unnamed Pet' ),
			'post_content' => wp_kses_post( $data['description'] ?? '' ),
		);

		if ( $post_id ) {
			// Fast-path: compare hash to skip entirely if nothing changed.
			$stored_hash = get_post_meta( $post_id, '_pet_api_hash', true );
			if ( $stored_hash === $api_hash ) {
				return 'unchanged';
			}

			// Something changed — full update.
			$post_data['ID'] = $post_id;
			wp_update_post( $post_data );
			$this->update_pet_meta( $post_id, $data );
			$this->update_pet_taxonomies( $post_id, $data );
			$this->maybe_set_featured_image( $post_id, $data );
			$this->store_api_snapshot( $post_id, $data, $api_hash );
			return 'updated';
		}

		$post_id = wp_insert_post( $post_data );
		if ( is_wp_error( $post_id ) ) {
			return 'errors';
		}

		$this->update_pet_meta( $post_id, $data );
		$this->update_pet_taxonomies( $post_id, $data );
		$this->maybe_set_featured_image( $post_id, $data );
		$this->store_api_snapshot( $post_id, $data, $api_hash );
		return 'created';
	}

	/**
	 * Compute a deterministic hash of the API response data.
	 *
	 * Strips admin-only fields (link_*, name_and_ps_id_link) that contain
	 * Petstablished UI chrome and could trigger false-positive changes.
	 * Sorts keys recursively so identical payloads always hash identically.
	 *
	 * @param array $data Raw API response for a single pet.
	 * @return string SHA-256 hex hash.
	 */
	private function compute_api_hash( array $data ): string {
		// Remove admin-only fields that don't represent pet data.
		$filtered = array_filter(
			$data,
			fn( $key ) => ! str_starts_with( $key, 'link_' ) && $key !== 'name_and_ps_id_link',
			ARRAY_FILTER_USE_KEY
		);

		$normalized = $this->ksort_recursive( $filtered );
		return hash( 'sha256', wp_json_encode( $normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Recursively sort array keys for deterministic hashing.
	 *
	 * @param mixed $value Value to normalize.
	 * @return mixed Normalized value.
	 */
	private function ksort_recursive( mixed $value ): mixed {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		// Only sort associative arrays (objects); leave indexed arrays in order.
		if ( array_is_list( $value ) ) {
			return array_map( [ $this, 'ksort_recursive' ], $value );
		}

		ksort( $value );
		return array_map( [ $this, 'ksort_recursive' ], $value );
	}

	/**
	 * Store the full API response JSON and its hash for this pet.
	 *
	 * The stored snapshot enables:
	 * - Fast hash-based change detection on subsequent syncs.
	 * - Backfill of newly-mapped fields without re-fetching from the API.
	 * - Admin-side debugging / "what changed" inspection.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $data    Raw API response for this pet.
	 * @param string $hash    Pre-computed SHA-256 hash.
	 */
	private function store_api_snapshot( int $post_id, array $data, string $hash ): void {
		update_post_meta( $post_id, '_pet_api_response', wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		update_post_meta( $post_id, '_pet_api_hash', $hash );
	}

	/**
	 * Write only the essential meta fields.
	 *
	 * Display-only data is read from _pet_api_response at hydration time.
	 * Only the Petstablished ID is stored as individual meta because it's
	 * the primary lookup key used by the sync to match API records to
	 * local posts (via meta_key/meta_value query in process_single_pet).
	 */
	private function update_pet_meta( int $post_id, array $data ): void {
		$ps_id = $data['id'] ?? null;
		if ( $ps_id ) {
			update_post_meta( $post_id, '_pet_ps_id', sanitize_text_field( (string) $ps_id ) );
		}
	}

	/**
	 * Sync all taxonomy terms from the API response.
	 *
	 * Handles:
	 * 1. Standard faceted taxonomies (animal, breed, age, etc.) for filtering.
	 * 2. Secondary breed / secondary color as additional terms.
	 * 3. Boolean attribute terms in pet_attribute taxonomy — replaces meta_query
	 *    filtering with much faster tax_query filtering.
	 */
	private function update_pet_taxonomies( int $post_id, array $data ): void {
		// Standard taxonomy mappings (API key → taxonomy).
		$tax_map = array(
			'status'        => 'pet_status',
			'animal'        => 'pet_animal',
			'primary_breed' => 'pet_breed',
			'age'           => 'pet_age',
			'sex'           => 'pet_sex',
			'size'          => 'pet_size',
			'primary_color' => 'pet_color',
			'coat_length'   => 'pet_coat',
		);

		foreach ( $tax_map as $api_key => $taxonomy ) {
			$value = $data[ $api_key ] ?? '';
			if ( $value ) {
				wp_set_object_terms( $post_id, sanitize_text_field( $value ), $taxonomy );
			}
		}

		// Secondary breed (appended).
		if ( ! empty( $data['secondary_breed'] ) ) {
			wp_set_object_terms( $post_id, sanitize_text_field( $data['secondary_breed'] ), 'pet_breed', true );
		}

		// Secondary color (appended).
		if ( ! empty( $data['secondary_color'] ) ) {
			wp_set_object_terms( $post_id, sanitize_text_field( $data['secondary_color'] ), 'pet_color', true );
		}

		// Boolean attributes → pet_attribute taxonomy terms.
		$this->update_attribute_terms( $post_id, $data );
	}

	/**
	 * Assign pet_attribute taxonomy terms based on boolean API fields.
	 *
	 * Reads the attribute_map from entities.json config to map API fields
	 * to taxonomy term slugs. A pet gets a term if its API value is truthy.
	 * The full term set is replaced each sync (not appended) so removed
	 * attributes are correctly cleared.
	 */
	private function update_attribute_terms( int $post_id, array $data ): void {
		$config     = \Petstablished\Core\Config::get_path( 'entities', 'entities.pet', [] );
		$attr_map   = $config['attribute_map'] ?? [];
		$truthy     = $config['attribute_truthy_values'] ?? [ 'yes', 'Yes', '1', 'true' ];
		$truthy_lc  = array_map( 'strtolower', $truthy );

		$terms = [];
		foreach ( $attr_map as $api_key => $term_slug ) {
			$value = $data[ $api_key ] ?? '';
			if ( is_string( $value ) && in_array( strtolower( $value ), $truthy_lc, true ) ) {
				$terms[] = $term_slug;
			}
		}

		// Replace all attribute terms (false = not append).
		wp_set_object_terms( $post_id, $terms, 'pet_attribute', false );
	}

	/**
	 * Set or update the featured image from the API's primary photo.
	 *
	 * On first sync: downloads and attaches the image.
	 * On subsequent syncs: compares the incoming API image URL against
	 * the stored URL. If the shelter updated the photo in Petstablished,
	 * the old attachment is deleted and replaced.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Raw API response for this pet.
	 */
	private function maybe_set_featured_image( int $post_id, array $data ): void {
		$new_image_url = $data['images'][0]['image']['url'] ?? '';

		if ( ! $new_image_url ) {
			return;
		}

		// Check if the image has changed by comparing against the stored source URL.
		$stored_source_url = get_post_meta( $post_id, '_pet_source_image_url', true );
		$has_thumbnail     = has_post_thumbnail( $post_id );

		if ( $has_thumbnail && $stored_source_url === $new_image_url ) {
			// Image hasn't changed — nothing to do.
			return;
		}

		// Image is new or changed — download it.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $new_image_url, $post_id, $data['name'] ?? '', 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			return;
		}

		// If replacing an existing image, clean up the old attachment.
		if ( $has_thumbnail ) {
			$old_attachment_id = get_post_thumbnail_id( $post_id );
			if ( $old_attachment_id ) {
				wp_delete_attachment( (int) $old_attachment_id, true );
			}
		}

		set_post_thumbnail( $post_id, $attachment_id );
		update_post_meta( $post_id, '_pet_source_image_url', $new_image_url );
	}

	private function remove_stale_pets( array $api_pets ): int {
		$api_ids = array_column( $api_pets, 'id' );
		$removed = 0;

		$local_pets = get_posts( array(
			'post_type'   => 'pet',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
		) );

		foreach ( $local_pets as $post_id ) {
			$ps_id = get_post_meta( $post_id, '_pet_ps_id', true );
			if ( $ps_id && ! in_array( (int) $ps_id, $api_ids, true ) ) {
				wp_update_post( array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				) );
				$removed++;
			}
		}

		return $removed;
	}
}