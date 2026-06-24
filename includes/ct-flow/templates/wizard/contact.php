<?php
/**
 * Contact Step — Two sections: Customer contact + CoffeeTrail admin contact
 *
 * Fields:
 *   phone           → _phone  (visible on listing page)
 *   whatsapp        → _whatsapp
 *   ct_admin_phone  → _ct_admin_phone (internal, not shown to public)
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d            = $state['data']['contact'] ?? [];
$saved_phone  = (string) ( $d['phone']          ?? '' );
$saved_admin  = (string) ( $d['ct_admin_phone'] ?? '' );

if ( ! function_exists( 'ct_phone_prefix_field' ) ) {
	/**
	 * Render a tel input with an Israeli dialing-prefix dropdown.
	 *
	 * The dropdown carries no name, so collectStepFields() skips it; ct-wizard.js
	 * combines the selected prefix with the local digits into the input's value
	 * before saving (and PHP pre-splits the saved value here for first paint).
	 *
	 * @param string $name        Field name (phone/whatsapp/ct_admin_phone).
	 * @param string $id          Input id.
	 * @param string $full_value  Saved combined value (e.g. "0501234567").
	 * @param array  $attrs       Extra flags (e.g. ['required' => true]).
	 */
	function ct_phone_prefix_field( $name, $id, $full_value, $attrs = [] ) {
		$prefixes = [ '050', '051', '052', '053', '054', '055', '058', '02', '03', '04', '08', '09' ];
		$digits   = preg_replace( '/\D/', '', (string) $full_value );
		$selected = '';
		foreach ( $prefixes as $p ) {
			if ( '' !== $digits && 0 === strpos( $digits, $p ) && strlen( $p ) > strlen( $selected ) ) {
				$selected = $p;
			}
		}
		if ( '' === $selected ) {
			$selected = '050';
		}
		$local = ( '' !== $digits && 0 === strpos( $digits, $selected ) )
			? substr( $digits, strlen( $selected ) )
			: '';
		$is_required   = ! empty( $attrs['required'] );
		$required      = $is_required ? ' required' : '';
		// ct-required on the wrapper lets ct-wizard.js mark it red when left empty
		// (symmetric to the green valid state), mirroring the text-field behaviour.
		$wrapper_class = 'ct-phone-field' . ( $is_required ? ' ct-required' : '' );
		?>
		<div class="<?php echo esc_attr( $wrapper_class ) ?>">
			<select class="ct-phone-prefix-select" aria-label="קידומת">
				<?php foreach ( $prefixes as $p ) : ?>
					<option value="<?php echo esc_attr( $p ) ?>" <?php selected( $selected, $p ) ?>><?php echo esc_html( $p ) ?></option>
				<?php endforeach ?>
			</select>
			<input type="tel"
				id="<?php echo esc_attr( $id ) ?>"
				name="<?php echo esc_attr( $name ) ?>"
				class="ct-phone-input"
				placeholder="XXX-XXXX"
				value="<?php echo esc_attr( $local ) ?>"
				maxlength="9"
				inputmode="tel"
				autocomplete="tel"<?php echo $required ?>>
		</div>
		<?php
	}
}
?>
<div class="ct-step" id="ct-step-contact">

	<h2 class="ct-step__title">איך אפשר ליצור איתכם קשר?</h2>
	<p class="ct-step__subtitle">(אפשר לבחור כמה)</p>

	<!-- -----------------------------------------------------------------------
	     Section 1: Customer-facing contact
	     ----------------------------------------------------------------------- -->
	<div class="ct-step__section">
		<h3 class="ct-step__section-title">👥 לקוחות</h3>
		<p class="ct-field-desc">כך לקוחות יוכלו ליצור קשר דרך העמוד שלכם.</p>

		<!-- Phone -->
		<div class="ct-field-group">
			<label class="ct-field-label" for="ct-phone">טלפון</label>
			<?php ct_phone_prefix_field( 'phone', 'ct-phone', $d['phone'] ?? '' ); ?>
		</div>

		<!-- WhatsApp -->
		<div class="ct-field-group">
			<label class="ct-field-label" for="ct-whatsapp">WhatsApp</label>
			<?php ct_phone_prefix_field( 'whatsapp', 'ct-whatsapp', $d['whatsapp'] ?? '' ); ?>
		</div>
	</div>

	<hr style="border:none;border-top:1px solid #E5E7EB;margin:24px 0;">

	<!-- -----------------------------------------------------------------------
	     Section 2: CoffeeTrail admin contact (internal)
	     ----------------------------------------------------------------------- -->
	<div class="ct-step__section">
		<h3 class="ct-step__section-title">📋 יצירת קשר מול קופיטרייל</h3>
		<p class="ct-field-desc">המספר הזה ישמש אותנו לעדכונים ותמיכה בלבד. הוא לא יוצג ללקוחות.</p>

		<div class="ct-field-group">
			<label class="ct-field-label ct-field-label--required" for="ct-admin-phone">
				מספר ליצירת קשר
			</label>
			<?php ct_phone_prefix_field( 'ct_admin_phone', 'ct-admin-phone', $d['ct_admin_phone'] ?? '', [ 'required' => true ] ); ?>
		</div>

		<!-- Autofill checkbox -->
		<label class="ct-checkbox-item" style="margin-top:-8px;">
			<input type="checkbox"
				id="ct-autofill-admin-phone"
				value="1"
				<?php checked( ! empty( $saved_phone ) && $saved_phone === $saved_admin ) ?>>
			<span class="ct-checkbox-item__label">מלא אוטומטית מהמספר שלמעלה</span>
		</label>
	</div>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
