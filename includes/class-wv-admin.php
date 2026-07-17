<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wordvane_Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'wp_ajax_wv_save_wizard',    [ $this, 'ajax_save_wizard' ] );
		add_action( 'wp_ajax_wv_save_settings',  [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_wv_save_checklist', [ $this, 'ajax_save_checklist' ] );
		add_action( 'wp_ajax_wv_suggest_keyword', [ $this, 'ajax_suggest_keyword' ] );
		add_action( 'wp_ajax_wv_dismiss_upsell', [ $this, 'ajax_dismiss_upsell' ] );
	}

	public function register_menus() {
		add_menu_page(
			__( 'Wordvane', 'wordvane' ),
			__( 'Wordvane', 'wordvane' ),
			'edit_posts',
			'wv-generator',
			[ $this, 'page_generator' ],
			'dashicons-edit-page',
			30
		);

		add_submenu_page(
			'wv-generator',
			__( 'Generate Article', 'wordvane' ),
			__( 'Generate Article', 'wordvane' ),
			'edit_posts',
			'wv-generator',
			[ $this, 'page_generator' ]
		);

		add_submenu_page(
			'wv-generator',
			__( 'SEO Insights', 'wordvane' ),
			__( 'SEO Insights', 'wordvane' ),
			'edit_posts',
			'wv-insights',
			[ $this, 'page_insights' ]
		);

		add_submenu_page(
			'wv-generator',
			__( 'Settings', 'wordvane' ),
			__( 'Settings', 'wordvane' ),
			'manage_options',
			'wv-settings',
			[ $this, 'page_settings' ]
		);

		add_submenu_page(
			null,
			__( 'Setup Wizard', 'wordvane' ),
			__( 'Setup Wizard', 'wordvane' ),
			'manage_options',
			'wv-wizard',
			[ $this, 'page_wizard' ]
		);

		/**
		 * Fires after Wordvane's built-in submenu pages are registered.
		 *
		 * Pro uses this to register additional submenu pages (Bulk Queue,
		 * Content Calendar) without modifying the free plugin.
		 *
		 * @since 1.0.0
		 * @hook  wordvane_admin_menu_items
		 * @param string $parent_slug The parent menu slug ('wv-generator').
		 */
		do_action( 'wordvane_admin_menu_items', 'wv-generator' );
	}

	public function enqueue_assets( $hook ) {
		$wv_pages = [
			'toplevel_page_wv-generator',
			'wordvane_page_wv-insights',
			'wordvane_page_wv-settings',
			'admin_page_wv-wizard',
		];

		if ( ! in_array( $hook, $wv_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'wv-admin',
			WORDVANE_PLUGIN_URL . 'admin/css/wv-admin.css',
			[],
			WORDVANE_VERSION
		);

		wp_enqueue_script(
			'wv-admin',
			WORDVANE_PLUGIN_URL . 'admin/js/wv-admin.js',
			[ 'jquery' ],
			WORDVANE_VERSION,
			true
		);

		$settings = get_option( 'wv_settings', [] );
		$is_pro   = Wordvane_Features::is_pro();

		$fs              = function_exists( 'wordvane_fs' ) ? wordvane_fs() : null;
		$trial_available = $fs
			&& method_exists( $fs, 'is_trial_utilized' )
			&& ! $fs->is_trial_utilized()
			&& method_exists( $fs, 'get_trial_url' );
		$trial_url       = $trial_available ? $fs->get_trial_url() : null;

		wp_localize_script( 'wv-admin', 'wvAdmin', [
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'wv_nonce' ),
			'wizardComplete' => (bool) get_option( 'wv_wizard_complete' ),
			'products'       => $settings['products'] ?? [],
			'hasAiProvider'  => function_exists( 'wp_ai_client_prompt' ),
			'checklist'      => get_user_meta( get_current_user_id(), 'wv_checklist', true ) ?: [],
			'isPro'          => $is_pro,
			'upgradeUrl'     => Wordvane_Features::get_upgrade_url(),
			'trialUrl'       => $trial_url,
			'strings'        => [
				'generating'             => __( 'Generating your article...', 'wordvane' ),
				'no_ai_provider'         => __( 'No AI provider is active. Go to Settings → Connectors to install one.', 'wordvane' ),
				'err_generic'            => __( 'Generation failed: ', 'wordvane' ),
				'err_network'            => __( 'Network error. Please try again.', 'wordvane' ),
				'timeout'                => __( 'Request timed out. The article may be too long — try again.', 'wordvane' ),
				'saved'                  => __( 'Settings saved!', 'wordvane' ),
				'save_error'             => __( 'Could not save settings. Please try again.', 'wordvane' ),
				'business_type_required' => __( 'Please select a business type to continue.', 'wordvane' ),
				'required_fields'        => __( 'Please fill in all required fields.', 'wordvane' ),
				'wizard_error'           => __( 'Could not save settings. Please try again.', 'wordvane' ),
				'publish_error'          => __( 'Could not publish. Please try again.', 'wordvane' ),
				'edit_post'              => __( 'Edit post ↗', 'wordvane' ),
				'view_post'              => __( 'View post ↗', 'wordvane' ),
				'internal_links_tip'     => __( 'Consider adding internal links to other posts manually', 'wordvane' ),
				'remove'                 => __( 'Remove', 'wordvane' ),
				'placeholder_product_name'       => __( 'Product / Service Name', 'wordvane' ),
				'placeholder_product_url'        => __( 'URL on your site', 'wordvane' ),
				'placeholder_product_desc'       => __( 'e.g. Custom leather wallet, hand-stitched, 5 card slots, $89', 'wordvane' ),
				'placeholder_product_desc_short' => __( 'One-line description', 'wordvane' ),
				'grade_a'   => __( 'Great — this article is well optimized. Publish it.', 'wordvane' ),
				'grade_b'   => __( 'Good start. Fill in any missing items before publishing.', 'wordvane' ),
				'grade_c'   => __( 'Needs work. Review the warnings before publishing.', 'wordvane' ),
				'seo_checks' => [
					[ 'label' => __( 'Keyword found in title', 'wordvane' ),        'tip' => __( 'The post title should contain your target keyword.', 'wordvane' ) ],
					[ 'label' => __( 'Keyword in first paragraph', 'wordvane' ),    'tip' => __( 'Google checks the first paragraph for your main keyword.', 'wordvane' ) ],
					[ 'label' => __( 'Meta title under 60 chars', 'wordvane' ),     'tip' => __( 'Titles longer than 60 characters get cut off in search results.', 'wordvane' ) ],
					[ 'label' => __( 'Meta description filled in', 'wordvane' ),    'tip' => __( 'A compelling meta description improves click-through rates.', 'wordvane' ) ],
					[ 'label' => __( 'Article over 1000 words', 'wordvane' ),       'tip' => __( 'Longer, detailed articles tend to rank better for competitive keywords.', 'wordvane' ) ],
					[ 'label' => __( 'FAQ section present', 'wordvane' ),           'tip' => __( 'FAQ sections help Google show your content as a featured snippet.', 'wordvane' ) ],
				],
			],
		] );
	}

	public function page_generator() {
		include WORDVANE_PLUGIN_DIR . 'admin/views/page-generator.php';
	}

	public function page_insights() {
		include WORDVANE_PLUGIN_DIR . 'admin/views/page-insights.php';
	}

	public function page_settings() {
		include WORDVANE_PLUGIN_DIR . 'admin/views/page-settings.php';
	}

	public function page_wizard() {
		include WORDVANE_PLUGIN_DIR . 'admin/views/page-wizard.php';
	}

	public function ajax_save_wizard() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON container; individual fields sanitized inside sanitize_settings().
		$raw  = wp_unslash( $_POST['settings'] ?? '' );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( [ 'message' => 'Invalid data.' ] );
			return;
		}

		$settings = $this->sanitize_settings( $data );
		update_option( 'wv_settings', $settings );
		update_option( 'wv_wizard_complete', true );

		$suggested_keyword = Wordvane_Generator::suggest_keyword(
			$settings['business_type'] ?? '',
			$settings['what_they_sell'] ?? ''
		);

		wp_send_json_success( [
			'redirect'          => admin_url( 'admin.php?page=wv-generator&wv_welcome=1' ),
			'suggested_keyword' => $suggested_keyword,
		] );
	}

	public function ajax_save_settings() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		$existing = get_option( 'wv_settings', [] );
		$settings = $this->sanitize_settings( $_POST );

		update_option( 'wv_settings', array_merge( $existing, $settings ) );
		wp_send_json_success( [ 'message' => 'saved' ] );
	}

	public function ajax_save_checklist() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		$checklist = array_map( 'absint', (array) wp_unslash( $_POST['checklist'] ?? [] ) );
		update_user_meta( get_current_user_id(), 'wv_checklist', $checklist );
		wp_send_json_success();
	}

	public function ajax_suggest_keyword() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		$settings = get_option( 'wv_settings', [] );
		$keyword  = Wordvane_Generator::suggest_keyword(
			$settings['business_type'] ?? '',
			$settings['what_they_sell'] ?? ''
		);

		wp_send_json_success( [ 'keyword' => $keyword ] );
	}

	public function ajax_dismiss_upsell() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		$allowed = [ 'settings_comparison', 'insights_upgrade' ];
		$key     = sanitize_key( wp_unslash( $_POST['key'] ?? '' ) );

		if ( ! in_array( $key, $allowed, true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid key.' ] );
		}

		update_user_meta( get_current_user_id(), 'wv_dismissed_' . $key, 1 );
		wp_send_json_success();
	}

	private function sanitize_settings( $data ) {
		$settings = [];

		$text_fields = [
			'business_type', 'business_name', 'what_they_sell', 'ideal_customer',
			'main_goal', 'brand_voice', 'topics_to_avoid', 'locations_served',
			'seo_plugin', 'post_status', 'default_category', 'model_preference',
		];

		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$settings[ $field ] = sanitize_text_field( wp_unslash( $data[ $field ] ) );
			}
		}

		if ( isset( $data['what_they_sell'] ) ) {
			$settings['what_they_sell'] = sanitize_textarea_field( wp_unslash( $data['what_they_sell'] ) );
		}
		if ( isset( $data['ideal_customer'] ) ) {
			$settings['ideal_customer'] = sanitize_textarea_field( wp_unslash( $data['ideal_customer'] ) );
		}
		if ( isset( $data['brand_voice'] ) ) {
			$settings['brand_voice'] = sanitize_textarea_field( wp_unslash( $data['brand_voice'] ) );
		}
		if ( isset( $data['topics_to_avoid'] ) ) {
			$settings['topics_to_avoid'] = sanitize_textarea_field( wp_unslash( $data['topics_to_avoid'] ) );
		}

		if ( isset( $data['products'] ) && is_array( $data['products'] ) ) {
			$settings['products'] = [];
			foreach ( array_slice( $data['products'], 0, 3 ) as $p ) {
				if ( is_array( $p ) && ! empty( $p['name'] ) ) {
					$settings['products'][] = [
						'name'        => sanitize_text_field( wp_unslash( $p['name'] ) ),
						'url'         => esc_url_raw( wp_unslash( $p['url'] ?? '' ) ),
						'description' => sanitize_text_field( wp_unslash( $p['description'] ?? '' ) ),
					];
				}
			}
		}

		return $settings;
	}
}
