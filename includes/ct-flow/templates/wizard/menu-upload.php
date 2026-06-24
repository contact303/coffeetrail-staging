<?php
/**
 * Menu Upload Step
 *
 * User selects image OR PDF upload option.
 * Both zones shown inline; only the selected one is active.
 * All optional.
 *
 * Fields:
 *   menu_type        → 'image' | 'pdf'
 *   menu_image       → attachment ID
 *   menu_pdf         → attachment ID
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d             = $state['data']['menu-upload'] ?? [];
$saved_type    = $d['menu_type']  ?? '';
$saved_img_id  = absint( $d['menu_image'] ?? 0 );
$saved_pdf_id  = absint( $d['menu_pdf']   ?? 0 );
$img_url       = $saved_img_id ? wp_get_attachment_thumb_url( $saved_img_id ) : '';
$pdf_url       = $saved_pdf_id ? wp_get_attachment_url( $saved_pdf_id ) : '';
?>
<div class="ct-step" id="ct-step-menu-upload">

	<h2 class="ct-step__title">איך תרצו להוסיף את התפריט?</h2>
	<p class="ct-step__subtitle">אופציונלי — ניתן לדלג ולהוסיף מאוחר יותר</p>

	<!-- Option: Image -->
	<div class="ct-card-group" style="grid-template-columns:1fr 1fr;margin-bottom:20px;">
		<label class="ct-card-option <?php echo $saved_type === 'image' ? 'ct-card-option--selected' : '' ?>"
			for="ct-menu-type-image">
			<input type="radio"
				id="ct-menu-type-image"
				name="menu_type"
				value="image"
				<?php checked( $saved_type, 'image' ) ?>>
			<span class="ct-card-option__icon">🖼️</span>
			<span class="ct-card-option__title">תמונה</span>
		</label>

		<label class="ct-card-option <?php echo $saved_type === 'pdf' ? 'ct-card-option--selected' : '' ?>"
			for="ct-menu-type-pdf">
			<input type="radio"
				id="ct-menu-type-pdf"
				name="menu_type"
				value="pdf"
				<?php checked( $saved_type, 'pdf' ) ?>>
			<span class="ct-card-option__icon">📄</span>
			<span class="ct-card-option__title">PDF</span>
		</label>
	</div>

	<!-- Image upload zone -->
	<div class="ct-menu-upload-image <?php echo $saved_type !== 'image' ? 'ct-hidden' : '' ?>">
		<div class="ct-upload-zone"
			data-field-key="menu_image"
			aria-label="העלאת תמונת תפריט">

			<input type="file"
				class="ct-upload-input"
				accept="image/*,.heic,.heif"
				aria-label="בחרו תמונת תפריט">

			<?php if ( $img_url ) : ?>
				<div class="ct-upload-preview">
					<img src="<?php echo esc_url( $img_url ) ?>" alt="תמונת תפריט">
					<button type="button" class="ct-upload-remove">&times;</button>
				</div>
				<input type="hidden" name="menu_image" value="<?php echo esc_attr( $saved_img_id ) ?>">
			<?php else : ?>
				<div class="ct-upload-zone__placeholder">
					<span class="ct-upload-zone__icon">🖼️</span>
					<span class="ct-upload-zone__title">גררו תמונה לכאן</span>
					<span class="ct-upload-zone__hint">JPG, PNG, HEIC עד 10MB</span>
				</div>
			<?php endif ?>
		</div>
	</div>

	<!-- PDF upload zone -->
	<div class="ct-menu-upload-pdf <?php echo $saved_type !== 'pdf' ? 'ct-hidden' : '' ?>">
		<div class="ct-upload-zone"
			data-field-key="menu_pdf"
			aria-label="העלאת PDF תפריט">

			<input type="file"
				class="ct-upload-input"
				accept=".pdf,application/pdf"
				aria-label="בחרו קובץ PDF">

			<?php if ( $pdf_url ) : ?>
				<div style="padding:12px;text-align:center;">
					<span style="font-size:24px;">📄</span><br>
					<a href="<?php echo esc_url( $pdf_url ) ?>" target="_blank" style="font-size:13px;">
						צפייה ב-PDF
					</a>
					<button type="button" class="ct-upload-remove" style="display:block;margin:8px auto 0;">&times; הסר</button>
				</div>
				<input type="hidden" name="menu_pdf" value="<?php echo esc_attr( $saved_pdf_id ) ?>">
			<?php else : ?>
				<div class="ct-upload-zone__placeholder">
					<span class="ct-upload-zone__icon">📄</span>
					<span class="ct-upload-zone__title">גררו קובץ PDF לכאן</span>
					<span class="ct-upload-zone__hint">PDF בלבד עד 20MB</span>
				</div>
			<?php endif ?>
		</div>
	</div>

	<p class="ct-field-desc" style="text-align:center;margin-top:16px;">
		אפשר לדלג על שלב זה ולהוסיף תפריט מאוחר יותר
	</p>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
