<?php
/**
 * Template Name: CT Login
 *
 * Full-screen split-screen login page for the CoffeeTrail listing-owner flow.
 * Mirrors page-ct-register.php — outputs its own <html>…</html> shell with no
 * theme header or footer, and reuses every .ct-auth-* style.
 *
 * GET params read:
 *   redirect   – URL to send the user after a successful login
 *   email      – pre-fills the email field
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Block access for logged-in users — redirect straight on.
if ( is_user_logged_in() ) {
	$redirect = esc_url_raw( $_REQUEST['redirect'] ?? home_url( '/' ) );
	wp_safe_redirect( $redirect );
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Login form processing (self-POST)
// ─────────────────────────────────────────────────────────────────────────────

$errors = [];
$old    = [];

if ( ! empty( $_POST['ct_login_nonce'] ) ) {

	$nonce_valid = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ct_login_nonce'] ) ), 'ct_login' );

	if ( ! $nonce_valid ) {
		$errors['_form'] = 'בקשה לא תקינה. אנא רעננו את הדף ונסו שוב.';
	} else {

		$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$password = (string) wp_unslash( $_POST['password'] ?? '' );
		$remember = ! empty( $_POST['rememberme'] );
		$redirect = esc_url_raw( wp_unslash( $_POST['redirect'] ?? home_url( '/' ) ) );

		$old = compact( 'email' );

		if ( ! is_email( $email ) ) {
			$errors['email'] = 'יש להזין כתובת אימייל תקינה';
		}
		if ( '' === $password ) {
			$errors['password'] = 'יש להזין סיסמה';
		}

		if ( empty( $errors ) ) {
			$user = wp_signon(
				[
					'user_login'    => $email,
					'user_password' => $password,
					'remember'      => $remember,
				],
				is_ssl()
			);

			if ( is_wp_error( $user ) ) {
				// Keep the message generic to avoid leaking which field was wrong.
				$errors['_form'] = 'האימייל או הסיסמה שגויים. אנא נסו שוב.';
			} else {
				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Prepare template variables
// ─────────────────────────────────────────────────────────────────────────────

$redirect_url = esc_url_raw( $_REQUEST['redirect'] ?? home_url( '/' ) );
$pre_email    = sanitize_email( $old['email'] ?? $_GET['email'] ?? '' );

// Mirror the theme's Google::is_enabled() logic (settings may live in c27's
// serialized array rather than individual wp_options entries).
$_google_enabled_opt = get_option( 'mylisting_social_login_google_enabled', null );
$_google_enabled     = is_null( $_google_enabled_opt )
	? (bool) c27()->get_setting( 'social_login_google_enabled' )
	: (bool) $_google_enabled_opt;
$_google_client_opt  = get_option( 'mylisting_social_login_google_client_id', null );
$_google_client_id   = is_null( $_google_client_opt )
	? (string) c27()->get_setting( 'social_login_google_client_id' )
	: (string) $_google_client_opt;
$google_enabled = $_google_enabled && ! empty( $_google_client_id );

// Link back to the custom register page (slug "ct-register") when present.
$register_page = get_page_by_path( 'ct-register' );
$register_url  = ( $register_page instanceof WP_Post )
	? add_query_arg( [ 'redirect' => $redirect_url ], get_permalink( $register_page ) )
	: wp_registration_url();

// Current-page URL for the form action (self-POST keeps GET params intact).
$page_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Helper: add the error class to a field that failed validation.
function ct_login_field_class( $field, $errors ) {
	return 'ct-field' . ( isset( $errors[ $field ] ) ? ' ct-field--error' : '' );
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> — התחברות</title>
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
				ברוכים השבים,<br>
				בואו נמשיך מאיפה שהפסקתם.
			</h1>
			<p class="ct-auth-brand-sub">
				התחברו כדי לנהל את העגלה שלכם
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

		<div class="ct-auth-variant ct-auth-variant--active" id="ct-auth-login">

			<h2 class="ct-auth-title">התחברו לחשבון שלכם</h2>
			<p class="ct-auth-subtitle">שמחים לראות אתכם שוב.</p>

			<form method="post" action="<?php echo esc_url( $page_url ); ?>" class="ct-auth-form" id="ct-form-login" novalidate>
				<?php wp_nonce_field( 'ct_login', 'ct_login_nonce' ); ?>
				<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect_url ); ?>">

				<div class="ct-field-group">
					<input type="email"
					       name="email"
					       id="ct-login-email"
					       class="<?php echo esc_attr( ct_login_field_class( 'email', $errors ) ); ?>"
					       placeholder="אימייל"
					       value="<?php echo esc_attr( $pre_email ); ?>"
					       autocomplete="email"
					       dir="rtl">
					<?php if ( isset( $errors['email'] ) ) : ?>
					<span class="ct-field-error" role="alert"><?php echo esc_html( $errors['email'] ); ?></span>
					<?php endif; ?>
				</div>

				<div class="ct-field-group ct-field-group--password">
					<input type="password"
					       name="password"
					       id="ct-login-password"
					       class="<?php echo esc_attr( ct_login_field_class( 'password', $errors ) ); ?>"
					       placeholder="סיסמה"
					       autocomplete="current-password"
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
					<input type="checkbox" name="rememberme" value="1"
					       <?php checked( ! empty( $_POST['rememberme'] ) ); ?>>
					<span>זכרו אותי</span>
				</label>

				<?php if ( $google_enabled ) : ?>
				<div class="ct-auth-divider">
					<span>או</span>
				</div>
				<div class="cts-google-signin"></div>
				<?php endif; ?>

				<button type="submit" name="login" class="ct-auth-btn">התחברו</button>
			</form>

			<p class="ct-auth-login-prompt">
				אין לכם עדיין חשבון?
				<a href="<?php echo esc_url( $register_url ); ?>" class="ct-auth-login-link">הירשמו</a>
			</p>

		</div><!-- /#ct-auth-login -->

	</div><!-- /.ct-auth-page__panel -->

</div><!-- /.ct-auth-page -->

<?php wp_footer(); ?>
</body>
</html>
