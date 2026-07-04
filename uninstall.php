<?php
/**
 * Uninstall handler — removes all plugin data.
 *
 * Runs when the plugin is deleted from the Plugins screen. Pets are
 * synced from the Petstablished API, so nothing removed here is
 * user-authored or unrecoverable: reinstalling and running Sync Now
 * restores the catalog.
 *
 * Removes:
 *  - all `vcps_pet` posts (and their post meta / term relationships)
 *  - all terms in the plugin's `pet_*` taxonomies
 *  - Site Editor customizations of plugin templates and template parts
 *    (wp_template / wp_template_part posts filed under this plugin's
 *    wp_theme term), plus that term itself
 *  - settings and sync-state options, plugin transients, the scheduled
 *    sync cron event, and per-user favorites/comparison meta
 *
 * @package Petstablished_Sync
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete this plugin's data for the current site.
 */
function vcps_uninstall_site(): void {
	global $wpdb;

	// --- Pets -----------------------------------------------------------
	// wp_delete_post() also removes post meta and term relationships.
	$pet_ids = get_posts( array(
		'post_type'      => 'vcps_pet',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	foreach ( $pet_ids as $pet_id ) {
		wp_delete_post( $pet_id, true );
	}

	// --- Taxonomy terms -------------------------------------------------
	// The plugin isn't loaded during uninstall, so its taxonomies are
	// unregistered and get_terms()/wp_delete_term() would refuse them.
	// Stub-register each one just for this request.
	$taxonomies = array(
		'pet_status',
		'pet_animal',
		'pet_breed',
		'pet_age',
		'pet_sex',
		'pet_size',
		'pet_color',
		'pet_coat',
		'pet_attribute',
	);

	foreach ( $taxonomies as $taxonomy ) {
		register_taxonomy( $taxonomy, 'vcps_pet' );

		$term_ids = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		) );

		if ( ! is_wp_error( $term_ids ) ) {
			foreach ( $term_ids as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}

		unregister_taxonomy( $taxonomy );
	}

	// --- Site Editor customizations of plugin templates -----------------
	// Saved under this plugin's wp_theme term (see
	// Petstablished_Templates::get_customized_template()); inert without
	// the plugin.
	$template_ids = get_posts( array(
		'post_type'      => array( 'wp_template', 'wp_template_part' ),
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'wp_theme',
				'field'    => 'name',
				'terms'    => 'vcpahumane-pet-sync',
			),
		),
	) );

	foreach ( $template_ids as $template_id ) {
		wp_delete_post( $template_id, true );
	}

	$theme_term = get_term_by( 'name', 'vcpahumane-pet-sync', 'wp_theme' );
	if ( $theme_term ) {
		wp_delete_term( $theme_term->term_id, 'wp_theme' );
	}

	// --- Options ----------------------------------------------------------
	delete_option( 'petstablished_sync_settings' );
	delete_option( 'petstablished_last_sync' );
	delete_option( 'petstablished_last_sync_stats' );

	// --- Transients -------------------------------------------------------
	// Known names first; then sweep suffixed ones (e.g. paged sync
	// snapshots) directly, since there is no core wildcard API.
	delete_transient( 'petstablished_sync_in_progress' );
	delete_transient( 'petstablished_sync_incomplete' );
	delete_transient( 'petstablished_sync_pets' );

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_petstablished_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_petstablished_' ) . '%'
		)
	);

	// --- Cron ---------------------------------------------------------------
	wp_clear_scheduled_hook( 'petstablished_scheduled_sync' );

	// Pet rewrite rules (/adopt/pets/…) are stale once the CPT is gone.
	flush_rewrite_rules( false );
}

if ( is_multisite() ) {
	$vcps_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $vcps_site_ids as $vcps_site_id ) {
		switch_to_blog( (int) $vcps_site_id );
		vcps_uninstall_site();
		restore_current_blog();
	}
} else {
	vcps_uninstall_site();
}

// User meta is network-wide (shared usermeta table) — delete once for
// all users. Logged-in visitors' favorites/comparison selections.
delete_metadata( 'user', 0, '_pet_favorites', '', true );
delete_metadata( 'user', 0, '_pet_comparison', '', true );
