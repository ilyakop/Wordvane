<?php
/**
 * Plugin Name: Wordvane
 * Plugin URI: https://topdevs.net/wordvane
 * Description: AI-powered SEO article generator for WordPress. Create keyword-optimized content using the WordPress AI Client and publish directly to your site.
 * Version: 1.0.0
 * Author: TopDevs
 * Author URI: https://topdevs.net
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wordvane
 * Requires at least: 7.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WV_VERSION', '1.0.0' );
define( 'WV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WV_PLUGIN_DIR . 'includes/wv-tooltips.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-limits.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-generator.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-publisher.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-seo.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-admin.php';

register_activation_hook( __FILE__, 'wv_activate' );
function wv_activate() {
	$month_key = 'wv_article_count_' . date( 'Y' ) . '_' . date( 'm' );
	if ( ! get_option( 'wv_activated' ) ) {
		update_option( 'wv_activated', current_time( 'timestamp' ) );
		update_option( 'wv_wizard_complete', false );
		add_option( $month_key, 0 );
		set_transient( 'wv_activation_redirect', true, 30 );
	}
}

register_deactivation_hook( __FILE__, 'wv_deactivate' );
function wv_deactivate() {
	wp_clear_scheduled_hook( 'wv_monthly_reset' );
}

add_action( 'plugins_loaded', 'wv_load_textdomain' );
function wv_load_textdomain() {
	load_plugin_textdomain( 'wordvane', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'admin_init', 'wv_activation_redirect' );
function wv_activation_redirect() {
	if ( get_transient( 'wv_activation_redirect' ) ) {
		delete_transient( 'wv_activation_redirect' );
		if ( ! get_option( 'wv_wizard_complete' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wv-wizard' ) );
			exit;
		}
	}
}

add_action( 'admin_notices', 'wv_ai_provider_notice' );
function wv_ai_provider_notice() {
	if ( function_exists( 'wp_ai_client_prompt' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}
	$wv_screens = [
		'toplevel_page_wv-generator',
		'wordvane_page_wv-insights',
		'wordvane_page_wv-settings',
		'admin_page_wv-wizard',
	];
	if ( ! in_array( $screen->id, $wv_screens, true ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Wordvane', 'wordvane' ) . ':</strong> ' .
		esc_html__( 'No AI provider is active. Go to Settings → Connectors and install an AI provider plugin (Anthropic, Google, or OpenAI) to start generating articles.', 'wordvane' ) .
		'</p></div>';
}

if ( is_admin() ) {
	new WV_Admin();
	new WV_SEO();
}
