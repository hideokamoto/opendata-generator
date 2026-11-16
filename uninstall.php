<?php
/**
 * OpenData Generator Uninstall
 *
 * Uninstalling OpenData Generator deletes options and mapping posts.
 *
 * @package OpenData Generator
 * @since 3.0.3
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load plugin config
require_once plugin_dir_path( __FILE__ ) . 'classes/class.config.php';

/**
 * Delete plugin options
 */
function odg_delete_options() {
	delete_option( 'odg-context' );
}

/**
 * Delete all mapping posts
 */
function odg_delete_mapping_posts() {
	global $wpdb;

	// Get all mapping posts
	$mapping_posts = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
			ODG_Config::NAME
		)
	);

	// Delete each post and its metadata
	// wp_delete_post() with force=true automatically deletes associated post meta
	foreach ( $mapping_posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

/**
 * Remove the plugin's data from one site.
 */
function odg_uninstall_site() {
	// Delete options
	odg_delete_options();

	// Delete mapping posts
	odg_delete_mapping_posts();

	// Drop the cached rewrite rules so they are rebuilt without the odg-jsonld endpoints.
	// flush_rewrite_rules() is not used: after switch_to_blog() it would write the rules
	// built for the original site into the switched site.
	delete_option( 'rewrite_rules' );
}

/**
 * Main uninstall function
 *
 * On multisite the options and the mapping posts live in each site's own tables,
 * so both are removed site by site (previously only the options were).
 */
function odg_uninstall() {
	if ( ! is_multisite() ) {
		odg_uninstall_site();
		return;
	}

	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		odg_uninstall_site();
		restore_current_blog();
	}
}

// Run uninstall
odg_uninstall();
