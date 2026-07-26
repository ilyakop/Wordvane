<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests that Wordvane_Prompt_Builder produces correct prompts for all three
 * Featured Product modes, and that "No product mention" fully suppresses brand
 * context — including names embedded in what_they_sell or product descriptions.
 *
 * Run: vendor/bin/phpunit
 */
class PromptBuilderTest extends TestCase {

	/** Business DNA with a distinctive brand name unlikely to appear by coincidence. */
	private array $dna;

	protected function setUp(): void {
		$this->dna = [
			'business_name'   => 'GlacierBrew Co',
			'business_type'   => 'craft brewery',
			'what_they_sell'  => 'GlacierBrew Co cold-process ales and lagers, brewed in small batches',
			'ideal_customer'  => 'craft beer enthusiasts aged 25–45',
			'main_goal'       => 'Sell more GlacierBrew Co subscription boxes',
			'locations_served' => 'Pacific Northwest',
			'brand_voice'     => 'Casual and knowledgeable',
			'topics_to_avoid' => 'mass-market beer brands',
			'products'        => [
				[
					'name'        => 'GlacierBrew Co IPA Pack',
					'url'         => 'https://glacierbrew.example/ipa',
					'description' => 'Six-bottle mixed IPA selection',
				],
				[
					'name'        => 'GlacierBrew Co Lager Bundle',
					'url'         => 'https://glacierbrew.example/lager',
					'description' => 'Eight-pack seasonal lagers',
				],
			],
		];
	}

	// -------------------------------------------------------------------------
	// None mode
	// -------------------------------------------------------------------------

	public function test_none_system_prompt_excludes_business_name(): void {
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft beer fermentation', '', 'how-to', 'none'
		);

		$this->assertStringNotContainsStringIgnoringCase(
			'GlacierBrew',
			$prompt,
			'System prompt in none-mode must not contain the business name'
		);
	}

	public function test_none_system_prompt_excludes_what_they_sell(): void {
		// what_they_sell contains "GlacierBrew Co" — regression guard for the Fieldstack AI bug.
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft beer fermentation', '', 'how-to', 'none'
		);

		$this->assertStringNotContainsStringIgnoringCase(
			'GlacierBrew',
			$prompt,
			'Brand name embedded in what_they_sell must not leak into none-mode prompt'
		);
	}

	public function test_none_system_prompt_excludes_product_names(): void {
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft beer fermentation', '', 'how-to', 'none'
		);

		$this->assertStringNotContainsStringIgnoringCase(
			'GlacierBrew Co IPA Pack',
			$prompt,
			'Product names must not appear in none-mode system prompt'
		);
		$this->assertStringNotContainsStringIgnoringCase(
			'GlacierBrew Co Lager Bundle',
			$prompt,
			'Product names must not appear in none-mode system prompt'
		);
	}

	public function test_none_user_message_excludes_business_name(): void {
		$msg = Wordvane_Prompt_Builder::build_user_message(
			'how-to', 'craft beer fermentation', '', $this->dna, 'none', ''
		);

		$this->assertStringNotContainsStringIgnoringCase(
			'GlacierBrew',
			$msg,
			'User message in none-mode must not contain the business name'
		);
	}

	public function test_none_system_prompt_contains_hard_constraint(): void {
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft beer fermentation', '', 'how-to', 'none'
		);

		$this->assertStringContainsString(
			'ABSOLUTE RULE',
			$prompt,
			'None-mode prompt must open with the hard no-promotion constraint'
		);
	}

	// -------------------------------------------------------------------------
	// Soft mode
	// -------------------------------------------------------------------------

	public function test_soft_system_prompt_includes_business_name(): void {
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft beer fermentation', '', 'how-to', 'soft'
		);

		$this->assertStringContainsStringIgnoringCase(
			'GlacierBrew Co',
			$prompt,
			'Soft-mode system prompt must include the business name'
		);
	}

	public function test_soft_system_prompt_excludes_product_list(): void {
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft beer fermentation', '', 'how-to', 'soft'
		);

		$this->assertStringNotContainsStringIgnoringCase(
			'GlacierBrew Co IPA Pack',
			$prompt,
			'Soft-mode must not pass the product list — one brand mention only, no product CTAs'
		);
	}

	// -------------------------------------------------------------------------
	// Spotlight mode (product index 0)
	// -------------------------------------------------------------------------

	public function test_spotlight_system_prompt_includes_product_name(): void {
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft IPA', '', 'spotlight', '0'
		);

		$this->assertStringContainsStringIgnoringCase(
			'GlacierBrew Co IPA Pack',
			$prompt,
			'Spotlight-mode system prompt must include the selected product name'
		);
	}

	public function test_spotlight_system_prompt_includes_product_url(): void {
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft IPA', '', 'spotlight', '0'
		);

		$this->assertStringContains(
			'https://glacierbrew.example/ipa',
			$prompt,
			'Spotlight-mode system prompt must include the product URL'
		);
	}

	public function test_spotlight_user_message_labels_mode_correctly(): void {
		$msg = Wordvane_Prompt_Builder::build_user_message(
			'spotlight', 'craft IPA', '', $this->dna, '0', ''
		);

		$this->assertStringContains(
			'Featured spotlight',
			$msg,
			'User message must label the mode as "Featured spotlight"'
		);
	}

	// -------------------------------------------------------------------------
	// Out-of-range spotlight index falls back to none behaviour
	// -------------------------------------------------------------------------

	public function test_invalid_product_index_treated_as_none(): void {
		$prompt = Wordvane_Prompt_Builder::build_system_prompt(
			$this->dna, 'craft beer', '', 'how-to', '99'
		);

		// Index 99 doesn't exist — must not leak brand identity.
		$this->assertStringNotContainsStringIgnoringCase(
			'GlacierBrew',
			$prompt,
			'An out-of-range product index must fall through to none-mode behaviour'
		);
	}
}
