<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single source of truth for the Pro feature list.
 * Referenced by: Settings comparison card, Insights upgrade widget, readme.txt (manually kept in sync).
 *
 * @return array[] Each entry has keys: 'slug', 'label'.
 */
function wv_get_pro_features() {
	return [
		[
			'slug'  => 'unlimited_generation',
			'label' => __( 'Unlimited article generation — no monthly cap', 'wordvane' ),
		],
		[
			'slug'  => 'bulk_queue',
			'label' => __( 'Bulk Queue — generate from a keyword list in the background', 'wordvane' ),
		],
		[
			'slug'  => 'content_refresh',
			'label' => __( 'Content Refresh Mode with diff view before publishing', 'wordvane' ),
		],
		[
			'slug'  => 'internal_linking',
			'label' => __( 'Internal Linking Automation — contextual links inserted automatically', 'wordvane' ),
		],
		[
			'slug'  => 'multi_dna',
			'label' => __( 'Multiple Business DNA profiles (agency / multi-site)', 'wordvane' ),
		],
		[
			'slug'  => 'pro_types',
			'label' => __( 'Pro article types: Comparison, Listicle, Category Page, Product Description', 'wordvane' ),
		],
		[
			'slug'  => 'team_roles',
			'label' => __( 'Team roles & permissions (Content Creator, Publisher, Admin)', 'wordvane' ),
		],
	];
}
