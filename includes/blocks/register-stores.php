<?php
/**
 * Interactivity Store Registration
 *
 * Centralizes all wp_interactivity_state() and wp_interactivity_config() calls.
 *
 * v4.2.0: Adds favorites-modal and compare-bar script modules.
 * Adds loadOnClientNavigation for cross-page router support.
 *
 * @package Petstablished_Sync
 * @since 3.0.0
 */

declare( strict_types = 1 );

namespace Petstablished\Blocks;

/**
 * Register all interactivity stores and config.
 */
function register_stores(): void {
	// === Shared Config (non-reactive, available to all stores) ===
	wp_interactivity_config( 'petstablished', [
		'restUrl'      => rest_url(),
		'nonce'        => wp_create_nonce( 'wp_rest' ),
		'maxCompare'   => 4,
		'features'     => [
			'serverFiltering' => true,
			'searchHighlight' => true,
		],
		'i18n'         => get_i18n_strings(),
	] );

	$comparison = \Petstablished_Helpers::get_comparison();
	$has_comparison = ! empty( $comparison );

	// === Global Store State ===
	wp_interactivity_state( 'petstablished', [
		'favorites'            => \Petstablished_Helpers::get_favorites(),
		'comparison'           => $comparison,
		'comparisonMax'        => 4,
		'pets'                 => array(),
		'isLoading'            => false,
		'notification'         => null,
		'noNotification'       => true,
		'_compareBarExpanded'  => $has_comparison,
		'_compareBarPrevCount' => count( $comparison ),
		'isCompareBarHidden'   => ! $has_comparison,
		'isCompareBarVisible'  => $has_comparison,
		// Button text for pet-actions (used by derived state getters).
		'_i18n' => [
			'favorite'       => __( 'Favorite', 'petstablished-sync' ),
			'unfavorite'     => __( 'Unfavorite', 'petstablished-sync' ),
			'compare'        => __( 'Compare', 'petstablished-sync' ),
			'comparing'      => __( 'Comparing', 'petstablished-sync' ),
			'share'          => __( 'Share', 'petstablished-sync' ),
			'copyLink'       => __( 'Copy link', 'petstablished-sync' ),
			'copied'         => __( 'Copied!', 'petstablished-sync' ),
			'copiedAnnounce' => __( 'Link copied to clipboard', 'petstablished-sync' ),
		],
	] );

	// === Grid Store State ===
	wp_interactivity_state( 'petstablished/grid', [
		'isNavigating'          => false,
		'compatFiltersExpanded' => true,
	] );
}

/**
 * Register script modules.
 */
function register_script_modules(): void {
	// Utils module (shared dependency).
	wp_register_script_module(
		'petstablished-utils',
		PETSTABLISHED_SYNC_URL . 'assets/js/utils.js',
		[ '@wordpress/interactivity' ],
		PETSTABLISHED_SYNC_VERSION
	);

	// Global store module.
	wp_register_script_module(
		'petstablished-store',
		PETSTABLISHED_SYNC_URL . 'assets/js/store.js',
		[ '@wordpress/interactivity', 'petstablished-utils' ],
		PETSTABLISHED_SYNC_VERSION
	);
	wp_enqueue_script_module( 'petstablished-store' );

	// Grid store — interactivity-router is a dynamic dependency.
	wp_register_script_module(
		'petstablished-grid',
		PETSTABLISHED_SYNC_URL . 'assets/js/interactivity/grid.js',
		[
			'@wordpress/interactivity',
			'petstablished-store',
			'petstablished-utils',
			[
				'id'     => '@wordpress/interactivity-router',
				'import' => 'dynamic',
			],
		],
		PETSTABLISHED_SYNC_VERSION
	);

	// Compare bar store — uses the router dynamically for viewComparison.
	wp_register_script_module(
		'petstablished-compare-bar',
		PETSTABLISHED_SYNC_URL . 'assets/js/interactivity/compare-bar.js',
		[
			'@wordpress/interactivity',
			'petstablished-store',
			'petstablished-utils',
			[
				'id'     => '@wordpress/interactivity-router',
				'import' => 'dynamic',
			],
		],
		PETSTABLISHED_SYNC_VERSION
	);

	// Favorites modal store — standalone, no router dependency.
	wp_register_script_module(
		'petstablished-favorites-modal',
		PETSTABLISHED_SYNC_URL . 'assets/js/interactivity/favorites-modal.js',
		[
			'@wordpress/interactivity',
			'petstablished-store',
			'petstablished-utils',
		],
		PETSTABLISHED_SYNC_VERSION
	);

	/**
	 * Mark script modules as compatible with client-side navigation.
	 *
	 * @since 4.2.0 (WordPress 6.9)
	 *
	 * When the interactivity-router performs client-side navigation and
	 * encounters a page needing a module not present on the current page,
	 * modules marked with loadOnClientNavigation can be loaded on-the-fly.
	 *
	 * Critical for the attachTo pattern: navigating between pages that
	 * do/don't include the compare bar or favorites modal requires their
	 * script modules to load dynamically.
	 */
	if ( method_exists( wp_interactivity(), 'add_client_navigation_support_to_script_module' ) ) {
		wp_interactivity()->add_client_navigation_support_to_script_module( 'petstablished-store' );
		wp_interactivity()->add_client_navigation_support_to_script_module( 'petstablished-grid' );
		wp_interactivity()->add_client_navigation_support_to_script_module( 'petstablished-compare-bar' );
		wp_interactivity()->add_client_navigation_support_to_script_module( 'petstablished-favorites-modal' );
		wp_interactivity()->add_client_navigation_support_to_script_module( 'petstablished-utils' );
	}
}

/**
 * Get translatable UI strings.
 */
function get_i18n_strings(): array {
	return [
		'added'         => __( 'Added to favorites', 'petstablished-sync' ),
		'removed'       => __( 'Removed from favorites', 'petstablished-sync' ),
		'compareAdd'    => __( 'Added to comparison', 'petstablished-sync' ),
		'compareRemove' => __( 'Removed from comparison', 'petstablished-sync' ),
		'compareFull'   => __( 'Comparison is full (max 4)', 'petstablished-sync' ),
		'copied'        => __( 'Link copied!', 'petstablished-sync' ),
		'loading'       => __( 'Loading...', 'petstablished-sync' ),
		'error'         => __( 'Something went wrong', 'petstablished-sync' ),
		'noResults'     => __( 'No pets match your filters.', 'petstablished-sync' ),
		'searchPlaceholder' => __( 'Search by name or breed…', 'petstablished-sync' ),
	];
}