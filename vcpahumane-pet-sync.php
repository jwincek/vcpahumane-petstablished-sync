<?php
/**
 * Plugin Name: Pet Sync for Petstablished
 * Description: Sync adoptable pets from Petstablished with WordPress 6.9 Abilities API, Block Bindings, and Interactivity API.
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * Author: Jerome Wincek
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vcpahumane-pet-sync
 *
 * @package Petstablished_Sync
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'PETSTABLISHED_SYNC_VERSION', '1.0.0' );
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

	// Schedule cron sync. Route through the shared helper so the 6pm-anchor
	// and Sunday-skip semantics apply identically at activation and settings save.
	$settings = Petstablished_Admin::get_settings();
	if ( ! wp_next_scheduled( 'petstablished_scheduled_sync' ) ) {
		Petstablished_Admin::reschedule_cron( $settings['auto_sync'], $settings['sync_interval'] );
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

	// Apply compatibility filters (?compat_goodWithDogs=1 etc.) to the pet
	// archive / taxonomy main query. Compatibility data lives in the
	// pet_attribute taxonomy — it is NOT stored as post meta (the sync
	// keeps it in the _pet_api_response snapshot + attribute terms), so
	// this must be a tax_query. Mirrors Query::whereAttribute() and the
	// filter-pets ability so a no-JS request and the grid block agree.
	add_action( 'pre_get_posts', function( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$entities   = \Petstablished\Core\Config::get_item( 'entities', 'entities', [] );
		$taxonomies = array_column( $entities['vcps_pet']['taxonomies'] ?? [], 'taxonomy' );

		if ( ! $query->is_post_type_archive( 'vcps_pet' ) && ! $query->is_tax( $taxonomies ) ) {
			return;
		}

		// camelCase URL key (compat_<key>) => pet_attribute term slug.
		// Mirrors \Petstablished\Abilities\Pets\COMPAT_MAP and the grid block's URL params.
		$compat_map = [
			'goodWithDogs'   => 'good-with-dogs',
			'goodWithCats'   => 'good-with-cats',
			'goodWithKids'   => 'good-with-kids',
			'shotsCurrent'   => 'shots-current',
			'spayedNeutered' => 'spayed-neutered',
			'housebroken'    => 'housebroken',
			'specialNeeds'   => 'special-needs',
			'hypoallergenic' => 'hypoallergenic',
			'declawed'       => 'declawed',
		];
		$attribute_tax = $entities['vcps_pet']['attribute_taxonomy'] ?? 'pet_attribute';

		$compat_clauses = [];
		foreach ( $compat_map as $input_key => $term_slug ) {
			if ( ! empty( $_GET[ 'compat_' . $input_key ] ) ) {
				$compat_clauses[] = [
					'taxonomy' => $attribute_tax,
					'field'    => 'slug',
					'terms'    => $term_slug,
				];
			}
		}

		if ( empty( $compat_clauses ) ) {
			return;
		}

		if ( count( $compat_clauses ) > 1 ) {
			$compat_clauses['relation'] = 'AND';
		}

		// Combine with any pre-existing tax_query (e.g. an explicit one)
		// via AND. On a taxonomy archive the term constraint comes from
		// query vars and WordPress AND-merges it during parse_tax_query.
		$existing = $query->get( 'tax_query' ) ?: [];
		$query->set(
			'tax_query',
			$existing ? [ 'relation' => 'AND', $existing, $compat_clauses ] : $compat_clauses
		);
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
			'label'       => __( 'Pets', 'vcpahumane-pet-sync' ),
			'description' => __( 'Pet adoption data operations.', 'vcpahumane-pet-sync' ),
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