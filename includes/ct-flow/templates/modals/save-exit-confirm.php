<?php
/**
 * Save & Exit Confirmation Modal Template
 *
 * Shown when the user clicks "שמירה ויציאה".
 * JS handles the confirm action (AJAX save then redirect).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="ct-save-exit-modal" class="ct-modal" role="dialog" aria-modal="true" aria-labelledby="ct-save-exit-title" hidden>
	<div class="ct-modal__backdrop" data-close-modal></div>
	<div class="ct-modal__box ct-modal__box--confirm">

		<button type="button" class="ct-modal__close" data-close-modal aria-label="סגור">
			<span aria-hidden="true">&times;</span>
		</button>

		<div class="ct-modal__icon" aria-hidden="true">💾</div>

		<h2 class="ct-modal__title" id="ct-save-exit-title">
			לשמור ולצאת?
		</h2>
		<p class="ct-modal__body">
			הנתונים שהזנת עד עכשיו יישמרו ותוכלו להמשיך מאוחר יותר מאיפה שעצרתם.
		</p>

		<div class="ct-modal__actions">
			<button type="button" class="ct-wizard-btn ct-wizard-btn--next" id="ct-save-exit-confirm">
				שמירה ויציאה
			</button>
			<button type="button" class="ct-wizard-btn ct-wizard-btn--back" data-close-modal>
				המשך מילוי
			</button>
		</div>

	</div>
</div>
