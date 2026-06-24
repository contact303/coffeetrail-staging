<?php
/**
 * Success Step — Figma node 1368:182 (free) / 1369:209 (pro)
 *
 * Clean centered completion screen. No header bar (header.php returns early when
 * $current_step === 'success'). No footer — this is a terminal screen.
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package  'free' | 'pro'
 *   @var array  $state
 *   @var int    $job_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_pro      = ( $listing_package === 'pro' );
$listing_url = $job_id ? get_permalink( $job_id ) : home_url( '/' );
$upgrade_url = add_query_arg( 'listing_package', CT_FLOW_PRO_PRODUCT_ID, home_url( '/add-listing/' ) );
?>
<div class="ct-success-screen" id="ct-step-success">

	<!-- Logo badge — matches Figma 64×64 black rounded square -->
	<div class="ct-success-screen__logo">
		<span class="ct-success-screen__logo-emoji" aria-hidden="true">☕</span>
	</div>

	<!-- Headline -->
	<h1 class="ct-success-screen__title">
		<?php echo $is_pro ? 'העמוד שלכם באוויר 🎉' : 'העמוד שלכם מוכן 🎉' ?>
	</h1>

	<!-- Sub-headline -->
	<p class="ct-success-screen__subtitle">
		<?php echo $is_pro
			? 'עמוד ה-Pro שלכם פעיל ומוכן לקבל לקוחות.'
			: 'העמוד שלכם כבר באוויר.' ?>
	</p>

	<!-- Body text -->
	<p class="ct-success-screen__body">
		<?php echo $is_pro
			? 'לקוחות יכולים עכשיו למצוא אתכם, לראות תמונות, לעיין בתפריט ולגלות מה מיוחד בעגלה שלכם.'
			: 'לקוחות יכולים כבר עכשיו למצוא אתכם לפי מיקום ולראות את פרטי העסק שלכם.' ?>
	</p>

	<!-- Muted note -->
	<p class="ct-success-screen__note">
		<?php echo $is_pro
			? 'העמוד שלכם כולל כעת את כל תכונות ה-Pro.'
			: 'אפשר לשדרג ל-Pro בכל שלב ולהוסיף תמונות, תפריט ותכונות נוספות.' ?>
	</p>

	<!-- Primary CTA — teal #10b981 per Figma -->
	<?php if ( $listing_url !== home_url( '/' ) ) : ?>
		<a href="<?php echo esc_url( $listing_url ) ?>"
			class="ct-success-screen__cta"
			aria-label="לעמוד האישי שלי">
			לעמוד האישי שלי
		</a>
	<?php endif ?>

	<!-- Secondary link -->
	<?php if ( $is_pro && $listing_url !== home_url( '/' ) ) : ?>
		<a href="<?php echo esc_url( $listing_url ) ?>"
			target="_blank"
			class="ct-success-screen__link">
			צפו בעמוד כפי שלקוחות רואים אותו
		</a>
	<?php else : ?>
		<a href="<?php echo esc_url( $upgrade_url ) ?>"
			class="ct-success-screen__link">
			שדרוג ל-Pro
		</a>
	<?php endif ?>

</div>
