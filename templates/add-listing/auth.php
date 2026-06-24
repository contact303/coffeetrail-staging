<?php
/**
 * Add-listing auth notice -- CoffeeTrail child theme override.
 *
 * Replaces the parent's generic "sign in / register" block with Hebrew,
 * plan-aware messaging that links users to the correct registration page.
 *
 * IMPORTANT: Copy this file to my-listing-child/templates/add-listing/auth.php
 * for the override to take effect.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Already logged in -- nothing to show.
if ( is_user_logged_in() ) {
	return;
}

// Determine which plan the user is trying to register for.
$listing_package = ! empty( $_REQUEST['listing_package'] )
	? absint( c27()->get_package_id_for_validation( $_REQUEST['listing_package'] ) )
	: 0;

$is_pro = ( $listing_package === CT_FLOW_PRO_PRODUCT_ID );
$plan_label = $is_pro ? 'PRO' : 'חינמי';

// Build registration URL that redirects back to the add-listing page after signup.
$current_url    = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$register_url   = add_query_arg( 'redirect', rawurlencode( $current_url ), \MyListing\get_register_url() );
$login_url      = add_query_arg( 'redirect', rawurlencode( $current_url ), \MyListing\get_login_url() );
?>

<div class="form-section-wrapper active" id="form-section-auth">
	<div class="element form-section">
		<div class="pf-head round-icon">
			<div class="title-style-1">
				<i class="mi account_circle"></i>
				<h5>כניסה לחשבון</h5>
			</div>
		</div>
		<div class="pf-body">
			<fieldset class="fieldset-login_required ct-auth-notice">
				<div class="ct-auth-notice-inner">
					<p class="ct-auth-title">
						<strong>ההרשמה מיועדת לבעלי עגלות קפה / פוד טראקים בלבד.</strong>
					</p>
					<p class="ct-auth-plan-label">
						מסלול שנבחר: <strong class="ct-plan-badge ct-plan-badge--<?php echo $is_pro ? 'pro' : 'free' ?>"><?php echo esc_html( $plan_label ) ?></strong>
					</p>
					<p class="ct-auth-cta">
						כבר יש לך חשבון?
						<a href="<?php echo esc_url( $login_url ) ?>" class="buttons button-5">
							<i class="mi person"></i>
							כניסה לחשבון
						</a>
					</p>
					<p>
						<a href="<?php echo esc_url( $register_url ) ?>" class="buttons button-2 ct-register-btn">
							<i class="mi person_add"></i>
							הרשמה חדשה &mdash; מסלול <?php echo esc_html( $plan_label ) ?>
						</a>
					</p>
				</div>
			</fieldset>
		</div>
	</div>
</div>
