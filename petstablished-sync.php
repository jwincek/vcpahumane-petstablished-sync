<?php
/**
 * Plugin Name: Petstablished Sync
 * Description: Sync adoptable pets from Petstablished with WordPress 6.9 Abilities API, Block Bindings, and Interactivity API.
 * Version: 3.2.0
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * Author: Jerome Wincek / Claude
 * Text Domain: petstablished-sync
 *
 * @package Petstablished_Sync
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'PETSTABLISHED_SYNC_VERSION', '3.1.0' );
define( 'PETSTABLISHED_SYNC_FILE', __FILE__ );
define( 'PETSTABLISHED_SYNC_DIR', plugin_dir_path( __FILE__ ) );
define( 'PETSTABLISHED_SYNC_URL', plugin_dir_url( __FILE__ ) );

// Autoload classes.
spl_autoload_register( function ( string $class ): void {
	// Legacy classes: Petstablished_Foo → includes/class-petstablished-foo.php
	if ( str_starts_with( $class, 'Petstablished_' ) ) {
		$file = PETSTABLISHED_SYNC_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
			return;
		}
	}

	// Namespaced classes: Petstablished\Core\Config → includes/core/class-config.php
	if ( str_starts_with( $class, 'Petstablished\\' ) ) {
		$relative = substr( $class, strlen( 'Petstablished\\' ) );
		$parts    = explode( '\\', $relative );
		$name     = array_pop( $parts ); // Class name.
		$dir      = strtolower( implode( '/', $parts ) ); // Sub-directory.
		$file     = PETSTABLISHED_SYNC_DIR . 'includes/' . $dir . '/class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
			return;
		}
	}
} );

/**
 * Plugin activation — register CPTs early and flush rewrite rules.
 *
 * register_activation_hook must be called from the main plugin file,
 * not from inside a plugins_loaded callback. CPTs must be registered
 * before flush_rewrite_rules() or the /pets/ archive will 404.
 */
register_activation_hook( __FILE__, function(): void {
	// Initialize config so CPT_Registry can read post-types.json.
	\Petstablished\Core\Config::init( PETSTABLISHED_SYNC_DIR . 'config/' );

	// Register CPTs and taxonomies so WP knows about the rewrite rules.
	\Petstablished\Core\CPT_Registry::register_post_types();
	\Petstablished\Core\CPT_Registry::register_taxonomies();

	// Flush rewrite rules so /pets/ and taxonomy archives work immediately.
	flush_rewrite_rules();

	// Schedule cron sync.
	$settings = Petstablished_Admin::get_settings();
	if ( $settings['auto_sync'] && ! wp_next_scheduled( 'petstablished_scheduled_sync' ) ) {
		wp_schedule_event( time(), $settings['sync_interval'], 'petstablished_scheduled_sync' );
	}
} );

/**
 * Plugin deactivation — clean up cron and flush rewrite rules.
 */
register_deactivation_hook( __FILE__, function(): void {
	wp_clear_scheduled_hook( 'petstablished_scheduled_sync' );

	// Flush rewrite rules to remove our custom rules cleanly.
	flush_rewrite_rules();
} );

/**
 * Initialize the plugin.
 */
function petstablished_sync_init(): void {
	// Initialize config loader.
	\Petstablished\Core\Config::init( PETSTABLISHED_SYNC_DIR . 'config/' );

	// Config-driven CPT, taxonomy, and meta registration.
	\Petstablished\Core\CPT_Registry::init();

	// Apply compatibility meta filters to pet archive main queries.
	// This was previously in Petstablished_CPT but is a query concern, not registration.
	add_action( 'pre_get_posts', function( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$entities   = \Petstablished\Core\Config::get_item( 'entities', 'entities', [] );
		$taxonomies = array_column( $entities['pet']['taxonomies'] ?? [], 'taxonomy' );

		if ( ! $query->is_post_type_archive( 'pet' ) && ! $query->is_tax( $taxonomies ) ) {
			return;
		}

		$meta_prefix = $entities['pet']['meta_prefix'] ?? '_pet_';
		$fields      = $entities['pet']['fields'] ?? [];
		$meta_query  = $query->get( 'meta_query' ) ?: [];

		// Build meta query from URL compat_ params using entity field config.
		$compat_fields = [ 'ok_with_dogs', 'ok_with_cats', 'ok_with_kids', 'shots_current',
			'spayed_neutered', 'housebroken', 'special_needs' ];

		foreach ( $compat_fields as $field ) {
			$url_key = 'compat_' . lcfirst( str_replace( '_', '', ucwords( $field, '_' ) ) );
			if ( empty( $_GET[ $url_key ] ) ) {
				// Also check legacy URL format (good_with_dogs=yes).
				$legacy_key = $field;
				$value = isset( $_GET[ $legacy_key ] ) ? sanitize_text_field( $_GET[ $legacy_key ] ) : '';
				if ( $value !== 'yes' ) {
					continue;
				}
			}

			$truthy = $fields[ $field ]['truthy_values'] ?? [ 'yes', 'Yes', '1', 'true' ];
			$meta_query[] = [
				'key'     => $meta_prefix . $field,
				'value'   => $truthy,
				'compare' => 'IN',
			];
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$query->set( 'meta_query', $meta_query );
		}
	} );

	// Template helpers — shared functions for block render callbacks.
	require_once PETSTABLISHED_SYNC_DIR . 'includes/template-helpers.php';

	// Core functionality.
	new Petstablished_Blocks();
	new Petstablished_Variations();
	new Petstablished_Templates();

	// Config-driven abilities registration (replaces old Petstablished_Abilities class).
	add_action( 'wp_abilities_api_categories_init', function() {
		wp_register_ability_category( 'pets', [
			'label'       => __( 'Pets', 'petstablished-sync' ),
			'description' => __( 'Pet adoption data operations.', 'petstablished-sync' ),
		] );
	} );
	add_action( 'wp_abilities_api_init', [ \Petstablished\Abilities\Provider::class, 'register' ] );

	// Plugin-scoped REST routes for client-side ability execution.
	// The core Abilities REST API at /wp-abilities/v1/ requires an authenticated
	// user for ALL endpoints. Favorites and comparison must work for anonymous
	// front-end visitors, so we register thin routes that delegate to the
	// abilities directly while respecting each ability's permission_callback.
	require_once PETSTABLISHED_SYNC_DIR . 'includes/class-petstablished-rest.php';
	add_action( 'rest_api_init', [ 'Petstablished_REST', 'register_routes' ] );

	// Admin & Sync (admin only).
	if ( is_admin() ) {
		new Petstablished_Admin();
	}
	new Petstablished_Sync();
}
add_action( 'plugins_loaded', 'petstablished_sync_init' );