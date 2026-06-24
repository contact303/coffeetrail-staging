<?php
/**
 * Operating Hours Step
 *
 * Option 1: Google Business link (stored, no actual API sync in Phase 1)
 * Option 2: Manual per-day rows with iOS-style toggle + inline time inputs
 *
 * All optional. User can skip and update later.
 *
 * Fields:
 *   ct_google_biz_link        → _ct_google_biz_link
 *   hours[sun][open/close]    → _ct_hours_json / work_hours meta
 *   day_active[sun]           → toggles used during finalization
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d           = $state['data']['hours'] ?? [];
$google_link = esc_attr( $d['ct_google_biz_link'] ?? '' );
$saved_hours = (array) ( $d['hours'] ?? [] );
$saved_active = (array) ( $d['day_active'] ?? [] );

$days = [
	'sun' => "א'",
	'mon' => "ב'",
	'tue' => "ג'",
	'wed' => "ד'",
	'thu' => "ה'",
	'fri' => "ו'",
	'sat' => 'שבת',
];
?>
<div class="ct-step" id="ct-step-hours">

	<div class="ct-hours-banner">
		יותר לקוחות מגיעים כשהשעות הפעילות ברורות
	</div>

	<h2 class="ct-step__title">סנכרון שעות פעילות</h2>
	<p class="ct-step__subtitle">
		אפשר לחבר את שעות הפעילות מגוגל כדי שלא תצטרכו לעדכן אותן פעמיים.
	</p>

	<!-- Google Business sync -->
	<div class="ct-field-group">
		<label class="ct-field-label" for="ct-google-biz-link">
			סנכרון שעות הפעילות מ-Google Business
		</label>
		<input type="url"
			id="ct-google-biz-link"
			name="ct_google_biz_link"
			class="ct-field"
			placeholder="הדביקו את הקישור לעמוד Google Business שלכם"
			value="<?php echo $google_link ?>"
			inputmode="url"
			dir="ltr">
		<p class="ct-field-desc">
			השעות יתעדכנו בקופיטרייל אוטומטית פעם ביום
		</p>
	</div>

	<div class="ct-hours-divider">או הגדירו ידנית</div>

	<!-- Manual hours -->
	<div>
		<p class="ct-step__section-title">שעות פעילות</p>
		<p class="ct-field-desc">
			הפעילו את הימים הרלוונטיים והגדירו שעות פתיחה וסגירה.
		</p>

		<div class="ct-hours-rows">
			<?php foreach ( $days as $key => $label ) :
				$day_data  = $saved_hours[ $key ] ?? [];

				// Determine active state: prefer saved day_active flag,
				// fall back to whether open/close times are set.
				if ( isset( $saved_active[ $key ] ) ) {
					$is_open = $saved_active[ $key ] === '1';
				} else {
					$is_open = ! empty( $day_data['open'] ) || ! empty( $day_data['close'] );
				}

				$open_val  = esc_attr( $day_data['open']  ?? '07:30' );
				$close_val = esc_attr( $day_data['close'] ?? '16:00' );
				$toggle_id = 'ct-hours-toggle-' . esc_attr( $key );
			?>
				<div class="ct-hours-row <?php echo $is_open ? 'is-open' : '' ?>"
					data-day="<?php echo esc_attr( $key ) ?>">

					<span class="ct-hours-row__label"><?php echo esc_html( $label ) ?></span>

					<label class="ct-toggle-switch" for="<?php echo $toggle_id ?>">
						<input type="checkbox"
							id="<?php echo $toggle_id ?>"
							class="ct-hours-toggle"
							data-day="<?php echo esc_attr( $key ) ?>"
							<?php checked( $is_open ) ?>>
						<span class="ct-toggle-slider"></span>
					</label>

					<div class="ct-hours-row__times<?php echo ! $is_open ? ' ct-hidden' : '' ?>">
						<span class="ct-hours-row__time-label">פתיחה</span>
						<div class="ct-hours-row__time-wrap">
							<input type="time"
								name="hours[<?php echo esc_attr( $key ) ?>][open]"
								class="ct-hours-time-input"
								value="<?php echo $open_val ?>">
						</div>
						<span class="ct-hours-row__dash">—</span>
						<span class="ct-hours-row__time-label">סגירה</span>
						<div class="ct-hours-row__time-wrap">
							<input type="time"
								name="hours[<?php echo esc_attr( $key ) ?>][close]"
								class="ct-hours-time-input"
								value="<?php echo $close_val ?>">
						</div>
					</div>

					<span class="ct-hours-row__closed<?php echo $is_open ? ' ct-hidden' : '' ?>">סגור</span>

				</div>
			<?php endforeach ?>
		</div>
	</div>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
