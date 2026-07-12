<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WV_Limits {

	const FREE_LIMIT = 5;

	private static function option_key() {
		return 'wv_article_count_' . date( 'Y' ) . '_' . date( 'm' );
	}

	public static function get_usage() {
		return (int) get_option( self::option_key(), 0 );
	}

	public static function get_limit() {
		return self::FREE_LIMIT;
	}

	public static function increment_usage() {
		$key     = self::option_key();
		$current = (int) get_option( $key, 0 );
		update_option( $key, $current + 1 );
	}

	public static function is_limit_reached() {
		return self::get_usage() >= self::get_limit();
	}

	public static function get_reset_date() {
		$next_month = mktime( 0, 0, 0, (int) date( 'm' ) + 1, 1, (int) date( 'Y' ) );
		return date( 'F j, Y', $next_month );
	}

	public static function get_days_until_reset() {
		$next_month = mktime( 0, 0, 0, (int) date( 'm' ) + 1, 1, (int) date( 'Y' ) );
		$diff       = $next_month - current_time( 'timestamp' );
		return max( 0, (int) ceil( $diff / DAY_IN_SECONDS ) );
	}

	public static function get_remaining() {
		return max( 0, self::get_limit() - self::get_usage() );
	}

	public static function get_percentage() {
		return min( 100, (int) round( ( self::get_usage() / self::get_limit() ) * 100 ) );
	}
}
