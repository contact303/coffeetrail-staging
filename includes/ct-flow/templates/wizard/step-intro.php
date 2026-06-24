<?php
/**
 * Step Intro Template (reusable for intro-1, intro-2, intro-3)
 *
 * Two-column layout per Figma (Z8TzfW2y9vAOg3HBlwXtuZ):
 *   - LEFT column : photo (order:-1 in RTL flex)
 *   - RIGHT column: step number, large title, subtitle, optional status badge
 *
 * Variables:
 *   @var string $current_step    'intro-1' | 'intro-2' | 'intro-3'
 *   @var string $listing_package 'free' | 'pro'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$intros = [
	'intro-1' => [
		'step_num'       => 'שלב 1',
		'title'          => 'ספרו לנו על העגלה שלכם',
		'subtitle'       => 'כמה פרטים בסיסיים ואתם כבר על המפה',
		'next_label'     => 'הבא',
		'image'          => get_stylesheet_directory_uri() . '/assets/images/wizard/intro-step1.jpg',
		'image_alt'      => 'כוסות קפה',
		'image_emoji'    => '☕',
		'badge'          => '',
	],
	'intro-2' => [
		'step_num'       => 'שלב 2',
		'title'          => 'משדרגים את העמוד שלכם',
		'subtitle'       => 'מוסיפים תמונות, תפריט ופרטים נוספים על העגלה שלכם.',
		'next_label'     => 'ממשיכים',
		'image'          => get_stylesheet_directory_uri() . '/assets/images/wizard/intro-step2.jpg',
		'image_alt'      => 'בית קפה',
		'image_emoji'    => '🏪',
		'badge'          => true,
		'badge_title'    => '🎉 אתם כבר על המפה',
		'badge_body'     => 'לקוחות יכולים כבר למצוא אתכם.',
	],
	'intro-3' => [
		'step_num'       => 'שלב 3',
		'title'          => 'מסיימים ועולים לאוויר',
		'subtitle'       => 'מגדירים שעות פעילות ומתחילים לקבל לקוחות.',
		'next_label'     => 'הבא',
		'image'          => get_stylesheet_directory_uri() . '/assets/images/wizard/intro-step3.jpg',
		'image_alt'      => 'כוס קפה',
		'image_emoji'    => '🚀',
		'badge'          => true,
		'badge_title'    => '✨ אתם כמעט שם',
		'badge_body'     => 'לקוחות יוכלו למצוא אתכם בקרוב.',
	],
];

$intro      = $intros[ $current_step ] ?? $intros['intro-1'];
$next_label = $intro['next_label'];
// intro-3 inverts the column order: image on RIGHT, text on LEFT (per Figma 1330:83)
$step_num    = str_replace( 'intro-', '', $current_step );
$intro_class = 'ct-intro-screen ct-intro-screen--' . $step_num
             . ( $current_step === 'intro-3' ? ' ct-intro-screen--reversed' : '' );
?>
<div class="<?php echo esc_attr( $intro_class ) ?>">

	<!-- ── Image column (LEFT visually, order:-1 in RTL flex) ── -->
	<div class="ct-intro-screen__image-col">
		<?php $use_card = in_array( $current_step, [ 'intro-2', 'intro-3' ] ); ?>
		<?php if ( $use_card ) : ?><div class="ct-intro-screen__image-card"><?php endif ?>
		<?php
		// Try to load the real photo; fall back to styled placeholder with emoji.
		$img_src = $intro['image'];
		// WordPress file_exists equivalent for child-theme assets
		$img_path = get_stylesheet_directory() . '/assets/images/wizard/intro-step' .
		            ( isset( $intros[ $current_step ] ) ? substr( $current_step, -1 ) : '1' ) . '.jpg';
		if ( file_exists( $img_path ) ) : ?>
			<img src="<?php echo esc_url( $img_src ) ?>"
				alt="<?php echo esc_attr( $intro['image_alt'] ) ?>"
				class="ct-intro-screen__image"
				onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
			<div class="ct-intro-screen__image-placeholder" style="display:none;" aria-hidden="true">
				<?php echo esc_html( $intro['image_emoji'] ) ?>
			</div>
		<?php else : ?>
			<div class="ct-intro-screen__image-placeholder" aria-hidden="true">
				<?php echo esc_html( $intro['image_emoji'] ) ?>
			</div>
		<?php endif ?>
		<?php if ( $use_card ) : ?></div><?php endif ?>
	</div>

	<!-- ── Text column (RIGHT visually) ── -->
	<div class="ct-intro-screen__text-col">

		<?php if ( ! empty( $intro['badge'] ) ) : ?>
			<div class="ct-intro-screen__badge">
				<div>
					<p class="ct-intro-screen__badge-title"><?php echo esc_html( $intro['badge_title'] ) ?></p>
					<p class="ct-intro-screen__badge-body"><?php echo esc_html( $intro['badge_body'] ) ?></p>
				</div>
			</div>
		<?php endif ?>

		<p class="ct-intro-screen__step-label"><?php echo esc_html( $intro['step_num'] ) ?></p>
		<h2 class="ct-intro-screen__title"><?php echo esc_html( $intro['title'] ) ?></h2>
		<p class="ct-intro-screen__subtitle"><?php echo esc_html( $intro['subtitle'] ) ?></p>

	</div>

</div>

<?php
include CT_FLOW_DIR . '/templates/wizard/footer.php';
