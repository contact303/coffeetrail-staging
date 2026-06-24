<?php
/**
 * Template Name: CT Register
 *
 * Full-screen split-screen registration page for the CoffeeTrail listing-owner
 * flow. Outputs its own <html>…</html> shell — no theme header or footer.
 *
 * GET params read:
 *   redirect          – URL to send the user after successful registration
 *   listing_package   – WC product ID (24 = Free, 25 = Pro)
 *   tab               – 'gmail' (default) | 'manual'
 *   email             – pre-fills the email field on the manual form
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Block access for logged-in users — redirect straight to the wizard.
if ( is_user_logged_in() ) {
	$redirect = esc_url_raw( $_REQUEST['redirect'] ?? home_url( '/' ) );
	wp_safe_redirect( $redirect );
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Registration form processing (self-POST)
// ─────────────────────────────────────────────────────────────────────────────

$errors  = [];
$old     = [];
$tab     = sanitize_key( $_GET['tab'] ?? 'gmail' );

if ( ! empty( $_POST['ct_register_nonce'] ) ) {

	$nonce_valid = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ct_register_nonce'] ) ), 'ct_register' );

	if ( ! $nonce_valid ) {
		$errors['_form'] = 'בקשה לא תקינה. אנא רעננו את הדף ונסו שוב.';
	} else {

		$form_type  = sanitize_key( $_POST['ct_form_type'] ?? 'manual' );
		$tab        = ( $form_type === 'gmail_email' ) ? 'gmail' : 'manual';

		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name']       ?? '' ) );
		$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name']        ?? '' ) );
		$email      = sanitize_email(      wp_unslash( $_POST['email']            ?? '' ) );
		$password   = wp_unslash(                      $_POST['password']         ?? ''   );
		$pkg_id     = absint(                          $_POST['ct_listing_package']?? 0   );
		$redirect   = esc_url_raw( wp_unslash(         $_POST['redirect']         ?? home_url( '/' ) ) );
		$marketing  = ! empty( $_POST['ct_marketing_consent'] );

		$old = compact( 'first_name', 'last_name', 'email' );

		// ── Validate ──────────────────────────────────────────────────────────

		if ( $form_type === 'manual' ) {
			if ( empty( $first_name ) ) {
				$errors['first_name'] = 'יש להזין שם פרטי';
			}
			if ( ! is_email( $email ) ) {
				$errors['email'] = 'יש להזין כתובת אימייל תקינה';
			}
			if ( strlen( $password ) < 8 ) {
				$errors['password'] = 'הסיסמה צריכה להכיל לפחות 8 תווים';
			}
		} else {
			// Gmail variant: email only.
			if ( ! is_email( $email ) ) {
				$errors['email'] = 'יש להזין כתובת אימייל תקינה';
			}
		}

		if ( empty( $errors ) && email_exists( $email ) ) {
			$errors['email'] = 'כתובת האימייל הזו כבר רשומה. <a href="' . esc_url( wc_get_account_endpoint_url( 'dashboard' ) ) . '">כניסה לחשבון קיים</a>';
		}

		// ── Create user ───────────────────────────────────────────────────────

		if ( empty( $errors ) ) {

			$auto_pass = ( $form_type !== 'manual' ) ? wp_generate_password( 16 ) : $password;

			$new_customer_id = wc_create_new_customer(
				$email,
				'',          // username — WC generates from email
				$auto_pass,
				[
					'first_name' => $first_name,
					'last_name'  => $last_name,
				]
			);

			if ( is_wp_error( $new_customer_id ) ) {
				$errors['_form'] = $new_customer_id->get_error_message();
				// Suppress any WooCommerce notices queued internally — errors are shown inline.
				if ( function_exists( 'wc_clear_notices' ) ) {
					wc_clear_notices();
				}
			} else {
				// Save meta used by the wizard and marketing.
				update_user_meta( $new_customer_id, '_ct_marketing_consent',      $marketing ? 1 : 0 );
				update_user_meta( $new_customer_id, '_ct_marketing_consent_date', current_time( 'timestamp' ) );

				if ( $pkg_id ) {
					$plan = ( $pkg_id === CT_FLOW_PRO_PRODUCT_ID ) ? 'pro' : 'free';
					update_user_meta( $new_customer_id, '_ct_registered_plan', $plan );
				}

				// Fire the standard WooCommerce hook so other integrations still work.
				do_action( 'woocommerce_created_customer', $new_customer_id, [], false );

				// Log in and redirect to the wizard.
				wc_set_customer_auth_cookie( $new_customer_id );
				wp_redirect( $redirect );
				exit;
			}
		}
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Prepare template variables
// ─────────────────────────────────────────────────────────────────────────────

$redirect_url = esc_url_raw( $_REQUEST['redirect'] ?? home_url( '/' ) );
$_raw_pkg     = $_REQUEST['listing_package'] ?? 0;
$pkg_id       = $_raw_pkg ? absint( c27()->get_package_id_for_validation( $_raw_pkg ) ) : 0;
$pre_email    = sanitize_email( $old['email'] ?? $_GET['email'] ?? '' );
// Mirror the theme's Google::is_enabled() logic — settings may live in c27's
// serialized array rather than individual wp_options entries.
$_google_enabled_opt = get_option( 'mylisting_social_login_google_enabled', null );
$_google_enabled     = is_null( $_google_enabled_opt )
	? (bool) c27()->get_setting( 'social_login_google_enabled' )
	: (bool) $_google_enabled_opt;
$_google_client_opt  = get_option( 'mylisting_social_login_google_client_id', null );
$_google_client_id   = is_null( $_google_client_opt )
	? (string) c27()->get_setting( 'social_login_google_client_id' )
	: (string) $_google_client_opt;
$google_enabled = $_google_enabled && ! empty( $_google_client_id );

// Prefer the custom CoffeeTrail login page (slug "ct-login") when it exists,
// falling back to the standard WordPress login form otherwise.
$login_page = get_page_by_path( 'ct-login' );
$login_url  = ( $login_page instanceof WP_Post )
	? add_query_arg( [ 'redirect' => $redirect_url ], get_permalink( $login_page ) )
	: wp_login_url( $redirect_url );

// Current-page URL for the form action (self-POST keeps GET params intact).
$page_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// ─────────────────────────────────────────────────────────────────────────────
// Helper: render a field with optional error state
// ─────────────────────────────────────────────────────────────────────────────
function ct_auth_field_class( $field, $errors ) {
	return 'ct-field' . ( isset( $errors[ $field ] ) ? ' ct-field--error' : '' );
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> — הרשמה</title>
<?php wp_head(); ?>
</head>
<body class="ct-auth-body">

<div class="ct-auth-page">

	<!-- ═══════════════════════════════════════════════════════
	     LEFT  ·  Green branding panel
	     ═══════════════════════════════════════════════════════ -->
	<div class="ct-auth-page__brand" aria-hidden="true">
		<div class="ct-auth-brand-inner">
			<!-- Coffee-cup icon (white SVG) -->
			<svg class="ct-auth-brand-icon" width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<path d="M33.33 13.33c0-2.76 2.1-5 4.68-4.99.34 2.23-1.16 4.42-3.36 4.99H33.33zm6.67 0c0-2.76 2.1-5 4.68-4.99.34 2.23-1.16 4.42-3.36 4.99H40zm6.67 0c0-2.76 2.1-5 4.68-4.99.34 2.23-1.16 4.42-3.36 4.99H46.67z" fill="rgba(255,255,255,0.9)"/>
				<path d="M16.67 23.33h46.66v5c0 17.27-9.49 30-23.33 30S16.67 45.6 16.67 28.33v-5z" stroke="rgba(255,255,255,0.9)" stroke-width="3.33" stroke-linejoin="round" fill="none"/>
				<path d="M63.33 28.33H70a6.67 6.67 0 0 1 0 13.34h-6.67" stroke="rgba(255,255,255,0.9)" stroke-width="3.33" stroke-linejoin="round" fill="none"/>
				<path d="M10 61.67h60" stroke="rgba(255,255,255,0.9)" stroke-width="3.33" stroke-linecap="round"/>
			</svg>

			<h1 class="ct-auth-brand-title">
				הוסיפו את העגלה שלכם,<br>
				הגדילו את העסק שלכם.
			</h1>
			<p class="ct-auth-brand-sub">
				הצטרפו לפלטפורמה שמחברת עגלות קפה עם לקוחות
			</p>
		</div>
	</div>

	<!-- ═══════════════════════════════════════════════════════
	     RIGHT  ·  Form panel
	     ═══════════════════════════════════════════════════════ -->
	<div class="ct-auth-page__panel login-container">
		<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect_url ); ?>">

		<!-- CoffeeTrail logo badge (black rounded square) -->
		<div class="ct-auth-logo-badge" aria-hidden="true">
			<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M11.67 4.67c0-.97.73-1.75 1.63-1.74.12.78-.4 1.54-1.17 1.74H11.67zm2.33 0c0-.97.73-1.75 1.63-1.74.12.78-.4 1.54-1.17 1.74H14zm2.33 0c0-.97.73-1.75 1.63-1.74.12.78-.4 1.54-1.17 1.74H16.33z" fill="white"/>
				<path d="M5.83 8.17h16.34v1.75c0 6.04-3.32 10.5-8.17 10.5s-8.17-4.46-8.17-10.5V8.17z" stroke="white" stroke-width="1.17" stroke-linejoin="round" fill="none"/>
				<path d="M22.17 9.92H24.5a2.33 2.33 0 0 1 0 4.66h-2.33" stroke="white" stroke-width="1.17" stroke-linejoin="round" fill="none"/>
				<path d="M3.5 21.58h21" stroke="white" stroke-width="1.17" stroke-linecap="round"/>
			</svg>
		</div>

		<?php if ( ! empty( $errors['_form'] ) ) : ?>
		<div class="ct-auth-notice ct-auth-notice--error" role="alert">
			<?php echo wp_kses_post( $errors['_form'] ); ?>
		</div>
		<?php endif; ?>

		<!-- ────────────────────────────────────────────────────
		     GMAIL VARIANT (default)
		     ──────────────────────────────────────────────────── -->
		<div class="ct-auth-variant<?php echo ( $tab !== 'manual' ) ? ' ct-auth-variant--active' : ''; ?>"
		     id="ct-auth-gmail" aria-hidden="<?php echo ( $tab === 'manual' ) ? 'true' : 'false'; ?>">

			<h2 class="ct-auth-title">צרו את החשבון שלכם</h2>
			<p class="ct-auth-subtitle">התחילו את ה-14 ימי ניסיון בחינם, בטלו בכל עת.</p>

			<form method="post" action="<?php echo esc_url( $page_url ); ?>" class="ct-auth-form" id="ct-form-gmail" novalidate>
				<?php wp_nonce_field( 'ct_register', 'ct_register_nonce' ); ?>
				<input type="hidden" name="ct_form_type"        value="gmail_email">
				<input type="hidden" name="redirect"            value="<?php echo esc_attr( $redirect_url ); ?>">
				<input type="hidden" name="ct_listing_package"  value="<?php echo esc_attr( $pkg_id ); ?>">

				<div class="ct-field-group">
					<input type="email"
					       name="email"
					       id="ct-gmail-email"
					       class="<?php echo esc_attr( ct_auth_field_class( 'email', $errors ) ); ?>"
					       placeholder="אימייל"
					       value="<?php echo esc_attr( $pre_email ); ?>"
					       autocomplete="email"
					       dir="rtl">
					<?php if ( isset( $errors['email'] ) && $tab !== 'manual' ) : ?>
					<span class="ct-field-error" role="alert"><?php echo wp_kses_post( $errors['email'] ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $google_enabled ) : ?>
				<div class="ct-auth-divider">
					<span>או</span>
				</div>
				<div class="cts-google-signin"></div>
				<?php endif; ?>

				<label class="ct-checkbox-label">
					<input type="checkbox" name="ct_marketing_consent" value="1"
					       <?php checked( ! empty( $_POST['ct_marketing_consent'] ) ); ?>>
					<span>אשמח לקבל עדכונים, טיפים והצעות מקופיטרייל</span>
				</label>

				<?php ct_auth_tos_text( CT_Flow_Registration::get_policy_url( 'terms_free' ), CT_Flow_Registration::get_policy_url( 'privacy' ) ); ?>

				<button type="submit" name="register" class="ct-auth-btn">הירשמו</button>
			</form>

			<p class="ct-auth-login-prompt">
				כבר יש לכם חשבון?
				<a href="<?php echo esc_url( $login_url ); ?>" class="ct-auth-login-link">התחברו</a>
			</p>

			<p class="ct-auth-switch">
				<button type="button" class="ct-auth-switch-btn" data-ct-switch-variant="ct-auth-manual">
					הרשמה עם שם וסיסמה
				</button>
			</p>

		</div><!-- /#ct-auth-gmail -->

		<!-- ────────────────────────────────────────────────────
		     MANUAL VARIANT
		     ──────────────────────────────────────────────────── -->
		<div class="ct-auth-variant<?php echo ( $tab === 'manual' ) ? ' ct-auth-variant--active' : ''; ?>"
		     id="ct-auth-manual" aria-hidden="<?php echo ( $tab !== 'manual' ) ? 'true' : 'false'; ?>">

			<h2 class="ct-auth-title ct-auth-title--sm">סיימו את ההרשמה</h2>

			<form method="post" action="<?php echo esc_url( $page_url ); ?>" class="ct-auth-form" id="ct-form-manual" novalidate>
				<?php wp_nonce_field( 'ct_register', 'ct_register_nonce' ); ?>
				<input type="hidden" name="ct_form_type"        value="manual">
				<input type="hidden" name="redirect"            value="<?php echo esc_attr( $redirect_url ); ?>">
				<input type="hidden" name="ct_listing_package"  value="<?php echo esc_attr( $pkg_id ); ?>">

				<div class="ct-field-group">
					<input type="text"
					       name="first_name"
					       id="ct-first-name"
					       class="<?php echo esc_attr( ct_auth_field_class( 'first_name', $errors ) ); ?>"
					       placeholder="שם פרטי"
					       value="<?php echo esc_attr( $old['first_name'] ?? '' ); ?>"
					       autocomplete="given-name"
					       dir="rtl">
					<?php if ( isset( $errors['first_name'] ) ) : ?>
					<span class="ct-field-error" role="alert"><?php echo esc_html( $errors['first_name'] ); ?></span>
					<?php endif; ?>
				</div>

				<div class="ct-field-group">
					<input type="text"
					       name="last_name"
					       id="ct-last-name"
					       class="ct-field"
					       placeholder="שם משפחה"
					       value="<?php echo esc_attr( $old['last_name'] ?? '' ); ?>"
					       autocomplete="family-name"
					       dir="rtl">
				</div>

				<div class="ct-field-group">
					<input type="email"
					       name="email"
					       id="ct-manual-email"
					       class="<?php echo esc_attr( ct_auth_field_class( 'email', $errors ) ); ?>"
					       placeholder="אימייל"
					       value="<?php echo esc_attr( $old['email'] ?? $pre_email ); ?>"
					       autocomplete="email"
					       dir="rtl">
					<?php if ( isset( $errors['email'] ) && $tab === 'manual' ) : ?>
					<span class="ct-field-error" role="alert"><?php echo wp_kses_post( $errors['email'] ); ?></span>
					<?php endif; ?>
					<span class="ct-field-hint">נשלח לכם באימייל אישורי נסיעה וקבלות.</span>
				</div>

				<div class="ct-field-group ct-field-group--password">
					<input type="password"
					       name="password"
					       id="ct-password"
					       class="<?php echo esc_attr( ct_auth_field_class( 'password', $errors ) ); ?>"
					       placeholder="סיסמה"
					       autocomplete="new-password"
					       dir="rtl">
					<button type="button" class="ct-password-toggle" aria-label="הצג/הסתר סיסמה">
						<svg class="ct-eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
							<path d="M1.67 10S4.17 4.17 10 4.17 18.33 10 18.33 10 15.83 15.83 10 15.83 1.67 10 1.67 10z" stroke="#9ca3af" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
							<circle cx="10" cy="10" r="2.5" stroke="#9ca3af" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
					<?php if ( isset( $errors['password'] ) ) : ?>
					<span class="ct-field-error" role="alert"><?php echo esc_html( $errors['password'] ); ?></span>
					<?php endif; ?>
				</div>

				<label class="ct-checkbox-label">
					<input type="checkbox" name="ct_marketing_consent" value="1"
					       <?php checked( ! empty( $_POST['ct_marketing_consent'] ) ); ?>>
					<span>אשמח לקבל עדכונים, טיפים והצעות מקופיטרייל</span>
				</label>

				<?php ct_auth_tos_text( CT_Flow_Registration::get_policy_url( 'terms_free' ), CT_Flow_Registration::get_policy_url( 'privacy' ) ); ?>

				<button type="submit" name="register" class="ct-auth-btn">הסכמה והמשך</button>
			</form>

			<p class="ct-auth-switch">
				<button type="button" class="ct-auth-switch-btn" data-ct-switch-variant="ct-auth-gmail">
					חזרה להרשמה מהירה
				</button>
			</p>

		</div><!-- /#ct-auth-manual -->

	</div><!-- /.ct-auth-page__panel -->

</div><!-- /.ct-auth-page -->

<?php wp_footer(); ?>
</body>
</html>

<?php
/**
 * Output the standard Terms of Service / Privacy paragraph.
 */
function ct_auth_tos_text( $terms_url, $privacy_url ) {
	?>
	<p class="ct-auth-tos" dir="rtl">
		בבחירת <strong>הסכמה והמשך</strong>, אני מסכים/ה ל
		<a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener">תנאי השירות</a>
		של עגלות קפה ול
		<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener">מדיניות הפרטיות</a>.
	</p>
	<?php
}
