<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template vars are local to this included file, not truly global.
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- wordvane_tooltip() is the only unescaped call; it returns pre-escaped HTML.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die( esc_html__( 'Unauthorized', 'wordvane' ) );
}

global $wp_version;
$settings         = get_option( 'wv_settings', [] );
$products         = $settings['products'] ?? [];
$month_key        = 'wv_article_count_' . gmdate( 'Y' ) . '_' . gmdate( 'm' );
$usage_this_month = (int) get_option( $month_key, 0 );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only nav flag set by our own redirect, not a form submission.
$show_welcome     = isset( $_GET['wv_welcome'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['wv_welcome'] ) );
$categories       = get_categories( [ 'hide_empty' => false ] );
$is_pro           = Wordvane_Features::is_pro();
$upgrade_url      = Wordvane_Features::get_upgrade_url();

$wp_version_ok  = version_compare( $wp_version, '7.0', '>=' );
$ai_provider_ok = function_exists( 'wp_ai_client_prompt' );
$is_locked      = ! $wp_version_ok || ! $ai_provider_ok;

/**
 * Filters the article types available in the generator UI.
 *
 * Each entry is keyed by the type slug sent to the AJAX handler.
 * Pro adds Comparison, Listicle, Category Page, and WooCommerce Product
 * Description by filtering this array. Free ships 3 types.
 *
 * @since 1.0.0
 * @hook  wordvane_article_types
 * @param array[] $types {
 *   Associative array keyed by type slug.
 *   @type string $icon Icon emoji shown on the card.
 *   @type string $name Display name.
 *   @type string $desc One-line description shown on the card.
 * }
 */
$base_type_slugs = [ 'how-to', 'spotlight', 'faq' ];
$article_types   = apply_filters( 'wordvane_article_types', [
	'how-to' => [
		'icon' => '📖',
		'name' => __( 'How-To Guide', 'wordvane' ),
		'desc' => __( 'Step-by-step tutorial. Best for teaching something your audience wants to learn.', 'wordvane' ),
	],
	'spotlight' => [
		'icon' => '⭐',
		'name' => __( 'Spotlight', 'wordvane' ),
		'desc' => __( 'Showcase one product or service in depth. Great for converting browsers to buyers.', 'wordvane' ),
	],
	'faq' => [
		'icon' => '❓',
		'name' => __( 'FAQ Post', 'wordvane' ),
		'desc' => __( '5 questions your customers always ask. Great for Google featured snippets.', 'wordvane' ),
	],
] );

$enabled_types = [];
$locked_types  = [];

if ( $is_pro ) {
	$enabled_types = $article_types;
} else {
	foreach ( $article_types as $slug => $type ) {
		if ( in_array( $slug, $base_type_slugs, true ) ) {
			$enabled_types[ $slug ] = $type;
		} else {
			$locked_types[ $slug ] = $type; // Pro plugin installed but no license
		}
	}
	// Hardcoded Pro preview cards — always show even when Pro plugin is not installed
	$pro_preview_types = [
		'comparison'    => [
			'icon' => '⚖️',
			'name' => __( 'Comparison', 'wordvane' ),
			'desc' => __( 'X vs Y articles with pros/cons table and final verdict. High buying intent.', 'wordvane' ),
		],
		'listicle'      => [
			'icon' => '📋',
			'name' => __( 'Listicle', 'wordvane' ),
			'desc' => __( 'Ranked list post — one H2 per item. Great for featured snippets.', 'wordvane' ),
		],
		'category-page' => [
			'icon' => '🗂️',
			'name' => __( 'Category Page', 'wordvane' ),
			'desc' => __( 'Keyword-rich landing page with internal link structure.', 'wordvane' ),
		],
		'woo-product'   => [
			'icon' => '🛍️',
			'name' => __( 'Product Description', 'wordvane' ),
			'desc' => __( 'WooCommerce product description — short + long, benefits-first.', 'wordvane' ),
		],
	];
	foreach ( $pro_preview_types as $slug => $type ) {
		if ( ! isset( $locked_types[ $slug ] ) ) {
			$locked_types[ $slug ] = $type;
		}
	}
}

$first_type_slug = array_key_first( $enabled_types ) ?? 'how-to';

