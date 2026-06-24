<?php
/**
 * Images Upload Step
 *
 * Required: 1 cover image + at least 3 gallery images.
 *
 * Fields:
 *   cover_image  → attachment ID (crop 4:3)
 *   gallery[]    → attachment IDs (no crop, async queue)
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d              = $state['data']['images'] ?? [];
$cover_id       = absint( $d['cover_image'] ?? 0 );
$gallery_ids    = array_filter( (array) ( $d['gallery'] ?? [] ) );
$gallery_count  = count( $gallery_ids );
$cover_url      = $cover_id ? wp_get_attachment_image_url( $cover_id, 'medium' ) : '';
$needs_cover    = ! $cover_id;
$needs_gallery  = $gallery_count < 3;
$is_valid       = ! $needs_cover && ! $needs_gallery;
?>
<div class="ct-step" id="ct-step-images">

	<h2 class="ct-step__title">תמונות של העגלה שלכם</h2>
	<p class="ct-step__subtitle">תמונות טובות עוזרות ללקוחות לבחור בכם</p>

	<div class="ct-info-box" style="margin-bottom:20px;">
		📸 יש להעלות תמונות ללא פנים וללא כיתובים
	</div>

	<!-- Cover image (required, crop 4:3) -->
	<div class="ct-upload-field">
		<label class="ct-field-label ct-field-label--required">
			תמונת קאבר ראשית (חובה)
		</label>
		<p class="ct-field-desc">
			זו התמונה שתופיע ראשונה בדף שלכם.
		</p>

		<div class="ct-upload-zone"
			data-field-key="cover_image"
			data-crop="1"
			data-crop-ratio="1.3333"
			aria-label="העלאת תמונת קאבר">

			<input type="file"
				class="ct-upload-input"
				accept="image/*,.heic,.heif"
				aria-label="בחרו תמונת קאבר">

			<?php if ( $cover_url ) : ?>
				<div class="ct-upload-preview" style="width:100%;height:140px;border-radius:6px;overflow:hidden;">
					<img src="<?php echo esc_url( $cover_url ) ?>"
						alt="תמונת קאבר"
						style="width:100%;height:100%;object-fit:cover;">
					<button type="button" class="ct-upload-remove" aria-label="הסר קאבר">&times;</button>
					<input type="hidden" name="cover_image" value="<?php echo esc_attr( $cover_id ) ?>">
				</div>
			<?php endif ?>
			<div class="ct-upload-zone__placeholder"<?php echo $cover_url ? ' style="display:none;"' : '' ?>>
				<span class="ct-upload-zone__icon">🖼️</span>
				<span class="ct-upload-zone__title">העלו תמונה ראשית</span>
				<span class="ct-upload-zone__hint">PNG, JPG, HEIC · עד 3MB</span>
			</div>
		</div>
	</div>

	<!-- Gallery (min 3, max 15, no crop) -->
	<div class="ct-upload-field">
		<label class="ct-field-label ct-field-label--required">
			תמונות נוספות (לפחות 3)
		</label>
		<p class="ct-field-desc">
			מומלץ להוסיף תמונות של העגלה, האוכל והאווירה.
		</p>

		<div class="ct-upload-zone"
			data-field-key="gallery"
			aria-label="העלאת תמונות נוספות">

			<input type="file"
				class="ct-upload-input"
				accept="image/*,.heic,.heif"
				multiple
				aria-label="בחרו תמונות">

			<div class="ct-upload-zone__placeholder">
				<span class="ct-upload-zone__icon">📷</span>
				<span class="ct-upload-zone__title">לחצו להעלאה או גררו לכאן</span>
				<span class="ct-upload-zone__hint">ניתן לבחור מספר תמונות בו-זמנית</span>
			</div>
		</div>

		<!-- Gallery previews container -->
		<div class="ct-upload-previews" id="ct-gallery-previews">
			<?php foreach ( $gallery_ids as $gid ) :
				$gid = absint( $gid );
				$thumb = wp_get_attachment_thumb_url( $gid );
				if ( ! $thumb ) { continue; }
			?>
				<div class="ct-upload-preview">
					<img src="<?php echo esc_url( $thumb ) ?>" alt="תמונה">
					<button type="button" class="ct-upload-remove" aria-label="הסר תמונה">&times;</button>
					<input type="hidden" name="gallery[]" value="<?php echo esc_attr( $gid ) ?>">
				</div>
			<?php endforeach ?>
		</div>

		<!-- Hidden fields already saved -->
		<span class="ct-gallery-counter">
			<?php echo esc_html( $gallery_count ) ?>/15 תמונות הועלו<?php echo $needs_gallery ? ' — נדרשות לפחות 3' : '' ?>
		</span>
	</div>

</div>

<?php
$next_disabled   = ! $is_valid;
$footer_message  = $next_disabled
	? ( $needs_cover
		? 'כדי להמשיך, צריך להוסיף תמונת קאבר ולפחות 3 תמונות נוספות'
		: 'כדי להמשיך, צריך להוסיף עוד ' . ( 3 - $gallery_count ) . ' תמונות נוספות' )
	: '';
include CT_FLOW_DIR . '/templates/wizard/footer.php';
