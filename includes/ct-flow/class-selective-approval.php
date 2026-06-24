<?php
/**
 * CT_Flow_Selective_Approval
 *
 * Keeps published listings LIVE while specific field changes queue for
 * admin approval.
 *
 * FLOW (edit-listing form):
 *
 *  1. pre_post_update fires when save_listing() calls wp_update_post().
 *     At this point update_listing_data() has NOT yet run, so post meta
 *     still holds the old (current live) values.
 *     → We snapshot old values for all approval-required fields into a static array.
 *
 *  2. mylisting/submission/save-listing-data fires at priority 9999,
 *     after update_listing_data() has written new values to post meta.
 *     → We compare new meta with the snapshot.
 *     → For fields that changed: store {old, new} in _ct_pending_changes,
 *       restore the old meta value (so the live listing shows no change),
 *       and set the _ct_has_pending_changes flag.
 *     → Notify the admin via email.
 *
 * APPROVAL-REQUIRED FIELDS:
 *
 *  The list of field keys that require approval is configurable via the
 *  'ct_approval_required_fields' filter.  Add field keys in functions.php:
 *
 *      add_filter( 'ct_approval_required_fields', function( $keys ) {
 *          $keys[] = 'gallery';  // photos / gallery field key
 *          $keys[] = 'story';    // story / description field key
 *          return $keys;
 *      } );
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Selective_Approval {

	/**
	 * Snapshot of old field meta values, keyed by post ID.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $snapshots = [];

	public static function init() {
		// Step 1: snapshot before wp_update_post is called.
		add_action( 'pre_post_update', [ __CLASS__, 'snapshot_old_values' ], 10, 2 );

		// Step 2: compare and queue after all field meta has been saved.
		add_action( 'mylisting/submission/save-listing-data', [ __CLASS__, 'process_changes' ], 9999, 2 );
	}

	// -------------------------------------------------------------------------
	// Public helpers used by the admin panel
	// -------------------------------------------------------------------------

	/**
	 * Return the list of field keys that require admin approval on edit.
	 *
	 * @return string[]
	 */
	public static function get_approval_required_fields() {
		return (array) apply_filters( 'ct_approval_required_fields', [] );
	}

	/**
	 * Return pending changes for a listing, or an empty array.
	 *
	 * @param int $listing_id
	 * @return array  ['field_key' => ['old' => mixed, 'new' => mixed, 'submitted_at' => int]]
	 */
	public static function get_pending_changes( $listing_id ) {
		$data = get_post_meta( absint( $listing_id ), '_ct_pending_changes', true );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Apply a single approved field change (write new value to post meta).
	 *
	 * @param int    $listing_id
	 * @param string $field_key
	 * @return bool  True if the change was found and applied.
	 */
	public static function approve_field( $listing_id, $field_key ) {
		$pending = self::get_pending_changes( $listing_id );
		$field_key = sanitize_key( $field_key );

		if ( ! isset( $pending[ $field_key ] ) ) {
			return false;
		}

		$new_value = $pending[ $field_key ]['new'];
		update_post_meta( $listing_id, '_' . $field_key, $new_value );

		unset( $pending[ $field_key ] );
		self::_save_pending( $listing_id, $pending );

		do_action( 'ct_flow/field_approved', $listing_id, $field_key, $new_value );
		return true;
	}

	/**
	 * Reject a single pending field change (discard new value).
	 *
	 * @param int    $listing_id
	 * @param string $field_key
	 * @return bool  True if the change was found and rejected.
	 */
	public static function reject_field( $listing_id, $field_key ) {
		$pending = self::get_pending_changes( $listing_id );
		$field_key = sanitize_key( $field_key );

		if ( ! isset( $pending[ $field_key ] ) ) {
			return false;
		}

		$old_value = $pending[ $field_key ]['old'];
		unset( $pending[ $field_key ] );
		self::_save_pending( $listing_id, $pending );

		// Ensure the live value stays as the old (it already is, but double-check).
		update_post_meta( $listing_id, '_' . $field_key, $old_value );

		do_action( 'ct_flow/field_rejected', $listing_id, $field_key, $old_value );
		return true;
	}

	// -------------------------------------------------------------------------
	// Hooks
	// -------------------------------------------------------------------------

	/**
	 * Step 1: capture the current (old) meta values for approval-required fields
	 * before wp_update_post writes the new post title/content to the DB.
	 * At this point, the field meta has NOT yet been updated.
	 *
	 * @param int   $post_id
	 * @param array $data    Post data about to be saved.
	 * @return void
	 */
	public static function snapshot_old_values( $post_id, $data ) {
		// Only apply on frontend edit-listing form submissions.
		if ( is_admin() ) {
			return;
		}
		if ( empty( $_POST['job_manager_form'] ) || $_POST['job_manager_form'] !== 'edit-listing' ) {
			return;
		}
		if ( get_post_type( $post_id ) !== 'job_listing' ) {
			return;
		}

		$approval_fields = self::get_approval_required_fields();
		if ( empty( $approval_fields ) ) {
			return;
		}

		$snapshot = [];
		foreach ( $approval_fields as $field_key ) {
			$snapshot[ $field_key ] = get_post_meta( $post_id, '_' . sanitize_key( $field_key ), true );
		}

		self::$snapshots[ $post_id ] = $snapshot;
	}

	/**
	 * Step 2: compare new meta values with the snapshot and queue changed
	 * approval-required fields for admin review.
	 *
	 * @param int   $listing_id
	 * @param array $fields  Field objects (may not be needed directly).
	 * @return void
	 */
	public static function process_changes( $listing_id, $fields ) {
		if ( ! isset( self::$snapshots[ $listing_id ] ) ) {
			return; // Not an edit we're tracking.
		}

		$snapshot        = self::$snapshots[ $listing_id ];
		$approval_fields = self::get_approval_required_fields();
		$pending         = self::get_pending_changes( $listing_id );
		$new_pending     = [];
		$now             = current_time( 'timestamp' );

		foreach ( $approval_fields as $field_key ) {
			$field_key  = sanitize_key( $field_key );
			$old_value  = $snapshot[ $field_key ] ?? null;
			$new_value  = get_post_meta( $listing_id, '_' . $field_key, true );

			// Skip if nothing changed.
			// phpcs:ignore -- we intentionally use != for loose comparison
			if ( $new_value == $old_value ) {
				continue;
			}

			// Queue the change.
			$new_pending[ $field_key ] = [
				'old'          => $old_value,
				'new'          => $new_value,
				'submitted_at' => $now,
			];

			// Restore old value so the live listing remains unchanged.
			update_post_meta( $listing_id, '_' . $field_key, $old_value );
		}

		if ( empty( $new_pending ) ) {
			// No approval-required changes; nothing to do.
			unset( self::$snapshots[ $listing_id ] );
			return;
		}

		// Merge with any already-pending changes (user may have queued multiple
		// rounds of edits before the admin has reviewed).
		$merged = array_merge( $pending, $new_pending );
		self::_save_pending( $listing_id, $merged );

		// Trigger email notification hook.
		do_action( 'ct_flow/pending_changes_queued', $listing_id, $new_pending );

		// Clean up snapshot.
		unset( self::$snapshots[ $listing_id ] );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Persist the pending-changes array and update the flag meta.
	 *
	 * @param int   $listing_id
	 * @param array $pending
	 * @return void
	 */
	private static function _save_pending( $listing_id, $pending ) {
		if ( empty( $pending ) ) {
			delete_post_meta( $listing_id, '_ct_pending_changes' );
			delete_post_meta( $listing_id, '_ct_has_pending_changes' );
		} else {
			update_post_meta( $listing_id, '_ct_pending_changes', $pending );
			update_post_meta( $listing_id, '_ct_has_pending_changes', 1 );
		}
	}
}
