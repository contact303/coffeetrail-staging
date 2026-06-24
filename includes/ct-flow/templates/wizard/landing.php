<?php
/**
 * Wizard Landing Template — "All Phases Overview" screen
 *
 * Matches Figma node 1272:803 (Z8TzfW2y9vAOg3HBlwXtuZ):
 *   - Title column (RIGHT in RTL): large headline "לעלות עגלה לקופיטרייל זה עניין של כמה דקות"
 *   - Phases list (LEFT in RTL): numbered steps with green icon, title, description
 *   - Footer: "מתחילים" green button on left, no back button
 *
 * Variables:
 *   @var string $listing_package  'free' | 'pro'
 *   @var bool   $has_draft        Whether the user has a resumable draft.
 *   @var array  $state            Full wizard state.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$add_listing_url = home_url( '/add-listing/' );
?>

<?php if ( ! empty( $has_draft ) ) : ?>
	<div class="ct-info-box ct-info-box--green" style="margin:0 0 20px;padding:12px 16px;background:var(--cw-green-bg);border-radius:10px;font-size:14px;">
		<strong>יש לכם רישום שלא הושלם.</strong>
		<a href="<?php echo esc_url( add_query_arg( 'ct_resume', '1', $add_listing_url ) ) ?>"
			style="color:var(--cw-green);font-weight:600;margin-right:8px;">
			המשיכו מאיפה שעצרתם ←
		</a>
	</div>
<?php endif ?>

<div class="ct-wizard-landing">

	<!-- ── Title column (RIGHT in RTL, first in DOM) ── -->
	<div class="ct-wizard-landing__title-col">
		<h1 class="ct-wizard-landing__title">
			לעלות עגלה לקופיטרייל זה עניין של כמה דקות
		</h1>
		<?php if ( $listing_package === 'pro' ) : ?>
			<p style="margin-top:16px;font-size:14px;color:var(--cw-text-muted);">
				נבחר מסלול <strong>PRO</strong> — תשלום יבוצע בסוף התהליך.
			</p>
		<?php endif ?>
	</div>

	<!-- ── Phases list (LEFT in RTL) ── -->
	<div class="ct-wizard-landing__phases-col">

	<!--
		DOM order per RTL flex: number (RIGHT) → texts (MIDDLE) → icon (LEFT).
		Figma 1272:803: [icon left] [text middle] [number right].
	-->
	<div class="ct-wizard-landing__phase">
		<span class="ct-wizard-landing__phase-num">1</span>
		<div class="ct-wizard-landing__phase-texts">
			<h3 class="ct-wizard-landing__phase-title">ספרו לנו על העגלה שלכם</h3>
			<p class="ct-wizard-landing__phase-desc">מוסיפים מיקום ופרטים בסיסיים ואתם כבר מופיעים</p>
		</div>
		<div class="ct-wizard-landing__phase-icon">📍</div>
	</div>

	<div class="ct-wizard-landing__phase">
		<span class="ct-wizard-landing__phase-num">2</span>
		<div class="ct-wizard-landing__phase-texts">
			<h3 class="ct-wizard-landing__phase-title">משדרגים את העמוד שלכם</h3>
			<p class="ct-wizard-landing__phase-desc">מוסיפים תמונות, תפריט ופרטים שיעזרו ללקוחות למצוא אתכם בקלות</p>
		</div>
		<div class="ct-wizard-landing__phase-icon">✨</div>
	</div>

	<div class="ct-wizard-landing__phase">
		<span class="ct-wizard-landing__phase-num">3</span>
		<div class="ct-wizard-landing__phase-texts">
			<h3 class="ct-wizard-landing__phase-title">מסיימים ומשדרגים חשיפה</h3>
			<p class="ct-wizard-landing__phase-desc">מגדירים שעות ומתחילים לקבל לקוחות</p>
		</div>
		<div class="ct-wizard-landing__phase-icon">🚀</div>
	</div>

	</div>

</div>

<?php
// Footer: "מתחילים" button only, no back button on first step.
$next_label = 'מתחילים';
$prev_step  = null;
include CT_FLOW_DIR . '/templates/wizard/footer.php';
