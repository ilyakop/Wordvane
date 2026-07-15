<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Unauthorized', 'wordvane' ) );
}

$settings    = get_option( 'wv_settings', [] );
$is_pro      = WV_Features::is_pro();
$upgrade_url = WV_Features::get_upgrade_url();
$categories  = get_categories( [ 'hide_empty' => false ] );

/**
 * Filters the tabs shown on the Wordvane Settings page.
 *
 * Pro injects a 'license' tab (Freemius license key + plan info),
 * an 'agency' tab (multi-site management), and a 'team' tab (user roles).
 * Tab content is rendered via the 'wordvane_settings_tab_content' action.
 *
 * @since 1.0.0
 * @hook  wordvane_settings_tabs
 * @param array $tabs Associative array of slug => label. Default contains
 *                    'dna' and 'publishing'.
 */
$tabs = apply_filters( 'wordvane_settings_tabs', [
	'dna'        => __( 'Business DNA', 'wordvane' ),
	'publishing' => __( 'Publishing', 'wordvane' ),
] );

$valid_tabs = array_keys( $tabs );
$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ?? $valid_tabs[0] ) );
$active_tab = in_array( $active_tab, $valid_tabs, true ) ? $active_tab : $valid_tabs[0];

/**
 * Filters the number of Business DNA profiles a user can store.
 *
 * Free tier is locked to 1. Pro can allow multiple profiles for agencies
 * managing many clients under one WordPress installation.
 *
 * @since 1.0.0
 * @hook  wordvane_business_dna_profiles
 * @param int $count Max allowed profiles. Default 1.
 */
