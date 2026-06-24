<?php
/**
 * CT_Flow_Auto_Save
 *
 * Provides AJAX-based auto-save for the add/edit listing form.
 *
 * The JavaScript (ct-auto-save.js) collects text, select, and textarea field
 * values at a configurable debounce interval (default 3 s of inactivity) and
 * POSTs them to this handler.
 *
 * Behaviour:
 *  - If the form has an existing job_id (resuming a draft): updates the
 *    draft with the posted field values via wp_update_post + update_post_meta.
 *  - If job_id is 0 or absent (first time): creates a new draft post and
 *    returns the new job_id so JS can insert it as a hidden input for
 *    subsequent auto-saves.
 *
 * Note: File / gallery uploads are NOT handled by auto-save (they require
 * multipart forms and only save on explicit form submission).
 *
 * Security:
 *  - Nonce: 'ct_autosave' (verified by check_ajax_referer).
 *  - User must be logged in.
 *  - job_id (if given) must belong to the current user.
 *  - Only field keys that appear in the listing type's submit-form fields
 *    are accepted (anything else is silently ignored).
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Auto_Save {

	public static function init() {
		add_action( 'wp_ajax_ct_autosave', [ __CLASS__, 'handle' ] );
	}

	// -------------------------------------------------------------------------
	// AJAX handler
	// -------------------------------------------------------------------------

	/**
	 * Handle the auto-save AJAX request.
	 *
	 * Expected POST fields:
	 *   nonce          (string)  — wp_nonce for 'ct_autosave'
	 *   job_id         (int)     — 0 if no draft yet, otherwise existing draft ID
	 *   listing_type   (string)  — slug of the listing type
	 *   listing_package (int)   — WC product ID (24 or 25)
	 *   fields         (array)   — associative [field_key => raw_value]
	 *
	 * @return void  Sends JSON and exits.
	 */
	public static function handle() {
		check_ajax_referer( 'ct_autosave', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Authentication required.' ], 401 );
		}

		$user_id      = get_current_user_id();
		$job_id       = absint( $_POST['job_id'] ?? 0 );
		$listing_type = sanitize_text_field( $_POST['listing_type'] ?? '' );
		$raw_fields   = $_POST['fields'] ?? [];

		if ( ! is_array( $raw_fields ) ) {
			wp_send_json_error( [ 'message' => 'Invalid fields payload.' ], 400 );
		}

		// ------------------------------------------------------------------
		// Validate listing type
		// ------------------------------------------------------------------
		if ( ! $listing_type ) {
			wp_send_json_error( [ 'message' => 'Missing listing_type.' ], 400 );
		}

		$type = \MyListing\Src\Listing_Type::get_by_name( $listing_type );
		if ( ! $type ) {
			wp_send_json_error( [ 'message' => 'Unknown listing type.' ], 400 );
		}

		// Build a whitelist of allowed field keys for this listing type.
		$allowed_keys = array_keys( array_filter( $type->get_fields(), function( $field ) {
			return ! empty( $field->props['show_in_submit_form'] );
		} ) );

		// ------------------------------------------------------------------
		// Validate / create the draft
		// ------------------------------------------------------------------
		if ( $job_id > 0 ) {
			$post = get_post( $job_id );

			if (
				! $post
				|| (int) $post->post_author !== $user_id
				|| $post->post_type !== 'job_listing'
				|| ! in_array( $post->post_status, [ 'draft', 'pending', 'auto-draft' ], true )
			) {
				wp_send_json_error( [ 'message' => 'Invalid draft.' ], 403 );
			}
		} else {
			// Create a new auto-draft.
			$job_id = wp_insert_post( [
				'post_type'   => 'job_listing',
				'post_status' => 'draft',
				'post_author' => $user_id,
				'post_title'  => sanitize_text_field( $raw_fields['job_title'] ?? __( '(טיוטה)', 'my-listing' ) ),
			], true );

			if ( is_wp_error( $job_id ) ) {
				wp_send_json_error( [ 'message' => $job_id->get_error_message() ], 500 );
			}

			update_post_meta( $job_id, '_listing_type', $listing_type );
		}

		// ------------------------------------------------------------------
		// Save text / select / textarea fields (skip files)
		// ------------------------------------------------------------------
		$saved = [];

		foreach ( $raw_fields as $key => $value ) {
			$key = sanitize_key( $key );

			if ( ! in_array( $key, $allowed_keys, true ) ) {
				continue; // Reject any field not in the listing type definition.
			}

			if ( $key === 'job_title' ) {
				wp_update_post( [ 'ID' => $job_id, 'post_title' => sanitize_text_field( $value ) ] );
				$saved[] = $key;
				continue;
			}

			if ( $key === 'job_description' ) {
				wp_update_post( [ 'ID' => $job_id, 'post_content' => wp_kses_post( $value ) ] );
				$saved[] = $key;
				continue;
			}

			// Only save scalar / simple values; skip arrays (handled by dedicated field UIs).
			if ( is_array( $value ) ) {
				continue;
			}

			update_post_meta( $job_id, '_' . $key, sanitize_textarea_field( $value ) );
			$saved[] = $key;
		}

		update_post_meta( $job_id, '_ct_autosaved_at', current_time( 'timestamp' ) );

		wp_send_json_success( [
			'job_id'     => $job_id,
			'saved'      => $saved,
			'saved_at'   => current_time( 'timestamp' ),
			'saved_at_f' => current_time( get_option( 'time_format' ) ),
		] );
	}
}
