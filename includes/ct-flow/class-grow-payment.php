<?php
/**
 * CT_Grow_Payment
 *
 * Wraps all Grow Light API server-side calls for the CoffeeTrail PRO payment
 * flow.  Uses chargeType=1 to charge the card immediately at submission time.
 * If admin unpublishes a listing, refund_payment() calls refundtransaction.
 *
 * Credentials are read from wp-config.php constants:
 *   CT_GROW_API_KEY   — API key issued by Grow
 *   CT_GROW_USER_ID   — Merchant user ID
 *   CT_GROW_PAGE_CODE — Payment page code (must be in SDK wallet mode)
 *   CT_GROW_SDK_URL   — Full URL to Grow's client-side JS SDK
 *   CT_GROW_ENV       — 'test' | 'live'  (defaults to 'test')
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Grow_Payment {

	/**
	 * Grow API base URLs.
	 * Verify these with Grow support if requests time out.
	 */
	const API_BASE_TEST = 'https://testsecure.grow-il.com';
	const API_BASE_LIVE = 'https://secure.grow-il.com';

	/**
	 * Default SDK CDN URL — confirm the exact path with Grow support or portal.
	 */
	const SDK_URL_DEFAULT = 'https://cdn.grow-il.com/grow-payment-sdk.js';

	public static function init() {
		// AJAX: create a Grow payment process and return authCode to the browser.
		add_action( 'wp_ajax_ct_grow_init', [ __CLASS__, 'ajax_init_payment' ] );
	}

	// =========================================================================
	// Public API
	// =========================================================================

	/**
	 * Create a direct (immediate) charge payment process for a listing.
	 *
	 * chargeType=1 charges the card immediately on completion.  The listing is
	 * published as soon as the webhook confirms statusCode=2.
	 * saveCardToken=1 saves the card for future recurring billing.
	 *
	 * On success, processId and processToken are stored as listing meta so
	 * the webhook and refund methods can correlate the transaction.
	 *
	 * @param int   $listing_id  Job listing post ID.
	 * @param float $amount      Amount in ILS.
	 * @param array $extra       Optional extra Grow params to merge.
	 * @return array|WP_Error    Grow 'data' array (includes authCode) or error.
	 */
	public static function create_charge( int $listing_id, float $amount, array $extra = [] ) {
		assert( $listing_id > 0, 'create_charge: listing_id must be > 0' );
		assert( $amount > 0,     'create_charge: amount must be > 0' );

		$add_listing_url = self::_add_listing_url();

		$success_url = add_query_arg(
			[ 'ct_grow_result' => 'success', 'job_id' => $listing_id ],
			$add_listing_url
		);
		$cancel_url  = add_query_arg(
			[ 'ct_grow_result' => 'cancel', 'job_id' => $listing_id ],
			$add_listing_url
		);

		$params = array_merge( [
			'apiKey'        => self::_api_key(),
			'userId'        => self::_user_id(),
			'pageCode'      => self::_page_code(),
			'sum'           => intval( round( $amount ) ),
			'chargeType'    => 1, // Direct (immediate) charge
			'saveCardToken' => 1,
			'description'   => 'CoffeeTrail PRO — listing #' . $listing_id,
			'successUrl'    => $success_url,
			'cancelUrl'     => $cancel_url,
		], $extra );

		$result = self::_post( '/api-light-server/1.0/createpaymentprocess', $params );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Persist identifiers used later by the webhook and refund method.
		update_post_meta( $listing_id, '_ct_grow_process_id',    $result['processId']    ?? '' );
		update_post_meta( $listing_id, '_ct_grow_process_token', $result['processToken'] ?? '' );
		update_post_meta( $listing_id, '_ct_grow_charge_amount', $amount );
		update_post_meta( $listing_id, '_ct_grow_charge_at',     current_time( 'timestamp' ) );

		return $result;
	}

	/**
	 * Refund (void) a completed charge after admin unpublishes a listing.
	 *
	 * Calls refundtransaction.  If no transaction is stored (e.g. Free listing,
	 * or webhook never arrived), returns true silently so unpublish proceeds.
	 *
	 * NOTE: Confirm with Grow that refundtransaction has no time window limit.
	 * If a window exists, callers should check _ct_grow_charge_at before calling.
	 *
	 * @param int $listing_id
	 * @return true|WP_Error
	 */
	public static function refund_payment( int $listing_id ) {
		$transaction_id = get_post_meta( $listing_id, '_ct_grow_transaction_id', true );

		if ( ! $transaction_id ) {
			// No charge on record — nothing to refund.
			return true;
		}

		$result = self::_post( '/api-light-server/1.0/refundtransaction', [
			'apiKey'        => self::_api_key(),
			'userId'        => self::_user_id(),
			'transactionId' => $transaction_id,
		] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_post_meta( $listing_id, '_ct_grow_refunded_at', current_time( 'timestamp' ) );

		return true;
	}

	/**
	 * Query Grow for the current status of a payment process.
	 * Used as a fallback when the webhook callback arrives after the browser.
	 *
	 * @param int $listing_id
	 * @return array|WP_Error  Grow data array or error.
	 */
	public static function get_transaction_status( int $listing_id ) {
		$process_id    = get_post_meta( $listing_id, '_ct_grow_process_id',    true );
		$process_token = get_post_meta( $listing_id, '_ct_grow_process_token', true );

		if ( ! $process_id ) {
			return new WP_Error(
				'ct_grow_no_process',
				'[CT Grow] No process ID found for listing #' . $listing_id
			);
		}

		return self::_post( '/api-light-server/1.0/getpaymentprocessinfo', [
			'apiKey'        => self::_api_key(),
			'userId'        => self::_user_id(),
			'processId'     => $process_id,
			'processToken'  => $process_token,
		] );
	}

	/**
	 * Whether this listing has a confirmed charge transaction stored
	 * (set by the server-to-server webhook callback).
	 *
	 * @param int $listing_id
	 * @return bool
	 */
	public static function has_charged_payment( int $listing_id ): bool {
		return (bool) get_post_meta( $listing_id, '_ct_grow_transaction_id', true );
	}

	/**
	 * Return the PRO plan price in ILS from the WooCommerce product.
	 *
	 * Uses CT_FLOW_PRO_PRODUCT_ID (product #25) defined in ct-flow.php.
	 *
	 * @return float  0.0 if the product cannot be loaded.
	 */
	public static function get_pro_price(): float {
		$product = wc_get_product( CT_FLOW_PRO_PRODUCT_ID );
		return $product ? (float) $product->get_price() : 0.0;
	}

	/**
	 * Whether Grow credentials are configured in wp-config.php.
	 * Used to show a placeholder notice when they are not yet set.
	 *
	 * @return bool
	 */
	public static function is_configured(): bool {
		return defined( 'CT_GROW_API_KEY' )   && CT_GROW_API_KEY
			&& defined( 'CT_GROW_USER_ID' )   && CT_GROW_USER_ID
			&& defined( 'CT_GROW_PAGE_CODE' ) && CT_GROW_PAGE_CODE;
	}

	/**
	 * Return the Grow client-side SDK URL.
	 *
	 * Override in wp-config.php:
	 *   define('CT_GROW_SDK_URL', 'https://...');
	 *
	 * @return string
	 */
	public static function sdk_url(): string {
		return defined( 'CT_GROW_SDK_URL' ) ? CT_GROW_SDK_URL : self::SDK_URL_DEFAULT;
	}

	// =========================================================================
	// AJAX handler
	// =========================================================================

	/**
	 * AJAX: initialise a Grow payment process for the browser.
	 *
	 * Expected POST: nonce, job_id
	 * Returns JSON: { authCode, processId }
	 *
	 * @return void  Terminates with wp_send_json_*.
	 */
	public static function ajax_init_payment() {
		check_ajax_referer( 'ct_grow_init', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'לא מחוברים לחשבון.' ], 403 );
		}

		$listing_id = absint( $_POST['job_id'] ?? 0 );
		if ( ! $listing_id ) {
			wp_send_json_error( [ 'message' => 'מזהה הרישום חסר.' ], 400 );
		}

		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! $listing || ! $listing->editable_by_current_user() ) {
			wp_send_json_error( [ 'message' => 'גישה נדחתה.' ], 403 );
		}

		$amount = self::get_pro_price();
		if ( $amount <= 0 ) {
			wp_send_json_error( [
				'message' => 'מחיר לא תקין — בדקו את הגדרות מוצר PRO ב-WooCommerce.',
			], 500 );
		}

		$result = self::create_charge( $listing_id, $amount );
		if ( is_wp_error( $result ) ) {
			mlog()->error(
				'[CT Grow] create_charge failed for listing #' . $listing_id
				. ': ' . $result->get_error_message()
			);
			wp_send_json_error( [
				'message' => 'שגיאה ביצירת תהליך התשלום. אנא נסו שנית.',
			], 500 );
		}

		wp_send_json_success( [
			'authCode'  => $result['authCode']   ?? '',
			'processId' => $result['processId']  ?? '',
		] );
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * POST to a Grow API endpoint and decode the JSON response.
	 *
	 * @param string $path  e.g. '/api-light-server/1.0/createpaymentprocess'
	 * @param array  $body  Request parameters.
	 * @return array|WP_Error  Decoded 'data' sub-array on success, or WP_Error.
	 */
	private static function _post( string $path, array $body ) {
		$base = self::_is_live() ? self::API_BASE_LIVE : self::API_BASE_TEST;
		$url  = rtrim( $base, '/' ) . $path;

		$response = wp_remote_post( $url, [
			'timeout'   => 20,
			'headers'   => [ 'Content-Type' => 'application/json; charset=UTF-8' ],
			'body'      => wp_json_encode( $body ),
			'sslverify' => true,
		] );

		if ( is_wp_error( $response ) ) {
			mlog()->error( '[CT Grow] HTTP error at ' . $path . ': ' . $response->get_error_message() );
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$raw       = wp_remote_retrieve_body( $response );
		$decoded   = json_decode( $raw, true );

		if ( ! is_array( $decoded ) ) {
			$err = '[CT Grow] Non-JSON response from ' . $path . ' (HTTP ' . $http_code . '): ' . substr( $raw, 0, 200 );
			mlog()->error( $err );
			return new WP_Error( 'ct_grow_invalid_response', $err );
		}

		// Grow returns status=1 for success, status=0 for error.
		if ( empty( $decoded['status'] ) || (int) $decoded['status'] !== 1 ) {
			$err_msg = $decoded['err'] ?? ( 'Unknown Grow error (HTTP ' . $http_code . ')' );
			mlog()->error( '[CT Grow] API error from ' . $path . ': ' . $err_msg );
			return new WP_Error( 'ct_grow_api_error', $err_msg, $decoded );
		}

		// Return the 'data' sub-array when present, otherwise the full response.
		return $decoded['data'] ?? $decoded;
	}

	private static function _is_live(): bool {
		return defined( 'CT_GROW_ENV' ) && CT_GROW_ENV === 'live';
	}

	private static function _api_key(): string {
		return defined( 'CT_GROW_API_KEY' ) ? (string) CT_GROW_API_KEY : '';
	}

	private static function _user_id(): string {
		return defined( 'CT_GROW_USER_ID' ) ? (string) CT_GROW_USER_ID : '';
	}

	private static function _page_code(): string {
		return defined( 'CT_GROW_PAGE_CODE' ) ? (string) CT_GROW_PAGE_CODE : '';
	}

	private static function _add_listing_url(): string {
		$page_id = absint( c27()->get_setting( 'general_add_listing_page' ) );
		return $page_id ? get_permalink( $page_id ) : home_url( '/add-listing/' );
	}
}
