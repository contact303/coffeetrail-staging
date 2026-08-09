<?php
/**
 * User registration form template.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ct-auth-methods" data-auth-methods>
	<div class="ct-auth-methods__tabs" role="tablist" aria-label="שיטת הרשמה">
		<button class="ct-auth-methods__tab is-active" type="button" data-auth-method="sms" role="tab" aria-selected="true">
			הרשמה עם SMS
		</button>
		<button class="ct-auth-methods__tab" type="button" data-auth-method="regular" role="tab" aria-selected="false">
			הרשמה עם אימייל
		</button>
	</div>

	<div class="ct-auth-methods__panel is-active" data-auth-method-panel="sms">
		<div class="ct-auth-phone">
			<label class="ct-auth-phone__label" for="ct-register-phone">מספר טלפון</label>
			<div class="ct-auth-phone__fields" dir="ltr">
				<select class="ct-auth-phone__prefix" id="ct-register-prefix" aria-label="קידומת מדינה">
					<option value="+972" selected>+972</option>
					<option value="+1">+1</option>
					<option value="+44">+44</option>
				</select>
				<span class="ct-auth-phone__separator" aria-hidden="true">-</span>
				<input class="ct-auth-phone__number" type="tel" id="ct-register-phone" inputmode="tel" autocomplete="tel-national" placeholder="50-1234567">
			</div>
			<button class="ct-auth-phone__submit" type="button">המשך עם SMS</button>
		</div>

		<?php do_action( 'coffeetrail_auth_sms_register_form' ); ?>
	</div>

	<div class="ct-auth-methods__panel" data-auth-method-panel="regular" hidden>

		<?php
		if ( function_exists( 'ct_render_account_registration' ) ) {
			ct_render_account_registration();
		}
		?>

	</div>
</div>