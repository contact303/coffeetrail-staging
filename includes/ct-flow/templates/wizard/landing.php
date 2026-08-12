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

<?php //if ( ! empty( $has_draft ) ) : ?>
	<!--<div class="ct-info-box ct-info-box--green ct-wizard-landing__resume-notice" role="status">
		<strong>יש לכם רישום שלא הושלם.</strong>
		<a href="<?php echo esc_url( add_query_arg( 'ct_resume', '1', $add_listing_url ) ) ?>"
			class="ct-wizard-landing__resume-link">
			המשיכו מאיפה שעצרתם ←
		</a>
	</div>-->
<?php //endif ?>

<section class="ct-wizard-landing" aria-labelledby="ct-wizard-landing-title">

	<div class="ct-wizard-landing__title-col">
		<span class="ct-wizard-landing__eyebrow">בואו נתחיל</span>
		<h1 id="ct-wizard-landing-title" class="ct-wizard-landing__title">
			לעלות עגלה לקופיטרייל זה עניין של כמה דקות
		</h1>
	</div>

	<ol class="ct-wizard-landing__phases-col" aria-label="שלבי העלאת העגלה">
		<li class="ct-wizard-landing__phase">
			<span class="ct-wizard-landing__phase-num" aria-hidden="true">1</span>
			<div class="ct-wizard-landing__phase-texts">
				<h2 class="ct-wizard-landing__phase-title">ספרו לנו על העגלה שלכם</h2>
				<p class="ct-wizard-landing__phase-desc">מוסיפים מיקום ופרטים בסיסיים ואתם כבר מופיעים.</p>
			</div>
			<div class="ct-wizard-landing__phase-media" aria-hidden="true">
				<img src="<?php echo get_stylesheet_directory_uri() ?>/includes/ct-flow/assets/images/drinks.jpeg"
					alt="ספרו לנו על העגלה שלכם"
					width="86"
					height="86">
			</div>
		</li>

		<li class="ct-wizard-landing__phase">
			<span class="ct-wizard-landing__phase-num" aria-hidden="true">2</span>
			<div class="ct-wizard-landing__phase-texts">
				<h2 class="ct-wizard-landing__phase-title">משדרגים את העמוד שלכם</h2>
				<p class="ct-wizard-landing__phase-desc">מוסיפים תמונות, תפריט ופרטים שיעזרו ללקוחות למצוא אתכם בקלות.</p>
			</div>
			<div class="ct-wizard-landing__phase-media" aria-hidden="true">
				<img src="<?php echo get_stylesheet_directory_uri() ?>/includes/ct-flow/assets/images/food.png"
					alt="משדרגים את העמוד שלכם"
					width="86"
					height="86">
			</div>
		</li>

		<li class="ct-wizard-landing__phase">
			<span class="ct-wizard-landing__phase-num" aria-hidden="true">3</span>
			<div class="ct-wizard-landing__phase-texts">
				<h2 class="ct-wizard-landing__phase-title">מסיימים ומשדרגים חשיפה</h2>
				<p class="ct-wizard-landing__phase-desc">מגדירים שעות ומתחילים לקבל לקוחות.</p>
			</div>
			<div class="ct-wizard-landing__phase-media" aria-hidden="true">
				<img src="<?php echo get_stylesheet_directory_uri() ?>/includes/ct-flow/assets/images/map.png"
					alt="מסיימים ומשדרגים חשיפה"
					width="86"
					height="86">
			</div>
		</li>
	</ol>
</section>

<?php
// Footer: "מתחילים" button only, no back button on first step.
$next_label = 'מתחילים';
$prev_step  = null;
include CT_FLOW_DIR . '/templates/wizard/footer.php';