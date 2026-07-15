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

require_once WV_PLUGIN_DIR . 'includes/class-wv-features.php';
require_once WV_PLUGIN_DIR . 'includes/wv-tooltips.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-limits.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-generator.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-publisher.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-seo.php';
require_once WV_PLUGIN_DIR . 'includes/class-wv-admin.php';
require_once WV_PLUGIN_DIR . 'includes/wv-pro-features-list.php';

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

// ---------------------------------------------------------------------------
// Freemius SDK
// ---------------------------------------------------------------------------
if ( ! function_exists( 'wordvane_fs' ) ) {
	function wordvane_fs() {
		global $wordvane_fs;

		if ( ! isset( $wordvane_fs ) ) {
			$sdk = WV_PLUGIN_DIR . 'vendor/freemius/start.php';
			if ( ! file_exists( $sdk ) ) {
				return null;
			}
			require_once $sdk;

			$wordvane_fs = fs_dynamic_init( array(
				'id'                  => '34586',
				'slug'                => 'wordvane',
				'premium_slug'        => 'wordvane-pro',
				'type'                => 'plugin',
				'public_key'          => 'pk_c219a407ab5f4b817249268ae6262',
				'is_premium'          => false,
				'premium_suffix'      => 'Pro',
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'is_org_compliant'    => true,
				'trial'               => array(
					'days'               => 7,
					'is_require_payment' => true,
				),
				'menu'                => array(
					'slug'    => 'wv-generator',
					'account' => true,
					'contact' => false,
					'support' => false,
				),
			) );
		}

		return $wordvane_fs;
	}

	wordvane_fs();
	do_action( 'wordvane_fs_loaded' );
}

// Wire Freemius into WV_Features once the SDK is available.
add_filter( 'wordvane_is_pro', static function() {
	$fs = wordvane_fs();
	return $fs && $fs->can_use_premium_code();
} );

// Upgrade URL — three states:
//   1. Pro plugin active, no license → license tab (user just needs to activate)
//   2. Pro plugin not active          → Freemius in-admin checkout
//   3. Pro plugin active + licensed   → irrelevant (no upsells shown)
add_filter( 'wordvane_upgrade_url', static function() {
	if ( class_exists( 'WVP_License' ) && ! WVP_License::is_active() ) {
		return admin_url( 'admin.php?page=wv-settings&tab=license' );
	}
	$fs = wordvane_fs();
	return $fs ? $fs->get_upgrade_url() : 'https://topdevs.net/wordvane-pro';
} );

// ---------------------------------------------------------------------------
// Plugin action links (Plugins list page — plain text link, no notice styling)
// ---------------------------------------------------------------------------
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), static function( $links ) {
	if ( ! WV_Features::is_pro() ) {
		$pro_installed = class_exists( 'WVP_License' );
		$label         = $pro_installed
			? __( 'Activate License', 'wordvane' )
			: __( 'Upgrade to Pro', 'wordvane' );
		$upgrade_link  = '<a href="' . esc_url( WV_Features::get_upgrade_url() ) . '">' . $label . '</a>';
		array_unshift( $links, $upgrade_link );
	}
	return $links;
} );

// ---------------------------------------------------------------------------
// Dismissible upgrade widget on Insights dashboard (non-Pro users only)
// ---------------------------------------------------------------------------
add_action( 'wordvane_dashboard_widgets', 'wv_render_insights_upgrade_widget', 1 );
function wv_render_insights_upgrade_widget() {
	if ( WV_Features::is_pro() ) {
		return;
	}
	if ( get_user_meta( get_current_user_id(), 'wv_dismissed_insights_upgrade', true ) ) {
		return;
	}

	$total_generated = 0;
	for ( $m = 1; $m <= 12; $m++ ) {
		$total_generated += (int) get_option( 'wv_article_count_' . gmdate( 'Y' ) . '_' . sprintf( '%02d', $m ), 0 );
	}

	$fs              = function_exists( 'wordvane_fs' ) ? wordvane_fs() : null;
	$trial_available = $fs
		&& method_exists( $fs, 'is_trial_utilized' )
		&& ! $fs->is_trial_utilized()
		&& method_exists( $fs, 'get_trial_url' );
	$cta_url         = $trial_available ? $fs->get_trial_url() : WV_Features::get_upgrade_url();
	$cta_label       = $trial_available ? __( 'Start Free Trial →', 'wordvane' ) : __( 'Get Wordvane Pro →', 'wordvane' );

	if ( $total_generated >= 5 ) {
		/* translators: %d: number of articles generated */
		$copy = sprintf( __( "You've generated %d articles with Wordvane. At this pace you'll hit the 5/month cap repeatedly — Pro removes the limit entirely and adds Bulk Queue to scale to 50+ articles.", 'wordvane' ), $total_generated );
	} elseif ( $total_generated >= 2 ) {
		/* translators: %d: number of articles generated */
		$copy = sprintf( __( "You've generated %d articles. Pro removes the 5/month limit and unlocks Bulk Queue, Content Refresh Mode, and advanced article types.", 'wordvane' ), $total_generated );
	} else {
		$copy = __( 'Pro removes the 5/month limit and unlocks Bulk Queue, Content Refresh Mode, and advanced article types like Comparison and Listicle.', 'wordvane' );
	}

	include WV_PLUGIN_DIR . 'admin/views/widget-upgrade.php';
}

// ---------------------------------------------------------------------------

if ( is_admin() ) {
	new WV_Admin();
	new WV_SEO();
}