$fs              = function_exists( 'wordvane_fs' ) ? wordvane_fs() : null;
$trial_available = $fs
	&& method_exists( $fs, 'has_trial_plan' )
	&& $fs->has_trial_plan()
	&& method_exists( $fs, 'is_trial_utilized' )
	&& ! $fs->is_trial_utilized()
	&& method_exists( $fs, 'get_trial_url' );
$trial_url       = $trial_available ? $fs->get_trial_url() : null;

// Publisher post types
$publisher_post_types = apply_filters( 'wordvane_publisher_post_types', [ 'post' ] );
$pro_post_type_labels = [
	'page'    => __( 'Page', 'wordvane' ),
	'product' => __( 'WooCommerce Product', 'wordvane' ),
];
?>
<div class="wrap wv-generator-page">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Generate Article', 'wordvane' ); ?></h1>

	<div class="wv-lockable-content<?php echo $is_locked ? ' wv-is-locked' : ''; ?>">

		<?php if ( $is_locked ) : ?>
		<div class="wv-lock-overlay" aria-live="polite">
			<div class="wv-lock-icon">🔒</div>
			<h2><?php esc_html_e( 'One more step before you can generate', 'wordvane' ); ?></h2>
			<p><?php esc_html_e( 'Wordvane needs two things to work:', 'wordvane' ); ?></p>

			<ul class="wv-lock-checklist">
				<li class="<?php echo $wp_version_ok ? 'wv-lock-done' : 'wv-lock-todo'; ?>">
					<span class="wv-lock-status"><?php echo $wp_version_ok ? '✅' : '❌'; ?></span>
					<span class="wv-lock-label">
						<strong><?php esc_html_e( 'WordPress 7.0 or higher', 'wordvane' ); ?></strong>
						<?php if ( ! $wp_version_ok ) : ?>
						<span class="wv-lock-note">
							<?php
							printf(
								/* translators: %s: current WP version */
								esc_html__( 'You are running WordPress %s. Please update to continue.', 'wordvane' ),
								esc_html( $wp_version )
							);
							?>
						</span>
						<?php else : ?>
						<span class="wv-lock-note"><?php esc_html_e( 'Your WordPress version is supported.', 'wordvane' ); ?></span>
						<?php endif; ?>
					</span>
				</li>
				<li class="<?php echo $ai_provider_ok ? 'wv-lock-done' : 'wv-lock-todo'; ?>">
					<span class="wv-lock-status"><?php echo $ai_provider_ok ? '✅' : '❌'; ?></span>
					<span class="wv-lock-label">
						<strong><?php esc_html_e( 'AI Provider connected', 'wordvane' ); ?></strong>
						<?php if ( ! $ai_provider_ok ) : ?>
						<span class="wv-lock-note">
							<?php esc_html_e( 'Install an AI provider plugin and connect it under Settings → Connectors. Works with Anthropic, Google, and OpenAI.', 'wordvane' ); ?>
						</span>
						<?php else : ?>
						<span class="wv-lock-note"><?php esc_html_e( 'AI provider is active and ready.', 'wordvane' ); ?></span>
						<?php endif; ?>
					</span>
				</li>
			</ul>

			<div class="wv-lock-actions">
				<?php if ( ! $wp_version_ok ) : ?>
				<a href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Update WordPress →', 'wordvane' ); ?>
				</a>
				<?php endif; ?>
				<?php if ( ! $ai_provider_ok ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=connectors' ) ); ?>" class="button <?php echo $wp_version_ok ? 'button-primary' : 'button-secondary'; ?>">
					<?php esc_html_e( 'Set Up AI Provider →', 'wordvane' ); ?>
				</a>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( $show_welcome ) : ?>
		<div class="wv-welcome-banner">
			<div class="wv-welcome-inner">
				<h2>🎉 <?php esc_html_e( 'You are all set! Here is what to do first:', 'wordvane' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Write your first article below', 'wordvane' ); ?></li>
					<li><?php esc_html_e( 'Review it and hit Publish', 'wordvane' ); ?></li>
					<li><?php esc_html_e( 'Repeat 2x this week — consistency is everything', 'wordvane' ); ?></li>
				</ol>
				<p>💡 <?php esc_html_e( 'Sites that publish 2 articles/week for 3 months consistently start seeing real Google traffic. Wordvane makes that effortless.', 'wordvane' ); ?></p>
			</div>
		</div>
		<?php endif; ?>

		<!-- Usage Counter -->
		<div class="wv-usage-counter">
			<div class="wv-usage-left">
				<strong>📊 <?php
				/* translators: %d: number of articles generated this month */
				printf( esc_html__( 'Articles this month: %d', 'wordvane' ), absint( $usage_this_month ) );
				?></strong>
			</div>
			<?php if ( ! $is_pro ) : ?>
			<div class="wv-usage-right">
				<a href="<?php echo esc_url( $upgrade_url ); ?>" class="wv-upgrade-link">
					<?php esc_html_e( 'Upgrade to Pro →', 'wordvane' ); ?>
				</a>
			</div>
			<?php endif; ?>
		</div>

		<div id="wv-generator-layout" class="wv-generator-layout">
			<!-- Input Form -->
			<div class="wv-generator-form" id="wv-generator-form-col">
				<div class="wv-card">
					<div class="wv-field-group">
						<label class="wv-label" for="wv-keyword">
							<?php esc_html_e( 'Target Keyword', 'wordvane' ); ?> <span class="required">*</span>
							<?php echo wordvane_tooltip( 'target_keyword' ); ?>
						</label>
						<input type="text" id="wv-keyword" class="large-text"
							placeholder="<?php esc_attr_e( 'e.g. affordable wedding flowers Chicago', 'wordvane' ); ?>">
						<div class="wv-field-error" id="wv-keyword-error" style="display:none;">
							<?php esc_html_e( 'Please enter a target keyword.', 'wordvane' ); ?>
						</div>
					</div>

					<div class="wv-field-group">
						<label class="wv-label" for="wv-secondary-keywords">
							<?php esc_html_e( 'Secondary Keywords', 'wordvane' ); ?>
							<?php echo wordvane_tooltip( 'secondary_keywords' ); ?>
						</label>
						<input type="text" id="wv-secondary-keywords" class="large-text"
							placeholder="<?php esc_attr_e( 'e.g. bridal bouquets, wedding flower costs (comma separated)', 'wordvane' ); ?>">
					</div>

					<div class="wv-field-group">
						<label class="wv-label">
							<?php esc_html_e( 'Article Type', 'wordvane' ); ?> <span class="required">*</span>
							<?php echo wordvane_tooltip( 'article_type' ); ?>
						</label>
						<div class="wv-article-type-cards">
							<?php foreach ( $enabled_types as $type_slug => $type ) : ?>
							<div class="wv-article-type-card<?php echo $type_slug === $first_type_slug ? ' selected' : ''; ?>"
								data-type="<?php echo esc_attr( $type_slug ); ?>">
								<div class="wv-type-icon"><?php echo esc_html( $type['icon'] ); ?></div>
								<div class="wv-type-name"><?php echo esc_html( $type['name'] ); ?></div>
								<div class="wv-type-desc"><?php echo esc_html( $type['desc'] ); ?></div>
							</div>
							<?php endforeach; ?>
							<?php foreach ( $locked_types as $type_slug => $type ) : ?>
							<div class="wv-article-type-card wv-type-locked"
								data-type="<?php echo esc_attr( $type_slug ); ?>"
								data-pro-desc="<?php echo esc_attr( $type['desc'] ); ?>"
								role="button"
								tabindex="0"
								aria-label="<?php /* translators: %s: article type name */ echo esc_attr( sprintf( __( '%s — Pro feature. Click to learn more.', 'wordvane' ), $type['name'] ) ); ?>">
								<span class="wv-pro-badge"><?php esc_html_e( 'Pro', 'wordvane' ); ?></span>
								<div class="wv-type-icon"><?php echo esc_html( $type['icon'] ); ?></div>
								<div class="wv-type-name"><?php echo esc_html( $type['name'] ); ?></div>
								<div class="wv-type-desc"><?php echo esc_html( $type['desc'] ); ?></div>
							</div>
							<?php endforeach; ?>
						</div>
						<div id="wv-pro-type-popover" class="wv-pro-popover" role="tooltip" aria-live="polite">
							<button type="button" class="wv-pro-popover-close" aria-label="<?php esc_attr_e( 'Close', 'wordvane' ); ?>">✕</button>
							<p class="wv-pro-popover-desc"></p>
							<a href="<?php echo esc_url( $trial_url ?: $upgrade_url ); ?>"
								id="wv-pro-popover-cta"
								class="wv-cta-btn wv-cta-btn-sm">
								<?php echo esc_html( $trial_available ? __( 'Start Free Trial →', 'wordvane' ) : __( 'Get Wordvane Pro →', 'wordvane' ) ); ?>
							</a>
						</div>
					</div>

					<div class="wv-field-group">
						<label class="wv-label" for="wv-featured-product">
							<?php esc_html_e( 'Featured Product', 'wordvane' ); ?>
							<?php echo wordvane_tooltip( 'featured_product' ); ?>
						</label>
						<select id="wv-featured-product">
							<option value="-1"><?php esc_html_e( 'No specific product — general brand awareness', 'wordvane' ); ?></option>
							<?php foreach ( $products as $i => $product ) :
								if ( empty( $product['name'] ) ) continue;
								?>
								<option value="<?php echo esc_attr( $i ); ?>">
									<?php echo esc_html( $product['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="wv-field-group">
						<label class="wv-label" for="wv-custom-instructions">
							<?php esc_html_e( 'Custom Instructions (optional)', 'wordvane' ); ?>
						</label>
						<textarea id="wv-custom-instructions" class="large-text" rows="3"
							placeholder="<?php esc_attr_e( "Any extra notes for this article. e.g. 'Mention our summer sale', 'Write for complete beginners', 'Keep a casual friendly tone'", 'wordvane' ); ?>"></textarea>
					</div>

					<button type="button" id="wv-generate-btn" class="button button-primary button-hero wv-generate-btn">
						✨ <?php esc_html_e( 'Generate Article', 'wordvane' ); ?>
					</button>
				</div>
			</div>

			<!-- Right Column: Output -->
			<div class="wv-generator-output" id="wv-generator-output-col" style="display:none;">
				<div class="wv-card">
					<div class="wv-generating-status" id="wv-generating-status">
						<span class="spinner is-active" style="float:none;"></span>
						<?php esc_html_e( 'Generating your article...', 'wordvane' ); ?>
					</div>
					<div id="wv-streaming-output" class="wv-streaming-output"></div>
				</div>

				<!-- Post Generation Panel -->
				<div id="wv-post-generation-panel" class="wv-post-generation-panel" style="display:none;">
					<div class="wv-pgp-row">
						<div class="wv-pgp-left">
							<div class="wv-card">
								<h3><?php esc_html_e( 'Article Details', 'wordvane' ); ?></h3>

								<div class="wv-field-group">
									<label class="wv-label" for="wv-post-title"><?php esc_html_e( 'Post Title', 'wordvane' ); ?></label>
									<input type="text" id="wv-post-title" class="large-text">
								</div>

								<div class="wv-field-group">
									<label class="wv-label" for="wv-meta-title">
										<?php esc_html_e( 'Meta Title', 'wordvane' ); ?>
										<?php echo wordvane_tooltip( 'meta_title' ); ?>
									</label>
									<input type="text" id="wv-meta-title" class="large-text" maxlength="80">
									<div class="wv-char-counter" id="wv-meta-title-counter">
										<span id="wv-meta-title-count">0</span> / 60
									</div>
								</div>

								<div class="wv-field-group">
									<label class="wv-label" for="wv-meta-description">
										<?php esc_html_e( 'Meta Description', 'wordvane' ); ?>
										<?php echo wordvane_tooltip( 'meta_description' ); ?>
									</label>
									<textarea id="wv-meta-description" class="large-text" rows="3" maxlength="200"></textarea>
									<div class="wv-char-counter" id="wv-meta-desc-counter">
										<span id="wv-meta-desc-count">0</span> / 155
									</div>
								</div>

								<div class="wv-field-group">
									<label class="wv-label" for="wv-slug"><?php esc_html_e( 'URL Slug', 'wordvane' ); ?></label>
									<input type="text" id="wv-slug" class="large-text">
								</div>

								<div class="wv-field-group">
									<label class="wv-label" for="wv-tags"><?php esc_html_e( 'Tags', 'wordvane' ); ?></label>
									<input type="text" id="wv-tags" class="large-text"
										placeholder="<?php esc_attr_e( 'Comma separated tags', 'wordvane' ); ?>">
								</div>

								<div class="wv-field-group">
									<label class="wv-label" for="wv-post-category"><?php esc_html_e( 'Category', 'wordvane' ); ?></label>
									<select id="wv-post-category">
										<option value=""><?php esc_html_e( '— Uncategorized —', 'wordvane' ); ?></option>
										<?php foreach ( $categories as $cat ) : ?>
											<option value="<?php echo esc_attr( $cat->term_id ); ?>"
												<?php selected( $settings['default_category'] ?? '', $cat->term_id ); ?>>
												<?php echo esc_html( $cat->name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="wv-field-group">
									<label class="wv-label"><?php esc_html_e( 'Post Status', 'wordvane' ); ?></label>
									<label class="wv-radio-label">
										<input type="radio" name="wv_publish_status" value="draft"
											<?php checked( $settings['post_status'] ?? 'draft', 'draft' ); ?>>
										<?php esc_html_e( 'Draft', 'wordvane' ); ?>
									</label>
									<label class="wv-radio-label">
										<input type="radio" name="wv_publish_status" value="publish"
											<?php checked( $settings['post_status'] ?? 'draft', 'publish' ); ?>>
										<?php esc_html_e( 'Publish Now', 'wordvane' ); ?>
									</label>
								</div>

								<div class="wv-field-group">
									<label class="wv-label" for="wv-post-type"><?php esc_html_e( 'Post Type', 'wordvane' ); ?></label>
									<select id="wv-post-type">
										<option value="post"><?php esc_html_e( 'Post', 'wordvane' ); ?></option>
										<?php if ( $is_pro ) : ?>
											<?php foreach ( $publisher_post_types as $pt ) :
												if ( 'post' === $pt ) continue;
												$pt_label = $pro_post_type_labels[ $pt ] ?? ucfirst( $pt );
											?>
											<option value="<?php echo esc_attr( $pt ); ?>"><?php echo esc_html( $pt_label ); ?></option>
											<?php endforeach; ?>
										<?php else : ?>
											<?php foreach ( $pro_post_type_labels as $pt => $pt_label ) : ?>
											<option value="<?php echo esc_attr( $pt ); ?>" disabled>
												<?php echo esc_html( $pt_label . ' — ' . __( 'Pro', 'wordvane' ) ); ?>
											</option>
											<?php endforeach; ?>
										<?php endif; ?>
									</select>
								</div>

								<input type="hidden" id="wv-post-id" value="0">
							</div>
						</div>

						<div class="wv-pgp-right">
							<div class="wv-card wv-seo-score-card">
								<h3><?php esc_html_e( 'SEO Score', 'wordvane' ); ?></h3>
								<div id="wv-seo-checklist" class="wv-seo-checklist"></div>
								<div class="wv-grade-wrap">
									<?php esc_html_e( 'Grade:', 'wordvane' ); ?>
									<span id="wv-grade-badge" class="wv-grade-badge">—</span>
								</div>
							</div>
						</div>
					</div>

					<div id="wv-publish-result" class="wv-publish-result" style="display:none;"></div>
					<div id="wv-publish-saving" class="wv-publish-saving" style="display:none;">
						<span class="spinner is-active" style="float:none;"></span>
						<?php esc_html_e( 'Saving...', 'wordvane' ); ?>
					</div>

					<div class="wv-pgp-actions">
						<button type="button" id="wv-save-draft" class="button button-secondary">
							💾 <?php esc_html_e( 'Save as Draft', 'wordvane' ); ?>
						</button>
						<button type="button" id="wv-publish-now" class="button button-primary">
							🚀 <?php esc_html_e( 'Publish Now', 'wordvane' ); ?>
						</button>
						<button type="button" id="wv-regenerate" class="button">
							🔄 <?php esc_html_e( 'Regenerate', 'wordvane' ); ?>
						</button>
						<button type="button" id="wv-copy-html" class="button">
							📋 <?php esc_html_e( 'Copy HTML', 'wordvane' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>

	</div><!-- .wv-lockable-content -->
</div>
