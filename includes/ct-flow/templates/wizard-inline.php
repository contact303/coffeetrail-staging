<?php
/**
 * Wizard Inline Template
 *
 * Renders the multi-step wizard as a full-viewport fixed overlay within an
 * existing Elementor/WordPress page template.  Used by the filter-fallback
 * path in CT_Flow_Wizard_Page::maybe_intercept_widget() when
 * template_redirect could not take over the response — the most common cause
 * being that the 'general_add_listing_page' theme setting stores a stale post
 * ID (after a staging DB import) so is_page() returns false.
 *
 * Differences from wizard-shell.php:
 *  - No <!DOCTYPE html>, <html>, <head>, <body> wrapper — the Elementor page
 *    template provides those.
 *  - No wp_head() / wp_footer() calls — already handled by the page template.
 *  - CT Flow CSS/JS assets are enqueued by CT_Flow_Fixes::enqueue_assets()
 *    via wp_enqueue_scripts, so they are available here automatically.
 *  - The container carries the `.ct-wizard-inline` class, which applies
 *    `position: fixed; inset: 0; z-index: 99999` (see ct-wizard.css) to
 *    cover the Elementor chrome and give the wizard full-screen ownership.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Resolve wizard state (identical logic to wizard-shell.php)
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
// Render wizard container (no page-level HTML wrapper)
// ---------------------------------------------------------------------------
?>
<div id="ct-wizard-container"
	class="ct-wizard-page ct-wizard-inline"
	dir="rtl"
	data-step="<?php echo esc_attr( $current_step ) ?>"
	data-package="<?php echo esc_attr( $listing_package ) ?>"
	data-job-id="<?php echo esc_attr( $job_id ) ?>">

	<!-- Wizard header (not shown on landing / success) -->
	<?php if ( ! in_array( $current_step, [ 'landing', 'success' ], true ) ) :
		include CT_FLOW_DIR . '/templates/wizard/header.php';
	endif ?>

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
// Marker templates must be present when MyListing.Maps.Marker builds its pin HTML.
// In inline mode wp_footer() is handled by the page, so we inject them here directly.
$marker_templates = get_template_directory() . '/partials/marker-templates.php';
if ( file_exists( $marker_templates ) ) {
	include $marker_templates;
}
?>
