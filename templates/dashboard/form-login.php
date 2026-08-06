<?php
/**
 * Render the login and registration forms on the My Account page.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'mylisting-auth' );

$auth_css = get_stylesheet_directory() . '/includes/my-account/css/coffeetrail-auth.css';
$auth_js  = get_stylesheet_directory() . '/includes/my-account/js/coffeetrail-auth.js';

wp_enqueue_style(
	'coffeetrail-auth',
	get_stylesheet_directory_uri() . '/includes/my-account/css/coffeetrail-auth.css',
	[],
	file_exists( $auth_css ) ? filemtime( $auth_css ) : null
);

wp_enqueue_script(
	'coffeetrail-auth',
	get_stylesheet_directory_uri() . '/includes/my-account/js/coffeetrail-auth.js',
	[],
	file_exists( $auth_js ) ? filemtime( $auth_js ) : null,
	true
);

do_action( 'woocommerce_before_customer_login_form' );
add_filter( 'mylisting/hide-footer', '__return_true' );

// Login is always the default tab. Keep registration active after a failed submit.
$active_form         = isset( $_POST['register'] ) ? 'register' : 'login';
$registration_open   = get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes';
$custom_logo_id      = get_theme_mod( 'custom_logo' );
$custom_logo_url     = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';
?>

<section class="ct-auth" dir="rtl">
	<div class="ct-auth__inner">
		<a class="ct-auth__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( $custom_logo_url ) : ?>
				<img class="ct-auth__logo" src="<?php echo esc_url( $custom_logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php else : ?>
				<span class="ct-auth__site-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<?php endif; ?>
		</a>

		<div class="ct-auth__card">
			<div class="auth-notices ct-auth__notices"><?php woocommerce_output_all_notices(); ?></div>

			<?php if ( ! empty( $_GET['notice'] ) && 'login-required' === $_GET['notice'] ) : ?>
				<div class="woocommerce-info">
					עליכם להתחבר כדי לבצע פעולה זו.
				</div>
			<?php endif; ?>

			<ul class="login-tabs no-list-style ct-auth__main-tabs" role="tablist">
				<li class="<?php echo 'login' === $active_form ? 'active' : ''; ?>" role="presentation">
					<a href="#" data-form="login" data-auth-title="התחברו לחשבון שלכם" role="tab" aria-selected="<?php echo 'login' === $active_form ? 'true' : 'false'; ?>">
						התחברות
					</a>
				</li>

				<?php if ( $registration_open ) : ?>
					<li class="<?php echo 'register' === $active_form ? 'active' : ''; ?>" role="presentation">
						<a href="#" data-form="register" data-auth-title="צרו חשבון חדש" role="tab" aria-selected="<?php echo 'register' === $active_form ? 'true' : 'false'; ?>">
							הרשמה
						</a>
					</li>
				<?php endif; ?>
			</ul>

			<h1 class="ct-auth__title" data-auth-heading>
				<?php echo 'register' === $active_form ? 'צרו חשבון חדש' : 'התחברו לחשבון שלכם'; ?>
			</h1>

			<div class="ct-auth__social-login">
				<?php do_action( 'woocommerce_after_login_form_fields' ); ?>
			</div>

			<div class="ct-auth__panel sign-in-box form-box login-form-wrap <?php echo 'login' !== $active_form ? 'hide' : ''; ?>" data-auth-form="login">
				<?php require locate_template( 'templates/auth/login-form.php' ); ?>
				<?php c27()->get_partial( 'spinner', [
					'color'   => '#777',
					'classes' => 'center-vh',
					'size'    => 24,
					'width'   => 2.5,
				] ); ?>
			</div>

			<?php if ( $registration_open ) : ?>
				<div class="ct-auth__panel sign-in-box register-form-wrap <?php echo 'register' !== $active_form ? 'hide' : ''; ?>" data-auth-form="register">
					<?php require locate_template( 'templates/auth/register-form.php' ); ?>
					<?php c27()->get_partial( 'spinner', [
						'color'   => '#777',
						'classes' => 'center-vh',
						'size'    => 24,
						'width'   => 2.5,
					] ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $registration_open ) : ?>
			<p class="ct-auth__switch-copy" data-auth-switch-copy="login">
				אין לכם עדיין חשבון?
				<a href="#" data-auth-switch="register">הירשמו</a>
			</p>
			<p class="ct-auth__switch-copy" data-auth-switch-copy="register" <?php echo 'register' !== $active_form ? 'hidden' : ''; ?>>
				כבר יש לכם חשבון?
				<a href="#" data-auth-switch="login">התחברו</a>
			</p>
		<?php endif; ?>
	</div>
</section>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
<?php do_action( 'mylisting/after-auth-forms' ); ?>