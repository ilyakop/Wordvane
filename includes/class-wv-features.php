<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central feature-gate service for Wordvane.
 *
 * Every Pro/Free decision passes through this class. Freemius wires into the
 * 'wordvane_is_pro' filter (see wordvane.php). Extension plugins use the
 * 'wordvane_has_feature' filter for fine-grained feature gates.
 *
 * Dev bypass: define WV_PRO_DEV true in wp-config.php to unlock Pro locally
 * without a Freemius license.
 *
 * @since 1.0.0
 */
class WV_Features {

	/** Free-tier Business DNA profile count. */
	const FREE_DNA_PROFILES = 1;

	/**
	 * Whether a valid Pro license is active.
	 *
	 * Returns true when:
	 * - WV_PRO_DEV constant is defined and truthy (dev/testing bypass), OR
	 * - A filter registered by Freemius confirms a valid premium license.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_pro() {
		if ( defined( 'WV_PRO_DEV' ) && WV_PRO_DEV ) {
			return true;
		}

		/**
		 * Filters whether a Pro license is active.
		 * Freemius wires into this in wordvane.php after SDK initialization.
		 *
		 * @since 1.0.0
		 * @param bool $is_pro Whether Pro is active. Default false.
		 */
		return (bool) apply_filters( 'wordvane_is_pro', false );
	}

	/**
	 * Get a filterable numeric limit.
	 *
	 * @since 1.0.0
	 * @param string $key     Limit identifier (e.g. 'monthly_articles').
	 * @param int    $default Free-tier default value.
	 * @return int
	 */
	public static function get_limit( $key, $default ) {
		return (int) apply_filters( "wordvane_limit_{$key}", $default );
	}

	/**
	 * Whether a named Pro feature is available on this installation.
	 *
	 * @since 1.0.0
	 * @param string $slug Feature slug. Known values:
	 *   'bulk_queue', 'content_calendar', 'multi_dna_profile',
	 *   'internal_linking', 'content_refresh',
	 *   'white_label', 'team_roles'.
	 * @return bool
	 */
	public static function has_feature( $slug ) {
		/**
		 * Filters whether a specific Pro feature is available.
		 * Wordvane Pro overrides this to return true for its feature set.
		 * Partial-feature add-ons can gate individual slugs here.
		 *
		 * @since 1.0.0
		 * @param bool   $available True when any Pro license is active.
		 * @param string $slug      Feature slug being checked.
		 */
		return (bool) apply_filters( 'wordvane_has_feature', self::is_pro(), $slug );
	}

	/**
	 * Upgrade URL for CTA links throughout the admin UI.
	 *
	 * Freemius overrides this via the 'wordvane_upgrade_url' filter in
	 * wordvane.php to return the Freemius checkout URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_upgrade_url() {
		/**
		 * Filters the Pro upgrade URL used in all upgrade CTAs.
		 * Freemius replaces this with wordvane_fs()->get_upgrade_url().
		 *
		 * @since 1.0.0
		 * @param string $url Fallback upgrade URL.
		 */
		return (string) apply_filters( 'wordvane_upgrade_url', 'https://topdevs.net/wordvane-pro' );
	}
}
