<?php
/**
 * Payment Step (PRO only)
 *
 * Direct charge via Grow (chargeType=1).
 * Monthly billing active; annual grayed out with "בקרוב" badge (Phase 2).
 * The Grow wallet SDK handles card input; we provide an AJAX bridge.
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 *   @var int    $job_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$price_monthly = 150;  // ILS — shown in UI; real price sourced from WC product in AJAX
$price_annual  = 1500; // ILS — Phase 2 (grayed out)

$next_billing   = date_i18n( 'd בF Y', strtotime( '+1 month' ) );

$grow_configured = class_exists( 'CT_Grow_Payment' ) && CT_Grow_Payment::is_configured();
?>
<div class="ct-step" id="ct-step-payment">

	<h2 class="ct-step__title">העמוד שלכם מוכן לעלות לאוויר</h2>
	<p class="ct-step__subtitle">בחרו מסלול כדי לשדרג את העגלה שלכם בקופיטרייל.</p>

	<div class="ct-payment-layout">

		<!-- -----------------------------------------------------------------------
		     Sidebar: order summary
		     ----------------------------------------------------------------------- -->
		<div class="ct-order-summary">
			<p class="ct-order-summary__title">סיכום הזמנה</p>

			<div class="ct-order-line">
				<span>מסלול PRO — חיוב חודשי</span>
				<strong>₪<?php echo number_format( $price_monthly ) ?></strong>
			</div>
			<div class="ct-order-total">
				<span>סה״כ היום</span>
				<span>₪<?php echo number_format( $price_monthly ) ?></span>
			</div>

			<div class="ct-order-info-box">
				🗓 החיוב הבא יתבצע ב-<?php echo esc_html( $next_billing ) ?>
			</div>

			<ul style="list-style:none;padding:0;margin:14px 0 0;font-size:13px;">
				<li style="margin-bottom:6px;">✅ פרסום מיידי בקופיטרייל</li>
				<li style="margin-bottom:6px;">✅ עדכונים בזמן אמת</li>
				<li>✅ תמיכה ישירה</li>
			</ul>
		</div>

		<!-- -----------------------------------------------------------------------
		     Main: plan selection + payment form
		     ----------------------------------------------------------------------- -->
		<div>

			<!-- Plan selection -->
			<p class="ct-step__section-title">תוכנית חיוב</p>

			<!-- Monthly (active) -->
			<div class="ct-plan-option ct-plan-option--selected" id="ct-plan-monthly">
				<div class="ct-plan-option__header">
					<strong>חיוב חודשי</strong>
					<strong>₪<?php echo number_format( $price_monthly ) ?> / חודש</strong>
				</div>
				<p style="font-size:12px;color:var(--cw-text-muted);margin:4px 0 0;">
					גמישות מלאה עם אפשרות לבטל בכל עת.
				</p>
			</div>

			<!-- Annual (Phase 2 — grayed out) -->
			<div class="ct-plan-option ct-plan-option--disabled" id="ct-plan-annual" aria-disabled="true">
				<div class="ct-plan-option__header">
					<strong>חיוב שנתי</strong>
					<span class="ct-plan-badge ct-plan-badge--soon">בקרוב</span>
				</div>
				<p style="font-size:12px;color:var(--cw-text-muted);margin:4px 0 0;">
					₪<?php echo number_format( $price_annual ) ?> לשנה — מתאים לעגלות שפועלות לאורך השנה.
				</p>
			</div>

			<input type="hidden" name="ct_billing_plan" value="monthly">

			<hr style="border:none;border-top:1px solid #E5E7EB;margin:20px 0;">

			<!-- Payment form / Grow wallet container -->
			<?php if ( $grow_configured ) : ?>
				<p class="ct-step__section-title">פרטי תשלום</p>

			<!-- Loader shown while wallet initialises -->
			<div id="ct-grow-loader" style="display:flex;align-items:center;justify-content:center;height:100px;color:var(--cw-text-muted);font-size:14px;">
				⏳ טוען טופס תשלום...
			</div>

			<!-- Error box shown on init/payment failure -->
			<div id="ct-grow-error" class="ct-info-box" style="display:none;border-color:var(--cw-error);background:var(--cw-error-light);color:var(--cw-error);"></div>

			<div id="ct-grow-wallet-container" style="min-height:220px;display:none;"></div>

			<!-- Pass job_id to the Grow wallet init -->
			<script>
			if (typeof ctGrowData !== 'undefined') {
				ctGrowData.jobId = <?php echo absint( $job_id ) ?>;
			}
			</script>

			<?php else : ?>
				<!-- Dev/staging fallback when Grow is not configured -->
				<div class="ct-info-box" style="text-align:center;">
					<p><strong>💳 טופס תשלום (סביבת פיתוח)</strong></p>
					<p style="font-size:12px;">Grow אינו מוגדר. הוסיפו CT_GROW_API_KEY לקובץ wp-config.php.</p>
					<p style="font-size:12px;margin-top:8px;">ניתן להמשיך לתצוגת Success לצורכי בדיקה.</p>
					<input type="hidden" name="ct_grow_dev_bypass" value="1">
				</div>
			<?php endif ?>

			<!-- T&C inline reminder -->
			<p style="font-size:12px;color:var(--cw-text-muted);margin-top:14px;text-align:center;">
				📋 על ידי לחיצה על "מאשר ועולה לאוויר" אני מאשר/ת את
				<a href="#" style="color:var(--cw-green);">תנאי השימוש</a>.
				<br>העמוד יעלה לאוויר <strong>מיד לאחר התשלום</strong>.
			</p>

		</div><!-- right col -->
	</div><!-- .ct-payment-layout -->

</div>

<?php
$next_label  = 'מאשר/ת ועולה לאוויר';
$next_hidden = true; // Grow wallet drives advancement — wizard Next is invisible on this step.
include CT_FLOW_DIR . '/templates/wizard/footer.php';
