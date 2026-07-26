<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wordvane_Generator {

	public function __construct() {
		add_action( 'wp_ajax_wv_generate', [ $this, 'ajax_generate' ] );
	}

	public function ajax_generate() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		if ( ! wordvane_has_configured_ai_provider() ) {
			wp_send_json_error( [ 'message' => 'no_ai_provider' ] );
			return;
		}

		$settings = get_option( 'wv_settings', [] );

		$keyword             = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
		$secondary_keywords  = sanitize_text_field( wp_unslash( $_POST['secondary_keywords'] ?? '' ) );
		$article_type        = sanitize_text_field( wp_unslash( $_POST['article_type'] ?? 'how-to' ) );
		$featured_product    = sanitize_key( wp_unslash( $_POST['featured_product'] ?? 'none' ) );
		$custom_instructions = sanitize_textarea_field( wp_unslash( $_POST['custom_instructions'] ?? '' ) );

		if ( empty( $keyword ) ) {
			wp_send_json_error( [ 'message' => 'keyword_required' ] );
			return;
		}

		/**
		 * Filters the generation arguments before the AI prompt is built.
		 *
		 * Pro uses this to inject bulk-queue context, content-refresh flags,
		 * override max_tokens, swap in a different DNA profile, or pre-populate
		 * the optional '_system_prompt' / '_user_message' keys to bypass the
		 * free prompt templates entirely.
		 *
		 * @since 1.0.0
		 * @hook  wordvane_generation_args
		 * @param array {
		 *   @type string $keyword             Primary keyword.
		 *   @type string $secondary_keywords  Comma-separated secondary keywords.
		 *   @type string $article_type        Content type slug (how-to, spotlight, faq, or Pro slugs).
		 *   @type string $featured_product    'none' | 'soft' | '0' | '1' | '2' — product mode or index.
		 *   @type string $custom_instructions Extra user-supplied instructions.
		 *   @type array  $settings            Business DNA option array.
		 *   @type int    $max_tokens          AI client max_tokens. Default 4096.
		 *   @type string $_system_prompt      Optional: full system-prompt override (skips free template).
		 *   @type string $_user_message       Optional: full user-message override (skips free template).
		 * }
		 * @param int $user_id ID of the user initiating generation.
		 */
		$generation_args = apply_filters( 'wordvane_generation_args', [
			'keyword'             => $keyword,
			'secondary_keywords'  => $secondary_keywords,
			'article_type'        => $article_type,
			'featured_product'    => $featured_product,
			'custom_instructions' => $custom_instructions,
			'settings'            => $settings,
			'max_tokens'          => 6000,
		], get_current_user_id() );

		// Log prompt-construction inputs whenever WP_DEBUG is on.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[Wordvane] ajax_generate — featured_product: %s | business_name: %s | product_names: %s',
				var_export( $generation_args['featured_product'], true ),
				var_export( $generation_args['settings']['business_name'] ?? '(not set)', true ),
				var_export( array_column( $generation_args['settings']['products'] ?? [], 'name' ), true )
			) );
		}

		$system_prompt = $generation_args['_system_prompt']
			?? Wordvane_Prompt_Builder::build_system_prompt(
				$generation_args['settings'],
				$generation_args['keyword'],
				$generation_args['secondary_keywords'],
				$generation_args['article_type'],
				$generation_args['featured_product']
			);

		$user_message = $generation_args['_user_message']
			?? Wordvane_Prompt_Builder::build_user_message(
				$generation_args['article_type'],
				$generation_args['keyword'],
				$generation_args['secondary_keywords'],
				$generation_args['settings'],
				$generation_args['featured_product'],
				$generation_args['custom_instructions']
			);

		/**
		 * Fires immediately before the AI generation call.
		 * Pro uses this for pre-generation logging, queue status updates, etc.
		 *
		 * @since 1.0.0
		 * @hook  wordvane_before_generate
		 * @param array $generation_args Filtered generation arguments.
		 */
		do_action( 'wordvane_before_generate', $generation_args );

		$prompt = wp_ai_client_prompt()
			->using_system_instruction( $system_prompt )
			->with_text( $user_message )
			->using_max_tokens( $generation_args['max_tokens'] );

		$model_pref = $generation_args['settings']['model_preference'] ?? '';
		if ( ! empty( $model_pref ) ) {
			$prompt = $prompt->using_model_preference( $model_pref );
		}

		// Give PHP and the WP HTTP layer enough time for a full article response.
		set_time_limit( 180 ); // phpcs:ignore WordPress.PHP.IniSet.Risky, Squiz.PHP.DiscouragedFunctions.Discouraged
		add_filter( 'http_request_args', [ $this, 'extend_ai_timeout' ] );
		$result = $prompt->generate_text();
		remove_filter( 'http_request_args', [ $this, 'extend_ai_timeout' ] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
			return;
		}

		$wv_month_key = 'wv_article_count_' . gmdate( 'Y' ) . '_' . gmdate( 'm' );
		update_option( $wv_month_key, (int) get_option( $wv_month_key, 0 ) + 1 );

		/**
		 * Fires after a successful AI generation.
		 * Pro uses this for post-generation logging, reporting, and white-label hooks.
		 *
		 * @since 1.0.0
		 * @hook  wordvane_after_generate
		 * @param string $result          Raw generated article text.
		 * @param array  $generation_args Filtered generation arguments.
		 */
		do_action( 'wordvane_after_generate', $result, $generation_args );

		$meta = self::extract_metadata( $result, $generation_args['keyword'] );

		$response = [ 'text' => $result, 'meta' => $meta ];

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$response['_debug'] = [
				'featured_product' => $generation_args['featured_product'],
				'system_prompt'    => $system_prompt,
				'user_message'     => $user_message,
			];
		}

		wp_send_json_success( $response );
	}

	public function extend_ai_timeout( $args ) {
		$args['timeout'] = 150;
		return $args;
	}

	private static function extract_metadata( string $article_text, string $keyword ): array {
		$plain = wp_strip_all_tags( $article_text );
		$plain = mb_substr( $plain, 0, 10000 );

		$prompt_text = "Based on the article below, output ONLY a valid JSON object — no explanation, no code fences, no markdown, just the raw JSON.\n\n"
			. "Required fields:\n"
			. "- meta_title: under 60 chars, include \"" . $keyword . "\" near the start\n"
			. "- meta_description: under 155 chars, action-oriented, include the keyword\n"
			. "- slug: URL-friendly, hyphens only, no stop words\n"
			. "- tags: JSON array of 5 relevant tag strings\n"
			. "- faq_schema: JSON array of 5 objects, each {\"question\":\"...\",\"answer\":\"...\"}, based on FAQ content in the article\n\n"
			. "Article:\n" . $plain;

		$response = wp_ai_client_prompt()
			->with_text( $prompt_text )
			->using_max_tokens( 1200 )
			->generate_text();

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$json_str = trim( $response );
		if ( strncmp( $json_str, '```', 3 ) === 0 ) {
			$json_str = preg_replace( '/^```[a-z]*\n?/', '', $json_str );
			$json_str = rtrim( $json_str, " \n`" );
		}

		$meta = json_decode( $json_str, true );
		return is_array( $meta ) ? $meta : [];
	}

	public static function suggest_keyword( $business_type, $what_they_sell ) {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return '';
		}

		$result = wp_ai_client_prompt()
			->with_text( "Suggest ONE specific long-tail SEO keyword (3-5 words) for a {$business_type} business that sells: {$what_they_sell}. Reply with only the keyword, nothing else." )
			->using_max_tokens( 50 )
			->generate_text();

		if ( is_wp_error( $result ) ) {
			return '';
		}

		return trim( $result );
	}

}

new Wordvane_Generator();
