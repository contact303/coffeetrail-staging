<?php
/**
 * Cart Type Selection Step
 *
 * Maps to existing `type` taxonomy (slugs: coffee-cart, food-truck).
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_type = $state['data']['cart-type']['cart_type'] ?? '';
$icons_url  = get_stylesheet_directory_uri() . '/assets/images/wizard/icons/';
?>
<div class="ct-step" id="ct-step-cart-type">

	<h2 class="ct-step__title">איזה סוג של עגלה יש לך?</h2>

	<div class="ct-card-group" role="radiogroup" aria-label="סוג העגלה">

		<label class="ct-card-option <?php echo $saved_type === 'coffee-cart' ? 'ct-card-option--selected' : '' ?>"
			for="ct-type-coffee-cart">
			<input type="radio"
				id="ct-type-coffee-cart"
				name="cart_type"
				value="coffee-cart"
				<?php checked( $saved_type, 'coffee-cart' ) ?>>
			<span class="ct-card-option__icon-wrap">
				<img src="<?php echo esc_url( $icons_url . 'icon-coffee-cart.svg' ) ?>"
					alt="" width="30" height="30" class="ct-card-option__icon-img">
			</span>
			<span class="ct-card-option__text">
				<span class="ct-card-option__title">עגלת קפה</span>
				<span class="ct-card-option__desc">קפה, מאפים, כריכים ועוד</span>
			</span>
		</label>

		<label class="ct-card-option <?php echo $saved_type === 'food-truck' ? 'ct-card-option--selected' : '' ?>"
			for="ct-type-food-truck">
			<input type="radio"
				id="ct-type-food-truck"
				name="cart_type"
				value="food-truck"
				<?php checked( $saved_type, 'food-truck' ) ?>>
			<span class="ct-card-option__icon-wrap">
				<img src="<?php echo esc_url( $icons_url . 'icon-food-truck.svg' ) ?>"
					alt="" width="30" height="30" class="ct-card-option__icon-img">
			</span>
			<span class="ct-card-option__text">
				<span class="ct-card-option__title">פוד טראק</span>
				<span class="ct-card-option__desc">אוכל רחוב (בלי קפה)</span>
			</span>
		</label>

	</div>

</div>

<?php
$next_disabled = empty( $saved_type );
include CT_FLOW_DIR . '/templates/wizard/footer.php';
