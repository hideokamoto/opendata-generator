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

	// Delete options for multisite
	if ( is_multisite() ) {
		global $wpdb;
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			delete_option( 'odg-context' );
			restore_current_blog();
		}
	}
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
	foreach ( $mapping_posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}

	// Clean up post meta table
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
		)
	);
}

/**
 * Main uninstall function
 */
function odg_uninstall() {
	// Delete options
	odg_delete_options();

	// Delete mapping posts
	odg_delete_mapping_posts();

	// Flush rewrite rules
	flush_rewrite_rules();
}

// Run uninstall
odg_uninstall();
