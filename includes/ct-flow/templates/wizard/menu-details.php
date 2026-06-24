<?php
/**
 * Menu Details Step
 *
 * Sections: Popular items, Dietary options, Kids options, Special dishes, Kosher.
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

$d                 = $state['data']['menu-details'] ?? [];
$saved_popular     = (array) ( $d['popular_items']    ?? [] );
$popular_other     = esc_attr( $d['popular_other']    ?? '' );
$saved_dietary     = (array) ( $d['dietary_options']  ?? [] );
$saved_kids        = (array) ( $d['kids_options']     ?? [] );
$kids_other        = esc_attr( $d['kids_other']       ?? '' );
$special_dishes    = esc_attr( $d['special_dishes']   ?? '' );
$is_kosher         = $d['is_kosher']                  ?? '';
$saved_kosher_type = (array) ( $d['kosher_type']      ?? [] );
$kosher_cert_id    = absint( $d['kosher_certificate'] ?? 0 );

$popular_items = [
	'khachapuri'  => "חצ'פורי",
	'jachnun'     => "ג'חנון",
	'malawach'    => 'מלאווח',
	'meat-dish'   => 'מנה בשרית',
	'kubaneh'     => 'קובנה',
	'pizza'       => 'פיצה',
	'acai-bowl'   => 'קערת אסאי',
	'keto-coffee' => 'קפה קיטוגני',
];

$kids_options = [
	'toast'     => 'טוסטים',
	'cookies'   => 'עוגיות',
	'choco-balls'=> 'כדורי שוקולד',
	'popsicles' => 'ארטיקים טבעיים',
	'soft-drink'=> 'שתייה מתוקה',
];
?>
<div class="ct-step" id="ct-step-menu-details">

	<h2 class="ct-step__title">ספרו לנו עוד על התפריט שלכם</h2>
	<p class="ct-step__subtitle">זה עוזר ללקוחות להבין מה מחכה להם אצלכם ולבחור להגיע דווקא אליכם</p>

	<!-- Popular items -->
	<div class="ct-checkboxes-section">
		<p class="ct-checkboxes-section__title">מה המנות הפופולריות אצלכם?</p>
		<div class="ct-checkbox-grid">
			<?php foreach ( $popular_items as $value => $label ) : ?>
				<label class="ct-checkbox-item">
					<input type="checkbox"
						name="popular_items[]"
						value="<?php echo esc_attr( $value ) ?>"
						<?php checked( in_array( $value, $saved_popular, true ) ) ?>>
					<span class="ct-checkbox-item__label"><?php echo esc_html( $label ) ?></span>
				</label>
			<?php endforeach ?>
		</div>
		<div class="ct-field-group">
			<label class="ct-field-label" for="ct-popular-other">אחר:</label>
			<input type="text"
				id="ct-popular-other"
				name="popular_other"
				class="ct-field"
				placeholder="פסטה, בורקס, גלידה..."
				value="<?php echo $popular_other ?>">
		</div>
	</div>

	<!-- Dietary -->
	<div class="ct-checkboxes-section">
		<p class="ct-checkboxes-section__title">יש לכם גם?</p>
		<div class="ct-checkbox-group">
			<label class="ct-checkbox-item">
				<input type="checkbox"
					name="dietary_options[]"
					value="vegan"
					<?php checked( in_array( 'vegan', $saved_dietary, true ) ) ?>>
				<span>
					<span class="ct-checkbox-item__label">טבעוני 🌱</span>
					<span class="ct-checkbox-item__desc">מנות ללא כל מוצרי חי</span>
				</span>
			</label>
			<label class="ct-checkbox-item">
				<input type="checkbox"
					name="dietary_options[]"
					value="gluten-free"
					<?php checked( in_array( 'gluten-free', $saved_dietary, true ) ) ?>>
				<span>
					<span class="ct-checkbox-item__label">ללא גלוטן 🌾</span>
					<span class="ct-checkbox-item__desc">מנות מתאימות לרגישים לגלוטן</span>
				</span>
			</label>
		</div>
	</div>

	<!-- Kids -->
	<div class="ct-checkboxes-section">
		<p class="ct-checkboxes-section__title">יש לכם גם אופציות לילדים?</p>
		<div class="ct-checkbox-grid">
			<?php foreach ( $kids_options as $value => $label ) : ?>
				<label class="ct-checkbox-item">
					<input type="checkbox"
						name="kids_options[]"
						value="<?php echo esc_attr( $value ) ?>"
						<?php checked( in_array( $value, $saved_kids, true ) ) ?>>
					<span class="ct-checkbox-item__label"><?php echo esc_html( $label ) ?></span>
				</label>
			<?php endforeach ?>
		</div>
		<div class="ct-field-group">
			<label class="ct-field-label" for="ct-kids-other">אחר:</label>
			<input type="text"
				id="ct-kids-other"
				name="kids_other"
				class="ct-field"
				placeholder="פיצה אישית, מיץ טבעי..."
				value="<?php echo $kids_other ?>">
		</div>
	</div>

	<!-- Special dishes -->
	<div class="ct-checkboxes-section">
		<p class="ct-checkboxes-section__title">מה המנות המיוחדות שלכם?</p>
		<div class="ct-info-box" style="margin-bottom:10px;">
			💡 <strong>למשל:</strong> קרואסון חמאה עם קרם פיסטוק, כריך סלמון עם לימון
		</div>
		<textarea name="special_dishes"
			class="ct-field"
			rows="3"
			placeholder="ספרו בכמה מילים על המנות שאתם הכי גאים בהן..."><?php echo esc_textarea( $d['special_dishes'] ?? '' ) ?></textarea>
		<div class="ct-info-box" style="margin-top:8px;background:#fefce8;border-color:#fde047;">
			✨ ניתן להוסיף תפריט מלא בהמשך
		</div>
	</div>

	<hr style="border:none;border-top:1px solid #E5E7EB;margin:24px 0;">

	<!-- Kosher -->
	<div class="ct-checkboxes-section">
		<p class="ct-checkboxes-section__title">האם העגלה כשרה?</p>
		<div class="ct-card-group" style="grid-template-columns:1fr 1fr;">
			<label class="ct-card-option <?php echo $is_kosher === 'yes' ? 'ct-card-option--selected' : '' ?>"
				for="ct-kosher-yes">
				<input type="radio" id="ct-kosher-yes" name="is_kosher" value="yes" <?php checked( $is_kosher, 'yes' ) ?>>
				<span class="ct-card-option__title">כן ✓</span>
			</label>
			<label class="ct-card-option <?php echo $is_kosher === 'no' ? 'ct-card-option--selected' : '' ?>"
				for="ct-kosher-no">
				<input type="radio" id="ct-kosher-no" name="is_kosher" value="no" <?php checked( $is_kosher, 'no' ) ?>>
				<span class="ct-card-option__title">לא</span>
			</label>
		</div>

		<!-- Kosher details (shown only when is_kosher=yes) -->
		<div class="ct-kosher-details <?php echo $is_kosher !== 'yes' ? 'ct-hidden' : '' ?>">
			<p class="ct-field-desc" style="margin-top:14px;">איזה סוג של כשרות?</p>
			<div class="ct-checkbox-group">
				<?php
				$kosher_types = [ 'rabanut' => 'רבנות', 'mehadrin' => 'מהדרין', 'supervised' => 'בפיקוח' ];
				foreach ( $kosher_types as $val => $lbl ) : ?>
					<label class="ct-checkbox-item">
						<input type="checkbox"
							name="kosher_type[]"
							value="<?php echo esc_attr( $val ) ?>"
							<?php checked( in_array( $val, $saved_kosher_type, true ) ) ?>>
						<span class="ct-checkbox-item__label"><?php echo esc_html( $lbl ) ?></span>
					</label>
				<?php endforeach ?>
			</div>

			<!-- Certificate upload -->
			<div class="ct-upload-field" style="margin-top:12px;">
				<label class="ct-field-label">תעודת כשרות</label>
				<div class="ct-upload-zone" data-field-key="kosher_certificate" aria-label="העלאת תעודת כשרות">
					<input type="file" class="ct-upload-input" accept="image/*,.heic,.heif,.pdf">
					<?php if ( $kosher_cert_id ) :
						$cert_thumb = wp_get_attachment_thumb_url( $kosher_cert_id );
					?>
						<?php if ( $cert_thumb ) : ?>
							<div class="ct-upload-preview">
								<img src="<?php echo esc_url( $cert_thumb ) ?>" alt="תעודת כשרות">
								<button type="button" class="ct-upload-remove">&times;</button>
							</div>
						<?php else : ?>
							<p>📄 קובץ הועלה</p>
						<?php endif ?>
						<input type="hidden" name="kosher_certificate" value="<?php echo esc_attr( $kosher_cert_id ) ?>">
					<?php else : ?>
						<div class="ct-upload-zone__placeholder">
							<span class="ct-upload-zone__icon">📋</span>
							<span class="ct-upload-zone__title">לחץ להעלאה או גררו לכאן</span>
							<span class="ct-upload-zone__hint">PNG, JPG, PDF עד 10MB</span>
						</div>
					<?php endif ?>
				</div>
			</div>
		</div>
	</div>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
