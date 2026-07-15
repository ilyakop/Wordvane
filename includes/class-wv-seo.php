<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WV_SEO {

	public function __construct() {
		add_action( 'save_post', [ $this, 'on_save_post' ], 20, 2 );
		add_action( 'wp_head', [ $this, 'output_faq_schema' ] );
	}

	public function on_save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$meta_title = get_post_meta( $post_id, '_wv_meta_title', true );
		if ( empty( $meta_title ) ) {
			return;
		}
		$meta_description = get_post_meta( $post_id, '_wv_meta_description', true );
		$target_keyword   = get_post_meta( $post_id, '_wv_target_keyword', true );
		$faq_schema       = get_post_meta( $post_id, '_wv_faq_schema', true );
		$this->apply_seo_meta( $post_id, $meta_title, $meta_description, $target_keyword, is_array( $faq_schema ) ? $faq_schema : [] );
	}

	public function apply_seo_meta( $post_id, $meta_title, $meta_description, $target_keyword, $faq_schema = [] ) {
		$settings   = get_option( 'wv_settings', [] );
		$seo_plugin = $settings['seo_plugin'] ?? 'none';

		switch ( $seo_plugin ) {
			case 'yoast':
				update_post_meta( $post_id, '_yoast_wpseo_title', $meta_title );
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
				break;

			case 'rankmath':
				update_post_meta( $post_id, 'rank_math_title', $meta_title );
				update_post_meta( $post_id, 'rank_math_description', $meta_description );
				update_post_meta( $post_id, 'rank_math_focus_keyword', $target_keyword );
				break;

			case 'aioseo':
				update_post_meta( $post_id, '_aioseo_title', $meta_title );
				update_post_meta( $post_id, '_aioseo_description', $meta_description );
				break;

			// 'none': schema is output via wp_head — no content injection needed.
		}
	}

	/**
	 * Output FAQ JSON-LD schema in <head> for SRW posts when no SEO plugin is active.
	 * This avoids injecting a <script> tag into post_content, which Gutenberg wraps
	 * in a Classic block.
	 */
	public function output_faq_schema() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$settings   = get_option( 'wv_settings', [] );
		$seo_plugin = $settings['seo_plugin'] ?? 'none';
		if ( 'none' !== $seo_plugin ) {
			return;
		}

		$post_id = get_the_ID();

		/**
		 * Filters the schema types Wordvane outputs in <head> for a post.
		 *
		 * Free tier outputs 'FAQPage' when FAQ data exists. Pro can add or
		 * replace schema types (e.g. 'HowTo', 'Article', 'Product'). Remove
		 * 'FAQPage' from the array to suppress the default FAQ block entirely.
		 *
		 * @since 1.0.0
		 * @hook  wordvane_seo_schema_types
		 * @param string[] $types   Schema @type values to output. Default ['FAQPage'].
		 * @param int      $post_id Current post ID.
		 */
		$schema_types = (array) apply_filters( 'wordvane_seo_schema_types', [ 'FAQPage' ], $post_id );
		if ( ! in_array( 'FAQPage', $schema_types, true ) ) {
			return;
		}

		$faq_schema = get_post_meta( $post_id, '_wv_faq_schema', true );
		if ( empty( $faq_schema ) || ! is_array( $faq_schema ) ) {
			return;
		}

		$entities = [];
		foreach ( $faq_schema as $item ) {
			if ( ! empty( $item['question'] ) && ! empty( $item['answer'] ) ) {
				$entities[] = [
					'@type'          => 'Question',
					'name'           => sanitize_text_field( $item['question'] ),
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => sanitize_textarea_field( $item['answer'] ),
					],
				];
			}
		}

		if ( empty( $entities ) ) {
			return;
		}

		$schema = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		];

		echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . "</script>\n";
	}
}
