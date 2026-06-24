<?php
/**
 * Wizard Footer Template
 *
 * Renders the persistent bottom bar. Per Figma (Z8TzfW2y9vAOg3HBlwXtuZ):
 *   - "הבא"  (Next)  = green pill on the LEFT   side (last  DOM child in RTL flex = flex-end)
 *   - "חזרה" (Back)  = plain text on the RIGHT  side (first DOM child in RTL flex = flex-start)
 *   - Disabled state = gray background (#d1d5db), NOT opacity
 *   - No separate message div above — message goes inline between buttons
 *
 * Variables expected:
 *   @var string      $current_step    Current step key.
 *   @var string      $listing_package 'free' | 'pro'.
 *   @var string|null $prev_step       Previous step key (null on first step).
 *   @var bool        $next_disabled   Whether Next button starts disabled.
 *   @var bool        $next_hidden     Whether Next button is hidden (e.g. payment step).
 *   @var string      $next_label      Label for the Next button.
 *   @var string      $footer_message  Optional validation message between buttons.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prev_step      = $prev_step      ?? CT_Flow_Wizard_Controller::prev_step( $current_step, $listing_package );
$next_disabled  = $next_disabled  ?? false;
$next_hidden    = $next_hidden    ?? false;
$next_label     = $next_label     ?? 'הבא';
$footer_message = $footer_message ?? '';

// Success screen has no navigation footer.
if ( $current_step === 'success' ) {
	return;
}
?>
<footer class="ct-wizard-footer" role="contentinfo">
	<div class="ct-wizard-footer__inner">

		<!--
			Next / primary button — LEFT side visually (last in RTL DOM order).
			Moved FIRST here so the JS can target #ct-next-btn without DOM order assumptions,
			but visual order is corrected via order CSS on the back button.
		-->
		<?php if ( ! $next_hidden ) : ?>
			<button type="button"
				class="ct-wizard-btn ct-wizard-btn--next<?php echo $next_disabled ? ' ct-wizard-btn--disabled' : '' ?>"
				id="ct-next-btn"
				style="order:2"
				data-action="next"
				data-step="<?php echo esc_attr( $current_step ) ?>"
				<?php if ( $next_disabled ) : ?>aria-disabled="true"<?php endif ?>
				aria-label="<?php echo esc_attr( $next_label ) ?>">
				<?php echo esc_html( $next_label ) ?>
			</button>
		<?php else : ?>
			<span></span>
		<?php endif ?>

		<!-- Inline validation message (center) -->
		<div class="ct-wizard-footer__message" id="ct-footer-message" aria-live="polite"
			<?php if ( ! $footer_message ) : ?>style="display:none;"<?php endif ?>>
			<?php echo esc_html( $footer_message ) ?>
		</div>

		<!--
			Back button — RIGHT side visually (first in DOM = flex-start in RTL).
			Plain text, no border, per Figma.
		-->
		<?php if ( $prev_step ) : ?>
			<button type="button"
				class="ct-wizard-btn ct-wizard-btn--back"
				style="order:-1"
				data-action="back"
				data-prev-step="<?php echo esc_attr( $prev_step ) ?>"
				aria-label="חזרה לשלב הקודם">
				חזרה
			</button>
		<?php else : ?>
			<span style="order:-1;min-width:60px;"></span>
		<?php endif ?>

	</div>
</footer>
