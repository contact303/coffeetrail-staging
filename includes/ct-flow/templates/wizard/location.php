<?php
/**
 * Location Step — OSM/Leaflet map + address search
 *
 * Fields collected by the wizard AJAX and stored in $state['data']['location']:
 *   address  → human-readable address (from OSM autocomplete)
 *   lat      → latitude  (decimal, 5 d.p.)
 *   lng      → longitude (decimal, 5 d.p.)
 *   ct_roadside       → checkbox (optional)
 *   ct_location_link  → external map URL (optional)
 *
 * The wizard controller translates lat/lng/address to the MyListing
 * Location_Field format (job_location[0]) when publishing the listing.
 *
 * The OSM/Leaflet map is initialised by initLocationPicker() in ct-wizard.js
 * after the theme's mylisting-openstreetmap script fires.
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d        = $state['data']['location'] ?? [];
$lat      = esc_attr( $d['lat']     ?? '' );
$lng      = esc_attr( $d['lng']     ?? '' );
$address  = esc_attr( $d['address'] ?? '' );
$roadside = ! empty( $d['ct_roadside'] );
$loc_link = esc_attr( $d['ct_location_link'] ?? '' );

// Use the cart logo (from the basics step) as the map pin when available.
$logo_id  = absint( $state['data']['basics']['job_logo'] ?? 0 );
$logo_pin = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';

// Initial map centre: saved draft position if present, else Jerusalem.
// These keys are camelCase because MyListing.Maps.Map.init() reads
// this.options.defaultLat / defaultLng straight off the map element's data-options.
$map_lat = ( $d['lat'] ?? '' ) !== '' ? (float) $d['lat'] : 31.7683;
$map_lng = ( $d['lng'] ?? '' ) !== '' ? (float) $d['lng'] : 35.2137;
$map_options = wp_json_encode( [
	'skin'            => false,
	'cluster_markers' => false,
	'scrollwheel'     => false,
	'draggable'       => true,
	'defaultLat'      => $map_lat,
	'defaultLng'      => $map_lng,
	'zoom'            => 12,
] );
?>
<div class="ct-step" id="ct-step-location">

	<h2 class="ct-step__title">איפה העגלה שלכם נמצאת?</h2>
	<p class="ct-step__subtitle">סמנו את המיקום המדויק כדי שלקוחות ימצאו אתכם</p>

	<!--
		OSM / Leaflet location widget.
		Class names match the theme's openstreetmap.js selectors so its
		Nominatim autocomplete wires up automatically after maps:loaded.
	-->
	<div class="ct-field-group">
		<label class="ct-field-label ct-field-label--required">חיפוש כתובת</label>
		<div class="location-field-wrapper"
			data-options='{"default-lat":31.7683,"default-lng":35.2137,"default-zoom":12}'>

			<!--
				class needs both:
				  .form-location-autocomplete → the global maps:loaded handler attaches
				    MyListing.Maps.Autocomplete (Nominatim) to it.
				  .address-input → the #location-picker-map picker handler binds to this
				    selector for autocomplete:change and writes reverse-geocoded text here.
			-->
			<input type="text"
				name="address"
				class="address-field address-input form-location-autocomplete ct-field ct-required"
				placeholder="חיפוש כתובת, עיר או אזור..."
				value="<?php echo $address ?>"
				autocomplete="off">

			<!--
				Leaflet map renders here. The id "location-picker-map" is what
				openstreetmap.js keys its picker handler on; ct-wizard.js builds the
				map with new MyListing.Maps.Map(this) and fires maps:loaded.
			-->
			<div id="location-picker-map"
				class="c27-custom-map picker location-picker-custom-map"
				data-options='<?php echo esc_attr( $map_options ) ?>'
				data-logo-pin="<?php echo esc_url( $logo_pin ) ?>">
			</div>
			<p class="ct-field-desc" style="text-align:center;margin-top:4px;">גררו את הסיכה לדיוק מיקום</p>

			<!-- Hidden inputs read by the wizard's form collector and by openstreetmap.js -->
			<div class="location-coords" style="display:none;">
				<input type="text" name="lat" class="latitude-input"  value="<?php echo $lat ?>">
				<input type="text" name="lng" class="longitude-input" value="<?php echo $lng ?>">
			</div>

		</div>
	</div>

	<!-- Roadside checkbox -->
	<div class="ct-field-group">
		<label class="ct-checkbox-item">
			<input type="checkbox"
				name="ct_roadside"
				value="1"
				<?php checked( $roadside ) ?>>
			<span>
				<span class="ct-checkbox-item__label">העגלה נמצאת בצד הדרך / נגישה ברכב</span>
			</span>
		</label>
	</div>

	<!-- Location link (optional) -->
	<div class="ct-field-group">
		<label class="ct-field-label" for="ct-location-link">
			יש לכם קישור למיקום?
			<span style="font-weight:400;color:var(--cw-text-muted);">(Google Maps / WhatsApp — אופציונלי)</span>
		</label>
		<input type="url"
			id="ct-location-link"
			name="ct_location_link"
			class="ct-field"
			placeholder="https://maps.app.goo.gl/..."
			value="<?php echo $loc_link ?>"
			inputmode="url"
			autocomplete="off">
	</div>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>
