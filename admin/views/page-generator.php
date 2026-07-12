<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die( esc_html__( 'Unauthorized', 'wordvane' ) );
}

global $wp_version;
$settings         = get_option( 'wv_settings', [] );
$products         = $settings['products'] ?? [];
$usage            = WV_Limits::get_usage();
$limit            = WV_Limits::get_limit();
$limit_reached    = WV_Limits::is_limit_reached();
$percentage       = WV_Limits::get_percentage();
$days_until_reset = WV_Limits::get_days_until_reset();
$reset_date       = WV_Limits::get_reset_date();
$show_welcome     = isset( $_GET['wv_welcome'] ) && '1' === $_GET['wv_welcome'];
$categories       = get_categories( [ 'hide_empty' => false ] );

$wp_version_ok  = version_compare( $wp_version, '7.0', '>=' );
$ai_provider_ok = function_exists( 'wp_ai_client_prompt' );
$is_locked      = ! $wp_version_ok || ! $ai_provider_ok;

$bar_color = 'wv-bar-green';
if ( $usage >= 4 ) {
	$bar_color = 'wv-bar-orange';
}
if ( $limit_reached ) {
	$bar_color = 'wv-bar-red';
}
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
					/* translators: 1: used count, 2: limit */
					printf( esc_html__( 'Articles this month: %1$d of %2$d used', 'wordvane' ), $usage, $limit );
				?></strong>
				<?php echo wv_tooltip( 'article_limit' ); ?>
			</div>
			<div class="wv-usage-right">
				<div class="wv-progress-bar-wrap">
					<div class="wv-progress-bar <?php echo esc_attr( $bar_color ); ?>" style="width:<?php echo esc_attr( $percentage ); ?>%"></div>
				</div>
				<span class="wv-reset-note">
					<?php
					if ( $limit_reached ) {
						/* translators: %s: reset date */
						printf( esc_html__( 'Resets on %s', 'wordvane' ), esc_html( $reset_date ) );
					} else {
						/* translators: %d: number of days */
						printf( esc_html__( 'Resets in %d days', 'wordvane' ), $days_until_reset );
					}
					?>
				</span>
				<a href="https://topdevs.net/wordvane-pro" target="_blank" rel="noopener noreferrer" class="wv-upgrade-link">
					<?php esc_html_e( 'Upgrade for unlimited', 'wordvane' ); ?>
				</a>
			</div>
		</div>

		<?php if ( $limit_reached ) : ?>
		<div class="wv-limit-reached-box">
			<h3>🔒 <?php esc_html_e( 'You have used all 5 free articles this month.', 'wordvane' ); ?></h3>
			<p><?php
				/* translators: %s: reset date */
				printf( esc_html__( 'Your limit resets on %s.', 'wordvane' ), esc_html( $reset_date ) );
			?></p>
			<p><?php esc_html_e( 'Need more? Wordvane Pro gives you:', 'wordvane' ); ?></p>
			<ul>
				<li>✓ <?php esc_html_e( 'Unlimited article generation', 'wordvane' ); ?></li>
				<li>✓ <?php esc_html_e( 'Bulk scheduling (set and forget)', 'wordvane' ); ?></li>
				<li>✓ <?php esc_html_e( 'All 7 article types', 'wordvane' ); ?></li>
				<li>✓ <?php esc_html_e( 'Keyword suggester', 'wordvane' ); ?></li>
			</ul>
			<a href="https://topdevs.net/wordvane-pro" target="_blank" rel="noopener noreferrer" class="button button-primary wv-upgrade-btn">
				<?php esc_html_e( 'Get Wordvane Pro →', 'wordvane' ); ?>
			</a>
		</div>
		<?php endif; ?>

		<div id="wv-generator-layout" class="wv-generator-layout">
			<!-- Input Form -->
			<div class="wv-generator-form" id="wv-generator-form-col">
				<div class="wv-card">
					<div class="wv-field-group">
						<label class="wv-label" for="wv-keyword">
							<?php esc_html_e( 'Target Keyword', 'wordvane' ); ?> <span class="required">*</span>
							<?php echo wv_tooltip( 'target_keyword' ); ?>
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
							<?php echo wv_tooltip( 'secondary_keywords' ); ?>
						</label>
						<input type="text" id="wv-secondary-keywords" class="large-text"
							placeholder="<?php esc_attr_e( 'e.g. bridal bouquets, wedding flower costs (comma separated)', 'wordvane' ); ?>">
					</div>

					<div class="wv-field-group">
						<label class="wv-label">
							<?php esc_html_e( 'Article Type', 'wordvane' ); ?> <span class="required">*</span>
							<?php echo wv_tooltip( 'article_type' ); ?>
						</label>
						<div class="wv-article-type-cards">
							<div class="wv-article-type-card selected" data-type="how-to">
								<div class="wv-type-icon">📖</div>
								<div class="wv-type-name"><?php esc_html_e( 'How-To Guide', 'wordvane' ); ?></div>
								<div class="wv-type-desc"><?php esc_html_e( 'Step-by-step tutorial. Best for teaching something your audience wants to learn.', 'wordvane' ); ?></div>
							</div>
							<div class="wv-article-type-card" data-type="spotlight">
								<div class="wv-type-icon">⭐</div>
								<div class="wv-type-name"><?php esc_html_e( 'Spotlight', 'wordvane' ); ?></div>
								<div class="wv-type-desc"><?php esc_html_e( 'Showcase one product or service in depth. Great for converting browsers to buyers.', 'wordvane' ); ?></div>
							</div>
							<div class="wv-article-type-card" data-type="faq">
								<div class="wv-type-icon">❓</div>
								<div class="wv-type-name"><?php esc_html_e( 'FAQ Post', 'wordvane' ); ?></div>
								<div class="wv-type-desc"><?php esc_html_e( '5 questions your customers always ask. Great for Google featured snippets.', 'wordvane' ); ?></div>
							</div>
						</div>
					</div>

					<div class="wv-field-group">
						<label class="wv-label" for="wv-featured-product">
							<?php esc_html_e( 'Featured Product', 'wordvane' ); ?>
							<?php echo wv_tooltip( 'featured_product' ); ?>
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

					<?php if ( ! $limit_reached ) : ?>
					<button type="button" id="wv-generate-btn" class="button button-primary button-hero wv-generate-btn">
						✨ <?php esc_html_e( 'Generate Article', 'wordvane' ); ?>
					</button>
					<?php else : ?>
					<button type="button" class="button button-primary button-hero wv-generate-btn" disabled>
						🔒 <?php esc_html_e( 'Monthly limit reached', 'wordvane' ); ?>
					</button>
					<?php endif; ?>
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
										<?php echo wv_tooltip( 'meta_title' ); ?>
									</label>
									<input type="text" id="wv-meta-title" class="large-text" maxlength="80">
									<div class="wv-char-counter" id="wv-meta-title-counter">
										<span id="wv-meta-title-count">0</span> / 60
									</div>
								</div>

								<div class="wv-field-group">
									<label class="wv-label" for="wv-meta-description">
										<?php esc_html_e( 'Meta Description', 'wordvane' ); ?>
										<?php echo wv_tooltip( 'meta_description' ); ?>
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
						<span class="wv-muted-note"><?php esc_html_e( 'Regenerating uses 1 of your monthly articles', 'wordvane' ); ?></span>
					</div>
				</div>
			</div>
		</div>

	</div><!-- .wv-lockable-content -->
</div>
