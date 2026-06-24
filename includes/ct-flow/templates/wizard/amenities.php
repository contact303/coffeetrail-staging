<?php
/**
 * Amenities Step
 *
 * Three grouped checkbox sections mapping to existing taxonomy fields.
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

$saved = (array) ( $state['data']['amenities']['amenities'] ?? [] );

$sections = [
	[
		'title' => 'מה יש בעגלה?',
		'items' => [
			'wifi'          => 'Wi-Fi',
			'power-outlets' => 'שקעי חשמל ללקוחות',
			'fresh-produce' => 'קטיף במקום',
			'rain-shelter'  => 'יש מחסה מהגשם',
		],
	],
	[
		'title' => 'מה יש לידכם?',
		'items' => [
			'parking'          => 'חניה קרובה',
			'disabled-parking' => 'חניה לנכים',
			'restrooms'        => 'שירותים בקרבת מקום',
			'ev-charging'      => 'עמדת טעינה לרכב חשמלי',
			'nearby-shops'     => 'חנויות / אטרקציות קרובות',
		],
	],
	[
		'title' => 'מה מיוחד בלוקיישן?',
		'items' => [
			'park'         => 'פארק',
			'open-view'    => 'נוף פתוח / תצפית',
			'national-park'=> 'גן לאומי / גן שעשועים',
			'near-water'   => 'קרוב לים / נחל / מעיין',
			'farm'         => 'משק חקלאי / משתלה',
		],
	],
];
?>
<div class="ct-step" id="ct-step-amenities">

	<h2 class="ct-step__title">מה יש באזור ומה מייחד אתכם?</h2>
	<p class="ct-step__subtitle">(אפשר לבחור כמה)</p>

	<?php foreach ( $sections as $section ) : ?>
		<div class="ct-checkboxes-section">
			<p class="ct-checkboxes-section__title"><?php echo esc_html( $section['title'] ) ?></p>
			<div class="ct-checkbox-grid">
				<?php foreach ( $section['items'] as $value => $label ) : ?>
					<label class="ct-checkbox-item">
						<input type="checkbox"
							name="amenities[]"
							value="<?php echo esc_attr( $value ) ?>"
							<?php checked( in_array( $value, $saved, true ) ) ?>>
						<span>
							<span class="ct-checkbox-item__label"><?php echo esc_html( $label ) ?></span>
						</span>
					</label>
				<?php endforeach ?>
			</div>
		</div>
	<?php endforeach ?>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
