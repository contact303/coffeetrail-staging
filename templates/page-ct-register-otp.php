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
// Email OTP helpers
// ─────────────────────────────────────────────────────────────────────────────

function ct_register_encrypt_value( $value ) {
	if ( ! function_exists( 'openssl_encrypt' ) ) {
		return new WP_Error( 'ct_openssl_missing', 'לא ניתן להתחיל את תהליך האימות כרגע.' );
	}

	$key = hash( 'sha256', wp_salt( 'auth' ), true );
	$iv  = random_bytes( 12 );
	$tag = '';

	$encrypted = openssl_encrypt(
		(string) $value,
		'aes-256-gcm',
		$key,
		OPENSSL_RAW_DATA,
		$iv,
		$tag
	);

	if ( false === $encrypted ) {
		return new WP_Error( 'ct_encrypt_failed', 'לא ניתן להתחיל את תהליך האימות כרגע.' );
	}

	return base64_encode( $iv . $tag . $encrypted );
}

function ct_register_decrypt_value( $payload ) {
	if ( ! function_exists( 'openssl_decrypt' ) ) {
		return '';
	}

	$decoded = base64_decode( (string) $payload, true );
	if ( false === $decoded || strlen( $decoded ) < 29 ) {
		return '';
	}

	$key       = hash( 'sha256', wp_salt( 'auth' ), true );
	$iv        = substr( $decoded, 0, 12 );
	$tag       = substr( $decoded, 12, 16 );
	$encrypted = substr( $decoded, 28 );

	$decrypted = openssl_decrypt(
		$encrypted,
		'aes-256-gcm',
		$key,
		OPENSSL_RAW_DATA,
		$iv,
		$tag
	);

	return false === $decrypted ? '' : $decrypted;
}

function ct_register_get_otp_key( $token ) {
	return 'ct_register_otp_' . hash( 'sha256', (string) $token );
}

