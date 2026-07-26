<?php
/**
 * PHPUnit bootstrap — loads only what Wordvane_Prompt_Builder needs.
 *
 * Wordvane_Prompt_Builder is pure PHP (no WP function calls), so we stub
 * just the ABSPATH guard and load the class directly.
 */

define( 'ABSPATH', '' );

require_once dirname( __DIR__ ) . '/includes/class-wv-prompt-builder.php';
