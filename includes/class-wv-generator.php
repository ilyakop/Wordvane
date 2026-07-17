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

		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			wp_send_json_error( [ 'message' => 'no_ai_provider' ] );
			return;
		}

		$settings = get_option( 'wv_settings', [] );

		$keyword             = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
		$secondary_keywords  = sanitize_text_field( wp_unslash( $_POST['secondary_keywords'] ?? '' ) );
		$article_type        = sanitize_text_field( wp_unslash( $_POST['article_type'] ?? 'how-to' ) );
		$featured_product    = absint( $_POST['featured_product'] ?? -1 );
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
		 *   @type int    $featured_product    Product array index, or -1 for none.
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
			'max_tokens'          => 4096,
		], get_current_user_id() );

		$system_prompt = $generation_args['_system_prompt']
			?? $this->build_system_prompt(
				$generation_args['settings'],
				$generation_args['keyword'],
				$generation_args['secondary_keywords'],
				$generation_args['article_type'],
				$generation_args['featured_product']
			);

		$user_message = $generation_args['_user_message']
			?? $this->build_user_message(
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

		wp_send_json_success( [ 'text' => $result ] );
	}

	public function extend_ai_timeout( $args ) {
		$args['timeout'] = 150;
		return $args;
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

	private function build_system_prompt( $settings, $keyword, $secondary_keywords, $article_type, $featured_product_index ) {
		$business_name  = $settings['business_name'] ?? 'our business';
		$business_type  = $settings['business_type'] ?? 'business';
		$what_they_sell = $settings['what_they_sell'] ?? '';
		$ideal_customer = $settings['ideal_customer'] ?? '';
		$main_goal      = $settings['main_goal'] ?? '';
		$locations      = $settings['locations_served'] ?? '';
		$brand_voice    = $settings['brand_voice'] ?? 'Professional and helpful';
		$topics_avoid   = $settings['topics_to_avoid'] ?? '';
		$products       = $settings['products'] ?? [];

		$products_text = '';
		if ( ! empty( $products ) ) {
			foreach ( $products as $p ) {
				if ( ! empty( $p['name'] ) ) {
					$products_text .= '- ' . $p['name'];
					if ( ! empty( $p['url'] ) ) {
						$products_text .= ' | URL: ' . $p['url'];
					}
					if ( ! empty( $p['description'] ) ) {
						$products_text .= ' | ' . $p['description'];
					}
					$products_text .= "\n";
				}
			}
		}

		$featured_product_name = 'general brand awareness';
		$featured_product_url  = '';
		if ( $featured_product_index >= 0 && isset( $products[ $featured_product_index ] ) ) {
			$fp                    = $products[ $featured_product_index ];
			$featured_product_name = $fp['name'] ?? '';
			$featured_product_url  = $fp['url'] ?? '';
		}

		return "You are an expert SEO content writer for {$business_name}.

Business type: {$business_type}
What they offer: {$what_they_sell}
Ideal customer: {$ideal_customer}
Business goal: {$main_goal}
Locations served: {$locations}
Brand voice: {$brand_voice}
Topics to never mention: {$topics_avoid}

Products to promote:
{$products_text}

Writing rules — follow these exactly:
- Write like a real human expert, not a content robot
- Short paragraphs — 2 to 3 sentences maximum
- Vary sentence length — mix short punchy sentences with longer ones. Some sentences should be very short.
- Use real-world examples that match {$business_type}
- Link to products naturally — never force it
- NEVER use these phrases: \"In today's digital landscape\", \"In conclusion\", \"It's worth noting\", \"Dive into\", \"Game-changer\", \"Leverage\", \"Needless to say\", \"At the end of the day\"
- Write for humans first, Google second

Article structure — use this exact order:
1. Hook opening: start with a relatable problem, surprising stat, or bold statement. NEVER start with \"Are you looking for...\"
2. Context: why this topic matters (2 short paragraphs)
3. Main body: structure depends on article type:
   - How-To: numbered steps with subheadings
   - Spotlight: problem → solution → features → proof
   - FAQ: 5 real customer questions with direct answers
4. Product mention: natural reference to {$featured_product_name} with a link to {$featured_product_url}
5. Closing: one short paragraph with a clear next step — no \"In conclusion\"
6. FAQ section: 5 questions a real customer would Google, with concise direct answers (for featured snippets)

SEO requirements:
- H1 must contain: {$keyword}
- First paragraph must contain: {$keyword}
- At least one H2 must be a variation of: {$keyword}
- Use secondary keywords ({$secondary_keywords}) naturally, 2-3 times each throughout the article
- Target length: 1500 words

OUTPUT FORMAT — THIS IS MANDATORY:
- Output raw HTML directly. Do NOT use markdown. Do NOT wrap anything in code fences (no ```html, no ```json, no ``` of any kind).
- Use only these HTML tags: h1, h2, h3, p, ul, ol, li, strong, em, a
- Immediately after the last line of HTML (no blank line separator), output the JSON block below on a new line starting with {
- The JSON block must start with { and end with } with no text before or after it

{
  \"meta_title\": \"\",
  \"meta_description\": \"\",
  \"slug\": \"\",
  \"tags\": [],
  \"faq_schema\": [
    {\"question\": \"\", \"answer\": \"\"},
    {\"question\": \"\", \"answer\": \"\"},
    {\"question\": \"\", \"answer\": \"\"},
    {\"question\": \"\", \"answer\": \"\"},
    {\"question\": \"\", \"answer\": \"\"}
  ]
}

meta_title: under 60 chars, include {$keyword} near the start
meta_description: under 155 chars, action-oriented, include keyword, give a reason to click
slug: URL-friendly, hyphens only, no stop words
tags: 5 relevant WordPress tags as array
faq_schema: same 5 FAQ questions from the article body";
	}

	private function build_user_message( $article_type, $keyword, $secondary_keywords, $settings, $featured_product_index, $custom_instructions ) {
		$products    = $settings['products'] ?? [];
		$type_labels = [
			'how-to'    => 'How-To Guide',
			'spotlight' => 'Product Spotlight',
			'faq'       => 'FAQ Post',
		];
		$type_label = $type_labels[ $article_type ] ?? 'How-To Guide';

		$featured_name = 'No specific product — general brand awareness';
		$featured_url  = '';
		if ( $featured_product_index >= 0 && isset( $products[ $featured_product_index ] ) ) {
			$fp            = $products[ $featured_product_index ];
			$featured_name = $fp['name'] ?? '';
			$featured_url  = $fp['url'] ?? '';
		}

		return "Write a {$type_label} article.
Primary keyword: {$keyword}
Secondary keywords: {$secondary_keywords}
Featured product: {$featured_name} ({$featured_url})
Extra instructions: {$custom_instructions}";
	}
}

new Wordvane_Generator();
