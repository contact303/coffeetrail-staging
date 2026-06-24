<?php
/**
 * Tag visibility for `case27_job_listing_tags`.
 *
 * Admin: two groups (search, then single listing), each with checkbox + optional date.
 * Explore/search lists: `mylisting/get-terms/query-args` omits terms when
 * `exclude_from_search_filter` = '1' OR when `exclude_from_search_filter_date` is set
 * and the site local date is on/after that date.
 * Single listing tags block: uses `tags-block.php` + `exclude_from_single_listing` /
 * `exclude_from_single_listing_date` only.
 *
 * @package my-listing-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Increment MyListing's taxonomy version for tags so term choice transients invalidate.
 */
function ct_bump_mylisting_case27_tags_cache_version(): void {
	$taxonomy = 'case27_job_listing_tags';
	$versions = (array) json_decode( (string) get_option( 'mylisting_taxonomy_versions', '[]' ), true );
	if ( ! isset( $versions[ $taxonomy ] ) ) {
		$versions[ $taxonomy ] = 0;
	} else {
		$versions[ $taxonomy ] = absint( $versions[ $taxonomy ] ) + 1;
	}
	update_option( 'mylisting_taxonomy_versions', wp_json_encode( $versions ) );
}

/**
 * Exclude hidden tags from `\MyListing\get_terms()` results (explore, Ajax, etc.).
 *
 * @param array<string,mixed> $query_args Arguments passed to `get_terms()`.
 * @param array<string,mixed> $args       Full MyListing get_terms args (taxonomy, listing_type, etc.).
 * @return array<string,mixed>
 */
function ct_tag_visibility_filter_get_terms_query_args( array $query_args, array $args ): array {
	if ( empty( $args['taxonomy'] ) || $args['taxonomy'] !== 'case27_job_listing_tags' ) {
		return $query_args;
	}

	$today = current_time( 'Y-m-d' );

	// Immediate: not flagged with exclude_from_search_filter = '1'.
	$search_immediate_ok = [
		'relation' => 'OR',
		[
			'key'     => 'exclude_from_search_filter',
			'compare' => 'NOT EXISTS',
		],
		[
			'key'     => 'exclude_from_search_filter',
			'value'   => '1',
			'compare' => '!=',
		],
	];

	// Scheduled search removal: hide on and after exclude_from_search_filter_date (YYYY-MM-DD).
	$search_scheduled_ok = [
		'relation' => 'OR',
		[
			'key'     => 'exclude_from_search_filter_date',
			'compare' => 'NOT EXISTS',
		],
		[
			'key'     => 'exclude_from_search_filter_date',
			'value'   => '',
			'compare' => '=',
		],
		[
			'key'     => 'exclude_from_search_filter_date',
			'value'   => $today,
			'compare' => '>',
			'type'    => 'CHAR',
		],
	];

	$visibility_meta = [
		'relation' => 'AND',
		$search_immediate_ok,
		$search_scheduled_ok,
	];

	$existing = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] )
		? $query_args['meta_query']
		: [];

	if ( [] === $existing ) {
		$query_args['meta_query'] = [ $visibility_meta ];
		return $query_args;
	}

	$all_numeric_keys = [] === array_diff_key( $existing, array_filter( $existing, 'is_int', ARRAY_FILTER_USE_KEY ) );

	if ( $all_numeric_keys ) {
		$query_args['meta_query'] = array_merge(
			[ 'relation' => 'AND' ],
			array_values( $existing ),
			[ $visibility_meta ]
		);
		return $query_args;
	}

	$query_args['meta_query'] = [
		'relation' => 'AND',
		$existing,
		$visibility_meta,
	];

	return $query_args;
}

add_filter( 'mylisting/get-terms/query-args', 'ct_tag_visibility_filter_get_terms_query_args', 10, 2 );

// -----------------------------------------------------------------------------
// Admin UI — visibility fields on the tag edit screen
// -----------------------------------------------------------------------------

