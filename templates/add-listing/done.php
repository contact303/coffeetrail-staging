<?php
/**
 * Add-listing done/thank-you template -- CoffeeTrail child theme override.
 *
 * Displays Hebrew thank-you messages with plan-aware and status-aware copy.
 * For new listings awaiting admin approval: "הרישום בדרך!" style message.
 * For published listings: direct link to the listing page.
 *
 * IMPORTANT: Copy this file to my-listing-child/templates/add-listing/done.php
 * for the override to take effect.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_switch = isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'switch';
$is_relist = isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'relist';

$listing_status = $listing ? $listing->get_status() : 'pending';
$listing_title  = $listing ? esc_html( $listing->get_title() ) : '';
$listing_link   = $listing ? esc_url( $listing->get_link() ) : '';

$dashboard_url = wc_get_account_endpoint_url( 'my-listings' );
?>

<div class="container c27-listing-submitted-notice ct-done-notice">
	<div class="row">
		<div class="col-md-10 col-md-push-1">
			<div class="element submit-l-message">
				<div class="pf-head">
					<div class="title-style-1">
						<h5>
							<i class="material-icons">check_circle_outline</i>

							<?php if ( $is_switch ) : ?>
								<?php if ( $listing_status === 'publish' ) : ?>
									המסלול עבור <strong><?php echo $listing_title ?></strong> עודכן בהצלחה.
								<?php elseif ( $listing_status === 'pending' ) : ?>
									המסלול עבור <strong><?php echo $listing_title ?></strong> עודכן ויופיע לאחר אישור.
								<?php endif ?>

							<?php elseif ( $is_relist ) : ?>
								<?php if ( $listing_status === 'publish' ) : ?>
									העגלה פורסמה מחדש! <a href="<?php echo $listing_link ?>">לצפייה בדף</a>
								<?php elseif ( $listing_status === 'pending' ) : ?>
									העגלה הוגשה מחדש ותופיע לאחר אישור.
								<?php else : ?>
									העגלה נשמרה כטיוטה.
								<?php endif ?>

							<?php else : // Standard new listing flow ?>
								<?php if ( $listing_status === 'publish' ) : ?>
									<span class="ct-done-headline">העגלה פורסמה בהצלחה! 🎉</span>
									<span class="ct-done-sub">
										הדף שלכם פעיל.
										<a href="<?php echo $listing_link ?>" class="ct-done-view-link">לצפייה בדף &rarr;</a>
									</span>
								<?php elseif ( $listing_status === 'pending' ) : ?>
									<span class="ct-done-headline">העגלה בדרך!</span>
									<span class="ct-done-sub">
										הפרטים שלכם התקבלו ועוברים אישור על ידי הצוות שלנו.
										נחזור אליכם בקרוב בדוא"ל.
									</span>
								<?php elseif ( $listing_status === 'draft' ) : ?>
									<span class="ct-done-headline">הטיוטה נשמרה.</span>
									<span class="ct-done-sub">תוכלו להמשיך ולהשלים את הפרטים מלוח הבקרה.</span>
								<?php endif ?>
							<?php endif ?>

						</h5>
					</div>
				</div>

				<div class="ct-done-actions">
					<a href="<?php echo esc_url( $dashboard_url ) ?>" class="buttons button-5">
						<i class="mi dashboard"></i>
						ללוח הבקרה שלי
					</a>
				</div>

			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	// Prevent form resubmission on browser back.
	if ( window.history.replaceState ) {
		window.history.replaceState( null, null, window.location.href );
	}
</script>
