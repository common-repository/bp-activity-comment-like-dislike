<?php
/**
 * Plugin Name:       Comment Like Dislike for BuddyPress Activity
 * Description:       Comment Like Dislike for BuddyPress Activity also known as upvote / downvote counters.
 * Version:           1.0
 * Author:            Dhaval Kasavala
 * Text Domain:       bp-activity-comment-like-dislike
 * Domain Path:       /languages/
 *
 * @package bp-activity-comment-like-dislike
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


define( 'ACLD_PATH', plugin_dir_path( __FILE__ ) );

define( 'ACLD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'ACLD_PLUGIN_FILE', __FILE__ );

if ( is_admin() ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! in_array( 'buddypress/bp-loader.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'acld_requires_plugin' );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}


/**
 * Print notice of activation.
 *
 * @return void
 */
function acld_requires_plugin() {
	/* translators: %1$s: Name of plugin */
	echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( esc_html__( '%1$s requires to install the %2$sBuddypress%3$s plugin.', 'bp-activity-comment-like-dislike' ), esc_html__( 'Comment Like Dislike for BuddyPress Activity ', 'bp-activity-comment-like-dislike' ), '<a href="https://wordpress.org/plugins/buddypress" target="_blank">', '</a>' ) . '</strong></p></div>';
}

require ACLD_PATH . 'includes/class-acld-vote-updown.php';
