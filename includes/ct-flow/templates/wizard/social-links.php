<?php
/**
 * Social Links Step
 *
 * Fields: Instagram, Facebook, TikTok, Website.
 * All optional.
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d         = $state['data']['social-links'] ?? [];
$instagram = esc_attr( $d['instagram'] ?? '' );
$facebook  = esc_attr( $d['facebook']  ?? '' );
$tiktok    = esc_attr( $d['tiktok']    ?? '' );
$website   = esc_attr( $d['website']   ?? '' );

$fields = [
	[
		'name'        => 'instagram',
		'label'       => 'Instagram',
		'icon'        => '📸',
		'placeholder' => 'https://instagram.com/שם_העגלה',
		'value'       => $instagram,
	],
	[
		'name'        => 'facebook',
		'label'       => 'Facebook',
		'icon'        => '👥',
		'placeholder' => 'https://facebook.com/שם_העגלה',
		'value'       => $facebook,
	],
	[
		'name'        => 'tiktok',
		'label'       => 'TikTok',
		'icon'        => '🎵',
		'placeholder' => 'https://tiktok.com/@שם_העגלה',
		'value'       => $tiktok,
	],
	[
		'name'        => 'website',
		'label'       => 'אתר אינטרנט',
		'icon'        => '🌐',
		'placeholder' => 'https://www.האתר-שלכם.co.il',
		'value'       => $website,
	],
];
?>
<div class="ct-step" id="ct-step-social-links">

	<h2 class="ct-step__title">איפה עוד אפשר למצוא אתכם?</h2>
	<p class="ct-step__subtitle">הוסיפו קישורים כדי שלקוחות יוכלו להכיר אתכם יותר</p>
	<p class="ct-field-desc">כל השדות אופציונליים</p>

	<?php foreach ( $fields as $field ) : ?>
		<div class="ct-field-group">
			<label class="ct-field-label" for="ct-<?php echo esc_attr( $field['name'] ) ?>">
				<?php echo esc_html( $field['icon'] . ' ' . $field['label'] ) ?>
			</label>
			<input type="url"
				id="ct-<?php echo esc_attr( $field['name'] ) ?>"
				name="<?php echo esc_attr( $field['name'] ) ?>"
				class="ct-field"
				placeholder="<?php echo esc_attr( $field['placeholder'] ) ?>"
				value="<?php echo $field['value'] ?>"
				inputmode="url"
				autocomplete="url"
				dir="ltr">
		</div>
	<?php endforeach ?>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
