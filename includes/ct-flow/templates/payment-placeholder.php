<?php
/**
 * PRO payment step template — Grow Payment Gateway.
 *
 * Shown at priority 22 (after the listing form at priority 10, before done).
 * Renders the Grow SDK wallet for suspended-charge (pre-auth) collection.
 * When Grow credentials are not yet configured, shows a friendly notice so
 * the rest of the flow still works during development/testing.
 *
 * Variables available:
 *   @var \MyListing\Src\Forms\Add_Listing_Form $form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$job_id     = $form->get_job_id();
$configured = class_exists( 'CT_Grow_Payment' ) && CT_Grow_Payment::is_configured();
$pro_price  = $configured ? CT_Grow_Payment::get_pro_price() : 0.0;
$sdk_url    = class_exists( 'CT_Grow_Payment' ) ? CT_Grow_Payment::sdk_url() : '';
?>

<section class="i-section ct-payment-section" dir="rtl">
	<div class="container">

		<div class="row section-title">
			<h2 class="case27-primary-text">תשלום — מסלול PRO</h2>
		</div>

		<div class="element submit-l-message ct-payment-section-box">

			<?php if ( $configured ) : ?>
				<!-- ============================================================
				     Grow SDK wallet
				     ============================================================ -->

				<div class="ct-payment-header">
					<div class="ct-payment-icon">
						<i class="material-icons">credit_card</i>
					</div>
					<h3 class="ct-payment-title">פרטי תשלום</h3>
					<p class="ct-payment-body">
						נבצע <strong>אישור מראש (pre-authorization)</strong> לכרטיס האשראי שלך
						על סך <strong><?php echo esc_html( number_format( $pro_price, 0 ) ) ?> ₪</strong>.
					</p>
					<p class="ct-payment-body ct-payment-notice-line">
						<i class="material-icons" style="font-size:16px;vertical-align:middle;">info_outline</i>
						<strong>לא יבוצע חיוב בפועל</strong> עד לאישור הרישום על ידי צוות CoffeeTrail.
						נחזור אליכם בדוא"ל תוך 2–3 ימי עסקים.
					</p>
				</div>

				<!-- Loading indicator while AJAX / SDK initialise -->
				<div id="ct-grow-loader" class="ct-grow-loader" style="display:none;">
					<div class="ct-grow-loader-inner">
						<span class="ct-grow-spinner"></span>
						<span>טוען מערכת תשלום...</span>
					</div>
				</div>

				<!-- Error message box -->
				<div id="ct-grow-error" class="ct-grow-error" style="display:none;"></div>

				<!-- Grow SDK renders the wallet inside this element -->
				<div id="ct-grow-wallet-container" class="ct-grow-wallet-container" style="display:none;">
					<!-- growPayment.renderPaymentOptions() targets this container -->
				</div>

				<!-- Hidden continue form — submitted by ct-grow-wallet.js after payment -->
				<form method="post" id="ct-payment-continue-form" style="display:none;">
					<input type="hidden" name="job_manager_form" value="submit-listing">
					<input type="hidden" name="step"             value="<?php echo esc_attr( $form->get_step() ) ?>">
					<?php if ( $job_id ) : ?>
						<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ) ?>">
					<?php endif ?>
					<?php if ( ! empty( $_REQUEST['listing_type'] ) ) : ?>
						<input type="hidden" name="listing_type" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['listing_type'] ) ) ?>">
					<?php endif ?>
					<?php if ( ! empty( $_REQUEST['listing_package'] ) ) : ?>
						<input type="hidden" name="listing_package" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['listing_package'] ) ) ?>">
					<?php endif ?>
					<input type="hidden" name="continue"    value="1">
					<input type="hidden" name="ct_grow_paid" id="ct-grow-paid" value="0">
					<button type="submit" disabled class="buttons button-2" style="margin-top:16px;">
						<i class="fa fa-check"></i>
						המשך לשלב הבא
					</button>
				</form>

				<!--
				     Load the Grow client-side SDK synchronously so growPayment
				     is available when ct-grow-wallet.js initialises.
				     Confirm the exact SDK URL with Grow support / your merchant portal.
				-->
				<script src="<?php echo esc_url( $sdk_url ) ?>" defer></script>

				<!-- Pass job_id to the already-enqueued ct-grow-wallet.js -->
				<script>
					if (window.ctGrowData) {
						ctGrowData.jobId = <?php echo intval( $job_id ) ?>;
					}
				</script>

			<?php else : ?>
				<!-- ============================================================
				     Credentials not yet configured — development placeholder
				     ============================================================ -->

				<div class="ct-payment-icon">
					<i class="material-icons">credit_card</i>
				</div>
				<h3 class="ct-payment-title">תשלום — מסלול PRO</h3>
				<p class="ct-payment-body">
					הפרטים שהזנת נשמרו.
					בשלב זה נבצע <strong>אישור מראש (pre-authorization)</strong> לכרטיס האשראי שלך.
				</p>
				<p class="ct-payment-body">
					<strong>לא יתבצע חיוב בפועל</strong> עד לאישור הרישום על ידי צוות CoffeeTrail.
					נחזור אליכם בדוא"ל תוך 2–3 ימי עסקים.
				</p>

				<div class="ct-payment-placeholder-notice">
					<i class="material-icons">build</i>
					מערכת התשלום (Grow) בתהליך הגדרה — יש להוסיף
					<code>CT_GROW_API_KEY</code>, <code>CT_GROW_USER_ID</code>,
					<code>CT_GROW_PAGE_CODE</code> ל-<code>wp-config.php</code>.
				</div>

				<!-- Pass-through form for development / when credentials missing -->
				<form method="post" id="ct-payment-placeholder-form">
					<div class="hidden">
						<input type="hidden" name="job_manager_form" value="submit-listing">
						<input type="hidden" name="step"             value="<?php echo esc_attr( $form->get_step() ) ?>">
						<?php if ( $job_id ) : ?>
							<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ) ?>">
						<?php endif ?>
						<?php if ( ! empty( $_REQUEST['listing_type'] ) ) : ?>
							<input type="hidden" name="listing_type" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['listing_type'] ) ) ?>">
						<?php endif ?>
						<?php if ( ! empty( $_REQUEST['listing_package'] ) ) : ?>
							<input type="hidden" name="listing_package" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['listing_package'] ) ) ?>">
						<?php endif ?>
						<input type="hidden" name="continue"     value="1">
						<input type="hidden" name="ct_grow_paid" value="0">
					</div>

					<div class="listing-form-submit-btn">
						<button type="submit" class="buttons button-2">
							<i class="fa fa-check"></i>
							אשר ושלח לבדיקה
						</button>
					</div>
				</form>

			<?php endif ?>

		</div><!-- .ct-payment-section-box -->
	</div>
</section>
