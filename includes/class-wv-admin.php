<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WV_Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'wp_ajax_wv_save_wizard', [ $this, 'ajax_save_wizard' ] );
		add_action( 'wp_ajax_wv_save_settings', [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_wv_check_limit', [ $this, 'ajax_check_limit' ] );
		add_action( 'wp_ajax_wv_save_checklist', [ $this, 'ajax_save_checklist' ] );
		add_action( 'wp_ajax_wv_suggest_keyword', [ $this, 'ajax_suggest_keyword' ] );
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
			WV_PLUGIN_URL . 'admin/css/wv-admin.css',
			[],
			WV_VERSION
		);

		wp_enqueue_script(
			'wv-admin',
			WV_PLUGIN_URL . 'admin/js/wv-admin.js',
			[ 'jquery' ],
			WV_VERSION,
			true
		);

		$settings = get_option( 'wv_settings', [] );

		wp_localize_script( 'wv-admin', 'wvAdmin', [
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'wv_nonce' ),
			'usage'          => WV_Limits::get_usage(),
			'limit'          => WV_Limits::get_limit(),
			'resetDate'      => WV_Limits::get_reset_date(),
			'daysUntil'      => WV_Limits::get_days_until_reset(),
			'percentage'     => WV_Limits::get_percentage(),
			'wizardComplete' => (bool) get_option( 'wv_wizard_complete' ),
			'products'       => $settings['products'] ?? [],
			'hasAiProvider'  => function_exists( 'wp_ai_client_prompt' ),
			'checklist'      => get_user_meta( get_current_user_id(), 'wv_checklist', true ) ?: [],
			'strings'        => [
				'generating'    => __( 'Generating your article...', 'wordvane' ),
				'limit_reached' => __( 'Monthly limit reached.', 'wordvane' ),
				'no_ai_provider' => __( 'No AI provider is active. Go to Settings → Connectors to install one.', 'wordvane' ),
				'err_generic'   => __( 'Generation failed: ', 'wordvane' ),
				'err_network'   => __( 'Network error. Please try again.', 'wordvane' ),
				'saved'         => __( 'Settings saved!', 'wordvane' ),
				'save_error'    => __( 'Could not save settings. Please try again.', 'wordvane' ),
			],
		] );
	}

	public function page_generator() {
		include WV_PLUGIN_DIR . 'admin/views/page-generator.php';
	}

	public function page_insights() {
		include WV_PLUGIN_DIR . 'admin/views/page-insights.php';
	}

	public function page_settings() {
		include WV_PLUGIN_DIR . 'admin/views/page-settings.php';
	}

	public function page_wizard() {
		include WV_PLUGIN_DIR . 'admin/views/page-wizard.php';
	}

	public function ajax_save_wizard() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		$raw  = wp_unslash( $_POST['settings'] ?? '' );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( [ 'message' => 'Invalid data.' ] );
			return;
		}

		$settings = $this->sanitize_settings( $data );
		update_option( 'wv_settings', $settings );
		update_option( 'wv_wizard_complete', true );

		$suggested_keyword = WV_Generator::suggest_keyword(
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
		$data     = wp_unslash( $_POST );
		$settings = $this->sanitize_settings( $data );

		update_option( 'wv_settings', array_merge( $existing, $settings ) );
		wp_send_json_success( [ 'message' => 'saved' ] );
	}

	public function ajax_check_limit() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		wp_send_json_success( [
			'usage'         => WV_Limits::get_usage(),
			'limit'         => WV_Limits::get_limit(),
			'limit_reached' => WV_Limits::is_limit_reached(),
			'reset_date'    => WV_Limits::get_reset_date(),
			'days_until'    => WV_Limits::get_days_until_reset(),
		] );
	}

	public function ajax_save_checklist() {
		check_ajax_referer( 'wv_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
			return;
		}

		$checklist = array_map( 'absint', (array) ( $_POST['checklist'] ?? [] ) );
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
		$keyword  = WV_Generator::suggest_keyword(
			$settings['business_type'] ?? '',
			$settings['what_they_sell'] ?? ''
		);

		wp_send_json_success( [ 'keyword' => $keyword ] );
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
