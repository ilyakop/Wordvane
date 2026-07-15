<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template vars are local to this included file, not truly global.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die( esc_html__( 'Unauthorized', 'wordvane' ) );
}

$total_generated = 0;
for ( $m = 1; $m <= 12; $m++ ) {
	$total_generated += (int) get_option( 'wv_article_count_' . gmdate( 'Y' ) . '_' . gmdate( 'm', gmmktime( 0, 0, 0, $m, 1 ) ), 0 );
}
$total_generated += (int) get_option( 'wv_article_count_' . ( (int) gmdate( 'Y' ) - 1 ) . '_12', 0 );

$query_30 = new WP_Query( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'date_query'     => [ [ 'after' => '30 days ago' ] ],
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => [ [ 'key' => '_wv_meta_title', 'compare' => 'EXISTS' ] ],
] );
$published_30_days = $query_30->found_posts;
$avg_per_week      = round( $published_30_days / 4, 1 );

$query_month = new WP_Query( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'date_query'     => [ [ 'year' => (int) gmdate( 'Y' ), 'monthnum' => (int) gmdate( 'm' ) ] ],
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => [ [ 'key' => '_wv_meta_title', 'compare' => 'EXISTS' ] ],
] );
$published_this_month = $query_month->found_posts;

$checklist = get_user_meta( get_current_user_id(), 'wv_checklist', true );
$checklist = is_array( $checklist ) ? $checklist : [];

if ( $avg_per_week >= 4 ) {
	$consistency_rating   = 3;
	$consistency_feedback = __( '✓✓✓ Power publisher. Make sure quality stays high at this volume.', 'wordvane' );
} elseif ( $avg_per_week >= 2 ) {
	$consistency_rating   = 2;
	$consistency_feedback = __( '✓✓ Great pace. This is the sweet spot for most small business sites. Keep it up.', 'wordvane' );
} elseif ( $avg_per_week >= 1 ) {
	$consistency_rating   = 1;
	$consistency_feedback = __( '✓ Good start — you are in the game. Aim for 2 per week to accelerate growth.', 'wordvane' );
} else {
	$consistency_rating   = 0;
	$consistency_feedback = __( '⚠️ Your site has gone quiet. Google deprioritizes sites that stop publishing. Even 1 article per week makes a real difference.', 'wordvane' );
}

