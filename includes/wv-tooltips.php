<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- all functions are prefixed wv_ which is this plugin's registered prefix.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wv_get_tooltips() {
	return [
		'business_niche' => [
			'label'       => __( 'What is your business niche?', 'wordvane' ),
			'explanation' => __( 'Describe what your business does in one sentence. This shapes every article the AI writes — the more specific, the better.', 'wordvane' ),
			'example'     => __( 'Florist: "Wedding and event floral design in Chicago" | SaaS: "Project management software for remote teams" | Shop: "Handmade leather goods for men"', 'wordvane' ),
			'pro_tip'     => __( 'Avoid vague descriptions like "marketing company". Specific niches produce more accurate, useful articles.', 'wordvane' ),
		],
		'target_audience' => [
			'label'       => __( 'Who are your ideal customers?', 'wordvane' ),
			'explanation' => __( 'Describe the person most likely to buy from you. The AI uses this to match the tone and vocabulary of your articles to your actual readers.', 'wordvane' ),
			'example'     => __( 'Women 30-50 looking for eco-friendly skincare | Small business owners with no IT department | First-time homebuyers in suburban areas', 'wordvane' ),
			'pro_tip'     => __( 'Include their biggest problem or goal. "Busy moms who want healthy meals but have no time to cook" is better than just "moms".', 'wordvane' ),
		],
		'products' => [
			'label'       => __( 'What are your products or services?', 'wordvane' ),
			'explanation' => __( 'Add up to 3 products or services. Every article will naturally mention and link to the most relevant one — turning your blog into a sales tool.', 'wordvane' ),
			'example'     => __( 'Product: "Wedding Bouquet Package" → URL: yoursite.com/bouquets → Description: "Custom bridal bouquets starting at $150"', 'wordvane' ),
			'pro_tip'     => __( 'Include the page URL so the AI can add real internal links inside the article.', 'wordvane' ),
		],
		'target_keyword' => [
			'label'       => __( 'What is a target keyword?', 'wordvane' ),
			'explanation' => __( 'The exact phrase you want Google to rank your article for. Think of it as the question your customer types into Google.', 'wordvane' ),
			'example'     => __( 'Instead of "flowers" try "affordable wedding flowers delivered Chicago" — specific keywords are easier to rank for and attract buyers, not just browsers.', 'wordvane' ),
			'pro_tip'     => __( 'Aim for 3-5 word phrases. One or two word keywords have millions of competing pages.', 'wordvane' ),
		],
		'secondary_keywords' => [
			'label'       => __( 'What are secondary keywords?', 'wordvane' ),
			'explanation' => __( 'Related phrases that get sprinkled through the article to help Google understand your topic more broadly.', 'wordvane' ),
			'example'     => __( 'Main keyword: "wedding florist Chicago" → Secondary: "bridal bouquets, wedding flower costs, cheap wedding flowers"', 'wordvane' ),
			'pro_tip'     => __( 'Leave this blank if you are unsure — the AI will choose related terms automatically.', 'wordvane' ),
		],
		'article_type' => [
			'label'       => __( 'What type of article should I write?', 'wordvane' ),
			'explanation' => __( 'Different article types serve different purposes. How-To articles teach, Product Spotlights sell, FAQ posts answer specific questions and appear in Google snippets.', 'wordvane' ),
			'example'     => __( 'How-To: "How to care for wedding flowers before the big day" | Spotlight: "Why brides choose our bouquet package" | FAQ: "Wedding flower questions answered"', 'wordvane' ),
			'pro_tip'     => __( 'Mix your types. How-To articles bring traffic. Product Spotlights convert visitors into buyers.', 'wordvane' ),
		],
		'featured_product' => [
			'label'       => __( 'Which product should this article promote?', 'wordvane' ),
			'explanation' => __( 'The article will naturally mention and link to this product where it makes sense. It is never forced — the AI weaves it in organically.', 'wordvane' ),
			'example'     => __( 'Writing about "how to choose wedding flowers"? Feature your bouquet package. Writing general tips? Choose "No specific product".', 'wordvane' ),
			'pro_tip'     => __( 'Match the product to the article topic for the most natural result.', 'wordvane' ),
		],
		'article_limit' => [
			'label'       => __( 'Why is there a 5 article limit?', 'wordvane' ),
			'explanation' => __( 'The free version of Wordvane includes 5 articles per month. This resets on the 1st of every month.', 'wordvane' ),
			'example'     => __( 'Used 4 of 5 this month? Your limit resets automatically on the 1st.', 'wordvane' ),
			'pro_tip'     => __( 'Upgrade to Wordvane Pro for unlimited article generation, bulk scheduling, and more article types.', 'wordvane' ),
		],
		'meta_title' => [
			'label'       => __( 'What is a meta title?', 'wordvane' ),
			'explanation' => __( 'The title Google shows in search results. It should include your target keyword and be under 60 characters — otherwise Google cuts it off.', 'wordvane' ),
			'example'     => __( 'Good: "Affordable Wedding Flowers Chicago | Rose Garden Floral" (58 chars) | Bad: "Welcome to our website" (no keyword)', 'wordvane' ),
			'pro_tip'     => __( 'Put your keyword near the start of the title — Google gives more weight to words that appear earlier.', 'wordvane' ),
		],
		'meta_description' => [
			'label'       => __( 'What is a meta description?', 'wordvane' ),
			'explanation' => __( 'The short summary Google shows under your title in search results. It does not directly affect ranking but it does affect whether people click your link.', 'wordvane' ),
			'example'     => __( 'Good: "Looking for affordable wedding flowers in Chicago? We create custom bouquets from $150. Free consultation." | Bad: "Read our latest blog post."', 'wordvane' ),
			'pro_tip'     => __( 'Keep it under 155 characters and include a clear reason to click — a benefit, offer, or question.', 'wordvane' ),
		],
		'seo_plugin' => [
			'label'       => __( 'Do I need an SEO plugin?', 'wordvane' ),
			'explanation' => __( 'SEO plugins like Yoast and Rank Math let you set meta titles and descriptions for every page. Wordvane fills these in automatically when you generate an article.', 'wordvane' ),
			'example'     => __( 'If you use Yoast or Rank Math, select it here and your meta fields will be filled automatically every time you publish.', 'wordvane' ),
			'pro_tip'     => __( 'Not using an SEO plugin yet? Install Rank Math — it is free and the setup wizard takes 5 minutes. It is worth it.', 'wordvane' ),
		],
		'posting_frequency' => [
			'label'       => __( 'How often should I publish?', 'wordvane' ),
			'explanation' => __( 'Google rewards consistent publishing. A site that posts twice a week for 3 months grows much faster than one that posts 20 articles at once then stops.', 'wordvane' ),
			'example'     => __( 'New site: 2 articles/week | Established blog: 3-4 articles/week | eCommerce: 2 product-focused articles/week minimum', 'wordvane' ),
			'pro_tip'     => __( 'Consistency beats volume every time. 2 articles every week for 3 months beats 30 articles dumped on day one.', 'wordvane' ),
		],
		'brand_voice' => [
			'label'       => __( 'What is brand voice?', 'wordvane' ),
			'explanation' => __( 'How your business sounds when it writes. Casual or formal? Fun or serious? Technical or simple? This shapes every article.', 'wordvane' ),
			'example'     => __( 'Casual: "Hey, here is the deal with wedding flowers" | Formal: "This guide outlines key considerations for floral arrangements"', 'wordvane' ),
			'pro_tip'     => __( 'Read one of your best pieces of existing content and describe how it sounds. That is your brand voice.', 'wordvane' ),
		],
	];
}

