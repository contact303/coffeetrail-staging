<?php
/**
 * Wizard Header Template
 *
 * Renders the persistent top bar: logo badge (RIGHT, first DOM) + action buttons (LEFT, last DOM).
 * No progress bar, no center step label — matches Figma design (Z8TzfW2y9vAOg3HBlwXtuZ).
 *
 * Variables expected from parent template:
 *   @var string $current_step       Current step key.
 *   @var string $listing_package    'free' | 'pro'.
 *   @var int    $job_id             Draft listing post ID (0 if not yet saved).
 *   @var array  $state              Full wizard state array.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Success screen has a different centered layout — no header bar.
if ( $current_step === 'success' ) {
	return;
}

$logo_url   = CT_Flow_Wizard_Page::get_logo_url();
$is_landing = $current_step === 'landing';
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

			<!-- "שמירה ויציאה" — shown on all non-landing steps -->
			<button type="button"
				class="ct-wizard-header__save-exit-btn js-save-exit-trigger"
				id="ct-save-exit-main"
				aria-label="שמירה ויציאה"
				<?php if ( $is_landing ) : ?>style="display:none;"<?php endif ?>>
				שמירה ויציאה
			</button>

			<!-- "יציאה" — shown on landing only -->
			<button type="button"
				class="ct-wizard-header__save-exit-btn js-save-exit-trigger"
				id="ct-exit-only"
				aria-label="יציאה"
				<?php if ( ! $is_landing ) : ?>style="display:none;"<?php endif ?>>
				יציאה
			</button>

			<!-- "שאלות?" — shown on all non-landing steps -->
			<button type="button"
				class="ct-wizard-header__questions-btn"
				id="ct-help-trigger"
				aria-label="שאלות ועזרה"
				<?php if ( $is_landing ) : ?>style="display:none;"<?php endif ?>>
				שאלות?
			</button>

		</div>

	</div>
	<!-- Progress bar hidden per Figma, kept in DOM for progressive enhancement -->
	<div class="ct-wizard-progress" role="progressbar" aria-hidden="true" style="display:none;"></div>
</header>
