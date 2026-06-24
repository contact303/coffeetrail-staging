<?php
/**
 * CT_Flow_Locked_Fields
 *
 * Injects visible-but-non-interactive "locked" PRO-only field teasers into
 * the add-listing form for Free users.
 *
 * Flow:
 *  1. paid-listings::listing_fields_visibility() at priority 30 removes fields
 *     that fail __listing_package conditions (i.e. PRO-only fields for a Free user).
 *
 *  2. CT_Flow_Locked_Fields::inject_locked_fields() at priority 31 runs AFTER
 *     the visibility filter and:
 *       a. Retrieves the full field list from the listing type.
 *       b. Finds fields that were present in the full list but absent from
 *          the filtered list (these are the PRO-only locked fields).
 *       c. Re-adds them to the field array so they appear in the template.
 *       d. Records their keys in self::$locked_keys for the template to query.
 *       e. Re-orders the merged array to match the original listing-type order.
 *
 *  3. The submit-form.php template calls CT_Flow_Locked_Fields::is_locked($key)
 *     to conditionally render the overlay.
 *
 *  4. The locked fields are rendered with an aria-hidden overlay and pointer-
 *     events disabled via CSS.  When the form is submitted, their POST values
 *     are empty (user cannot interact with them), so update() saves nothing.
 *
 * NOTE: Only applied in ADD mode (not edit mode) for Free package (product ID 24).
 *       Edit-mode locked-field teasers can be added as a future enhancement.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Locked_Fields {

	/**
	 * Keys of the fields that have been injected as locked.
	 *
	 * Populated by inject_locked_fields() and read by is_locked().
	 *
	 * @var string[]
	 */
	public static $locked_keys = [];

	public static function init() {
		add_filter( 'mylisting/submission/fields', [ __CLASS__, 'inject_locked_fields' ], 31, 2 );
	}

	// -------------------------------------------------------------------------
	// Public API used by the template
	// -------------------------------------------------------------------------

	/**
	 * Whether the field with the given key has been injected as locked.
	 *
	 * @param string $key  Field key.
	 * @return bool
	 */
	public static function is_locked( $key ) {
		return in_array( $key, self::$locked_keys, true );
	}

	// -------------------------------------------------------------------------
	// Filter callback
	// -------------------------------------------------------------------------

	/**
	 * Re-inject PRO-only fields as locked teasers for Free users.
	 *
	 * @param array                              $filtered_fields  Fields after visibility filter.
	 * @param \MyListing\Src\Listing|null        $listing          Listing instance (null in add mode).
	 * @return array  Merged and re-ordered fields (filtered + locked).
	 */
	public static function inject_locked_fields( $filtered_fields, $listing ) {
		// Reset the registry on each request (guard against multiple form instances).
		self::$locked_keys = [];

		// Only apply in ADD mode (not edit; $listing is null in add mode).
		if ( $listing !== null ) {
			return $filtered_fields;
		}

		// Only apply for the Free package.
		if ( empty( $_REQUEST['listing_package'] ) ) {
			return $filtered_fields;
		}
		$pkg_id = absint( c27()->get_package_id_for_validation( $_REQUEST['listing_package'] ) );
		if ( $pkg_id !== CT_FLOW_FREE_PRODUCT_ID ) {
			return $filtered_fields;
		}

		// Resolve the listing type.
		$type_slug = ! empty( $_REQUEST['listing_type'] )
			? sanitize_text_field( $_REQUEST['listing_type'] )
			: c27()->get_submission_listing_type();

		if ( ! $type_slug ) {
			return $filtered_fields;
		}

		$type = \MyListing\Src\Listing_Type::get_by_name( $type_slug );
		if ( ! $type ) {
			return $filtered_fields;
		}

		// Full field list for this listing type (same filter used in add-listing-form.php).
		$all_fields = array_filter( $type->get_fields(), function( $field ) {
			return ! empty( $field->props['show_in_submit_form'] );
		} );

		if ( empty( $all_fields ) ) {
			return $filtered_fields;
		}

		$filtered_keys = array_keys( $filtered_fields );

		// Find fields that were removed by the visibility filter.
		foreach ( $all_fields as $key => $field ) {
			if ( ! in_array( $key, $filtered_keys, true ) ) {
				// Free users cannot interact with locked fields, so required
				// validation must be disabled — otherwise submit_handler() would
				// throw a required-field error the user has no way to resolve.
				$field->props['required'] = false;

				// Mark as locked and add back to the display array.
				self::$locked_keys[] = $key;
				$filtered_fields[ $key ] = $field;
			}
		}

		if ( empty( self::$locked_keys ) ) {
			return $filtered_fields; // Nothing was removed — no locked teasers needed.
		}

		// Re-order the merged array to match the original listing-type field order.
		$ordered = [];
		foreach ( array_keys( $all_fields ) as $key ) {
			if ( isset( $filtered_fields[ $key ] ) ) {
				$ordered[ $key ] = $filtered_fields[ $key ];
			}
		}

		return $ordered;
	}
}
