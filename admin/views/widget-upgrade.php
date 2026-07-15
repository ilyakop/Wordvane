<?php
/**
 * Dismissible upgrade widget — rendered via wordvane_dashboard_widgets action.
 * Variables provided by wv_render_insights_upgrade_widget() in wordvane.php.
 *
 * @var int    $total_generated Articles generated so far this year.
 * @var string $cta_url         Upgrade or trial URL.
 * @var string $cta_label       Button label.
 * @var string $copy            Personalized body copy.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wv-insights-section" id="wv-insights-upgrade-widget">
	<div class="wv-upgrade-widget wv-card">
		<button type="button"
			class="wv-dismiss-btn"
			data-dismiss-key="insights_upgrade"
			aria-label="<?php esc_attr_e( 'Dismiss', 'wordvane' ); ?>">✕</button>
		<h2>✨ <?php esc_html_e( 'Scale your content with Pro', 'wordvane' ); ?></h2>
		<p><?php echo esc_html( $copy ); ?></p>
		<a href="<?php echo esc_url( $cta_url ); ?>"
			class="button wv-cta-btn">
			<?php echo esc_html( $cta_label ); ?>
		</a>
	</div>
</div>
