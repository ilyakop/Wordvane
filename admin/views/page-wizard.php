<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template vars are local to this included file, not truly global.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- wordvane_tooltip() is the only unescaped call; it returns pre-escaped HTML.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Unauthorized', 'wordvane' ) );
}

$settings = get_option( 'wv_settings', [] );
?>
<div class="wv-wizard-wrap">
	<div class="wv-wizard-header">
		<div class="wv-wizard-logo">
			<strong>Wordvane</strong>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wv-generator' ) ); ?>" class="wv-wizard-skip">
			<?php esc_html_e( 'Skip setup — I will do this later', 'wordvane' ); ?>
		</a>
	</div>

	<div class="wv-wizard-progress-bar">
		<div class="wv-progress-step active" data-step="1"><span>1</span></div>
		<div class="wv-progress-connector"></div>
		<div class="wv-progress-step" data-step="2"><span>2</span></div>
		<div class="wv-progress-connector"></div>
		<div class="wv-progress-step" data-step="3"><span>3</span></div>
	</div>

	<!-- Step 1: Business Type -->
	<div class="wv-wizard-step active" id="wv-step-1">
		<h1><?php esc_html_e( 'Welcome to Wordvane 👋', 'wordvane' ); ?></h1>
		<p class="wv-wizard-sub"><?php esc_html_e( "Let's set up your AI content engine in 3 quick steps.", 'wordvane' ); ?></p>
		<p class="wv-step-label"><?php esc_html_e( 'Step 1 of 3 — What best describes your business?', 'wordvane' ); ?></p>

		<div class="wv-business-type-cards">
			<?php
			foreach ( wordvane_get_business_types() as $key => $bt ) :
				$selected = ( ( $settings['business_type'] ?? '' ) === $key ) ? ' selected' : '';
				?>
				<div class="wv-biz-type-card<?php echo esc_attr( $selected ); ?>" data-value="<?php echo esc_attr( $key ); ?>">
					<span class="wv-biz-icon"><?php echo esc_html( $bt['icon'] ); ?></span>
					<span class="wv-biz-label"><?php echo esc_html( $bt['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wv-wizard-nav">
			<button type="button" class="button button-primary wv-btn-next" disabled data-next="2">
				<?php esc_html_e( 'Next →', 'wordvane' ); ?>
			</button>
		</div>
	</div>

	<!-- Step 2: Business Identity -->
	<div class="wv-wizard-step" id="wv-step-2">
		<h1><?php esc_html_e( 'Tell us about your business', 'wordvane' ); ?></h1>
		<p class="wv-step-label"><?php esc_html_e( 'Step 2 of 3', 'wordvane' ); ?></p>

		<div class="wv-field-group">
			<label class="wv-label">
				<?php esc_html_e( 'Business Name', 'wordvane' ); ?> <span class="required">*</span>
			</label>
			<input type="text" id="wv-business-name" class="regular-text wv-required"
				value="<?php echo esc_attr( $settings['business_name'] ?? '' ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Rose Garden Floral', 'wordvane' ); ?>">
		</div>

		<div class="wv-field-group">
			<label class="wv-label">
				<?php esc_html_e( 'What do you sell or offer?', 'wordvane' ); ?> <span class="required">*</span>
				<?php echo wordvane_tooltip( 'business_niche' ); ?>
			</label>
			<textarea id="wv-what-they-sell" class="large-text wv-required" rows="3"
				placeholder="<?php esc_attr_e( 'e.g. Handmade leather wallets and accessories for men, shipped worldwide', 'wordvane' ); ?>"><?php echo esc_textarea( $settings['what_they_sell'] ?? '' ); ?></textarea>
		</div>

		<div class="wv-field-group">
			<label class="wv-label">
				<?php esc_html_e( 'Who is your ideal customer?', 'wordvane' ); ?> <span class="required">*</span>
				<?php echo wordvane_tooltip( 'target_audience' ); ?>
			</label>
			<textarea id="wv-ideal-customer" class="large-text wv-required" rows="3"
				placeholder="<?php esc_attr_e( 'e.g. Men aged 25-45 who value quality over fast fashion and buy premium accessories', 'wordvane' ); ?>"><?php echo esc_textarea( $settings['ideal_customer'] ?? '' ); ?></textarea>
		</div>

		<div class="wv-field-group">
			<label class="wv-label"><?php esc_html_e( 'Your main website goal:', 'wordvane' ); ?></label>
			<?php
			$current_goal = $settings['main_goal'] ?? 'sell';
			foreach ( wordvane_get_main_goals() as $gval => $glabel ) :
				?>
				<label class="wv-radio-label">
					<input type="radio" name="wv_main_goal" value="<?php echo esc_attr( $gval ); ?>"
						<?php checked( $current_goal, $gval ); ?>>
					<?php echo esc_html( $glabel ); ?>
				</label>
			<?php endforeach; ?>
		</div>

		<div class="wv-wizard-nav">
			<button type="button" class="button wv-btn-back" data-back="1">
				<?php esc_html_e( '← Back', 'wordvane' ); ?>
			</button>
			<button type="button" class="button button-primary wv-btn-next" data-next="3">
				<?php esc_html_e( 'Next →', 'wordvane' ); ?>
			</button>
		</div>
	</div>

	<!-- Step 3: Products + Complete -->
	<div class="wv-wizard-step" id="wv-step-3">
		<h1><?php esc_html_e( 'What are you promoting?', 'wordvane' ); ?></h1>
		<p class="wv-wizard-sub">
			<?php esc_html_e( 'Add up to 3 products or services. Articles will link to these naturally.', 'wordvane' ); ?>
			<?php echo wordvane_tooltip( 'products' ); ?>
		</p>
		<p class="wv-step-label"><?php esc_html_e( 'Step 3 of 3', 'wordvane' ); ?></p>

		<div id="wv-products-repeater">
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
							placeholder="<?php esc_attr_e( 'e.g. Custom leather wallet, hand-stitched, 5 card slots, $89', 'wordvane' ); ?>"
							value="<?php echo esc_attr( $product['description'] ?? '' ); ?>">
					</div>
					<?php if ( $i > 0 ) : ?>
						<button type="button" class="button wv-remove-product">
							<?php esc_html_e( 'Remove', 'wordvane' ); ?>
						</button>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( count( $products ) < 3 ) : ?>
		<button type="button" id="wv-add-product" class="button">
			<?php esc_html_e( '+ Add another product', 'wordvane' ); ?>
		</button>
		<?php endif; ?>

		<p class="wv-muted-note">
			<?php esc_html_e( "Don't have products set up yet? No problem — you can add these later in Settings.", 'wordvane' ); ?>
		</p>

		<p class="wv-wizard-plan-note">
			<?php
			if ( Wordvane_Features::is_pro() ) {
				esc_html_e( "You're on the Pro plan — includes Bulk Queue, Content Refresh, and more.", 'wordvane' );
			} else {
				printf(
					wp_kses(
						/* translators: %s: upgrade URL */
						__( "You're generating articles on the <strong>Free plan</strong>. <a href=\"%s\">View Wordvane Pro →</a>", 'wordvane' ),
						[ 'strong' => [], 'a' => [ 'href' => [] ] ]
					),
					esc_url( Wordvane_Features::get_upgrade_url() )
				);
			}
			?>
		</p>

		<div class="wv-wizard-nav">
			<button type="button" class="button wv-btn-back" data-back="2">
				<?php esc_html_e( '← Back', 'wordvane' ); ?>
			</button>
			<button type="button" id="wv-complete-wizard" class="button button-primary">
				<?php esc_html_e( 'Complete Setup →', 'wordvane' ); ?>
			</button>
			<span id="wv-wizard-saving" style="display:none;">
				<span class="spinner is-active" style="float:none;"></span>
				<?php esc_html_e( 'Saving...', 'wordvane' ); ?>
			</span>
		</div>
	</div>
</div>
