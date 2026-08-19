<?php
/**
 * Wizard Header Template
 *
 * Renders the persistent top bar: logo badge (RIGHT, first DOM) + action buttons (LEFT, last DOM),
 * plus a three-segment group indicator ("שלב N מתוך 3") below the inner row.
 *
 * Variables expected from parent template:
 *   @var string $current_step       Current step key.
 *   @var string $listing_package    'free' | 'pro'.
 *   @var int    $job_id             Draft listing post ID (0 if not yet saved).
 *   @var array  $state              Full wizard state array.
 *   @var bool   $is_edit_mode       True in the edit-mode merged-card screen (see
 *                                   CT_Flow_Wizard_Edit). Hides the group indicator
 *                                   (there is no "step N of 3 toward publishing" in
 *                                   edit mode) and the save-exit/exit/help action
 *                                   buttons, which are wired to the build flow's
 *                                   per-user transient (ct_wizard_save_exit) that
 *                                   edit mode deliberately never touches.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Success screen has a different centered layout — no header bar.
if ( $current_step === 'success' ) {
	return;
}

$is_edit_mode = $is_edit_mode ?? false;
$logo_url   = CT_Flow_Wizard_Page::get_logo_url();
$is_landing = $current_step === 'landing';
$step_group = $is_edit_mode ? null : CT_Flow_Wizard_Controller::get_step_group( $current_step );
$group_total = count( CT_Flow_Wizard_Controller::STEP_GROUPS );
// All three buttons are always in the DOM so AJAX navigation can swap them
// without a page reload. PHP sets initial visibility; JS (updateHeaderForStep)
// toggles display on each step change.
?>
<header class="ct-wizard-header" role="banner">
	<div class="ct-wizard-header__inner">

		<!--
			Logo badge — RIGHT side in RTL (first in DOM = flex-start = right visually).
			Black 48x48px square with coffee cup icon, per Figma.
		-->
		<div class="ct-wizard-header__logo-badge" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ) ?>">
			<?php if ( $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ) ?>"
					alt="<?php echo esc_attr( get_bloginfo( 'name' ) ) ?>"
					onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
				<span class="ct-wizard-logo-emoji" style="display:none;">☕</span>
			<?php else : ?>
				<span class="ct-wizard-logo-emoji">☕</span>
			<?php endif ?>
		</div>

		<!--
			Three-segment group indicator ("שלב N מתוך 3"). Always in the DOM (same
			pattern as the header buttons above) so AJAX step loads can update it via
			ct-wizard.js (updateGroupIndicator) without a full header re-render.
			Hidden on landing/success, where get_step_group() returns null.
		-->
		<div class="ct-wizard-group-indicator"
			role="progressbar"
			aria-label="התקדמות בטופס"
			aria-valuemin="1"
			aria-valuemax="<?php echo esc_attr( $group_total ) ?>"
			aria-valuenow="<?php echo esc_attr( $step_group['index'] ?? 1 ) ?>"
			<?php if ( ! $step_group ) : ?>style="display:none;"<?php endif ?>>
			<span class="ct-wizard-group-indicator__label">
				<?php echo $step_group ? esc_html( sprintf( 'שלב %d מתוך %d', $step_group['index'], $step_group['total'] ) ) : '' ?>
			</span>
			<div class="ct-wizard-group-indicator__segments">
				<?php for ( $i = 1; $i <= $group_total; $i++ ) : ?>
					<span class="ct-wizard-group-indicator__segment<?php echo ( $step_group && $i <= $step_group['index'] ) ? ' is-active' : '' ?>"></span>
				<?php endfor ?>
			</div>
		</div>		

		<!--
			Action buttons — LEFT side in RTL (last in DOM = flex-end = left visually).

			Three buttons, always rendered, visibility toggled by PHP (initial) and
			by JS updateHeaderForStep() on every AJAX step change:
			  #ct-save-exit-main  — "שמירה ויציאה"  (all non-landing steps)
			  #ct-exit-only       — "יציאה"          (landing only)
			  #ct-help-trigger    — "שאלות?"          (all non-landing steps)

			Both save-exit variants carry the class js-save-exit-trigger so the
			single JS delegated handler fires for both.
		-->
		<div class="ct-wizard-header__actions">

			<!-- "שמירה ויציאה" — shown on all non-landing steps, never in edit mode
			     (wired to the build flow's ct_wizard_save_exit transient) -->
			<button type="button"
				class="ct-wizard-header__save-exit-btn js-save-exit-trigger"
				id="ct-save-exit-main"
				aria-label="שמירה ויציאה"
				<?php if ( $is_landing || $is_edit_mode ) : ?>style="display:none;"<?php endif ?>>
				שמירה ויציאה
			</button>

			<!-- "יציאה" — shown on landing only, never in edit mode -->
			<button type="button"
				class="ct-wizard-header__save-exit-btn js-save-exit-trigger"
				id="ct-exit-only"
				aria-label="יציאה"
				<?php if ( ! $is_landing || $is_edit_mode ) : ?>style="display:none;"<?php endif ?>>
				יציאה
			</button>

			<!-- "שאלות?" — shown on all non-landing steps, never in edit mode -->
			<button type="button"
				class="ct-wizard-header__questions-btn"
				id="ct-help-trigger"
				aria-label="שאלות ועזרה"
				<?php if ( $is_landing || $is_edit_mode ) : ?>style="display:none;"<?php endif ?>>
					<svg data-dc-tpl="39" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle data-dc-tpl="40" cx="12" cy="12" r="9"></circle><path data-dc-tpl="41" d="M9.2 9a2.8 2.8 0 0 1 5.4 1c0 1.8-2.6 2.5-2.6 2.5"></path><path data-dc-tpl="42" d="M12 17.5h.01"></path></svg>
				שאלות?
			</button>

		</div>

	</div>
</header>
