<?php
/**
 * "Days of week" advanced-search filter for MyListing.
 *
 * Multiselect checkboxes (advanced); single-select dropdown (basic). Weekday order:
 * Sunday first. Listings must have work-hours overlap on **every** selected day (AND),
 * independent of other filters’ facet settings.
 *
 * Day indexes match MyListing workhours (Monday=0 … Sunday=6). Each day is a
 * 1440-minute window in the 0..10080 weekly range.
 *
 * @package my-listing-child
 */

namespace MyListing\Src\Listing_Types\Filters;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Days_Of_Week extends Base_Filter {

	const MINUTES_PER_DAY = 1440;

	/** MyListing table index for Saturday (for Hebrew label override). */
	const TABLE_INDEX_SATURDAY = 5;

	protected function filter_props() {
		$this->props['type']        = 'days-of-week';
		$this->props['label']       = __( 'Open on days', 'my-listing-child' );
		$this->props['multiselect'] = 1;
	}

	/**
	 * Multiselect enabled for advanced checkboxes (`get_prop`); basic form forces single
	 * select in the template. Independent of stale stored JSON.
	 */
	public function get_prop( $prop ) {
		if ( 'multiselect' === $prop ) {
			return true;
		}

		return parent::get_prop( $prop );
	}

	/**
	 * @see get_prop()
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		if ( 'multiselect' === $offset ) {
			return true;
		}

		return parent::offsetGet( $offset );
	}

	/**
	 * Weekday choices for explore templates (checkboxes / dropdown).
	 *
	 * Values: "0".."6" (Mon..Sun). Hebrew: Saturday label is יום שבת instead of שבת.
	 */
	public function get_choices() {
		global $wp_locale;

		$wp_locale_day_index = [
			'6' => 0,
			'0' => 1,
			'1' => 2,
			'2' => 3,
			'3' => 4,
			'4' => 5,
			'5' => 6,
		];

		$choices      = [];
		$locale       = get_locale();
		$hebrew_label = ( 'he_IL' === $locale || 0 === strpos( (string) $locale, 'he_' ) );

		foreach ( $wp_locale_day_index as $table_index => $wp_index ) {
			$label = $wp_locale instanceof \WP_Locale
				? $wp_locale->get_weekday( $wp_index )
				: gmdate( 'l', strtotime( 'Monday +' . $table_index . ' day' ) );

			if ( $hebrew_label && (string) self::TABLE_INDEX_SATURDAY === (string) $table_index ) {
				$label = __( 'יום שבת', 'my-listing-child' );
			}

			$choices[] = [
				'value'    => (string) $table_index,
				'label'    => $label,
				'selected' => false,
			];
		}

		return $choices;
	}

	/**
	 * Comma-separated day indexes from $_GET (same shape as core Checkboxes filter).
	 */
	public function get_request_value() {
		if ( empty( $_GET[ $this->get_form_key() ] ) ) {
			return '';
		}

		$raw = sanitize_text_field( stripslashes( $_GET[ $this->get_form_key() ] ) );
		$selected = array_filter( array_map( 'trim', explode( ',', $raw ) ), function ( $value ) {
			return ctype_digit( (string) $value ) && (int) $value >= 0 && (int) $value <= 6;
		} );

		return implode( ',', $selected );
	}

	/**
	 * Intersect listing IDs: open on **all** selected weekdays (AND).
	 */
	public function apply_to_query( $args, $form_data ) {
		global $wpdb;

		if ( empty( $form_data[ $this->get_form_key() ] ) ) {
			return $args;
		}

		$raw_value = stripslashes( $form_data[ $this->get_form_key() ] );
		$day_indexes = array_values( array_unique( array_filter(
			array_map( 'intval', explode( ',', (string) $raw_value ) ),
			function ( $value ) {
				return $value >= 0 && $value <= 6;
			}
		) ) );

		if ( empty( $day_indexes ) ) {
			return $args;
		}

		$table = $wpdb->prefix . 'mylisting_workhours';
		$ids   = null;

		foreach ( $day_indexes as $day_index ) {
			$day_start = $day_index * self::MINUTES_PER_DAY;
			$day_end   = ( $day_index + 1 ) * self::MINUTES_PER_DAY;

			$sql = $wpdb->prepare(
				"SELECT DISTINCT listing_id FROM {$table} WHERE `start` < %d AND `end` > %d",
				$day_end,
				$day_start
			);

			$rows    = $wpdb->get_col( $sql );
			$day_ids = array_map( 'absint', (array) $rows );

			if ( empty( $day_ids ) ) {
				$ids = [];
				break;
			}

			$ids = null === $ids ? $day_ids : array_values( array_intersect( $ids, $day_ids ) );

			if ( empty( $ids ) ) {
				break;
			}
		}

		$ids = null === $ids ? [] : $ids;

		if ( empty( $ids ) ) {
			$ids = [ 0 ];
		}

		if ( ! empty( $args['post__in'] ) ) {
			$ids = array_values( array_intersect( $args['post__in'], $ids ) );
			if ( empty( $ids ) ) {
				$ids = [ 0 ];
			}
		}

		$args['post__in'] = $ids;
		return $args;
	}
}
