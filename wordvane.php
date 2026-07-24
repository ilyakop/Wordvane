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

define( 'WORDVANE_VERSION', '1.0.0' );
define( 'WORDVANE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WORDVANE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WORDVANE_PLUGIN_DIR . 'includes/class-wv-features.php';
require_once WORDVANE_PLUGIN_DIR . 'includes/wv-tooltips.php';
require_once WORDVANE_PLUGIN_DIR . 'includes/class-wv-generator.php';
require_once WORDVANE_PLUGIN_DIR . 'includes/class-wv-publisher.php';
require_once WORDVANE_PLUGIN_DIR . 'includes/class-wv-seo.php';
require_once WORDVANE_PLUGIN_DIR . 'includes/class-wv-admin.php';
require_once WORDVANE_PLUGIN_DIR . 'includes/wv-pro-features-list.php';

register_activation_hook( __FILE__, 'wordvane_activate' );
function wordvane_activate() {
	if ( ! get_option( 'wv_activated' ) ) {
		update_option( 'wv_activated', '1' );
		update_option( 'wv_wizard_complete', false );
		set_transient( 'wv_activation_redirect', true, 30 );
	}
}

register_deactivation_hook( __FILE__, 'wordvane_deactivate' );
function wordvane_deactivate() {
	wp_clear_scheduled_hook( 'wv_monthly_reset' ); // clean up legacy cron from earlier versions
}

add_action( 'admin_init', 'wordvane_activation_redirect' );
function wordvane_activation_redirect() {
	if ( ! get_transient( 'wv_activation_redirect' ) ) {
		return;
	}
	delete_transient( 'wv_activation_redirect' );
	// When Freemius is active it owns the post-activation redirect:
	// - New installs:    opt-in screen → after_connect/skip_url filters below → wizard
	// - Returning users: first-path in menu config → wizard (while not yet complete)
	// Only redirect directly when the Freemius SDK is absent.
	$fs = function_exists( 'wordvane_fs' ) ? wordvane_fs() : null;
	if ( $fs ) {
		return;
	}
	if ( ! get_option( 'wv_wizard_complete' ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=wv-wizard' ) );
		exit;
	}
}

add_action( 'admin_notices', 'wordvane_ai_provider_notice' );
function wordvane_ai_provider_notice() {
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

// After a new-install opt-in (connect or skip), send to wizard instead of generator.
// Registered before do_action('wordvane_fs_loaded') fires inside the block below.
add_action( 'wordvane_fs_loaded', static function() {
	$fs = wordvane_fs();
	if ( ! $fs || get_option( 'wv_wizard_complete' ) ) {
		return;
	}
	$wizard_url = admin_url( 'admin.php?page=wv-wizard' );
	$to_wizard  = static function() use ( $wizard_url ) {
		return $wizard_url;
	};
	$fs->add_filter( 'after_connect_url',         $to_wizard );
	$fs->add_filter( 'after_skip_url',            $to_wizard );
	$fs->add_filter( 'after_pending_connect_url', $to_wizard );
} );

if ( ! function_exists( 'wordvane_fs' ) ) {
	function wordvane_fs() {
		global $wordvane_fs;

		if ( ! isset( $wordvane_fs ) ) {
			$sdk = WORDVANE_PLUGIN_DIR . 'vendor/freemius/start.php';
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
					'slug'       => 'wv-generator',
					'account'    => true,
					'contact'    => false,
					'support'    => false,
					// For returning users (already registered, no opt-in shown): redirect to
					// wizard on first activation if setup isn't complete yet.
					'first-path' => get_option( 'wv_wizard_complete' ) ? false : 'admin.php?page=wv-wizard',
				),
			) );
		}

		return $wordvane_fs;
	}

	wordvane_fs();
	do_action( 'wordvane_fs_loaded' );
}

// Wire Freemius into Wordvane_Features once the SDK is available.
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
	if ( ! Wordvane_Features::is_pro() ) {
		$pro_installed = class_exists( 'WVP_License' );
		$label         = $pro_installed
			? __( 'Activate License', 'wordvane' )
			: __( 'Upgrade to Pro', 'wordvane' );
		$upgrade_link  = '<a href="' . esc_url( Wordvane_Features::get_upgrade_url() ) . '">' . $label . '</a>';
		array_unshift( $links, $upgrade_link );
	}
	return $links;
} );

// ---------------------------------------------------------------------------
// Dismissible upgrade widget on Insights dashboard (non-Pro users only)
// ---------------------------------------------------------------------------
add_action( 'wordvane_dashboard_widgets', 'wordvane_render_insights_upgrade_widget', 1 );
function wordvane_render_insights_upgrade_widget() {
	if ( Wordvane_Features::is_pro() ) {
		return;
	}
	if ( get_user_meta( get_current_user_id(), 'wv_dismissed_insights_upgrade', true ) ) {
		return;
	}

	$total_generated = 0;
	for ( $m = 1; $m <= 12; $m++ ) {
		$total_generated += (int) get_option( 'wv_article_count_' . gmdate( 'Y' ) . '_' . gmdate( 'm', gmmktime( 0, 0, 0, $m, 1 ) ), 0 );
	}

	$fs              = function_exists( 'wordvane_fs' ) ? wordvane_fs() : null;
	$trial_available = $fs
		&& method_exists( $fs, 'is_trial_utilized' )
		&& ! $fs->is_trial_utilized()
		&& method_exists( $fs, 'get_trial_url' );
	$cta_url         = $trial_available ? $fs->get_trial_url() : Wordvane_Features::get_upgrade_url();
	$cta_label       = $trial_available ? __( 'Start Free Trial →', 'wordvane' ) : __( 'Get Wordvane Pro →', 'wordvane' );

	if ( $total_generated >= 10 ) {
		/* translators: %d: number of articles generated */
		$copy = sprintf( __( "You've generated %d articles with Wordvane — Pro's Bulk Queue can process 50+ articles in the background automatically, saving hours of manual work.", 'wordvane' ), $total_generated );
	} elseif ( $total_generated >= 3 ) {
		/* translators: %d: number of articles generated */
		$copy = sprintf( __( "You've generated %d articles. Upgrade to Pro to unlock Bulk Queue, Content Refresh Mode, and advanced article types like Comparison and Listicle.", 'wordvane' ), $total_generated );
	} else {
		$copy = __( 'Pro unlocks Bulk Queue to generate articles from a keyword list in the background, Content Refresh Mode for updating old posts, and advanced article types like Comparison and Listicle.', 'wordvane' );
	}

	include WORDVANE_PLUGIN_DIR . 'admin/views/widget-upgrade.php';
}

// ---------------------------------------------------------------------------

if ( is_admin() ) {
	new Wordvane_Admin();
	new Wordvane_SEO();
}
