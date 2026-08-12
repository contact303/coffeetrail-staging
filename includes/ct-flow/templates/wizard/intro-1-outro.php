<?php
/**
 * intro-1-outro Template — welcome / live-outro screen
 *
 * Content-only screen shown immediately after the `location` step, which is
 * where the listing auto-publishes (see finalize_listing() in
 * class-wizard-controller.php). This is therefore the first screen a user
 * sees once their listing is already live. It collects no data and closes
 * group 1 of the three-group wizard indicator.
 *
 * Markup per docs/design/step-video-intro.html — a personal welcome-video
 * card. The play button is static markup only; no click-to-play wiring is
 * requested or implemented here.
 *
 * Variables:
 *   @var string $current_step    'intro-1-outro'
 *   @var string $listing_package 'free' | 'pro'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$next_label = 'הבא';
?>
<div class="ct-welcome-intro">
	<span class="ct-welcome-intro__badge">ברכה אישית ממיכל</span>

	<h1 class="ct-welcome-intro__title">עכשיו אתם חלק מקופיטרייל</h1>

	<p class="ct-welcome-intro__description">
		לפני שממשיכים, מיכל רוצה לברך אתכם באופן אישי ולשתף כמה טיפים שיעזרו לכם להפיק יותר מהעמוד שלכם.
	</p>

	<button class="ct-welcome-video" type="button" aria-label="ניגון ברכה אישית ממיכל">
		<span class="ct-welcome-video__overlay"></span>

		<span class="ct-welcome-video__play">
			<svg class="ct-welcome-video__play-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
				<path d="M8 5v14l11-7z"></path>
			</svg>
		</span>

		<span class="ct-welcome-video__speaker">
			<span class="ct-welcome-video__avatar">מ</span>
			<span class="ct-welcome-video__speaker-name">מיכל · מייסדת קופיטרייל</span>
		</span>
	</button>

	<p class="ct-welcome-intro__note">פחות מדקה • אפשר גם לדלג</p>
</div>

<?php
include CT_FLOW_DIR . '/templates/wizard/footer.php';
