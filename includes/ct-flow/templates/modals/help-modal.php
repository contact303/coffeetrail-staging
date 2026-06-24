<?php
/**
 * Help / Questions? Modal Template
 *
 * Rendered inline in the wizard page. Toggled visible/hidden via JS.
 * Replace the placeholder contact details with real ones when available.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="ct-help-modal" class="ct-modal" role="dialog" aria-modal="true" aria-labelledby="ct-help-modal-title" hidden>
	<div class="ct-modal__backdrop" data-close-modal></div>
	<div class="ct-modal__box ct-modal__box--help">

		<button type="button" class="ct-modal__close" data-close-modal aria-label="סגור">
			<span aria-hidden="true">&times;</span>
		</button>

		<h2 class="ct-modal__title" id="ct-help-modal-title">
			יש לכם שאלות? אנחנו כאן
		</h2>
		<p class="ct-modal__subtitle">
			צרו איתנו קשר בכל שאלה — נשמח לעזור.
		</p>

		<ul class="ct-modal__contact-list">
			<li class="ct-modal__contact-item">
				<span class="ct-modal__contact-icon" aria-hidden="true">💬</span>
				<span>
					<strong>WhatsApp</strong><br>
					<a href="https://wa.me/972500000000" target="_blank" rel="noopener">
						050-000-0000
					</a>
					<em class="ct-modal__note">(יוחלף בפרטי הקשר האמיתיים)</em>
				</span>
			</li>
			<li class="ct-modal__contact-item">
				<span class="ct-modal__contact-icon" aria-hidden="true">✉️</span>
				<span>
					<strong>אימייל</strong><br>
					<a href="mailto:support@coffeetrail.co.il">
						support@coffeetrail.co.il
					</a>
					<em class="ct-modal__note">(יוחלף בפרטי הקשר האמיתיים)</em>
				</span>
			</li>
		</ul>

		<p class="ct-modal__hours">
			זמני מענה: ימים א׳–ה׳, 09:00–18:00
		</p>

	</div>
</div>
