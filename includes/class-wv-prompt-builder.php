<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wordvane_Prompt_Builder {

	/**
	 * Builds the system prompt sent to the AI.
	 *
	 * None mode deliberately omits business_name, what_they_sell, and main_goal
	 * because those fields often contain brand names that would leak into the output.
	 * Only neutral context (industry, audience, tone, avoid-list) is passed.
	 *
	 * @param array  $settings             Business DNA option array.
	 * @param string $keyword              Primary keyword.
	 * @param string $secondary_keywords   Comma-separated secondary keywords.
	 * @param string $article_type         Content type slug.
	 * @param string $featured_product     'none' | 'soft' | '0' | '1' | '2'.
	 * @return string
	 */
	public static function build_system_prompt( array $settings, string $keyword, string $secondary_keywords, string $article_type, string $featured_product ): string {
		$business_name  = $settings['business_name'] ?? 'our business';
		$business_type  = $settings['business_type'] ?? 'business';
		$what_they_sell = $settings['what_they_sell'] ?? '';
		$ideal_customer = $settings['ideal_customer'] ?? '';
		$main_goal      = $settings['main_goal'] ?? '';
		$locations      = $settings['locations_served'] ?? '';
		$brand_voice    = $settings['brand_voice'] ?? 'Professional and helpful';
		$topics_avoid   = $settings['topics_to_avoid'] ?? '';
		$products       = $settings['products'] ?? [];

		$products_text = '';
		foreach ( $products as $p ) {
			if ( ! empty( $p['name'] ) ) {
				$products_text .= '- ' . $p['name'];
				if ( ! empty( $p['url'] ) ) {
					$products_text .= ' | URL: ' . $p['url'];
				}
				if ( ! empty( $p['description'] ) ) {
					$products_text .= ' | ' . $p['description'];
				}
				$products_text .= "\n";
			}
		}

		$is_spotlight = is_numeric( $featured_product ) && isset( $products[ (int) $featured_product ] );
		$is_none      = ! $is_spotlight && 'soft' !== $featured_product;

		// --- Persona, context block, products section ---
		if ( $is_none ) {
			// Omit business_name, what_they_sell, main_goal — all carry brand identifiers.
			// Also scrub the business name from every free-form field: users often embed the
			// brand name in fields like brand_voice ("Wordvane style") or ideal_customer
			// ("Wordvane users"), which would leak it into the context block.
			$scrub = static function ( string $text ) use ( $business_name ): string {
				if ( empty( $business_name ) || 'our business' === $business_name ) {
					return $text;
				}
				return str_ireplace( $business_name, '', $text );
			};

			$persona          = 'You are an expert content writer. You write purely informational articles with no brand affiliation.';
			$context_block    = 'Industry: ' . $scrub( $business_type ) . "\n"
				. 'Ideal reader: ' . $scrub( $ideal_customer ) . "\n"
				. 'Locations covered: ' . $scrub( $locations ) . "\n"
				. 'Tone: ' . $scrub( $brand_voice ) . "\n"
				. 'Topics to never mention: ' . $scrub( $topics_avoid );
			$products_section = '';
		} elseif ( 'soft' === $featured_product ) {
			// Full brand context, but no product list — prevents AI generating product CTAs.
			$persona          = "You are an expert content writer for {$business_name}.";
			$context_block    = "Business type: {$business_type}
What they offer: {$what_they_sell}
Ideal customer: {$ideal_customer}
Business goal: {$main_goal}
Locations served: {$locations}
Brand voice: {$brand_voice}
Topics to never mention: {$topics_avoid}";
			$products_section = '';
		} else {
			// Spotlight — full context and product list.
			$persona          = "You are an expert content writer for {$business_name}.";
			$context_block    = "Business type: {$business_type}
What they offer: {$what_they_sell}
Ideal customer: {$ideal_customer}
Business goal: {$main_goal}
Locations served: {$locations}
Brand voice: {$brand_voice}
Topics to never mention: {$topics_avoid}";
			$products_section = "Products to promote:\n{$products_text}\n";
		}

		// --- Step-4 product instruction ---
		if ( $is_spotlight ) {
			$fp                          = $products[ (int) $featured_product ];
			$fp_name                     = $fp['name'] ?? '';
			$fp_url                      = $fp['url'] ?? '';
			$product_mention_instruction = "4. Product spotlight: naturally reference {$fp_name}" .
				( $fp_url ? " with a link to {$fp_url}" : '' ) .
				". Weave it in organically — don't force it.";
		} elseif ( 'soft' === $featured_product ) {
			$product_mention_instruction = "4. Brand mention: include at most one brief, contextual sentence mentioning {$business_name} where directly relevant to the topic. Do not add a dedicated \"why choose\" section or a closing call-to-action. No product links.";
		} else {
			$product_mention_instruction = '4. [No promotional content] Skip this step entirely. The article must contain zero brand or product promotion. Write as an independent expert with no affiliation.';
		}

		// Hard constraint for none mode — naming the brand explicitly is far more effective
		// than a vague "no companies" rule, because models hallucinate familiar brands onto topics.
		$hard_constraint = $is_none
			? "ABSOLUTE RULE — THIS OVERRIDES EVERY OTHER INSTRUCTION:\n"
			. "You are a staff writer at an independent industry publication. You have no commercial relationships.\n"
			. "DO NOT name any company, product, service, or brand anywhere in this article.\n"
			. "This includes \"{$business_name}\" and any other product or vendor you may recognise from your training data as operating in this space.\n"
			. "Even if you 'know' which product matches this topic from your training — do not name it, reference it, or allude to it.\n"
			. "DO NOT write a 'Why Choose [anything]' section, a CTA, a 'Get Started' block, or any sign-up language.\n"
			. "DO NOT use 'we', 'our', or 'us' in a way that implies company affiliation.\n"
			. "Write about the CATEGORY, the CRITERIA, and the CONCEPTS — never about specific vendors.\n"
			. "Mentioning any brand name or writing any promotional section is a critical failure.\n\n"
			: '';

		if ( 'faq' === $article_type ) {
			$body_structure = 'FAQ: 5–7 H2 headings phrased as real customer questions, each answered in 1–2 paragraphs.';
		} elseif ( 'spotlight' === $article_type ) {
			$body_structure = 'Spotlight: problem → solution → key features (H3s) → social proof → next step.';
		} else {
			$body_structure = 'How-To: numbered steps as H2 headings, prerequisites first, 2–3 paragraphs each.';
		}

		return "{$hard_constraint}{$persona}

{$context_block}

{$products_section}Writing rules — follow these exactly:
- Write like a real human expert, not a content robot
- Short paragraphs — 2 to 3 sentences maximum
- Vary sentence length — mix short punchy sentences with longer ones. Some sentences should be very short.
- Use real-world examples that match {$business_type}
- Link to products naturally — never force it
- NEVER use these phrases: \"In today's digital landscape\", \"In conclusion\", \"It's worth noting\", \"Dive into\", \"Game-changer\", \"Leverage\", \"Needless to say\", \"At the end of the day\"
- Write for humans first, search engines second

Article structure — use this exact order:
1. Hook opening: start with a relatable problem, surprising stat, or bold statement. NEVER start with \"Are you looking for...\"
2. Context: why this topic matters (2 short paragraphs)
3. Main body: {$body_structure}
{$product_mention_instruction}
5. Closing: one short paragraph with a clear next step — no \"In conclusion\"
6. FAQ section: 5 questions a real customer would Google, with concise direct answers (for featured snippets)

SEO requirements:
- H1 must contain: {$keyword}
- First paragraph must contain: {$keyword}
- At least one H2 must be a variation of: {$keyword}
- Use secondary keywords ({$secondary_keywords}) naturally, 2-3 times each throughout the article
- Minimum length: 1200 words. Target 1400–1500 words.
- Every H2 section must have at least 3 paragraphs. Every paragraph must have at least 3 sentences.
- If you finish writing and the article is under 1200 words, you must add more subsections or expand existing ones before stopping.
- A short article is a failure. Err on the side of more depth, not less.

OUTPUT FORMAT:
- Use only these HTML tags: h1, h2, h3, p, ul, ol, li, strong, em, a
- You may wrap the article in a \`\`\`html block if you prefer — that is fine
- Do not include any JSON, metadata, or extra instructions at the end — metadata is handled separately";
	}

	/**
	 * Builds the user message sent alongside the system prompt.
	 *
	 * @param string $article_type
	 * @param string $keyword
	 * @param string $secondary_keywords
	 * @param array  $settings
	 * @param string $featured_product
	 * @param string $custom_instructions
	 * @return string
	 */
	public static function build_user_message( string $article_type, string $keyword, string $secondary_keywords, array $settings, string $featured_product, string $custom_instructions ): string {
		$products    = $settings['products'] ?? [];
		$type_labels = [
			'how-to'    => 'How-To Guide',
			'spotlight' => 'Product Spotlight',
			'faq'       => 'FAQ Post',
		];
		$type_label = $type_labels[ $article_type ] ?? 'How-To Guide';

		$business_name = $settings['business_name'] ?? 'our business';
		$is_spotlight  = is_numeric( $featured_product ) && isset( $products[ (int) $featured_product ] );

		if ( $is_spotlight ) {
			$fp           = $products[ (int) $featured_product ];
			$product_line = 'Product mode: Featured spotlight — ' . ( $fp['name'] ?? '' );
			if ( ! empty( $fp['url'] ) ) {
				$product_line .= ' (' . $fp['url'] . ')';
			}
		} elseif ( 'soft' === $featured_product ) {
			$product_line = "Product mode: Soft brand mention — one incidental sentence mentioning {$business_name} where relevant; no CTA, no dedicated section";
		} else {
			$product_line = 'Product mode: NONE — zero promotional content. No company names, no product names, no CTAs, no "Why Choose" sections. Independent expert voice only.';
		}

		return "Write a {$type_label} article.
Primary keyword: {$keyword}
Secondary keywords: {$secondary_keywords}
{$product_line}
Extra instructions: {$custom_instructions}

REMINDER: Minimum 1200 words — every H2 section needs at least 3 paragraphs. After the article, output a \`\`\`json block with meta_title, meta_description, slug, tags, and faq_schema. Do not skip it.";
	}
}
