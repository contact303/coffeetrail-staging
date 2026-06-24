<?php
/**
 * CT_Flow_Admin_Panel
 *
 * Registers the CoffeeTrail moderation admin page under the Listings menu.
 * The panel shows two tabs:
 *
 *  "פורסמו לאחרונה"  — recently published listings (newest first).
 *                     Admin can Unpublish any listing; for PRO listings this
 *                     also calls CT_Grow_Payment::refund_payment() and sends
 *                     an email to the owner.
 *
 *  "שינויים ממתינים" — published listings with queued field changes
 *                     (from CT_Flow_Selective_Approval).
 *
 * Actions are handled via admin POST requests with nonce verification.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Admin_Panel {

	const PAGE_SLUG  = 'ct-flow-moderation';
	const NONCE_NAME = 'ct_admin_action';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_actions' ] );
	}

	// -------------------------------------------------------------------------
	// Menu registration
	// -------------------------------------------------------------------------

	/**
	 * Add the moderation panel as a submenu under the WP job_listing post type menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=job_listing',
			'CoffeeTrail — מנהל תוכן',
			'CoffeeTrail ✦',
			'manage_options',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_page' ]
		);
	}

	// -------------------------------------------------------------------------
	// Action handler
	// -------------------------------------------------------------------------

	/**
	 * Handle admin POST actions from the moderation panel.
	 *
	 * @return void
	 */
	public static function handle_actions() {
		if ( ! isset( $_POST['ct_admin_action'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized', 403 );
		}
		if ( ! check_admin_referer( self::NONCE_NAME, 'ct_nonce' ) ) {
			wp_die( 'Invalid nonce', 403 );
		}

		$action     = sanitize_key( $_POST['ct_admin_action'] );
		$listing_id = absint( $_POST['ct_listing_id'] ?? 0 );

		if ( ! $listing_id ) {
			return;
		}

		switch ( $action ) {

			// -----------------------------------------------------------------
			// Unpublish a published listing.
			// For PRO listings: attempt a Grow refund and notify the owner.
			// -----------------------------------------------------------------
			case 'unpublish_listing':
				$reason = sanitize_textarea_field( $_POST['ct_unpublish_reason'] ?? '' );

				// Attempt Grow refund for PRO listings (non-blocking on failure).
				if ( class_exists( 'CT_Grow_Payment' ) && CT_Grow_Payment::has_charged_payment( $listing_id ) ) {
					$refunded = CT_Grow_Payment::refund_payment( $listing_id );
					if ( is_wp_error( $refunded ) ) {
						mlog()->error(
							'[CT Flow Admin] Grow refund failed for listing #' . $listing_id
							. ': ' . $refunded->get_error_message()
							. ' — proceeding with unpublish; refund may need manual processing.'
						);
						// Store flag so admin can see refund failed.
						update_post_meta( $listing_id, '_ct_grow_refund_failed', '1' );
					} else {
						delete_post_meta( $listing_id, '_ct_grow_refund_failed' );
					}
				}

				wp_update_post( [ 'ID' => $listing_id, 'post_status' => 'draft' ] );
				update_post_meta( $listing_id, '_ct_unpublish_reason', $reason );
				do_action( 'ct_flow/listing_unpublished', $listing_id, $reason );
				self::_redirect( 'unpublished' );
				break;

			// -----------------------------------------------------------------
			// Pending field change: approve one field
			// -----------------------------------------------------------------
			case 'approve_field':
				$field_key = sanitize_key( $_POST['ct_field_key'] ?? '' );
				CT_Flow_Selective_Approval::approve_field( $listing_id, $field_key );
				self::_redirect( 'field_approved' );
				break;

			// -----------------------------------------------------------------
			// Pending field change: reject one field
			// -----------------------------------------------------------------
			case 'reject_field':
				$field_key = sanitize_key( $_POST['ct_field_key'] ?? '' );
				CT_Flow_Selective_Approval::reject_field( $listing_id, $field_key );
				self::_redirect( 'field_rejected' );
				break;

			// -----------------------------------------------------------------
			// Pending field change: approve ALL fields for a listing
			// -----------------------------------------------------------------
			case 'approve_all_fields':
				$pending = CT_Flow_Selective_Approval::get_pending_changes( $listing_id );
				foreach ( array_keys( $pending ) as $field_key ) {
					CT_Flow_Selective_Approval::approve_field( $listing_id, $field_key );
				}
				self::_redirect( 'all_fields_approved' );
				break;

			// -----------------------------------------------------------------
			// Pending field change: reject ALL fields for a listing
			// -----------------------------------------------------------------
			case 'reject_all_fields':
				$pending = CT_Flow_Selective_Approval::get_pending_changes( $listing_id );
				foreach ( array_keys( $pending ) as $field_key ) {
					CT_Flow_Selective_Approval::reject_field( $listing_id, $field_key );
				}
				self::_redirect( 'all_fields_rejected' );
				break;
		}
	}

	// -------------------------------------------------------------------------
	// Page render
	// -------------------------------------------------------------------------

	/**
	 * Render the full moderation admin page.
	 *
	 * @return void
	 */
	public static function render_page() {
		$active_tab     = sanitize_key( $_GET['ct_tab'] ?? 'recent_listings' );
		$success_notice = sanitize_key( $_GET['ct_action'] ?? '' );

		$recent_listings  = self::_get_recent_listings();
		$pending_changes  = self::_get_listings_with_pending_changes();

		require CT_FLOW_DIR . '/templates/admin/moderation-panel.php';
	}

	// -------------------------------------------------------------------------
	// Private data fetchers
	// -------------------------------------------------------------------------

	/**
	 * Get the 50 most recently published job_listing posts.
	 *
	 * @return WP_Post[]
	 */
	private static function _get_recent_listings() {
		return get_posts( [
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );
	}

	/**
	 * Get published listings that have queued field changes.
	 *
	 * @return WP_Post[]
	 */
	private static function _get_listings_with_pending_changes() {
		return get_posts( [
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => '_ct_has_pending_changes',
					'value'   => '1',
					'compare' => '=',
				],
			],
		] );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Redirect back to the moderation panel with an action notice.
	 *
	 * @param string $action_slug
	 * @return void
	 */
	private static function _redirect( $action_slug ) {
		$url = add_query_arg( [
			'page'      => self::PAGE_SLUG,
			'post_type' => 'job_listing',
			'ct_action' => sanitize_key( $action_slug ),
		], admin_url( 'edit.php' ) );
		wp_safe_redirect( $url );
		exit;
	}
}
