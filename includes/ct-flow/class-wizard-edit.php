<?php
/**
 * CT_Flow_Wizard_Edit
 *
 * Stateless, per-request "edit one dashboard card" mode for an already-
 * published cc listing. Deliberately separate from the build flow
 * (CT_Flow_Wizard_Controller / CT_Flow_Wizard_Page): no per-user transient,
 * no sequential STEPS navigation, no next-step computation. A "card" merges
 * one or more existing wizard step templates onto a single screen, hydrated
 * directly from the listing's current postmeta/taxonomies/tables, saved
 * straight back on submit, and returns the user to the dashboard. No admin
 * approval — edits apply immediately.
 *
 * Responsibilities:
 *  - Intercepts /add-listing/?ct_edit_card={card}&job_id={id} on
 *    `template_redirect` at priority 0 — earlier than
 *    CT_Flow_Wizard_Page::maybe_output_wizard()'s priority 1 — so a matched
 *    request is fully handled and exits before any build-flow code runs.
 *  - Checks ownership (the job_id belongs to the current user, is a
 *    published 'cc' listing) before rendering or saving anything for it.
 *    Deliberately does NOT re-check tier: the free/pro split is already
 *    enforced at the account-dashboard route level
 *    (includes/my-account/functions.php's woocommerce_account_free_endpoint /
 *    woocommerce_account_pro_endpoint handlers redirect a user to their own
 *    package's route), and every edit-card dashboard button only ever gets
 *    printed on the template that redirect already routed the user to
 *    correctly — there is nothing left here to gate a second time.
 *  - Hydrates the card's step templates directly from the published post
 *    and renders them composed onto one screen (see templates/wizard-edit/).
 *  - Provides the ct_wizard_save_edit_card AJAX handler, which validates and
 *    persists via the CT_Flow_Wizard_Controller::validate_edit_fields() /
 *    persist_edit_card() entry points added for this purpose.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Wizard_Edit {

	/**
	 * Card definitions: which step templates compose the card.
	 *
	 * 'about' is the only card built so far (basics + contact + location,
	 * merged per the "על העגלה" dashboard section).
	 */
	const CARDS = [
		'about' => [
			'steps' => [ 'basics', 'contact', 'location' ],
		],
	];

	public static function init() {
		add_action( 'template_redirect', [ __CLASS__, 'maybe_output_edit_card' ], 0 );
		add_action( 'wp_ajax_ct_wizard_save_edit_card', [ __CLASS__, 'ajax_save_edit_card' ] );
	}

	// =========================================================================
	// Page output
	// =========================================================================

	/**
	 * Intercept and fully own the response for an edit-card request, before
	 * CT_Flow_Wizard_Page::maybe_output_wizard() (priority 1) ever runs.
	 *
	 * @return void
	 */
	public static function maybe_output_edit_card(): void {
		$card = sanitize_key( $_REQUEST['ct_edit_card'] ?? '' );
		if ( '' === $card ) {
			return;
		}

		if ( ! self::_is_add_listing_page() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			// Let CT_Flow_Registration::redirect_guests_to_register (also on
			// template_redirect) handle unauthenticated visitors, same as the
			// build flow does.
			return;
		}

		if ( ! isset( self::CARDS[ $card ] ) ) {
			self::_render_clean_error( 'כרטיס עריכה לא ידוע.' );
		}

		$job_id = absint( $_REQUEST['job_id'] ?? 0 );
		$check  = self::_check_ownership( $job_id );
		if ( is_wp_error( $check ) ) {
			self::_render_clean_error( $check->get_error_message() );
		}

		$definition      = self::CARDS[ $card ];
		$listing_package = self::_listing_package( $job_id );
		$state            = [ 'data' => self::_hydrate( $job_id, $definition['steps'] ) ];
		$return_url       = self::_dashboard_return_url( $job_id, $listing_package );

		$card_shell = CT_FLOW_DIR . '/templates/wizard-edit/' . $card . '-card-shell.php';
		if ( ! file_exists( $card_shell ) ) {
			self::_render_clean_error( 'תבנית העריכה לא נמצאה.' );
		}

		// Same Elementor/theme-chrome stripping wizard-shell.php uses — this
		// shell also calls wp_head()/wp_footer() directly with no Elementor
		// page around it. Reused via the public wrapper rather than duplicated.
		CT_Flow_Wizard_Page::prepare_minimal_shell_assets();

		$is_edit_mode = true;

		include $card_shell;
		exit;
	}

	// =========================================================================
	// AJAX: save a card
	// =========================================================================

	/**
	 * AJAX handler: validate and persist a merged edit-card save, then
	 * return the dashboard URL to redirect back to. No next-step, no
	 * auto-advance, no transient touched.
	 *
	 * Expected POST: nonce, card, job_id, fields
	 * Returns JSON: { success: true, redirect: string } | { success: false, errors|message }
	 *
	 * @return void
	 */
	public static function ajax_save_edit_card() {
		check_ajax_referer( CT_Flow_Wizard_Controller::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'יש להתחבר לחשבון.' ], 401 );
		}

		$card   = sanitize_key( $_POST['card']   ?? '' );
		$job_id = absint( $_POST['job_id'] ?? 0 );

		if ( ! isset( self::CARDS[ $card ] ) ) {
			wp_send_json_error( [ 'message' => 'כרטיס עריכה לא ידוע.' ], 400 );
		}

		$check = self::_check_ownership( $job_id );
		if ( is_wp_error( $check ) ) {
			wp_send_json_error( [ 'message' => $check->get_error_message() ], 403 );
		}

		$definition = self::CARDS[ $card ];
		$raw_fields = is_array( $_POST['fields'] ?? null ) ? $_POST['fields'] : [];

		$errors = CT_Flow_Wizard_Controller::validate_edit_fields( $definition['steps'], $raw_fields );
		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'errors' => $errors ], 422 );
		}

		// Guarded the same way ajax_save_step() guards its persistence body: any
		// PHP Error/Exception becomes a readable JSON error instead of a bare
		// 500, logged via mlog()->warn() (not ->error() — see FINDINGS.md R24,
		// that method does not exist on the Logger class).
		try {
			$fields_by_step = self::_split_about_card_fields( $raw_fields );
			CT_Flow_Wizard_Controller::persist_edit_card( $job_id, $fields_by_step );
		} catch ( \Throwable $e ) {
			mlog()->warn(
				'[CT Wizard Edit] ajax_save_edit_card fatal for job=' . $job_id . ' card=' . $card
				. ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
			);
			wp_send_json_error( [ 'message' => 'אירעה שגיאה בשמירה. אנא נסו שוב.' ], 500 );
		}

		wp_send_json_success( [
			'redirect' => self::_dashboard_return_url( $job_id, self::_listing_package( $job_id ) ),
		] );
	}

	/**
	 * Split the 'about' card's flat posted-fields blob into the per-step
	 * groups CT_Flow_Wizard_Controller::persist_edit_card() expects.
	 *
	 * This doubles as an explicit whitelist: any posted key not named here
	 * (e.g. a crafted ct_roadside, or an unrelated field entirely) is
	 * silently dropped rather than reaching update_post_meta() — stricter
	 * than the build flow's own _persist_fields_to_draft(), which writes
	 * whatever key it's given (see FINDINGS.md R5).
	 *
	 * @param array $raw_fields
	 * @return array{basics: array, contact: array, location: array}
	 */
	private static function _split_about_card_fields( array $raw_fields ): array {
		return [
			'basics'   => array_intersect_key( $raw_fields, array_flip( [ 'cart_type', 'job_title', 'job_logo' ] ) ),
			'contact'  => array_intersect_key( $raw_fields, array_flip( [ 'phone', 'whatsapp', 'ct_admin_phone' ] ) ),
			'location' => array_intersect_key( $raw_fields, array_flip( [ 'address', 'lat', 'lng', 'ct_location_link' ] ) ),
		];
	}

	// =========================================================================
	// Ownership
	// =========================================================================

	/**
	 * Verify the client-supplied job_id belongs to the current user and is a
	 * published 'cc' listing. Returns the loaded Listing object on success.
	 *
	 * No tier check here — see this file's class docblock. Tier eligibility
	 * for a card is the account-dashboard route's job, not edit mode's.
	 *
	 * "Not found", "wrong listing type", and "not owned by you" all return
	 * the identical generic message deliberately — distinguishing them would
	 * let a caller enumerate which job_ids exist. "Not published" gets its
	 * own message since it doesn't leak anything about a listing the
	 * requester doesn't already own.
	 *
	 * @param int $job_id
	 * @return \MyListing\Src\Listing|\WP_Error
	 */
	private static function _check_ownership( int $job_id ) {
		$forbidden = new \WP_Error( 'ct_edit_forbidden', 'אין לך הרשאה לערוך עמוד זה.' );

		$post = $job_id ? get_post( $job_id ) : null;
		if ( ! $post || $post->post_type !== 'job_listing' ) {
			return $forbidden;
		}

		if ( get_post_meta( $job_id, '_case27_listing_type', true ) !== 'cc' ) {
			return $forbidden;
		}

		if ( (int) $post->post_author !== get_current_user_id() ) {
			return $forbidden;
		}

		if ( ! in_array( $post->post_status, [ 'publish', 'pending' ], true ) ) {
			return new \WP_Error( 'ct_edit_not_published', 'ניתן לערוך רק עמודים שכבר פורסמו.' );
		}

		$listing = \MyListing\Src\Listing::get( $job_id );
		if ( ! $listing ) {
			return new \WP_Error( 'ct_edit_listing_object', 'אירעה שגיאה בטעינת העמוד.' );
		}

		return $listing;
	}

	/**
	 * @param int $job_id
	 * @return string 'pro' | 'free'
	 */
	private static function _listing_package( int $job_id ): string {
		return get_post_meta( $job_id, '_ct_listing_package', true ) === 'pro' ? 'pro' : 'free';
	}

	// =========================================================================
	// Hydration — read the published listing back into $state['data'][step] shape
	// =========================================================================

	/**
	 * @param int      $job_id
	 * @param string[] $steps
	 * @return array  Keyed by step, same shape templates read via $state['data'][step].
	 */
	private static function _hydrate( int $job_id, array $steps ): array {
		$data = [];

		foreach ( $steps as $step ) {
			switch ( $step ) {
				case 'basics':
					$data['basics'] = self::_hydrate_basics( $job_id );
					break;

				case 'contact':
					$data['contact'] = self::_hydrate_contact( $job_id );
					break;

				case 'location':
					$data['location'] = self::_hydrate_location( $job_id );
					break;
			}
		}

		return $data;
	}

	/**
	 * cart_type comes back from the 'type' taxonomy by slug — the same slug
	 * strings the build flow assigns it by (_save_taxonomies() looks up
	 * get_term_by('slug', $cart_type_slug, 'type')), so no mapping is needed.
	 * job_logo is stored as a GUID (_save_files_native() overwrites it with
	 * one); the template needs an attachment ID, so it's resolved back via
	 * attachment_url_to_postid().
	 *
	 * @param int $job_id
	 * @return array
	 */
	private static function _hydrate_basics( int $job_id ): array {
		$type_terms = wp_get_object_terms( $job_id, 'type', [ 'fields' => 'slugs' ] );
		$cart_type  = ! is_wp_error( $type_terms ) ? ( $type_terms[0] ?? '' ) : '';

		$logo_guid = get_post_meta( $job_id, '_job_logo', true );
		$logo_id   = $logo_guid ? attachment_url_to_postid( $logo_guid ) : 0;

		return [
			'job_title' => get_the_title( $job_id ),
			'cart_type' => $cart_type,
			'job_logo'  => $logo_id ?: '',
		];
	}

	/**
	 * Straight reads of the wizard's own draft-mirror postmeta (_phone,
	 * _whatsapp, _ct_admin_phone) — the same key names basics.php/contact.php
	 * already expect via $state['data']['contact'], not the differently-named
	 * native copies (_job_phone, _whatsapp_number) _save_simple_fields()
	 * also writes. persist_edit_card() keeps both copies in sync on save via
	 * the same two-layer pipeline the build flow uses, so reading either
	 * would agree — this reads the one matching the template's field names.
	 *
	 * @param int $job_id
	 * @return array
	 */
	private static function _hydrate_contact( int $job_id ): array {
		return [
			'phone'          => get_post_meta( $job_id, '_phone', true ),
			'whatsapp'       => get_post_meta( $job_id, '_whatsapp', true ),
			'ct_admin_phone' => get_post_meta( $job_id, '_ct_admin_phone', true ),
		];
	}

	/**
	 * Reads address/lat/lng from the mylisting_locations table via
	 * Location_Field — not the _latitude/_longitude/_location_coffee mirror
	 * postmeta — so it matches what persist_edit_card()'s
	 * _save_location_native() call will overwrite on save. ct_roadside is
	 * deliberately never populated (see location.php's is_edit_mode guard
	 * and FINDINGS.md R10) — its stored 'road' term carries no information
	 * about what the user actually chose, so there is nothing honest to
	 * hydrate it with.
	 *
	 * @param int $job_id
	 * @return array
	 */
	private static function _hydrate_location( int $job_id ): array {
		$out = [
			'address'          => '',
			'lat'              => '',
			'lng'              => '',
			'ct_location_link' => get_post_meta( $job_id, '_ct_location_link', true ),
		];

		$type    = \MyListing\Src\Listing_Type::get_by_name( 'cc' );
		$listing = \MyListing\Src\Listing::get( $job_id );

		if ( $type && $listing ) {
			$fields = $type->get_fields();
			if ( ! empty( $fields['job_location'] ) ) {
				$field = $fields['job_location'];
				$field->set_listing( $listing );
				$locations = $field->get_locations();
				$first     = $locations[0] ?? [];

				$out['address'] = $first['address'] ?? '';
				$out['lat']     = $first['lat']     ?? '';
				$out['lng']     = $first['lng']     ?? '';
			}
		}

		return $out;
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Duplicated from CT_Flow_Wizard_Page::_is_add_listing_page() rather than
	 * shared, so this class has zero coupling to the build-flow page class —
	 * a change to one page-detection check can't silently affect the other.
	 *
	 * @return bool
	 */
	private static function _is_add_listing_page(): bool {
		$add_listing_page_id = absint( c27()->get_setting( 'general_add_listing_page' ) );

		if ( $add_listing_page_id && is_page( $add_listing_page_id ) ) {
			return true;
		}

		return is_page( 'add-listing' );
	}

	/**
	 * Build the URL for a given card's edit-mode entry point on a listing.
	 * Shared by dashboard templates so the query-arg shape (and the
	 * add-listing-page lookup, matching class-dashboard-hooks.php's own
	 * fallback pattern) lives in one place rather than being copied into
	 * every card's dashboard button.
	 *
	 * @param string $card
	 * @param int    $job_id
	 * @return string
	 */
	public static function get_card_edit_url( string $card, int $job_id ): string {
		$add_listing_page_id = absint( c27()->get_setting( 'general_add_listing_page' ) );
		$base_url             = $add_listing_page_id ? get_permalink( $add_listing_page_id ) : home_url( '/add-listing/' );

		return add_query_arg( [
			'ct_edit_card' => $card,
			'job_id'       => $job_id,
		], $base_url );
	}

	/**
	 * @param int    $job_id
	 * @param string $listing_package
	 * @return string
	 */
	private static function _dashboard_return_url( int $job_id, string $listing_package ): string {
		if ( function_exists( 'ct_get_account_plan_url' ) ) {
			return ct_get_account_plan_url( $listing_package, 'my-page', $job_id );
		}

		return wc_get_account_endpoint_url( 'my-listings' );
	}

	/**
	 * Minimal, RTL-safe error page — deliberately not the wizard shell (no
	 * assets/state to prepare) and not a fatal. Used for both "the request
	 * can't be honored" cases from maybe_output_edit_card(): unknown card,
	 * ownership failure, or a missing template.
	 *
	 * @param string $message
	 * @return void  Exits.
	 */
	private static function _render_clean_error( string $message ): void {
		?><!DOCTYPE html>
		<html <?php language_attributes() ?> dir="rtl">
		<head>
			<meta charset="<?php bloginfo( 'charset' ) ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo esc_html( get_bloginfo( 'name' ) ) ?></title>
		</head>
		<body style="margin:0;padding:0;font-family:sans-serif;">
			<div style="max-width:420px;margin:96px auto;padding:0 24px;text-align:center;direction:rtl;">
				<p style="font-size:16px;color:#333;"><?php echo esc_html( $message ) ?></p>
				<a href="<?php echo esc_url( home_url( '/my-account/' ) ) ?>" style="color:#219156;font-weight:600;text-decoration:none;">
					חזרה לאזור האישי &larr;
				</a>
			</div>
		</body>
		</html>
		<?php
		exit;
	}
}
