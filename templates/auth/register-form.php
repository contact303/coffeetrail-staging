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
			הרשמה רגילה
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
		<form class="sign-in-form register mylisting-register ct-auth-form" method="post"
			action="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" enctype="multipart/form-data"
			<?php if ( mylisting()->get( 'roles.register_captcha' ) ) : ?>
				data-recaptcha="true"
				data-recaptcha-action="register_form"
			<?php endif; ?>
		>
			<?php if ( mylisting()->get( 'roles.secondary.enabled' ) ) :
				$choices = 'secondary' === mylisting()->get( 'roles.default_form' ) ? [ 'secondary', 'primary' ] : [ 'primary', 'secondary' ];
				?>
				<p class="choose-role-text">בחרו סוג חשבון</p>
				<div class="role-tabs">
					<?php foreach ( $choices as $role_key ) : ?>
						<div class="md-checkbox">
							<input type="radio" name="mylisting_user_role"
								id="mylisting_user_role-<?php echo esc_attr( $role_key ); ?>"
								value="<?php echo esc_attr( $role_key ); ?>"
								<?php checked( \MyListing\Src\User_Roles\get_posted_role(), $role_key ); ?>
							>
							<label for="mylisting_user_role-<?php echo esc_attr( $role_key ); ?>">
								<?php echo esc_html( c27()->ml_t(
									mylisting()->get( 'roles.' . $role_key . '.label' ),
									'user-role.label',
									[ 'role_key' => $role_key ]
								) ); ?>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php do_action( 'woocommerce_register_form_start' ); ?>

			<div class="primary-role-fields">
				<?php foreach ( \MyListing\Src\User_Roles\get_used_fields( 'primary' ) as $field ) :
					if ( ! $field->get_prop( 'show_in_register_form' ) ) {
						continue;
					}
					$field->set_role( 'primary' );
					$field->form = $field::FORM_REGISTER;
					?>
					<div class="fields-wrapper ct-auth-form__field">
						<?php $field->get_form_markup(); ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( mylisting()->get( 'roles.secondary.enabled' ) ) : ?>
				<div class="secondary-role-fields">
					<?php foreach ( \MyListing\Src\User_Roles\get_used_fields( 'secondary' ) as $field ) :
						if ( ! $field->get_prop( 'show_in_register_form' ) ) {
							continue;
						}
						$field->set_role( 'secondary' );
						$field->form = $field::FORM_REGISTER;
						?>
						<div class="fields-wrapper ct-auth-form__field">
							<?php $field->get_form_markup(); ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php do_action( 'woocommerce_register_form' ); ?>

			<?php if ( mylisting()->get( 'roles.register_captcha' ) ) : ?>
				<?php \MyListing\display_recaptcha(); ?>
			<?php endif; ?>

			<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
			<button type="submit" class="buttons button-2 full-width ct-auth-form__submit" name="register" value="Register"
				<?php if ( mylisting()->get( 'roles.register_captcha' ) && 'v3' === mylisting_get_setting( 'mylisting_recaptcha_type' ) ) : ?>
					disabled data-recaptcha-submit title="טוען..."
				<?php endif; ?>
			>
				הרשמה
			</button>

			<?php do_action( 'woocommerce_register_form_end' ); ?>
		</form>
	</div>
</div>