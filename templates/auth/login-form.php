<?php
/**
 * User login form template.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ct-auth-methods" data-auth-methods>
	<div class="ct-auth-methods__tabs" role="tablist" aria-label="שיטת התחברות">
		<button class="ct-auth-methods__tab is-active" type="button" data-auth-method="sms" role="tab" aria-selected="true">
			התחברות עם SMS
		</button>
		<button class="ct-auth-methods__tab" type="button" data-auth-method="regular" role="tab" aria-selected="false">
			התחברות רגילה
		</button>
	</div>

	<div class="ct-auth-methods__panel is-active" data-auth-method-panel="sms">
		<div class="ct-auth-phone">
			<label class="ct-auth-phone__label" for="ct-login-phone">מספר טלפון</label>
			<div class="ct-auth-phone__fields" dir="ltr">
				<select class="ct-auth-phone__prefix" id="ct-login-prefix" aria-label="קידומת מדינה">
					<option value="+972" selected>+972</option>
					<option value="+1">+1</option>
					<option value="+44">+44</option>
				</select>
				<span class="ct-auth-phone__separator" aria-hidden="true">-</span>
				<input class="ct-auth-phone__number" type="tel" id="ct-login-phone" inputmode="tel" autocomplete="tel-national" placeholder="50-1234567">
			</div>
			<button class="ct-auth-phone__submit" type="button">שלחו לי קוד</button>
		</div>

		<?php do_action( 'coffeetrail_auth_sms_login_form' ); ?>
	</div>

	<div class="ct-auth-methods__panel" data-auth-method-panel="regular" hidden>
		<form class="sign-in-form woocomerce-form woocommerce-form-login login ct-auth-form" method="post"
			action="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"
			<?php if ( mylisting()->get( 'roles.login_captcha' ) ) : ?>
				data-recaptcha="true"
				data-recaptcha-action="login_form"
			<?php endif; ?>
		>
			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<div class="form-group ct-auth-form__field">
				<label for="username">אימייל</label>
				<input type="text" name="username" id="username" placeholder="name@example.com" autocomplete="username"
					value="<?php echo ! empty( $_POST['username'] ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>">
			</div>

			<div class="form-group ct-auth-form__field">
				<label for="password">סיסמה</label>
				<input type="password" name="password" id="password" placeholder="••••••••" autocomplete="current-password">
			</div>

			<?php do_action( 'woocommerce_login_form' ); ?>
			<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

			<?php if ( mylisting()->get( 'roles.login_captcha' ) ) : ?>
				<?php \MyListing\display_recaptcha(); ?>
			<?php endif; ?>

			<div class="ct-auth-form__options">
				<label class="ct-auth-form__remember" for="rememberme">
					<input type="checkbox" name="rememberme" id="rememberme" value="forever">
					<span>זכרו אותי</span>
				</label>

				<a class="ct-auth-form__forgot" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
					שכחתם סיסמה?
				</a>
			</div>

			<button type="submit" class="buttons button-2 full-width ct-auth-form__submit" name="login" value="Login"
				<?php if ( mylisting()->get( 'roles.login_captcha' ) && 'v3' === mylisting_get_setting( 'mylisting_recaptcha_type' ) ) : ?>
					disabled data-recaptcha-submit title="טוען..."
				<?php endif; ?>
			>
				התחברות
			</button>

			<?php do_action( 'woocommerce_login_form_end' ); ?>
		</form>
	</div>
</div>