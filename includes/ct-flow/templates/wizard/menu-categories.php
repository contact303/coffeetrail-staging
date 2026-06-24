<?php
/**
 * Menu Categories Step
 *
 * Multi-select category cards. Maps to existing menu category taxonomy.
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

$saved = (array) ( $state['data']['menu-categories']['menu_categories'] ?? [] );

$categories = [
	'coffee-drinks' => [ 'icon' => '☕', 'label' => 'קפה ומשקאות' ],
	'sandwiches'    => [ 'icon' => '🥪', 'label' => 'כריכים' ],
	'pastries'      => [ 'icon' => '🥐', 'label' => 'מאפים' ],
	'salads'        => [ 'icon' => '🥗', 'label' => 'סלטים' ],
	'desserts'      => [ 'icon' => '🍰', 'label' => 'קינוחים' ],
	'hot-food'      => [ 'icon' => '🍳', 'label' => 'אוכל חם' ],
	'ice-cream'     => [ 'icon' => '🍦', 'label' => 'גלידה' ],
	'smoothies'     => [ 'icon' => '🥤', 'label' => 'שייקים' ],
];
?>
<div class="ct-step" id="ct-step-menu-categories">

	<h2 class="ct-step__title">מה יש בתפריט שלכם?</h2>
	<p class="ct-step__subtitle">כדי שלקוחות יבינו מה מחכה להם אצלכם</p>

	<p class="ct-field-desc">מה אתם מגישים? <span style="color:var(--cw-text-muted);">(אפשר לבחור כמה אפשרויות)</span></p>

	<div class="ct-card-group" style="grid-template-columns:repeat(auto-fill, minmax(130px,1fr));">
		<?php foreach ( $categories as $value => $cat ) : ?>
			<label class="ct-card-option <?php echo in_array( $value, $saved, true ) ? 'ct-card-option--selected' : '' ?>"
				style="padding:14px 10px;">
				<input type="checkbox"
					name="menu_categories[]"
					value="<?php echo esc_attr( $value ) ?>"
					style="position:absolute;opacity:0;width:0;height:0;"
					<?php checked( in_array( $value, $saved, true ) ) ?>>
				<span class="ct-card-option__icon" style="font-size:24px;"><?php echo esc_html( $cat['icon'] ) ?></span>
				<span class="ct-card-option__title" style="font-size:13px;"><?php echo esc_html( $cat['label'] ) ?></span>
			</label>
		<?php endforeach ?>
	</div>

	<p class="ct-field-desc" style="margin-top:8px;">
		💡 אפשר להוסיף תפריט מלא בהמשך
	</p>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