add_action(
	'case27_job_listing_tags_edit_form_fields',
	static function ( $term, $taxonomy ) {
		$exclude_search       = (bool) get_term_meta( $term->term_id, 'exclude_from_search_filter', true );
		$exclude_search_date  = (string) get_term_meta( $term->term_id, 'exclude_from_search_filter_date', true );
		$exclude_single       = (bool) get_term_meta( $term->term_id, 'exclude_from_single_listing', true );
		$exclude_single_date  = (string) get_term_meta( $term->term_id, 'exclude_from_single_listing_date', true );

		wp_nonce_field( 'ct_save_tag_visibility_' . $term->term_id, 'ct_tag_visibility_nonce' );
		?>

	<tr class="form-field">
		<th scope="row" colspan="2" style="padding-bottom:4px;padding-top:20px;border-top:1px solid #dcdcde;">
			<strong style="font-size:13px;"><?php echo esc_html( 'הסתרת תגית' ); ?></strong>
		</th>
	</tr>

	<tr class="form-field">
		<th scope="row" colspan="2" style="padding-bottom:2px;">
			<?php echo esc_html( 'אקספלור וחיפוש בדף הבית' ); ?>
		</th>
	</tr>

	<tr class="form-field">
		<th scope="row">
			<label for="ct_exclude_from_search_filter"><?php echo esc_html( 'הסר תגית מהאקספלור ומהחיפוש בדף הבית' ); ?></label>
		</th>
		<td>
			<input type="checkbox"
				id="ct_exclude_from_search_filter"
				name="ct_tag_visibility[exclude_from_search_filter]"
				value="1"
				<?php checked( $exclude_search ); ?>
			/>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row">
			<label for="ct_exclude_from_search_filter_date"><?php echo esc_html( 'תזמון להסרה מהאקספלור ומהחיפוש בדף הבית' ); ?></label>
		</th>
		<td>
			<input type="date"
				id="ct_exclude_from_search_filter_date"
				name="ct_tag_visibility[exclude_from_search_filter_date]"
				value="<?php echo esc_attr( $exclude_search_date ); ?>"
			/>
			<p class="description">
				<?php echo esc_html( 'מהתאריך שנבחר והלאה התגית לא תוצג באקספלור, בחיפוש ובפילטרים בדף הבית. השאירו ריק בשביל לבטל.' ); ?>
			</p>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" colspan="2" style="padding-top:16px;padding-bottom:2px;">
			<?php echo esc_html( 'דפי עגלה' ); ?>
		</th>
	</tr>

	<tr class="form-field">
		<th scope="row">
			<label for="ct_exclude_from_single_listing"><?php echo esc_html( 'הסר תגית מדפי עגלה' ); ?></label>
		</th>
		<td>
			<input type="checkbox"
				id="ct_exclude_from_single_listing"
				name="ct_tag_visibility[exclude_from_single_listing]"
				value="1"
				<?php checked( $exclude_single ); ?>
			/>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row">
			<label for="ct_exclude_from_single_listing_date"><?php echo esc_html( 'תזמון להסרה מדפי עגלה' ); ?></label>
		</th>
		<td>
			<input type="date"
				id="ct_exclude_from_single_listing_date"
				name="ct_tag_visibility[exclude_from_single_listing_date]"
				value="<?php echo esc_attr( $exclude_single_date ); ?>"
			/>
			<p class="description">
				<?php echo esc_html( 'מהתאריך שנבחר והלאה התגית לא תוצג בבלוק התגיות בדפי העגלה. השאירו ריק בשביל לבטל.' ); ?>
			</p>
		</td>
	</tr>

		<?php
	},
	20,
	2
);

/**
 * Persist visibility meta when a tag is created or updated.
 *
 * @param int $term_id Term ID.
 */
function ct_save_tag_visibility_meta( $term_id ): void {
	$term_id = absint( $term_id );
	if ( ! $term_id ) {
		return;
	}

	if (
		empty( $_POST['ct_tag_visibility_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['ct_tag_visibility_nonce'] ) ), 'ct_save_tag_visibility_' . $term_id )
	) {
		return;
	}

	$data = ( isset( $_POST['ct_tag_visibility'] ) && is_array( $_POST['ct_tag_visibility'] ) )
		? wp_unslash( $_POST['ct_tag_visibility'] )
		: [];

	$old_exclude_search  = (bool) get_term_meta( $term_id, 'exclude_from_search_filter', true );
	$old_search_schedule = (string) get_term_meta( $term_id, 'exclude_from_search_filter_date', true );

	if ( ! empty( $data['exclude_from_search_filter'] ) ) {
		update_term_meta( $term_id, 'exclude_from_search_filter', '1' );
	} else {
		delete_term_meta( $term_id, 'exclude_from_search_filter' );
	}

	$new_exclude_search = (bool) get_term_meta( $term_id, 'exclude_from_search_filter', true );

	$raw_search_date = isset( $data['exclude_from_search_filter_date'] )
		? sanitize_text_field( (string) $data['exclude_from_search_filter_date'] )
		: '';

	if ( $raw_search_date && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_search_date ) ) {
		update_term_meta( $term_id, 'exclude_from_search_filter_date', $raw_search_date );
	} else {
		delete_term_meta( $term_id, 'exclude_from_search_filter_date' );
	}

	$new_search_schedule = (string) get_term_meta( $term_id, 'exclude_from_search_filter_date', true );

	if ( ! empty( $data['exclude_from_single_listing'] ) ) {
		update_term_meta( $term_id, 'exclude_from_single_listing', '1' );
	} else {
		delete_term_meta( $term_id, 'exclude_from_single_listing' );
	}

	$raw_single_date = isset( $data['exclude_from_single_listing_date'] )
		? sanitize_text_field( (string) $data['exclude_from_single_listing_date'] )
		: '';

	if ( $raw_single_date && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_single_date ) ) {
		update_term_meta( $term_id, 'exclude_from_single_listing_date', $raw_single_date );
	} else {
		delete_term_meta( $term_id, 'exclude_from_single_listing_date' );
	}

	if (
		$old_exclude_search !== $new_exclude_search
		|| $old_search_schedule !== $new_search_schedule
	) {
		ct_bump_mylisting_case27_tags_cache_version();
	}
}

add_action( 'edited_case27_job_listing_tags', 'ct_save_tag_visibility_meta', 10, 1 );
add_action( 'created_case27_job_listing_tags', 'ct_save_tag_visibility_meta', 10, 1 );
