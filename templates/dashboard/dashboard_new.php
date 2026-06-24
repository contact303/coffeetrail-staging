<?php
/**
 * Dashboard `My Account` page template.
 *
 * @since 2.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

if ( ! \MyListing\Src\User_Roles\user_can_add_listings() ) {
	return require locate_template( 'templates/dashboard/dashboard-alt.php' );
}

// Get logged-in user stats.
$stats = mylisting()->stats()->get_user_stats( get_current_user_id() );
$user_id = get_current_user_id();
$endpoint = wc_get_account_endpoint_url( 'dashboard' );
$active_state = '';
if ( ! empty( $_GET['state_type'] ) && is_super_admin() ) {
	$active_state = sanitize_text_field( $_GET['state_type'] );

	if ( $active_state === 'sitewide' ) {
		$user_id = '';
		// Get logged-in user stats.
		$stats = mylisting()->stats()->get_admin_stats();
	}
}

// Filter dashboard stats by listing.
if ( ! empty( $_GET['listing'] ) && ( $listing = \MyListing\Src\Listing::get( $_GET['listing'] ) ) && $listing->editable_by_current_user() ) {
	return require locate_template( 'templates/dashboard/stats/single-listing.php' );
}

$wrapper_class = 'col-md-9';
if ( is_super_admin() ) {
	$wrapper_class = 'col-md-7';
}
?>

<div class="woocommerce-MyAccount-content">
        <div class="custom-design">
            <div class="lottie-animation" style="width: 300px; height: 300px;"></div>
            <div class="message">
                <h2>ברוכים הבאים לאזור האישי</h2>
                <p>בעתיד יופיעו בעמוד זה סטטיסטיקות אודות העגלה שלכם, כרגע באזור האישי ניתן לעדכן פרטים אודות שעות הפתיחה של העגלה</p>
            </div>
            <a href="https://coffeetrail.co.il/my-account/my-listings/" class="cta-button">מעבר לעריכת שעות הפתיחה</a>
    </div>
</div>

<?php
// Support WooCommerce dashboard hooks.
do_action( 'woocommerce_account_dashboard' );
do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );