<?php
/**
 * Work-hours → day-of-week tag sync.
 *
 * Keeps a predefined set of "open on day X" tags in sync with each listing's
 * `_work_hours` post meta. Runs in two modes:
 *
 *  1. Frontend add/edit: `mylisting/submission/save-listing-data` (after all
 *     submission fields including Tags).
 *  2. WP Admin listing edit: `mylisting/admin/save-listing-data` at priority 200
 *     (after `save_listing_fields` + `save_listing_settings`).
 *  3. Daily batch: `mylisting/schedule:daily` (imports, drift).
 *
 * Configuration: edit CT_WORK_HOURS_DAY_TAG_SLUGS below to match the tag slugs
 * you have created in WP Admin → Listings → Tags (case27_job_listing_tags).
 * Any day not present in the map is simply ignored.
 *
 * @package my-listing-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map from _work_hours day key → tag slug in case27_job_listing_tags.
 *
 * Keys must exactly match the English day names stored in `_work_hours` meta
 * (Monday … Sunday). Values must be slugs of existing terms in the taxonomy.
 * Days omitted here are left untouched on the listing.
 *
 * @var array<string,string>
 */
const CT_WORK_HOURS_DAY_TAG_SLUGS = [
	'Sunday'    => 'open-sunday',
	'Monday'    => 'open-monday',
	'Tuesday'   => 'open-tuesday',
	'Wednesday' => 'open-wednesday',
	'Thursday'  => 'open-thursday',
	'Friday'    => 'open-on-friday',
	'Saturday'  => 'open-on-saturday',
];

const CT_WORK_HOURS_TAXONOMY = 'case27_job_listing_tags';

// ---------------------------------------------------------------------------
// Form save: after MyListing's update_listing_data() (all fields, order-safe).
// ---------------------------------------------------------------------------

add_action( 'mylisting/submission/save-listing-data', 'ct_sync_day_tags_on_submission_save', 20, 2 );
add_action( 'mylisting/admin/save-listing-data', 'ct_sync_day_tags_on_admin_listing_save', 200, 2 );

/**
 * Sync day tags after MyListing frontend add/edit submission completes.
 *
 * Must not rely on `_work_hours` `updated_post_meta` alone: the Tags field
 * can save after work hours and replace taxonomy terms from POST in the same
 * request.
 *
 * @param int                  $job_id Listing post ID.
 * @param array<string,mixed>|null $_fields Passed by MyListing (unused).
 */
function ct_sync_day_tags_on_submission_save( $job_id, $_fields = null ): void {
	unset( $_fields );
	$post_id = absint( $job_id );
	if ( ! $post_id || get_post_type( $post_id ) !== 'job_listing' ) {
		return;
	}
	ct_sync_single_listing_day_tags( $post_id );
}

/**
 * Sync day tags after WP Admin save (after all listing fields and settings handlers).
 *
 * @param int                    $post_id Listing post ID.
 * @param object|\WP_Post|mixed $_listing MyListing Listing instance (unused).
 */
function ct_sync_day_tags_on_admin_listing_save( $post_id, $_listing = null ): void {
	unset( $_listing );
	$post_id = absint( $post_id );
	if ( ! $post_id || get_post_type( $post_id ) !== 'job_listing' ) {
		return;
	}
	ct_sync_single_listing_day_tags( $post_id );
}

// ---------------------------------------------------------------------------
// Daily batch sync: reconciles all published listings once per day.
// Uses the theme's own scheduled action — no extra cron registration needed.
// ---------------------------------------------------------------------------

add_action( 'mylisting/schedule:daily', 'ct_sync_all_listings_day_tags' );

/**
 * Batch-sync day tags for every published job_listing.
 *
 * Loads all listing IDs in a single lightweight query (fields=ids,
 * no_found_rows) and delegates per-listing work to
 * ct_sync_single_listing_day_tags(). Bumps the MyListing taxonomy cache
 * version once after all listings are processed.
 */
function ct_sync_all_listings_day_tags(): void {
	$day_term_ids = ct_get_day_term_id_map();

	if ( empty( $day_term_ids ) ) {
		return;
	}

	$query = new WP_Query( [
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	] );

	foreach ( $query->posts as $post_id ) {
		ct_sync_single_listing_day_tags( (int) $post_id, $day_term_ids );
	}

	ct_maybe_bump_tags_cache();
}

// ---------------------------------------------------------------------------
// Core sync logic
// ---------------------------------------------------------------------------

/**
 * Sync day-of-week tags for a single listing.
 *
 * Reads `_work_hours` meta, determines which days have non-closed status
 * (including open-all-day, enter-hours with at least one time range, and
 * by-appointment-only), then rewrites the listing's tags so that:
 *  - day tags that should be active are present,
 *  - day tags that should be inactive are removed,
 *  - all other (non-day) tags on the listing are preserved unchanged.
 *
 * @param int                   $post_id      Listing post ID.
 * @param array<string,int>|null $day_term_ids Optional pre-built day→term_id map
 *                                             (pass when calling in a loop to
 *                                             avoid redundant get_term_by calls).
 */
function ct_sync_single_listing_day_tags( int $post_id, ?array $day_term_ids = null ): void {
	if ( null === $day_term_ids ) {
		$day_term_ids = ct_get_day_term_id_map();
	}

	if ( empty( $day_term_ids ) ) {
		return;
	}

	$work_hours = get_post_meta( $post_id, '_work_hours', true );

	$active_day_term_ids = [];

	if ( is_array( $work_hours ) ) {
		foreach ( $day_term_ids as $day => $term_id ) {
			$day_data = $work_hours[ $day ] ?? [];
			if ( is_array( $day_data ) && ct_work_hours_day_is_open( $day_data ) ) {
				$active_day_term_ids[] = $term_id;
			}
		}
	}

	$all_day_term_id_values = array_values( $day_term_ids );

	// Fetch current tags; preserve every term that is NOT a managed day tag.
	$current_term_ids = wp_get_object_terms(
		$post_id,
		CT_WORK_HOURS_TAXONOMY,
		[ 'fields' => 'ids' ]
	);

	if ( is_wp_error( $current_term_ids ) ) {
		$current_term_ids = [];
	}

	$non_day_term_ids = array_values(
		array_diff( (array) $current_term_ids, $all_day_term_id_values )
	);

	$final_term_ids = array_values( array_unique(
		array_merge( $non_day_term_ids, $active_day_term_ids )
	) );

	wp_set_object_terms( $post_id, $final_term_ids, CT_WORK_HOURS_TAXONOMY, false );
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build and return a day-name → term_id map for the configured day tag slugs.
 *
 * Terms that do not exist yet in the taxonomy are silently omitted, so the
 * function is safe to call before tags have been created.
 *
 * @return array<string,int> E.g. [ 'Monday' => 42, 'Sunday' => 17, … ]
 */
function ct_get_day_term_id_map(): array {
	$map = [];
	foreach ( CT_WORK_HOURS_DAY_TAG_SLUGS as $day => $slug ) {
		$term = get_term_by( 'slug', $slug, CT_WORK_HOURS_TAXONOMY );
		if ( $term instanceof WP_Term ) {
			$map[ $day ] = $term->term_id;
		}
	}
	return $map;
}

/**
 * Determine whether a single day's _work_hours data counts as "open".
 *
 * Rules:
 *  - open-all-day        → open.
 *  - by-appointment-only → open (the place is reachable that day).
 *  - closed-all-day      → closed.
 *  - enter-hours         → open only if at least one range has non-empty
 *                          `from` and `to` values.
 *  - anything else       → closed (treats missing/unknown status as closed).
 *
 * @param array<mixed> $day_data Single day entry from _work_hours meta.
 * @return bool
 */
function ct_work_hours_day_is_open( array $day_data ): bool {
	$status = isset( $day_data['status'] ) ? (string) $day_data['status'] : '';

	if ( $status === 'open-all-day' || $status === 'by-appointment-only' ) {
		return true;
	}

	if ( $status === 'closed-all-day' || $status === '' ) {
		return false;
	}

	if ( $status === 'enter-hours' ) {
		foreach ( $day_data as $key => $value ) {
			if (
				is_int( $key ) &&
				is_array( $value ) &&
				! empty( $value['from'] ) &&
				! empty( $value['to'] )
			) {
				return true;
			}
		}
		return false;
	}

	return false;
}

/**
 * Bump the MyListing taxonomy cache version for case27_job_listing_tags if
 * the helper from tag_visibility_options.php is available.
 *
 * Called once after a batch sync to invalidate any cached term lists without
 * thrashing the option on every per-listing update.
 */
function ct_maybe_bump_tags_cache(): void {
	if ( function_exists( 'ct_bump_mylisting_case27_tags_cache_version' ) ) {
		ct_bump_mylisting_case27_tags_cache_version();
	}
}
