<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wv_get_tooltips() {
	return [
		'business_niche' => [
			'label'       => 'What is your business niche?',
			'explanation' => 'Describe what your business does in one sentence. This shapes every article the AI writes — the more specific, the better.',
			'example'     => 'Florist: "Wedding and event floral design in Chicago" | SaaS: "Project management software for remote teams" | Shop: "Handmade leather goods for men"',
			'pro_tip'     => 'Avoid vague descriptions like "marketing company". Specific niches produce more accurate, useful articles.',
		],
		'target_audience' => [
			'label'       => 'Who are your ideal customers?',
			'explanation' => 'Describe the person most likely to buy from you. The AI uses this to match the tone and vocabulary of your articles to your actual readers.',
			'example'     => 'Women 30-50 looking for eco-friendly skincare | Small business owners with no IT department | First-time homebuyers in suburban areas',
			'pro_tip'     => 'Include their biggest problem or goal. "Busy moms who want healthy meals but have no time to cook" is better than just "moms".',
		],
		'products' => [
			'label'       => 'What are your products or services?',
			'explanation' => 'Add up to 3 products or services. Every article will naturally mention and link to the most relevant one — turning your blog into a sales tool.',
			'example'     => 'Product: "Wedding Bouquet Package" → URL: yoursite.com/bouquets → Description: "Custom bridal bouquets starting at $150"',
			'pro_tip'     => 'Include the page URL so the AI can add real internal links inside the article.',
		],
		'target_keyword' => [
			'label'       => 'What is a target keyword?',
			'explanation' => 'The exact phrase you want Google to rank your article for. Think of it as the question your customer types into Google.',
			'example'     => 'Instead of "flowers" try "affordable wedding flowers delivered Chicago" — specific keywords are easier to rank for and attract buyers, not just browsers.',
			'pro_tip'     => 'Aim for 3-5 word phrases. One or two word keywords have millions of competing pages.',
		],
		'secondary_keywords' => [
			'label'       => 'What are secondary keywords?',
			'explanation' => 'Related phrases that get sprinkled through the article to help Google understand your topic more broadly.',
			'example'     => 'Main keyword: "wedding florist Chicago" → Secondary: "bridal bouquets, wedding flower costs, cheap wedding flowers"',
			'pro_tip'     => 'Leave this blank if you are unsure — the AI will choose related terms automatically.',
		],
		'article_type' => [
			'label'       => 'What type of article should I write?',
			'explanation' => 'Different article types serve different purposes. How-To articles teach, Product Spotlights sell, FAQ posts answer specific questions and appear in Google snippets.',
			'example'     => 'How-To: "How to care for wedding flowers before the big day" | Spotlight: "Why brides choose our bouquet package" | FAQ: "Wedding flower questions answered"',
			'pro_tip'     => 'Mix your types. How-To articles bring traffic. Product Spotlights convert visitors into buyers.',
		],
		'featured_product' => [
			'label'       => 'Which product should this article promote?',
			'explanation' => 'The article will naturally mention and link to this product where it makes sense. It is never forced — the AI weaves it in organically.',
			'example'     => 'Writing about "how to choose wedding flowers"? Feature your bouquet package. Writing general tips? Choose "No specific product".',
			'pro_tip'     => 'Match the product to the article topic for the most natural result.',
		],
		'article_limit' => [
			'label'       => 'Why is there a 5 article limit?',
			'explanation' => 'The free version of Wordvane includes 5 articles per month. This resets on the 1st of every month.',
			'example'     => 'Used 4 of 5 this month? Your limit resets automatically on the 1st.',
			'pro_tip'     => 'Upgrade to Wordvane Pro for unlimited article generation, bulk scheduling, and more article types.',
		],
		'meta_title' => [
			'label'       => 'What is a meta title?',
			'explanation' => 'The title Google shows in search results. It should include your target keyword and be under 60 characters — otherwise Google cuts it off.',
			'example'     => 'Good: "Affordable Wedding Flowers Chicago | Rose Garden Floral" (58 chars) | Bad: "Welcome to our website" (no keyword)',
			'pro_tip'     => 'Put your keyword near the start of the title — Google gives more weight to words that appear earlier.',
		],
		'meta_description' => [
			'label'       => 'What is a meta description?',
			'explanation' => 'The short summary Google shows under your title in search results. It does not directly affect ranking but it does affect whether people click your link.',
			'example'     => 'Good: "Looking for affordable wedding flowers in Chicago? We create custom bouquets from $150. Free consultation." | Bad: "Read our latest blog post."',
			'pro_tip'     => 'Keep it under 155 characters and include a clear reason to click — a benefit, offer, or question.',
		],
		'seo_plugin' => [
			'label'       => 'Do I need an SEO plugin?',
			'explanation' => 'SEO plugins like Yoast and Rank Math let you set meta titles and descriptions for every page. Wordvane fills these in automatically when you generate an article.',
			'example'     => 'If you use Yoast or Rank Math, select it here and your meta fields will be filled automatically every time you publish.',
			'pro_tip'     => 'Not using an SEO plugin yet? Install Rank Math — it is free and the setup wizard takes 5 minutes. It is worth it.',
		],
		'posting_frequency' => [
			'label'       => 'How often should I publish?',
			'explanation' => 'Google rewards consistent publishing. A site that posts twice a week for 3 months grows much faster than one that posts 20 articles at once then stops.',
			'example'     => 'New site: 2 articles/week | Established blog: 3-4 articles/week | eCommerce: 2 product-focused articles/week minimum',
			'pro_tip'     => 'Consistency beats volume every time. 2 articles every week for 3 months beats 30 articles dumped on day one.',
		],
		'brand_voice' => [
			'label'       => 'What is brand voice?',
			'explanation' => 'How your business sounds when it writes. Casual or formal? Fun or serious? Technical or simple? This shapes every article.',
			'example'     => 'Casual: "Hey, here is the deal with wedding flowers" | Formal: "This guide outlines key considerations for floral arrangements"',
			'pro_tip'     => 'Read one of your best pieces of existing content and describe how it sounds. That is your brand voice.',
		],
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
