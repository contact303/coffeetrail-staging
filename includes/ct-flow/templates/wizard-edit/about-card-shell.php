<?php
/**
 * Wizard Edit — "About the cart" card shell
 *
 * Full-page document for the edit-mode "about" card (basics + contact +
 * location composed onto one screen — the dashboard's "על העגלה" section).
 * Deliberately NOT built on wizard-shell.php: no resume detection, no
 * landing/step-order concepts, no modals wired to build-flow AJAX actions.
 *
 * Rendered by CT_Flow_Wizard_Edit::maybe_output_edit_card(), which has
 * already checked ownership and hydrated $state before including this
 * file, and calls exit immediately after.
 *
 * Variables available (set by maybe_output_edit_card()):
 *   @var string $card             Card slug ('about').
 *   @var int    $job_id           The listing being edited.
 *   @var array  $definition       self::CARDS[$card] — ['steps' => [...]].
 *   @var string $listing_package  'free' | 'pro'.
 *   @var array  $state            Hydrated ['data' => ['basics'=>[...], 'contact'=>[...], 'location'=>[...]]].
 *   @var string $return_url       Dashboard URL to return to (back link + post-save redirect).
 *   @var bool   $is_edit_mode     Always true here.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Any non-landing/non-success value works here — header.php/footer.php only
// compare against those two literals. Use the card's first step for a
// meaningful value rather than a magic string.
$current_step = $definition['steps'][0] ?? 'basics';
?><!DOCTYPE html>
<html <?php language_attributes() ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ) ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( get_bloginfo( 'name' ) ) ?> — עריכת פרטי העמוד</title>
	<?php wp_head() ?>
	<style>
		/* Hard-reset any theme/Elementor chrome that slipped through — same
		   reset wizard-shell.php uses, kept identical for visual consistency. */
		body.ct-wizard-page { margin: 0; padding: 0; background: #ffffff !important; }
		.elementor-popup-modal,
		.elementor-location-popup,
		[class*="elementor-popup"],
		.dialog-widget,
		.e-popup,
		#elementor-popup-modal-overlay { display: none !important; }
	</style>
</head>
<body <?php body_class( 'ct-wizard-page ct-wizard-edit-page' ) ?>>

<div id="ct-wizard-container"
	dir="rtl"
	data-step="<?php echo esc_attr( $current_step ) ?>"
	data-package="<?php echo esc_attr( $listing_package ) ?>"
	data-job-id="<?php echo esc_attr( $job_id ) ?>"
	data-edit-mode="1"
	data-edit-card="<?php echo esc_attr( $card ) ?>"
	data-edit-return-url="<?php echo esc_url( $return_url ) ?>">

	<?php include CT_FLOW_DIR . '/templates/wizard/header.php'; ?>

	<div id="ct-wizard-step-content">

		<a href="<?php echo esc_url( $return_url ) ?>"
			class="ct-edit-back-link"
			style="display:inline-block;margin:16px 24px 0;color:var(--ct-green,#219156);text-decoration:none;font-weight:600;font-size:14px;">
			&rarr; חזרה לאזור האישי
		</a>

		<?php
		// Compose the card's step templates onto one screen. Each render_step()
		// call suppresses its own trailing footer.php include (5th/6th args) —
		// see class-wizard-page.php's render_step() docblock — so exactly one
		// shared footer, with edit-mode's own back/label below, renders once.
		foreach ( $definition['steps'] as $step ) {
			echo CT_Flow_Wizard_Page::render_step( $step, $listing_package, $state, $job_id, true, true );
		}

		// Reuse footer.php's existing $prev_step/$next_label override seam
		// rather than a parallel mechanism. Empty string, not null — footer.php
		// null-coalesces ($prev_step ?? ...), so only a non-null falsy value
		// actually suppresses the computed default and omits the back button.
		$prev_step  = '';
		$next_label = 'שמירה וחזרה';
		include CT_FLOW_DIR . '/templates/wizard/footer.php';
		?>
	</div>

</div><!-- #ct-wizard-container -->

<?php
// Marker template HTML required by MyListing.Maps.Marker (getTemplate reads
// #case27-traditional-marker-template etc. from the DOM at runtime) — the
// location step's map picker needs this the same as it does in the build flow.
$marker_templates = get_template_directory() . '/partials/marker-templates.php';
if ( file_exists( $marker_templates ) ) {
	include $marker_templates;
}
?>

<?php wp_footer() ?>

<script>
/* Ensure any Elementor popup nodes that survived are hidden after DOM load */
(function() {
	var selectors = [
		'.elementor-popup-modal',
		'.elementor-location-popup',
		'[class*="elementor-popup"]',
		'.dialog-widget',
		'.e-popup'
	];
	var style = document.createElement('style');
	style.textContent = selectors.join(',') + '{display:none!important}';
	document.head.appendChild(style);
})();
</script>
</body>
</html>