/**
 * Returns the shared business-type options used in the wizard and settings page.
 *
 * @return array[] Keys are slugs; values have 'icon' and 'label'.
 */
function wv_get_business_types() {
	return [
		'ecommerce'    => [ 'icon' => '🛍️', 'label' => __( 'eCommerce Store', 'wordvane' ) ],
		'blog'         => [ 'icon' => '📝', 'label' => __( 'Blog / Content Site', 'wordvane' ) ],
		'local'        => [ 'icon' => '🏪', 'label' => __( 'Local Business', 'wordvane' ) ],
		'saas'         => [ 'icon' => '💻', 'label' => __( 'SaaS / Software', 'wordvane' ) ],
		'professional' => [ 'icon' => '👔', 'label' => __( 'Professional Services', 'wordvane' ) ],
		'other'        => [ 'icon' => '📦', 'label' => __( 'Other', 'wordvane' ) ],
	];
}

/**
 * Returns the shared main-goal options used in the wizard and settings page.
 *
 * @return array Keys are values; values are translated labels.
 */
function wv_get_main_goals() {
	return [
		'sell'      => __( 'Sell products or services', 'wordvane' ),
		'leads'     => __( 'Generate leads and inquiries', 'wordvane' ),
		'blog'      => __( 'Grow a blog audience', 'wordvane' ),
		'awareness' => __( 'Build brand awareness', 'wordvane' ),
	];
}

/**
 * Returns the shared SEO-plugin options used on the settings page.
 *
 * @return array Keys are option values; values are translated labels.
 */
function wv_get_seo_plugin_options() {
	return [
		'none'     => __( 'None', 'wordvane' ),
		'yoast'    => __( 'Yoast SEO', 'wordvane' ),
		'rankmath' => __( 'Rank Math', 'wordvane' ),
		'aioseo'   => __( 'All in One SEO', 'wordvane' ),
	];
}

function wv_tooltip( $key ) {
	$tooltips = wv_get_tooltips();
	if ( ! isset( $tooltips[ $key ] ) ) {
		return '';
	}
	$t = $tooltips[ $key ];
	return sprintf(
		'<span class="wv-tooltip-wrap">
			<span class="wv-tooltip-icon" tabindex="0" role="button" aria-label="%s">?</span>
			<span class="wv-tooltip-content" role="tooltip">
				<strong>%s</strong>
				<p>%s</p>
				<p class="wv-tip-example"><em>Example: %s</em></p>
				<p class="wv-tip-pro">&#128161; %s</p>
			</span>
		</span>',
		esc_attr( $t['label'] ),
		esc_html( $t['label'] ),
		esc_html( $t['explanation'] ),
		esc_html( $t['example'] ),
		esc_html( $t['pro_tip'] )
	);
}