$checklist_items = [
	0 => __( 'Published my first article with Wordvane', 'wordvane' ),
	1 => __( 'Installed an SEO plugin (Yoast or Rank Math)', 'wordvane' ),
	2 => __( 'Filled in meta title and description for every article', 'wordvane' ),
	3 => __( 'Published at least 5 articles total', 'wordvane' ),
	4 => __( 'Used all 3 article types at least once', 'wordvane' ),
	5 => __( 'Maintained 2+ articles per week for a full month', 'wordvane' ),
	6 => __( 'Added internal links between my articles', 'wordvane' ),
];
$checklist_tooltips = [
	0 => __( 'Every journey starts with one article. The first step is always the hardest.', 'wordvane' ),
	1 => __( 'SEO plugins let Wordvane fill in meta titles and descriptions automatically.', 'wordvane' ),
	2 => __( 'Meta title and description are the first thing Google and readers see in search results.', 'wordvane' ),
	3 => __( 'Five articles gives Google enough content to understand what your site is about.', 'wordvane' ),
	4 => __( 'Different article types reach customers at different stages of their journey.', 'wordvane' ),
	5 => __( 'Consistency is the single biggest factor in SEO growth. Two per week for 3 months changes everything.', 'wordvane' ),
	6 => __( 'Internal links tell Google which pages are important and keep readers on your site longer.', 'wordvane' ),
];
$checklist_complete = count( $checklist );
$is_pro             = WV_Features::is_pro();
$upgrade_url        = WV_Features::get_upgrade_url();
?>
<div class="wrap wv-insights-page">
	<h1><?php esc_html_e( 'Your SEO Playbook', 'wordvane' ); ?></h1>
	<p class="wv-page-sub"><?php esc_html_e( 'Everything you need to know to grow your Google traffic — no experience required.', 'wordvane' ); ?></p>

	<!-- Section 1: Content Score -->
	<div class="wv-insights-section">
		<h2><?php esc_html_e( 'Your Content Score', 'wordvane' ); ?></h2>
		<div class="wv-score-cards">
			<div class="wv-score-card">
				<div class="wv-score-number"><?php echo esc_html( $total_generated ); ?></div>
				<div class="wv-score-label"><?php esc_html_e( 'Total articles generated', 'wordvane' ); ?></div>
			</div>
			<div class="wv-score-card">
				<div class="wv-score-number"><?php echo esc_html( $published_this_month ); ?></div>
				<div class="wv-score-label"><?php esc_html_e( 'Published this month', 'wordvane' ); ?></div>
			</div>
			<div class="wv-score-card">
				<div class="wv-score-number"><?php echo esc_html( $avg_per_week ); ?></div>
				<div class="wv-score-label"><?php esc_html_e( 'Avg per week (last 30 days)', 'wordvane' ); ?></div>
			</div>
		</div>
		<div class="wv-consistency-feedback <?php echo $consistency_rating > 0 ? 'wv-feedback-good' : 'wv-feedback-warn'; ?>">
			<?php echo esc_html( $consistency_feedback ); ?>
		</div>
	</div>

	<!-- Section 2: How SEO Works -->
	<div class="wv-insights-section">
		<h2><?php esc_html_e( 'How SEO Actually Works', 'wordvane' ); ?></h2>
		<div class="wv-timeline">
			<div class="wv-timeline-item">
				<div class="wv-timeline-badge">📅</div>
				<div class="wv-timeline-content">
					<strong><?php esc_html_e( 'Week 1-4', 'wordvane' ); ?></strong>
					<p><?php esc_html_e( 'Google finds and indexes your articles. They exist in the search engine but may not rank yet.', 'wordvane' ); ?></p>
				</div>
			</div>
			<div class="wv-timeline-item">
				<div class="wv-timeline-badge">📅</div>
				<div class="wv-timeline-content">
					<strong><?php esc_html_e( 'Month 2-3', 'wordvane' ); ?></strong>
					<p><?php esc_html_e( 'Articles start appearing on pages 3-5 for your keywords. Real people start finding your site.', 'wordvane' ); ?></p>
				</div>
			</div>
			<div class="wv-timeline-item">
				<div class="wv-timeline-badge">📅</div>
				<div class="wv-timeline-content">
					<strong><?php esc_html_e( 'Month 4-6', 'wordvane' ); ?></strong>
					<p><?php esc_html_e( 'Strong articles reach page 1. Traffic starts compounding.', 'wordvane' ); ?></p>
				</div>
			</div>
			<div class="wv-timeline-item">
				<div class="wv-timeline-badge">📅</div>
				<div class="wv-timeline-content">
					<strong><?php esc_html_e( 'Month 6+', 'wordvane' ); ?></strong>
					<p><?php esc_html_e( 'Each article keeps working permanently. Old articles bring traffic while you sleep.', 'wordvane' ); ?></p>
				</div>
			</div>
		</div>
		<p class="wv-insights-note">
			<?php esc_html_e( 'This is why consistency matters more than perfection. An article published today is still bringing traffic in 2 years. Start now.', 'wordvane' ); ?>
		</p>
	</div>

	<!-- Section 3: Article Type Guide -->
	<div class="wv-insights-section">
		<h2><?php esc_html_e( 'Article Type Guide', 'wordvane' ); ?></h2>
		<div class="wv-type-guide-cards">
			<div class="wv-type-guide-card">
				<div class="wv-type-guide-icon">📖</div>
				<h3><?php esc_html_e( 'How-To Guide', 'wordvane' ); ?></h3>
				<p><?php esc_html_e( 'Teaches your audience something useful. Attracts people in the research phase. Builds trust before they buy.', 'wordvane' ); ?></p>
				<p><strong><?php esc_html_e( 'Best for:', 'wordvane' ); ?></strong> <?php esc_html_e( 'any business with a learning curve.', 'wordvane' ); ?></p>
				<p class="wv-ranking-time">⏱ <?php esc_html_e( 'Ranking timeline: 6-10 weeks typically.', 'wordvane' ); ?></p>
			</div>
			<div class="wv-type-guide-card">
				<div class="wv-type-guide-icon">⭐</div>
				<h3><?php esc_html_e( 'Product Spotlight', 'wordvane' ); ?></h3>
				<p><?php esc_html_e( 'Showcases one product or service in depth. Attracts people ready to buy. Converts readers into customers.', 'wordvane' ); ?></p>
				<p><strong><?php esc_html_e( 'Best for:', 'wordvane' ); ?></strong> <?php esc_html_e( 'eCommerce, services, SaaS.', 'wordvane' ); ?></p>
				<p class="wv-ranking-time">⏱ <?php esc_html_e( 'Ranking timeline: 8-14 weeks typically.', 'wordvane' ); ?></p>
			</div>
			<div class="wv-type-guide-card">
				<div class="wv-type-guide-icon">❓</div>
				<h3><?php esc_html_e( 'FAQ Post', 'wordvane' ); ?></h3>
				<p><?php esc_html_e( "Answers specific questions your customers Google. Often gets pulled into Google's featured snippet box — the answer that appears before search results.", 'wordvane' ); ?></p>
				<p><strong><?php esc_html_e( 'Best for:', 'wordvane' ); ?></strong> <?php esc_html_e( 'any business. Always worth doing.', 'wordvane' ); ?></p>
				<p class="wv-ranking-time">⏱ <?php esc_html_e( 'Ranking timeline: 4-8 weeks typically.', 'wordvane' ); ?></p>
			</div>
		</div>
		<p class="wv-insights-tip">
			💡 <?php esc_html_e( 'Pro tip: Mix your article types. How-To articles bring traffic. Product Spotlights convert visitors into buyers. FAQ posts win featured snippets.', 'wordvane' ); ?>
		</p>
	</div>

	<!-- Section 4: SEO Tips -->
	<div class="wv-insights-section">
		<h2><?php esc_html_e( 'SEO Tips', 'wordvane' ); ?></h2>
		<div class="wv-tips-grid">
			<div class="wv-tip-card">
				<h3><?php esc_html_e( 'Why specific keywords beat broad ones', 'wordvane' ); ?></h3>
				<p><?php esc_html_e( 'The keyword "shoes" has over 1 billion competing pages. The keyword "handmade leather shoes for wide feet" has thousands. Specific keywords are easier to rank for AND attract buyers, not just browsers.', 'wordvane' ); ?></p>
				<p><em><?php esc_html_e( 'Rule of thumb: if you can buy it on Amazon in one click, the keyword is too broad.', 'wordvane' ); ?></em></p>
			</div>
			<div class="wv-tip-card">
				<h3><?php esc_html_e( 'The compounding effect', 'wordvane' ); ?></h3>
				<p><?php esc_html_e( 'Month 1: 8 articles → ~50 visitors/month', 'wordvane' ); ?><br>
				<?php esc_html_e( 'Month 3: 24 articles → ~300 visitors/month', 'wordvane' ); ?><br>
				<?php esc_html_e( 'Month 6: 48 articles → ~1,200 visitors/month', 'wordvane' ); ?></p>
				<p><?php esc_html_e( 'Each article is a permanent door Google can send people through. The doors never close.', 'wordvane' ); ?></p>
			</div>
			<div class="wv-tip-card">
				<h3><?php esc_html_e( 'Why comparison articles convert best', 'wordvane' ); ?></h3>
				<p><?php esc_html_e( 'People searching "X vs Y" or "best X for Y" are ready to buy — they have done their research and just need a final push. These articles often convert at 3-5x the rate of general articles.', 'wordvane' ); ?></p>
			</div>
			<div class="wv-tip-card">
				<h3><?php esc_html_e( 'What a featured snippet is and how to get one', 'wordvane' ); ?></h3>
				<p><?php esc_html_e( 'The featured snippet is the answer box Google shows at the very top of search results — above all other links. FAQ posts and How-To articles with clear, direct answers get selected most often.', 'wordvane' ); ?></p>
				<p><?php esc_html_e( 'Write short, direct answers (40-60 words) for every FAQ question.', 'wordvane' ); ?></p>
			</div>
			<div class="wv-tip-card">
				<h3><?php esc_html_e( 'Internal linking explained', 'wordvane' ); ?></h3>
				<p><?php esc_html_e( 'Linking from one of your articles to another tells Google both pages are related and important. It also keeps readers on your site longer.', 'wordvane' ); ?></p>
				<p><?php esc_html_e( 'Simple rule: whenever you publish a new article, link to it from 2-3 older articles on related topics.', 'wordvane' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Section 5: Quick Wins Checklist -->
	<div class="wv-insights-section">
		<h2><?php esc_html_e( 'Quick Wins Checklist', 'wordvane' ); ?></h2>
		<div class="wv-checklist-progress-wrap">
			<div class="wv-checklist-progress-bar">
				<div class="wv-checklist-fill" style="width:<?php echo esc_attr( round( ( $checklist_complete / count( $checklist_items ) ) * 100 ) ); ?>%"></div>
			</div>
			<span>
				<?php
				/* translators: 1: completed, 2: total */
				printf( esc_html__( '%1$d of %2$d quick wins complete 🎯', 'wordvane' ), absint( $checklist_complete ), absint( count( $checklist_items ) ) );
				?>
			</span>
		</div>
		<ul class="wv-checklist" id="wv-quick-wins">
			<?php foreach ( $checklist_items as $idx => $item_label ) :
				$checked = in_array( $idx, $checklist, true );
				?>
				<li class="wv-checklist-item<?php echo $checked ? ' checked' : ''; ?>">
					<label>
						<input type="checkbox" class="wv-checklist-cb" data-index="<?php echo esc_attr( $idx ); ?>"
							<?php checked( $checked ); ?>>
						<?php echo esc_html( $item_label ); ?>
					</label>
					<span class="wv-tooltip-wrap">
						<span class="wv-tooltip-icon" tabindex="0">?</span>
						<span class="wv-tooltip-content" role="tooltip">
							<p><?php echo esc_html( $checklist_tooltips[ $idx ] ); ?></p>
						</span>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<?php
	/**
	 * Fires at the end of the Insights dashboard, after all free sections.
	 *
	 * Pro uses this to register content refresh suggestions and agency reporting cards.
	 *
	 * @since 1.0.0
	 * @hook  wordvane_dashboard_widgets
	 */
	do_action( 'wordvane_dashboard_widgets' );
	?>
</div>
