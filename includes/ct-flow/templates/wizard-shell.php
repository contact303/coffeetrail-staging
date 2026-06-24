<?php
/**
 * Wizard Shell Template
 *
 * Top-level wrapper for the multi-step wizard experience.
 * Replaces the default WordPress page template for /add-listing/?listing_type=cc.
 *
 * This template:
 *  - Outputs a minimal full-screen layout (no sidebar, no theme header/footer).
 *  - Reads wizard state from transient and determines the current step.
 *  - Renders wizard header, current step content, footer, and modals.
 *  - Passes state to ct-wizard.js via data attributes.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Resolve wizard state
// ---------------------------------------------------------------------------

$user_id         = get_current_user_id();
$listing_package = sanitize_key( $_REQUEST['listing_package'] ?? 'free' );

// Map numeric product IDs → string names BEFORE the whitelist check.
if ( absint( $_REQUEST['listing_package'] ?? 0 ) === CT_FLOW_PRO_PRODUCT_ID ) {
	$listing_package = 'pro';
} elseif ( absint( $_REQUEST['listing_package'] ?? 0 ) === CT_FLOW_FREE_PRODUCT_ID ) {
	$listing_package = 'free';
}

// Reject anything that isn't a valid package name.
$listing_package = in_array( $listing_package, [ 'free', 'pro' ], true ) ? $listing_package : 'free';

$state = CT_Flow_Wizard_Controller::get_state( $user_id );

// Draft resume: if the user has a transient and visits the page, skip landing.
$resume_requested = ! empty( $_REQUEST['ct_resume'] );
if ( CT_Flow_Wizard_Controller::has_draft() && ( $resume_requested || ! isset( $_REQUEST['ct_tab'] ) ) ) {
	$current_step = $state['current_step'];
} else {
	$current_step = 'landing';
	$state        = CT_Flow_Wizard_Controller::get_state( $user_id );
}

// Update package in state if given via URL.
if ( isset( $_REQUEST['listing_package'] ) ) {
	$state['listing_package'] = $listing_package;
}

$current_step    = $current_step    ?? 'landing';
$listing_package = $state['listing_package'] ?? $listing_package;
$job_id          = $state['job_id'] ?? 0;
$has_draft       = CT_Flow_Wizard_Controller::has_draft();

// ---------------------------------------------------------------------------
// Output minimal page head (no theme chrome)
// ---------------------------------------------------------------------------
?><!DOCTYPE html>
<html <?php language_attributes() ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ) ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( get_bloginfo( 'name' ) ) ?> — הוספת עגלה</title>
	<?php wp_head() ?>
	<style>
		/* Hard-reset any theme/Elementor chrome that slipped through */
		body.ct-wizard-page { margin: 0; padding: 0; background: #ffffff !important; }
		/* Nuke Elementor popup containers wherever they land */
		.elementor-popup-modal,
		.elementor-location-popup,
		[class*="elementor-popup"],
		.dialog-widget,
		.e-popup,
		#elementor-popup-modal-overlay { display: none !important; }
	</style>
</head>
<body <?php body_class( 'ct-wizard-page' ) ?>>

<div id="ct-wizard-container"
	dir="rtl"
	data-step="<?php echo esc_attr( $current_step ) ?>"
	data-package="<?php echo esc_attr( $listing_package ) ?>"
	data-job-id="<?php echo esc_attr( $job_id ) ?>">

	<!-- Wizard header (shown on all steps — header handles its own visibility) -->
	<?php include CT_FLOW_DIR . '/templates/wizard/header.php'; ?>

	<!-- Step content area -->
	<div id="ct-wizard-step-content">
		<?php echo CT_Flow_Wizard_Page::render_step( $current_step, $listing_package, $state, $job_id ) ?>
	</div>

</div><!-- #ct-wizard-container -->

<!-- Modals (always in DOM, toggled via JS) -->
<?php
include CT_FLOW_DIR . '/templates/modals/help-modal.php';
include CT_FLOW_DIR . '/templates/modals/save-exit-confirm.php';
?>

<?php
// Marker template HTML required by MyListing.Maps.Marker (getTemplate reads
// #case27-traditional-marker-template etc. from the DOM at runtime).
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
