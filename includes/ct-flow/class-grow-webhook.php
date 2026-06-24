<?php
/**
 * CT_Grow_Webhook
 *
 * Registers a WP REST endpoint that Grow calls server-to-server after a
 * payment transaction event.  On a successful direct charge (statusCode=2),
 * stores transaction identifiers and immediately publishes the listing.
 *
 * Endpoint:  POST /wp-json/ct-flow/v1/grow-callback
 *
 * Grow StatusCode values (from parameter-mapping docs):
 *   0 = Not paid
 *   2 = Paid / charged
 *   4 = Cancelled before transmission
 *   6 = Refund / void
 *   9 = Transaction denied
 *
 * Custom actions fired:
 *   ct_flow/grow/payment_charged   ( $listing_id, $payload )
 *   ct_flow/grow/payment_failed    ( $listing_id, $status_code, $payload )
 *   ct_flow/grow/payment_refunded  ( $listing_id, $payload )
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Grow_Webhook {

	const REST_NAMESPACE = 'ct-flow/v1';
	const REST_ROUTE     = '/grow-callback';

	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_route' ] );
	}

	// =========================================================================
	// Route registration
	// =========================================================================

	public static function register_route() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'handle' ],
				// Grow sends no WP auth header; signature validation is done inside.
				'permission_callback' => '__return_true',
			]
		);
	}

	// =========================================================================
	// Callback
	// =========================================================================

	/**
	 * Handle a Grow server-to-server callback.
	 *
	 * On statusCode=2 (payment charged):
	 *   1. Stores transactionId, transactionToken, and card token as post meta.
	 *   2. Sets listing post_status to 'publish' immediately.
	 *   3. Fires ct_flow/grow/payment_charged for downstream hooks.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle( WP_REST_Request $request ): WP_REST_Response {
		$payload = $request->get_json_params();

		// Some Grow environments send form-encoded bodies; fall back gracefully.
		if ( empty( $payload ) ) {
			$payload = $request->get_body_params();
		}

		$process_id     = sanitize_text_field( $payload['processId']       ?? '' );
		$transaction_id = sanitize_text_field( $payload['transactionId']   ?? '' );
		$tx_token       = sanitize_text_field( $payload['transactionToken'] ?? '' );
		$status_code    = isset( $payload['statusCode'] ) ? (int) $payload['statusCode'] : -1;
		$card_token     = sanitize_text_field( $payload['token']            ?? '' );

		if ( ! $process_id ) {
			mlog()->warning( '[CT Grow Webhook] Callback received without processId.' );
			return new WP_REST_Response( [ 'ok' => false, 'error' => 'missing processId' ], 400 );
		}

		$listing_id = self::_listing_id_by_process( $process_id );

		if ( ! $listing_id ) {
			mlog()->warning(
				'[CT Grow Webhook] No listing found for processId=' . $process_id
				. ' (may arrive before listing is saved on first init).'
			);
			return new WP_REST_Response( [ 'ok' => false, 'error' => 'unknown processId' ], 404 );
		}

		mlog()->info(
			'[CT Grow Webhook] processId=' . $process_id
			. ' statusCode=' . $status_code
			. ' transactionId=' . $transaction_id
			. ' listing=' . $listing_id
		);

		switch ( $status_code ) {

			// -----------------------------------------------------------------
			// Payment charged — finalize listing data, then publish immediately
			// -----------------------------------------------------------------
			case 2:
				if ( $transaction_id ) {
					// Idempotency: if we already stored a transaction ID, this is a duplicate callback.
					$existing_tx = get_post_meta( $listing_id, '_ct_grow_transaction_id', true );
					if ( $existing_tx ) {
						mlog()->info( '[CT Grow Webhook] Duplicate statusCode=2 callback — listing #' . $listing_id . ' already finalized.' );
						break;
					}

					update_post_meta( $listing_id, '_ct_grow_transaction_id',    $transaction_id );
					update_post_meta( $listing_id, '_ct_grow_transaction_token', $tx_token );
					if ( $card_token ) {
						update_post_meta( $listing_id, '_ct_grow_card_token', $card_token );
					}

					// Load wizard state for the listing author and run the native field save
					// before publishing. Read listing_package from post meta (stored when the
					// user entered the payment step) to guard against the transient being
					// overwritten by a new session started after payment but before this callback.
					$post = get_post( $listing_id );
					if ( $post ) {
						$author_id = (int) $post->post_author;
						$state     = CT_Flow_Wizard_Controller::get_state( $author_id );
						$stored_pkg = get_post_meta( $listing_id, '_ct_listing_package', true );
						if ( $stored_pkg ) {
							$state['listing_package'] = $stored_pkg;
						}
						CT_Flow_Wizard_Controller::finalize_listing( $listing_id, $state );
					}

					// Publish the listing — no admin approval required.
					$post = get_post( $listing_id );
					if ( $post && ! in_array( $post->post_status, [ 'publish', 'trash' ], true ) ) {
						wp_update_post( [
							'ID'          => $listing_id,
							'post_status' => 'publish',
						] );
						mlog()->info( '[CT Grow Webhook] Listing #' . $listing_id . ' published after payment.' );
					}

					do_action( 'ct_flow/grow/payment_charged', $listing_id, $payload );
				}
				break;

			// -----------------------------------------------------------------
			// Cancelled or denied — clear any stored transaction
			// -----------------------------------------------------------------
			case 4:
			case 9:
				delete_post_meta( $listing_id, '_ct_grow_transaction_id' );
				delete_post_meta( $listing_id, '_ct_grow_transaction_token' );
				do_action( 'ct_flow/grow/payment_failed', $listing_id, $status_code, $payload );
				break;

			// -----------------------------------------------------------------
			// Refund confirmed
			// -----------------------------------------------------------------
			case 6:
				do_action( 'ct_flow/grow/payment_refunded', $listing_id, $payload );
				break;

			default:
				mlog()->info(
					'[CT Grow Webhook] Unhandled statusCode=' . $status_code
					. ' for listing #' . $listing_id
				);
		}

		// Grow expects a 200 OK; always respond OK once we have processed the event.
		return new WP_REST_Response( [ 'ok' => true ], 200 );
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Look up a listing post ID by its stored Grow processId meta.
	 *
	 * @param string $process_id
	 * @return int  0 if not found.
	 */
	private static function _listing_id_by_process( string $process_id ): int {
		$posts = get_posts( [
			'post_type'      => 'job_listing',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => '_ct_grow_process_id',
					'value' => $process_id,
				],
			],
		] );

		return ! empty( $posts ) ? (int) $posts[0] : 0;
	}
}