function ct_register_send_otp_email( $email, $code ) {
	$subject       = sprintf( 'קוד האימות שלך ל-%s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$template_path = locate_template( 'templates/emails/ct-register-otp.php', false, false );

	if ( $template_path ) {
		$otp_code  = $code;
		$otp_email = $email;

		ob_start();
		include $template_path;
		$message = ob_get_clean();
	} else {
		$message = sprintf(
			'<div dir="rtl" style="font-family:Arial,sans-serif"><h2>אימות כתובת האימייל</h2><p>קוד האימות שלך הוא:</p><p style="font-size:32px;font-weight:700;letter-spacing:8px;direction:ltr;text-align:center">%s</p><p>הקוד תקף למשך 10 דקות.</p></div>',
			esc_html( $code )
		);
	}

	return wp_mail(
		$email,
		$subject,
		$message,
		[
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' <' . get_option( 'admin_email' ) . '>',
		]
	);
}

// ─────────────────────────────────────────────────────────────────────────────
// Registration + OTP processing (self-POST)
// ─────────────────────────────────────────────────────────────────────────────

$errors     = [];
$old        = [];
$tab        = sanitize_key( $_GET['tab'] ?? 'gmail' );
$otp_stage  = false;
$otp_token  = '';
$otp_email  = '';
$otp_notice = '';

if ( ! empty( $_POST['ct_register_nonce'] ) ) {
	$nonce_valid = wp_verify_nonce(
		sanitize_text_field( wp_unslash( $_POST['ct_register_nonce'] ) ),
		'ct_register'
	);

	if ( ! $nonce_valid ) {
		$errors['_form'] = 'בקשה לא תקינה. אנא רעננו את הדף ונסו שוב.';
	} else {
		$action = sanitize_key( $_POST['ct_register_action'] ?? 'request_otp' );

		// ── Resend OTP without losing the pending registration data ────────────
		if ( 'resend_otp' === $action ) {
			$otp_stage = true;
			$otp_token = sanitize_text_field( wp_unslash( $_POST['ct_otp_token'] ?? '' ) );
			$otp_key   = ct_register_get_otp_key( $otp_token );
			$pending   = get_transient( $otp_key );

			if ( ! is_array( $pending ) || empty( $pending['email'] ) ) {
				$errors['_form'] = 'תהליך האימות פג. יש להתחיל את ההרשמה מחדש.';
				$otp_stage = false;
			} else {
				$otp_email      = sanitize_email( $pending['email'] );
				$resend_count   = (int) ( $pending['resend_count'] ?? 0 );
				$next_resend_at = (int) ( $pending['next_resend_at'] ?? 0 );

				if ( $resend_count >= 3 ) {
					$errors['_form'] = 'הגעתם למספר המרבי של שליחות חוזרות. יש להתחיל את ההרשמה מחדש.';
				} elseif ( time() < $next_resend_at ) {
					$errors['_form'] = 'יש להמתין לפני שליחת קוד נוסף.';
				} else {
					$new_code                  = (string) random_int( 100000, 999999 );
					$pending['otp_hash']        = wp_hash_password( $new_code );
					$pending['attempts']        = 0;
					$pending['resend_count']    = $resend_count + 1;
					$pending['next_resend_at']  = time() + 30;

					if ( ct_register_send_otp_email( $otp_email, $new_code ) ) {
						set_transient( $otp_key, $pending, 10 * MINUTE_IN_SECONDS );
						$otp_notice = 'קוד אימות חדש נשלח לאימייל.';
					} else {
						$errors['_form'] = 'שליחת הקוד נכשלה. יש לנסות שוב בעוד מספר דקות.';
					}
				}
			}
		}

		// ── Step 2: Verify OTP and only then create the customer ───────────────
		if ( 'verify_otp' === $action ) {
			$otp_stage = true;
			$otp_token = sanitize_text_field( wp_unslash( $_POST['ct_otp_token'] ?? '' ) );
			$otp_code  = preg_replace( '/\D+/', '', wp_unslash( $_POST['ct_otp_code'] ?? '' ) );
			$otp_key   = ct_register_get_otp_key( $otp_token );
			$pending   = get_transient( $otp_key );

			if ( ! is_array( $pending ) || empty( $pending['email'] ) ) {
				$errors['_form'] = 'תהליך האימות פג. יש לחזור לטופס ההרשמה ולנסות שוב.';
				$otp_stage = false;
			} else {
				$otp_email = sanitize_email( $pending['email'] );

				if ( empty( $otp_code ) || 6 !== strlen( $otp_code ) ) {
					$errors['otp'] = 'יש להזין קוד בן 6 ספרות.';
				} elseif ( (int) ( $pending['attempts'] ?? 0 ) >= 5 ) {
					delete_transient( $otp_key );
					$errors['_form'] = 'בוצעו יותר מדי ניסיונות. יש להתחיל את ההרשמה מחדש.';
					$otp_stage = false;
				} elseif ( ! wp_check_password( $otp_code, $pending['otp_hash'] ) ) {
					$pending['attempts'] = (int) ( $pending['attempts'] ?? 0 ) + 1;
					set_transient( $otp_key, $pending, 10 * MINUTE_IN_SECONDS );
					$errors['otp'] = 'קוד האימות שהוזן אינו תקין.';
				} elseif ( email_exists( $otp_email ) ) {
					delete_transient( $otp_key );
					$errors['_form'] = 'כתובת האימייל הזו כבר רשומה. יש להתחבר לחשבון הקיים.';
					$otp_stage = false;
				} else {
					$password = ct_register_decrypt_value( $pending['password'] ?? '' );

					if ( empty( $password ) ) {
						delete_transient( $otp_key );

						$errors['_form'] = 'תהליך ההרשמה פג. יש להתחיל מחדש.';
						$otp_stage = false;
					} else {
						/**
						 * MyListing/WooCommerce compatibility.
						 * The values come from the server-side OTP transient,
						 * not from user-controlled hidden fields.
						 */
						$_POST['email']      = $otp_email;
						$_POST['user_email'] = $otp_email;
						$_POST['username']   = $otp_email;

						$new_customer_id = wc_create_new_customer(
							$otp_email,
							'',
							$password,
							[
								'first_name' => sanitize_text_field(
									$pending['first_name'] ?? ''
								),
								'last_name' => sanitize_text_field(
									$pending['last_name'] ?? ''
								),
							]
						);

						if ( is_wp_error( $new_customer_id ) ) {
							$errors['_form'] = $new_customer_id->get_error_message();

							if ( function_exists( 'wc_clear_notices' ) ) {
								wc_clear_notices();
							}
						} else {
							delete_transient( $otp_key );

							$marketing = ! empty( $pending['marketing'] );
							$pkg_id    = absint( $pending['pkg_id'] ?? 0 );
							$redirect  = wp_validate_redirect(
								esc_url_raw( $pending['redirect'] ?? '' ),
								home_url( '/' )
							);

							update_user_meta( $new_customer_id, '_ct_marketing_consent', $marketing ? 1 : 0 );
							update_user_meta( $new_customer_id, '_ct_marketing_consent_date', current_time( 'timestamp' ) );

							if ( $pkg_id ) {
								$plan = ( defined( 'CT_FLOW_PRO_PRODUCT_ID' ) && $pkg_id === CT_FLOW_PRO_PRODUCT_ID ) ? 'pro' : 'free';
								update_user_meta( $new_customer_id, '_ct_registered_plan', $plan );
							}

							// wc_create_new_customer() already fires woocommerce_created_customer.
							wc_set_customer_auth_cookie( $new_customer_id );
							wp_safe_redirect( $redirect );
							exit;
						}
					}
				}
			}
		}

		// ── Step 1: Validate registration form and send OTP ───────────────────
		if ( 'request_otp' === $action ) {
			$form_type  = sanitize_key( $_POST['ct_form_type'] ?? 'manual' );
			$tab        = ( 'gmail_email' === $form_type ) ? 'gmail' : 'manual';
			$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
			$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
			$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
			$password   = wp_unslash( $_POST['password'] ?? '' );
			$pkg_id     = absint( $_POST['ct_listing_package'] ?? 0 );
			$redirect   = wp_validate_redirect(
				esc_url_raw( wp_unslash( $_POST['redirect'] ?? '' ) ),
				home_url( '/' )
			);
			$marketing  = ! empty( $_POST['ct_marketing_consent'] );
			$old        = compact( 'first_name', 'last_name', 'email' );

			if ( 'manual' === $form_type ) {
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
				if ( ! is_email( $email ) ) {
					$errors['email'] = 'יש להזין כתובת אימייל תקינה';
				}
				$password = wp_generate_password( 20, true, true );
			}

			if ( empty( $errors ) && email_exists( $email ) ) {
				$errors['email'] = 'כתובת האימייל הזו כבר רשומה. <a href="' . esc_url( wc_get_account_endpoint_url( 'dashboard' ) ) . '">כניסה לחשבון קיים</a>';
			}

			$rate_key = 'ct_register_otp_rate_' . md5( strtolower( $email ) );
			if ( empty( $errors ) && get_transient( $rate_key ) ) {
				$errors['_form'] = 'קוד כבר נשלח לאימייל הזה. יש להמתין כדקה לפני ניסיון נוסף.';
			}

			if ( empty( $errors ) ) {
				$encrypted_password = ct_register_encrypt_value( $password );

				if ( is_wp_error( $encrypted_password ) ) {
					$errors['_form'] = $encrypted_password->get_error_message();
				} else {
					$otp_code  = (string) random_int( 100000, 999999 );
					$otp_token = bin2hex( random_bytes( 32 ) );
					$otp_key   = ct_register_get_otp_key( $otp_token );

					$pending = [
						'email'      => $email,
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'password'   => $encrypted_password,
						'pkg_id'     => $pkg_id,
						'redirect'   => $redirect,
						'marketing'  => $marketing ? 1 : 0,
						'otp_hash'   => wp_hash_password( $otp_code ),
						'attempts'        => 0,
						'resend_count'    => 0,
						'next_resend_at'  => time() + 30,
					];

					set_transient( $otp_key, $pending, 10 * MINUTE_IN_SECONDS );
					set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

					if ( ct_register_send_otp_email( $email, $otp_code ) ) {
						$otp_stage = true;
						$otp_email = $email;
					} else {
						delete_transient( $otp_key );
						$errors['_form'] = 'שליחת קוד האימות נכשלה. יש לנסות שוב בעוד מספר דקות.';
					}
				}
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

$login_url = wp_login_url( $redirect_url );

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
	<div class="ct-auth-page__panel<?php echo $otp_stage ? '' : ' login-container'; ?>">
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

		<?php if ( ! empty( $otp_notice ) ) : ?>
		<div class="ct-auth-notice ct-auth-notice--success" role="status">
			<?php echo esc_html( $otp_notice ); ?>
		</div>
		<?php endif; ?>

		<?php if ( $otp_stage ) : ?>
		<div class="ct-auth-variant ct-auth-variant--active" id="ct-auth-otp" aria-hidden="false">
			<h2 class="ct-auth-title">אימות כתובת האימייל</h2>
			<p class="ct-auth-subtitle">
				שלחנו קוד בן 6 ספרות אל
				<strong dir="ltr"><?php echo esc_html( $otp_email ); ?></strong>
			</p>

			<form method="post" action="<?php echo esc_url( $page_url ); ?>" class="ct-auth-form" id="ct-form-otp" novalidate>
				<?php wp_nonce_field( 'ct_register', 'ct_register_nonce' ); ?>
				<input type="hidden" name="ct_register_action" value="verify_otp">
				<input type="hidden" name="ct_otp_token" value="<?php echo esc_attr( $otp_token ); ?>">

				<div class="ct-field-group">
					<input type="text"
					       name="ct_otp_code"
					       id="ct-otp-code"
					       class="<?php echo esc_attr( ct_auth_field_class( 'otp', $errors ) ); ?>"
					       placeholder="קוד אימות"
					       inputmode="numeric"
					       autocomplete="one-time-code"
					       maxlength="6"
					       pattern="[0-9]{6}"
					       dir="ltr"
					       autofocus>
					<?php if ( isset( $errors['otp'] ) ) : ?>
					<span class="ct-field-error" role="alert"><?php echo esc_html( $errors['otp'] ); ?></span>
					<?php endif; ?>
				</div>

				<button type="submit" class="ct-auth-btn">אימות והמשך</button>
			</form>

			<form method="post" action="<?php echo esc_url( $page_url ); ?>" class="ct-otp-resend-form" id="ct-form-otp-resend">
				<?php wp_nonce_field( 'ct_register', 'ct_register_nonce' ); ?>
				<input type="hidden" name="ct_register_action" value="resend_otp">
				<input type="hidden" name="ct_otp_token" value="<?php echo esc_attr( $otp_token ); ?>">
				<button type="submit" class="ct-auth-switch-btn" id="ct-otp-resend-btn" disabled>
					שליחת קוד מחדש <span id="ct-otp-resend-timer">(30)</span>
				</button>
			</form>

			<p class="ct-auth-switch">
				<a class="ct-auth-switch-btn" href="<?php echo esc_url( remove_query_arg( [ 'email' ] ) ); ?>">שינוי כתובת האימייל</a>
			</p>
		</div>
		<?php else : ?>

		<!-- ────────────────────────────────────────────────────
		     GMAIL VARIANT (default)
		     ──────────────────────────────────────────────────── -->
		<div class="ct-auth-variant<?php echo ( $tab !== 'manual' ) ? ' ct-auth-variant--active' : ''; ?>"
		     id="ct-auth-gmail" aria-hidden="<?php echo ( $tab === 'manual' ) ? 'true' : 'false'; ?>">

			<h2 class="ct-auth-title">צרו את החשבון שלכם</h2>
			<p class="ct-auth-subtitle">התחילו את ה-14 ימי ניסיון בחינם, בטלו בכל עת.</p>

			<?php if ( $google_enabled ) : ?>
			<div class="cts-google-signin"></div>
			<?php endif; ?>

			<div class="ct-auth-divider">
				<span><?php echo $google_enabled ? 'או הירשמו באמצעות אימייל' : 'הירשמו באמצעות אימייל'; ?></span>
			</div>

			<form method="post" action="<?php echo esc_url( $page_url ); ?>" class="ct-auth-form" id="ct-form-gmail" novalidate>
				<?php wp_nonce_field( 'ct_register', 'ct_register_nonce' ); ?>
				<input type="hidden" name="ct_register_action" value="request_otp">
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
				<input type="hidden" name="ct_register_action" value="request_otp">
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

		<?php endif; ?>

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