$allowed_profiles = (int) apply_filters( 'wordvane_business_dna_profiles', 1 );
?>
<div class="wrap wv-settings-page">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Wordvane — Settings', 'wordvane' ); ?></h1>


	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $slug => $label ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wv-settings&tab=' . urlencode( $slug ) ) ); ?>"
			class="nav-tab <?php echo $slug === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php echo esc_html( $label ); ?>
		</a>
		<?php endforeach; ?>
	</nav>

	<div id="wv-settings-feedback" class="wv-settings-feedback" style="display:none;"></div>

	<?php if ( 'dna' === $active_tab ) : ?>
	<!-- Business DNA Tab -->
	<div class="wv-settings-section">
		<h2>
			<?php esc_html_e( 'Business Type', 'wordvane' ); ?>
			<?php if ( $allowed_profiles > 1 ) : ?>
			<span class="wv-profile-badge">
				<?php
				printf(
					/* translators: %d: max allowed profiles */
					esc_html__( 'Up to %d profiles', 'wordvane' ),
					$allowed_profiles
				);
				?>
			</span>
			<?php endif; ?>
		</h2>
		<div class="wv-business-type-cards">
			<?php
			$business_types = [
				'ecommerce'    => [ 'icon' => '🛍️', 'label' => 'eCommerce Store' ],
				'blog'         => [ 'icon' => '📝', 'label' => 'Blog / Content Site' ],
				'local'        => [ 'icon' => '🏪', 'label' => 'Local Business' ],
				'saas'         => [ 'icon' => '💻', 'label' => 'SaaS / Software' ],
				'professional' => [ 'icon' => '👔', 'label' => 'Professional Services' ],
				'other'        => [ 'icon' => '📦', 'label' => 'Other' ],
			];
			foreach ( $business_types as $key => $bt ) :
				$selected = ( ( $settings['business_type'] ?? '' ) === $key ) ? ' selected' : '';
				?>
				<div class="wv-biz-type-card<?php echo esc_attr( $selected ); ?>" data-value="<?php echo esc_attr( $key ); ?>">
					<span class="wv-biz-icon"><?php echo esc_html( $bt['icon'] ); ?></span>
					<span class="wv-biz-label"><?php echo esc_html( $bt['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<input type="hidden" id="wv-settings-business-type" value="<?php echo esc_attr( $settings['business_type'] ?? '' ); ?>">

		<h2><?php esc_html_e( 'Business Details', 'wordvane' ); ?></h2>

		<table class="form-table">
			<tr>
				<th><label for="wv-s-business-name"><?php esc_html_e( 'Business Name', 'wordvane' ); ?></label></th>
				<td>
					<input type="text" id="wv-s-business-name" class="regular-text"
						value="<?php echo esc_attr( $settings['business_name'] ?? '' ); ?>">
				</td>
			</tr>
			<tr>
				<th>
					<label for="wv-s-what-they-sell">
						<?php esc_html_e( 'What do you sell or offer?', 'wordvane' ); ?>
						<?php echo wv_tooltip( 'business_niche' ); ?>
					</label>
				</th>
				<td>
					<textarea id="wv-s-what-they-sell" class="large-text" rows="3"><?php echo esc_textarea( $settings['what_they_sell'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wv-s-ideal-customer">
						<?php esc_html_e( 'Who is your ideal customer?', 'wordvane' ); ?>
						<?php echo wv_tooltip( 'target_audience' ); ?>
					</label>
				</th>
				<td>
					<textarea id="wv-s-ideal-customer" class="large-text" rows="3"><?php echo esc_textarea( $settings['ideal_customer'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Main website goal:', 'wordvane' ); ?></label></th>
				<td>
					<?php
					$goals        = [
						'sell'      => 'Sell products or services',
						'leads'     => 'Generate leads and inquiries',
						'blog'      => 'Grow a blog audience',
						'awareness' => 'Build brand awareness',
					];
					$current_goal = $settings['main_goal'] ?? 'sell';
					foreach ( $goals as $gval => $glabel ) :
						?>
						<label class="wv-radio-label">
							<input type="radio" name="wv_s_main_goal" value="<?php echo esc_attr( $gval ); ?>"
								<?php checked( $current_goal, $gval ); ?>>
							<?php echo esc_html( $glabel ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wv-s-brand-voice">
						<?php esc_html_e( 'Brand Voice', 'wordvane' ); ?>
						<?php echo wv_tooltip( 'brand_voice' ); ?>
					</label>
				</th>
				<td>
					<textarea id="wv-s-brand-voice" class="large-text" rows="3"
						placeholder="<?php esc_attr_e( 'e.g. Friendly and direct, like texting a knowledgeable friend. Short sentences. No corporate jargon.', 'wordvane' ); ?>"><?php echo esc_textarea( $settings['brand_voice'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wv-s-topics-avoid"><?php esc_html_e( 'Topics to Avoid', 'wordvane' ); ?></label>
					<p class="description"><?php esc_html_e( 'Anything you never want mentioned in your articles.', 'wordvane' ); ?></p>
				</th>
				<td>
					<textarea id="wv-s-topics-avoid" class="large-text" rows="2"
						placeholder="<?php esc_attr_e( 'e.g. competitor names, pricing details, political topics', 'wordvane' ); ?>"><?php echo esc_textarea( $settings['topics_to_avoid'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wv-s-locations"><?php esc_html_e( 'Locations Served', 'wordvane' ); ?></label>
					<p class="description"><?php esc_html_e( 'Leave blank for worldwide.', 'wordvane' ); ?></p>
				</th>
				<td>
					<input type="text" id="wv-s-locations" class="regular-text"
						placeholder="<?php esc_attr_e( 'e.g. Chicago, Illinois', 'wordvane' ); ?>"
						value="<?php echo esc_attr( $settings['locations_served'] ?? '' ); ?>">
				</td>
			</tr>
		</table>

		<h2>
			<?php esc_html_e( 'Products / Services', 'wordvane' ); ?>
			<?php echo wv_tooltip( 'products' ); ?>
		</h2>

		<div id="wv-products-repeater-settings">
			<?php
			$products = $settings['products'] ?? [ [] ];
			if ( empty( $products ) ) {
				$products = [ [] ];
			}
			foreach ( $products as $i => $product ) :
				?>
				<div class="wv-product-row">
					<div class="wv-product-fields">
						<input type="text" class="regular-text wv-product-name"
							placeholder="<?php esc_attr_e( 'Product / Service Name', 'wordvane' ); ?>"
							value="<?php echo esc_attr( $product['name'] ?? '' ); ?>">
						<input type="url" class="regular-text wv-product-url"
							placeholder="<?php esc_attr_e( 'URL on your site', 'wordvane' ); ?>"
							value="<?php echo esc_attr( $product['url'] ?? '' ); ?>">
						<input type="text" class="regular-text wv-product-desc"
							placeholder="<?php esc_attr_e( 'One-line description', 'wordvane' ); ?>"
							value="<?php echo esc_attr( $product['description'] ?? '' ); ?>">
					</div>
					<?php if ( $i > 0 ) : ?>
						<button type="button" class="button wv-remove-product"><?php esc_html_e( 'Remove', 'wordvane' ); ?></button>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $products ) < 3 ) : ?>
			<button type="button" id="wv-add-product-settings" class="button">
				<?php esc_html_e( '+ Add product', 'wordvane' ); ?>
			</button>
		<?php endif; ?>

		<p class="submit">
			<button type="button" id="wv-save-dna" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'wordvane' ); ?>
			</button>
			<span id="wv-save-spinner" class="spinner" style="float:none;display:none;"></span>
		</p>
	</div>

	<?php elseif ( 'publishing' === $active_tab ) : ?>
	<!-- Publishing Tab -->
	<div class="wv-settings-section">
		<h2><?php esc_html_e( 'SEO Plugin', 'wordvane' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Select your SEO plugin so Wordvane can fill in meta fields automatically.', 'wordvane' ); ?></p>

		<table class="form-table">
			<tr>
				<th>
					<label><?php esc_html_e( 'Active SEO Plugin', 'wordvane' ); ?></label>
					<?php echo wv_tooltip( 'seo_plugin' ); ?>
				</th>
				<td>
					<?php
					$seo_plugins = [
						'none'     => 'None',
						'yoast'    => 'Yoast SEO',
						'rankmath' => 'Rank Math',
						'aioseo'   => 'All in One SEO',
					];
					$current_seo = $settings['seo_plugin'] ?? 'none';
					foreach ( $seo_plugins as $spval => $splabel ) :
						?>
						<label class="wv-radio-label">
							<input type="radio" name="wv_s_seo_plugin" value="<?php echo esc_attr( $spval ); ?>"
								<?php checked( $current_seo, $spval ); ?>>
							<?php echo esc_html( $splabel ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Default Post Status', 'wordvane' ); ?></label></th>
				<td>
					<label class="wv-radio-label">
						<input type="radio" name="wv_s_post_status" value="draft"
							<?php checked( $settings['post_status'] ?? 'draft', 'draft' ); ?>>
						<?php esc_html_e( 'Draft (recommended — review before publishing)', 'wordvane' ); ?>
					</label>
					<label class="wv-radio-label">
						<input type="radio" name="wv_s_post_status" value="publish"
							<?php checked( $settings['post_status'] ?? 'draft', 'publish' ); ?>>
						<?php esc_html_e( 'Publish immediately', 'wordvane' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="wv-s-default-cat"><?php esc_html_e( 'Default Category', 'wordvane' ); ?></label></th>
				<td>
					<select id="wv-s-default-cat">
						<option value=""><?php esc_html_e( '— None —', 'wordvane' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat->term_id ); ?>"
								<?php selected( $settings['default_category'] ?? '', $cat->term_id ); ?>>
								<?php echo esc_html( $cat->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wv-s-model-pref"><?php esc_html_e( 'Preferred AI Model', 'wordvane' ); ?></label>
					<p class="description"><?php esc_html_e( 'Optional. The AI client will use this model if your provider supports it, otherwise falls back to any available model.', 'wordvane' ); ?></p>
				</th>
				<td>
					<input type="text" id="wv-s-model-pref" class="regular-text"
						placeholder="<?php esc_attr_e( 'e.g. claude-sonnet-4-6', 'wordvane' ); ?>"
						value="<?php echo esc_attr( $settings['model_preference'] ?? '' ); ?>">
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="button" id="wv-save-api" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'wordvane' ); ?>
			</button>
			<span id="wv-save-spinner" class="spinner" style="float:none;display:none;"></span>
		</p>
	</div>

	<?php else : ?>
	<!-- Pro / custom tabs registered via wordvane_settings_tabs filter -->
	<div class="wv-settings-section">
		<?php
		/**
		 * Fires when a non-default settings tab is active.
		 *
		 * Pro uses this to render License, Agency, and Team tab content
		 * without modifying the free plugin file.
		 *
		 * @since 1.0.0
		 * @hook  wordvane_settings_tab_content
		 * @param string $active_tab The currently active tab slug.
		 */
		do_action( 'wordvane_settings_tab_content', $active_tab );
		?>
	</div>
	<?php endif; ?>

	<?php if ( ! $is_pro && ! get_user_meta( get_current_user_id(), 'wv_dismissed_settings_comparison', true ) ) : ?>
	<div class="wv-card wv-plan-compare-card" id="wv-settings-compare-card">
		<button type="button"
			class="wv-dismiss-btn"
			data-dismiss-key="settings_comparison"
			aria-label="<?php esc_attr_e( 'Dismiss', 'wordvane' ); ?>">✕</button>
		<div class="wv-plan-compare-inner">
			<div class="wv-plan-col">
				<span class="wv-plan-badge wv-plan-badge-free"><?php esc_html_e( 'Free', 'wordvane' ); ?></span>
				<ul>
					<li>✓ <?php esc_html_e( '3 article types', 'wordvane' ); ?></li>
					<li>✓ <?php esc_html_e( '5 articles / month', 'wordvane' ); ?></li>
					<li>✓ <?php esc_html_e( '1 Business DNA profile', 'wordvane' ); ?></li>
					<li>✓ <?php esc_html_e( 'Gutenberg block publishing', 'wordvane' ); ?></li>
					<li>✓ <?php esc_html_e( 'SEO integration (Yoast, Rank Math)', 'wordvane' ); ?></li>
				</ul>
			</div>
			<div class="wv-plan-col wv-plan-col-pro">
				<span class="wv-plan-badge wv-plan-badge-pro"><?php esc_html_e( 'Pro', 'wordvane' ); ?></span>
				<ul>
					<?php foreach ( wv_get_pro_features() as $feature ) : ?>
					<li>✓ <?php echo esc_html( $feature['label'] ); ?></li>
					<?php endforeach; ?>
				</ul>
				<a href="<?php echo esc_url( $upgrade_url ); ?>"
					class="button wv-cta-btn">
					<?php esc_html_e( 'Get Wordvane Pro →', 'wordvane' ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php endif; ?>
</div>
