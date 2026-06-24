<?php
/**
 * Basics Step — Cart Name + Logo Upload
 *
 * Fields:
 *   job_title  → post_title
 *   _job_logo  → attachment ID (uploaded via AJAX, crop 1:1)
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_title     = $state['data']['basics']['job_title'] ?? '';
$saved_logo_id   = absint( $state['data']['basics']['job_logo'] ?? 0 );
$logo_url        = $saved_logo_id ? wp_get_attachment_thumb_url( $saved_logo_id ) : '';
// Cart type moved onto this page (was its own step). Fall back to the old
// step's data key for any in-progress draft saved before the merge.
$saved_cart_type = $state['data']['basics']['cart_type']
	?? ( $state['data']['cart-type']['cart_type'] ?? '' );
?>
<div class="ct-step" id="ct-step-basics">

	<h2 class="ct-step__title">בוא נתחיל מהבסיס</h2>
	<p class="ct-step__subtitle">סוג העסק, שם העגלה ולוגו</p>

	<!-- Business type -->
	<div class="ct-field-group">
		<label class="ct-field-label ct-field-label--required">איזה סוג עסק יש לכם?</label>
		<div class="ct-radio-rows" role="radiogroup" aria-label="סוג העגלה">
			<label class="ct-checkbox-item">
				<input type="radio" name="cart_type" value="coffee-cart"
					<?php checked( $saved_cart_type, 'coffee-cart' ) ?>>
				<span class="ct-checkbox-item__label">עגלת קפה</span>
			</label>
			<label class="ct-checkbox-item">
				<input type="radio" name="cart_type" value="food-truck"
					<?php checked( $saved_cart_type, 'food-truck' ) ?>>
				<span class="ct-checkbox-item__label">פוד טראק</span>
			</label>
		</div>
	</div>

	<!-- Cart name -->
	<div class="ct-field-group">
		<label class="ct-field-label ct-field-label--required" for="ct-job-title">
			איך קוראים לעגלה שלך?
		</label>
		<input type="text"
			id="ct-job-title"
			name="job_title"
			class="ct-field ct-required"
			placeholder="שם העגלה שלך"
			value="<?php echo esc_attr( $saved_title ) ?>"
			required
			autocomplete="organization"
			maxlength="100">
	</div>

	<!-- Logo upload (crop 1:1) -->
	<div class="ct-field-group">
		<label class="ct-field-label" for="ct-logo-upload">
			לוגו של העגלה שלך
		</label>
		<p class="ct-field-desc">
			תמונה ריבועית מומלצת. גרור לשינוי גודל אחרי ההעלאה.
		</p>

		<div class="ct-upload-zone"
			data-field-key="job_logo"
			data-crop="1"
			data-crop-ratio="1"
			aria-label="העלאת לוגו">

			<input type="file"
				id="ct-logo-upload"
				class="ct-upload-input"
				accept="image/*,.heic,.heif"
				aria-label="בחרו קובץ לוגו">

			<?php if ( $logo_url ) : ?>
				<div class="ct-upload-preview">
					<img src="<?php echo esc_url( $logo_url ) ?>" alt="לוגו">
					<button type="button" class="ct-upload-remove" aria-label="הסר לוגו">&times;</button>
					<input type="hidden" name="job_logo" value="<?php echo esc_attr( $saved_logo_id ) ?>">
				</div>
			<?php endif ?>
			<div class="ct-upload-zone__placeholder"<?php echo $logo_url ? ' style="display:none;"' : '' ?>>
				<span class="ct-upload-zone__icon">🖼️</span>
				<span class="ct-upload-zone__title">העלו את הלוגו של העגלה שלך</span>
				<span class="ct-upload-zone__hint">JPG, PNG, HEIC · עד 3MB · מקסימום 500×500 פיקסל</span>
			</div>
		</div>
	</div>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
