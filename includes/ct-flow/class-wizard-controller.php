<?php
/**
 * CT_Flow_Wizard_Controller
 *
 * Central controller for the multi-step listing submission wizard.
 *
 * Responsibilities:
 *  - Defines the ordered list of wizard steps and their metadata.
 *  - Persists wizard state (current step, completed steps, draft job_id)
 *    in a WordPress transient keyed by user ID.
 *  - Provides AJAX handlers for:
 *      ct_wizard_save_step   — save one step's data and advance
 *      ct_wizard_save_exit   — save current data and redirect
 *      ct_wizard_upload_file — server-side file upload with MIME validation
 *  - Handles Free listing publish on final wizard step (no payment needed).
 *  - Validates step data server-side before advancing (mirrors client-side).
 *
 * State is cleared on successful submission or explicit "Start Over".
 * Draft resume: if a transient exists and the user visits /add-listing/,
 * the wizard renders from the saved step instead of the landing page.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Wizard_Controller {

	/** Transient key prefix — appended with user ID. */
	const TRANSIENT_PREFIX  = 'ct_wizard_state_';

	/** Transient TTL: 7 days. */
	const TRANSIENT_TTL     = 604800;

	/** AJAX nonce action. */
	const NONCE_ACTION      = 'ct_wizard_save';

	/** Allowed image MIME types for upload validation. */
	const ALLOWED_IMAGE_MIME = [
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/webp',
	];

	/** Max upload size in bytes (3 MB). */
	const MAX_UPLOAD_BYTES = 3145728;

	/**
	 * Ordered step definitions.
	 * Each entry: [ 'template' => string, 'label' => string, 'required' => bool ]
	 */
	const STEPS = [
		'landing'          => [ 'template' => 'landing',         'label' => '',              'required' => false ],
		'intro-1'          => [ 'template' => 'step-intro',      'label' => 'שלב 1',         'required' => false ],
		'basics'           => [ 'template' => 'basics',          'label' => 'פרטי בסיס',     'required' => true  ],
		'contact'          => [ 'template' => 'contact',         'label' => 'יצירת קשר',     'required' => true  ],
		'location'         => [ 'template' => 'location',        'label' => 'מיקום',         'required' => true  ],
		'intro-2'          => [ 'template' => 'step-intro',      'label' => 'שלב 2',         'required' => false ],
		'amenities'        => [ 'template' => 'amenities',       'label' => 'מאפיינים',      'required' => false ],
		'menu-categories'  => [ 'template' => 'menu-categories', 'label' => 'קטגוריות תפריט','required' => false ],
		'images'           => [ 'template' => 'images',          'label' => 'תמונות',        'required' => true  ],
		'menu-upload'      => [ 'template' => 'menu-upload',     'label' => 'תפריט',         'required' => false ],
		'menu-details'     => [ 'template' => 'menu-details',    'label' => 'פרטי תפריט',   'required' => false ],
		'social-links'     => [ 'template' => 'social-links',    'label' => 'רשתות חברתיות','required' => false ],
		'intro-3'          => [ 'template' => 'step-intro',      'label' => 'שלב 3',         'required' => false ],
		'hours'            => [ 'template' => 'hours',           'label' => 'שעות פעילות',  'required' => false ],
		'terms'            => [ 'template' => 'terms',           'label' => 'תנאים',         'required' => true  ],
		'payment'          => [ 'template' => 'payment',         'label' => 'תשלום',         'required' => true  ],
		'success'          => [ 'template' => 'success',         'label' => 'סיום',          'required' => false ],
	];

	public static function init() {
		add_action( 'wp_ajax_ct_wizard_save_step',   [ __CLASS__, 'ajax_save_step' ] );
		add_action( 'wp_ajax_ct_wizard_save_exit',   [ __CLASS__, 'ajax_save_exit' ] );
		add_action( 'wp_ajax_ct_wizard_upload_file', [ __CLASS__, 'ajax_upload_file' ] );
	}

	// =========================================================================
	// State Management
	// =========================================================================

	/**
	 * Load wizard state for current user from transient.
	 *
	 * @param int $user_id
	 * @return array {
	 *   @type string   $current_step     Step key from STEPS.
	 *   @type string[] $completed_steps  Array of completed step keys.
	 *   @type int      $job_id           Draft listing post ID (0 if not yet created).
	 *   @type string   $listing_package  'free' | 'pro'.
	 *   @type array    $data             Saved field values keyed by step.
	 * }
	 */
	public static function get_state( int $user_id ): array {
		$state = get_transient( self::TRANSIENT_PREFIX . $user_id );

		if ( ! is_array( $state ) ) {
			$state = [
				'current_step'    => 'landing',
				'completed_steps' => [],
				'job_id'          => 0,
				'listing_package' => 'free',
				'data'            => [],
			];
		}

		return $state;
	}

	/**
	 * Persist wizard state for current user.
	 *
	 * @param int   $user_id
	 * @param array $state
	 * @return void
	 */
	public static function save_state( int $user_id, array $state ): void {
		set_transient( self::TRANSIENT_PREFIX . $user_id, $state, self::TRANSIENT_TTL );
	}

	/**
	 * Clear wizard state for current user (on completion or cancel).
	 *
	 * @param int $user_id
	 * @return void
	 */
	public static function clear_state( int $user_id ): void {
		delete_transient( self::TRANSIENT_PREFIX . $user_id );
	}

	/**
	 * Whether the current user has an in-progress wizard draft.
	 *
	 * @return bool
	 */
	public static function has_draft(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$state = get_transient( self::TRANSIENT_PREFIX . get_current_user_id() );

		return is_array( $state )
			&& ! empty( $state['job_id'] )
			&& $state['current_step'] !== 'landing'
			&& $state['current_step'] !== 'success';
	}

	// =========================================================================
	// Step Helpers
	// =========================================================================

	/**
	 * Return the ordered array of step keys.
	 *
	 * @param string $listing_package  'free' | 'pro'
	 * @return string[]
	 */
	public static function get_step_order( string $listing_package = 'free' ): array {
		$keys = array_keys( self::STEPS );

		if ( $listing_package !== 'pro' ) {
			// Remove payment step for Free users.
			$keys = array_filter( $keys, fn( $k ) => $k !== 'payment' );
			$keys = array_values( $keys );
		}

		return $keys;
	}

	/**
	 * Return the next step key after the given step.
	 *
	 * @param string $current_step
	 * @param string $listing_package
	 * @return string|null  Null if $current_step is the last step.
	 */
	public static function next_step( string $current_step, string $listing_package = 'free' ): ?string {
		$order = self::get_step_order( $listing_package );
		$idx   = array_search( $current_step, $order, true );

		if ( $idx === false || $idx >= count( $order ) - 1 ) {
			return null;
		}

		return $order[ $idx + 1 ];
	}

	/**
	 * Return the previous step key before the given step.
	 *
	 * @param string $current_step
	 * @param string $listing_package
	 * @return string|null  Null if already on first step.
	 */
	public static function prev_step( string $current_step, string $listing_package = 'free' ): ?string {
		$order = self::get_step_order( $listing_package );
		$idx   = array_search( $current_step, $order, true );

		if ( $idx === false || $idx === 0 ) {
			return null;
		}

		return $order[ $idx - 1 ];
	}

	/**
	 * Return the template filename for a given step key.
	 *
	 * @param string $step_key
	 * @return string  e.g. 'cart-type'
	 */
	public static function get_template( string $step_key ): string {
		return self::STEPS[ $step_key ]['template'] ?? $step_key;
	}

	/**
	 * Compute step progress percentage (0–100) excluding landing/success/intro steps.
	 *
	 * @param string $current_step
	 * @param string $listing_package
	 * @return int
	 */
	public static function get_progress( string $current_step, string $listing_package = 'free' ): int {
		$non_ui_steps = array_filter(
			self::get_step_order( $listing_package ),
			fn( $k ) => ! in_array( $k, [ 'landing', 'intro-1', 'intro-2', 'intro-3', 'success' ], true )
		);
		$non_ui_steps = array_values( $non_ui_steps );
		$idx          = array_search( $current_step, $non_ui_steps, true );

		if ( $idx === false ) {
			return 0;
		}

		return (int) round( ( $idx / ( count( $non_ui_steps ) - 1 ) ) * 100 );
	}

	// =========================================================================
	// AJAX: Save step and advance
	// =========================================================================

	/**
	 * AJAX handler: validate and save a single wizard step's data, then advance.
	 *
	 * Expected POST:
	 *   nonce         string
	 *   step          string   current step key
	 *   package       string   'free' | 'pro'
	 *   fields        array    key => value pairs for this step
	 *
	 * Returns JSON:
	 *   { success: true, next_step: string, job_id: int }
	 *   { success: false, errors: string[] }
	 *
	 * @return void
	 */
	public static function ajax_save_step() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'errors' => [ 'יש להתחבר לחשבון.' ] ], 401 );
		}

		$user_id = get_current_user_id();
		$step    = sanitize_key( $_POST['step']    ?? '' );
		$package = sanitize_key( $_POST['package'] ?? 'free' );
		$package = in_array( $package, [ 'free', 'pro' ], true ) ? $package : 'free';

		if ( ! array_key_exists( $step, self::STEPS ) ) {
			wp_send_json_error( [ 'errors' => [ 'שלב לא ידוע.' ] ], 400 );
		}

		$raw_fields = is_array( $_POST['fields'] ?? null ) ? $_POST['fields'] : [];
		$errors     = self::_validate_step( $step, $raw_fields );

		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'errors' => $errors ], 422 );
		}

		// Guard the whole persistence body: any PHP Error/Exception is turned into a
		// readable JSON error instead of a bare 500 "critical error" page, and logged
		// with a full stack trace so the failing step can be diagnosed.
		try {

		$state = self::get_state( $user_id );
		$state['listing_package'] = $package;

		// Merge step data into state.
		$state['data'][ $step ] = self::_sanitize_fields( $raw_fields );

		// Ensure draft post exists.
		$job_id = self::_ensure_draft( $state, $user_id );
		if ( is_wp_error( $job_id ) ) {
			wp_send_json_error( [ 'errors' => [ $job_id->get_error_message() ] ], 500 );
		}
		$state['job_id'] = $job_id;

		// Persist text fields to the draft post.
		self::_persist_fields_to_draft( $job_id, $step, $state['data'][ $step ] );

		// Mark step completed and advance.
		if ( ! in_array( $step, $state['completed_steps'], true ) ) {
			$state['completed_steps'][] = $step;
		}

		// Early publish: the listing goes live (publish) as soon as the location step is
		// saved — for BOTH Free and Pro. Steps saved afterwards persist only their own
		// data onto the live listing (NOT a full finalize): re-running finalize_listing
		// every step would re-fire _assign_wc_package, and MyListing's use-free-package
		// handler creates a fresh package post and resets the listing status on each call
		// — which created duplicate packages and unpublished the listing mid-wizard.
		// Both paths are wrapped so enrichment errors can never block step navigation.
		$is_public = in_array( get_post_status( $job_id ), [ 'publish', 'pending' ], true );

		if ( $step === 'location' && ! $is_public ) {
			try {
				self::finalize_listing( $job_id, $state );
			} catch ( \Throwable $e ) {
				mlog()->error( '[CT Wizard] early-publish finalize failed for #' . $job_id . ': ' . $e->getMessage() );
			}
			wp_update_post( [ 'ID' => $job_id, 'post_status' => 'publish' ] );
			$is_public = true;
			do_action( 'ct_flow/listing_published_early', $job_id, $package );
		} elseif ( $is_public ) {
			// Listing already live — sync just this step's data onto it.
			try {
				self::_sync_published_step( $job_id, $step, $state );
			} catch ( \Throwable $e ) {
				mlog()->error( '[CT Wizard] _sync_published_step failed for #' . $job_id . ' step=' . $step . ': ' . $e->getMessage() );
			}
		}

		$next = self::next_step( $step, $package );

		if ( $next ) {
			$state['current_step'] = $next;

			// Free listings have no payment step; fire the legacy submit hook on the
			// success transition for back-compat. Publishing itself now happens early
			// (after the location step), so this no longer drives the publish.
			if ( $next === 'success' && $package === 'free' ) {
				do_action( 'ct_flow/listing_submitted_free', $job_id );
			}

			// For PRO: store the package in post meta when entering the payment step so
			// the Grow webhook can read the original package even if the transient changes.
			if ( $next === 'payment' && $package === 'pro' ) {
				update_post_meta( $job_id, '_ct_listing_package', 'pro' );
			}
		} else {
			// $next === null: we are already on the final step (success) — no further action.
			$state['current_step'] = 'success';
		}

		self::save_state( $user_id, $state );

		wp_send_json_success( [
			'next_step' => $state['current_step'],
			'job_id'    => $job_id,
		] );

		} catch ( \Throwable $e ) {
			mlog()->error(
				'[CT Wizard] ajax_save_step fatal on step "' . $step . '" job=' . ( $job_id ?? 0 )
				. ': ' . $e->getMessage()
				. ' @ ' . $e->getFile() . ':' . $e->getLine()
				. "\n" . $e->getTraceAsString()
			);
			wp_send_json_error( [
				'errors' => [ 'אירעה שגיאה בשמירת השלב. אנא נסו שוב.' ],
				'debug'  => $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
			], 500 );
		}
	}

	// =========================================================================
	// AJAX: Save and exit
	// =========================================================================

	/**
	 * AJAX handler: save current step data as draft and return redirect URL.
	 *
	 * Expected POST: nonce, step, package, fields
	 * Returns JSON: { success: true, redirect: string }
	 *
	 * @return void
	 */
	public static function ajax_save_exit() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'יש להתחבר לחשבון.' ], 401 );
		}

		$user_id    = get_current_user_id();
		$step       = sanitize_key( $_POST['step']    ?? '' );
		$package    = sanitize_key( $_POST['package'] ?? 'free' );
		$raw_fields = is_array( $_POST['fields'] ?? null ) ? $_POST['fields'] : [];

		$state = self::get_state( $user_id );
		$state['listing_package']   = $package;
		$state['data'][ $step ]     = self::_sanitize_fields( $raw_fields );

		$job_id = self::_ensure_draft( $state, $user_id );
		if ( ! is_wp_error( $job_id ) ) {
			$state['job_id'] = $job_id;
			self::_persist_fields_to_draft( $job_id, $step, $state['data'][ $step ] );
		}

		self::save_state( $user_id, $state );

		$redirect = wc_get_account_endpoint_url( 'my-listings' );

		wp_send_json_success( [ 'redirect' => $redirect ] );
	}

	// =========================================================================
	// AJAX: File upload
	// =========================================================================

	/**
	 * AJAX handler: validate and store an uploaded file, return attachment ID.
	 *
	 * Expected POST: nonce, job_id, field_key
	 * Expected FILE: file
	 * Returns JSON: { success: true, attachment_id: int, url: string }
	 *
	 * Server-side security: real MIME type is verified via finfo, not just
	 * the file extension or the browser-supplied Content-Type header.
	 *
	 * @return void
	 */
	public static function ajax_upload_file() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'יש להתחבר לחשבון.' ], 401 );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( [ 'message' => 'לא נשלח קובץ.' ], 400 );
		}

		$file      = $_FILES['file'];
		$field_key = sanitize_key( $_POST['field_key'] ?? 'gallery' );
		$job_id    = absint( $_POST['job_id'] ?? 0 );

		// Validate file size.
		if ( $file['size'] > self::MAX_UPLOAD_BYTES ) {
			wp_send_json_error( [ 'message' => 'הקובץ גדול מדי (מקסימום 3MB).' ], 400 );
		}

		// Validate real MIME type via finfo.
		$finfo     = new finfo( FILEINFO_MIME_TYPE );
		$real_mime = $finfo->file( $file['tmp_name'] );

		// PDF is allowed only for menu-upload field.
		$allowed_mime = self::ALLOWED_IMAGE_MIME;
		if ( $field_key === 'menu_pdf' ) {
			$allowed_mime[] = 'application/pdf';
		}

		if ( ! in_array( $real_mime, $allowed_mime, true ) ) {
			mlog()->warning(
				'[CT Wizard Upload] Blocked upload attempt: mime=' . $real_mime
				. ' field=' . $field_key
				. ' user=' . get_current_user_id()
			);
			wp_send_json_error( [ 'message' => 'סוג קובץ לא נתמך.' ], 400 );
		}

		// Use WordPress media upload functions.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_upload( 'file', $job_id > 0 ? $job_id : 0 );

		if ( is_wp_error( $attachment_id ) ) {
			mlog()->error( '[CT Wizard Upload] media_handle_upload failed: ' . $attachment_id->get_error_message() );
			wp_send_json_error( [ 'message' => 'שגיאה בשמירת הקובץ.' ], 500 );
		}

		// Attach to the draft listing if we have a job_id.
		if ( $job_id > 0 ) {
			wp_update_post( [ 'ID' => $attachment_id, 'post_parent' => $job_id ] );
		}

		wp_send_json_success( [
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'thumb_url'     => wp_get_attachment_thumb_url( $attachment_id ),
		] );
	}

	// =========================================================================
	// Step validation (server-side mirror of client-side rules)
	// =========================================================================

	/**
	 * Validate a step's fields and return an array of error messages.
	 *
	 * @param string $step
	 * @param array  $fields  Raw POST fields for this step.
	 * @return string[]
	 */
	private static function _validate_step( string $step, array $fields ): array {
		$errors = [];

		switch ( $step ) {
			case 'basics':
				$type = sanitize_key( $fields['cart_type'] ?? '' );
				if ( ! in_array( $type, [ 'coffee-cart', 'food-truck' ], true ) ) {
					$errors[] = 'יש לבחור סוג עגלה.';
				}
				if ( empty( trim( $fields['job_title'] ?? '' ) ) ) {
					$errors[] = 'שם העגלה הוא שדה חובה.';
				}
				break;

			case 'contact':
				// At least one customer contact method must be provided.
				$phone    = trim( $fields['phone']    ?? '' );
				$whatsapp = trim( $fields['whatsapp'] ?? '' );
				if ( empty( $phone ) && empty( $whatsapp ) ) {
					$errors[] = 'יש להזין לפחות אמצעי קשר אחד ללקוחות (טלפון או WhatsApp).';
				}
				// Admin contact is required.
				if ( empty( trim( $fields['ct_admin_phone'] ?? '' ) ) ) {
					$errors[] = 'מספר הטלפון לקופיטרייל הוא שדה חובה.';
				}
				break;

			case 'location':
				// Fields now come from the OSM/Leaflet widget (lat/lng/address).
				if ( empty( trim( $fields['lat'] ?? '' ) )
					|| empty( trim( $fields['lng'] ?? '' ) ) ) {
					$errors[] = 'יש לבחור מיקום על המפה.';
				}
				break;

			case 'images':
				// Client ensures cover + 3 gallery images are uploaded before this handler
				// is called. Server verifies via attachment meta on the draft post.
				// We accept this step's fields as a list of attachment IDs.
				$cover   = absint( $fields['cover_image'] ?? 0 );
				$gallery = is_array( $fields['gallery'] ?? null ) ? $fields['gallery'] : [];
				if ( ! $cover ) {
					$errors[] = 'יש להעלות תמונת קאבר.';
				}
				if ( count( $gallery ) < 3 ) {
					$errors[] = 'יש להעלות לפחות 3 תמונות נוספות.';
				}
				break;

		case 'terms':
			if ( empty( $fields['ct_listing_terms'] ) ) {
				$errors[] = 'יש לאשר את תנאי השימוש.';
			}
			// PRO-specific cancellation policy checkbox.
			$package = sanitize_key( $fields['listing_package'] ?? 'free' );
			if ( $package === 'pro' && empty( $fields['ct_cancellation_fee'] ) ) {
				$errors[] = 'יש לאשר את תנאי הביטול וסיום ההתקשרות.';
			}
			break;

		case 'payment':
			// Verify a successful Grow transaction was recorded for this listing
			// before allowing advancement to the success screen.
			$job_id_check = absint( $_POST['job_id'] ?? 0 );
			if ( ! $job_id_check || ! get_post_meta( $job_id_check, '_ct_grow_transaction_id', true ) ) {
				$errors[] = 'לא נמצאה אסמכתא לתשלום. אנא השלימו את תהליך התשלום.';
			}
			break;
		}

		return $errors;
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Ensure a draft post exists for this wizard session.
	 * Creates one if job_id is 0 or the stored post is no longer valid.
	 *
	 * @param array $state
	 * @param int   $user_id
	 * @return int|WP_Error  Post ID on success, WP_Error on failure.
	 */
	private static function _ensure_draft( array $state, int $user_id ) {
		$job_id = $state['job_id'] ?? 0;

		if ( $job_id > 0 ) {
			$post = get_post( $job_id );
			// Accept publish/pending too: the listing is published mid-wizard (after the
			// location step), so a resume must keep editing the SAME post rather than
			// spawning a duplicate draft.
			if ( $post && (int) $post->post_author === $user_id
				&& in_array( $post->post_status, [ 'draft', 'auto-draft', 'publish', 'pending' ], true ) ) {
				return $job_id;
			}
		}

		$title  = sanitize_text_field( $state['data']['basics']['job_title'] ?? __( '(טיוטה)', 'my-listing' ) );
		$job_id = wp_insert_post( [
			'post_type'   => 'job_listing',
			'post_status' => 'draft',
			'post_author' => $user_id,
			'post_title'  => $title,
		], true );

		if ( ! is_wp_error( $job_id ) ) {
			// Both meta keys needed: _listing_type (legacy) and _case27_listing_type
			// (used by \MyListing\Src\Listing::get() to resolve the listing type object).
			update_post_meta( $job_id, '_listing_type', 'cc' );
			update_post_meta( $job_id, '_case27_listing_type', 'cc' );
		}

		return $job_id;
	}

	/**
	 * Persist text/select fields from a step to the draft post.
	 * File fields (attachment IDs) are stored directly by ajax_upload_file.
	 *
	 * @param int    $job_id
	 * @param string $step
	 * @param array  $fields  Already-sanitized field values.
	 * @return void
	 */
	private static function _persist_fields_to_draft( int $job_id, string $step, array $fields ): void {
		// Fields that update post columns rather than post meta.
		$post_column_fields = [ 'job_title', 'job_description' ];

		foreach ( $fields as $key => $value ) {
			if ( is_array( $value ) ) {
				// Store arrays (checkboxes, gallery IDs) as serialized meta.
				update_post_meta( $job_id, '_' . $key, $value );
				continue;
			}

			if ( $key === 'job_title' ) {
				wp_update_post( [ 'ID' => $job_id, 'post_title' => sanitize_text_field( $value ) ] );
				continue;
			}

			if ( $key === 'job_description' ) {
				wp_update_post( [ 'ID' => $job_id, 'post_content' => wp_kses_post( $value ) ] );
				continue;
			}

			update_post_meta( $job_id, '_' . $key, sanitize_textarea_field( (string) $value ) );
		}

		// Special: terms agreement timestamp.
		if ( $step === 'terms' && ! empty( $fields['ct_listing_terms'] ) ) {
			$post   = get_post( $job_id );
			$author = $post ? (int) $post->post_author : 0;
			if ( $author ) {
				update_user_meta( $author, '_ct_terms_agreed_at', current_time( 'timestamp' ) );
			}
			update_post_meta( $job_id, '_ct_terms_agreed_at', current_time( 'timestamp' ) );
		}

		update_post_meta( $job_id, '_ct_wizard_step_' . $step . '_saved_at', current_time( 'timestamp' ) );
	}

	/**
	 * Sanitize a flat or deeply-nested fields array from POST.
	 *
	 * The previous implementation used array_map('sanitize_text_field', $value)
	 * for arrays, which collapsed 2-D structures (e.g. hours[sun][open]) to empty
	 * strings — destroying work-hours data and breaking step resume. This recursive
	 * version correctly traverses any depth while sanitizing every leaf value.
	 *
	 * @param array $fields
	 * @return array
	 */
	private static function _sanitize_fields( array $fields ): array {
		$clean = [];

		foreach ( $fields as $key => $value ) {
			$key = sanitize_key( (string) $key );

			if ( is_array( $value ) ) {
				// Recurse so nested structures (e.g. hours, day_active) are preserved.
				$clean[ $key ] = self::_sanitize_fields( $value );
			} else {
				$clean[ $key ] = sanitize_textarea_field( (string) $value );
			}
		}

		return $clean;
	}

	// =========================================================================
	// Native Field Persistence (finalize on completion)
	// =========================================================================

	/**
	 * Transform all accumulated wizard state data and persist it using
	 * MyListing's native field system. Called exactly once before publishing —
	 * for Free listings from ajax_save_step(), for PRO from the Grow webhook.
	 *
	 * Persistence targets:
	 *   - mylisting_locations table  (via Location_Field::update)
	 *   - mylisting_workhours table  (via Work_Hours_Field::update)
	 *   - wp_term_relationships      (via wp_set_object_terms)
	 *   - postmeta                   (files, links, simple fields)
	 *
	 * @param int   $job_id  The listing post ID.
	 * @param array $state   Full wizard state from transient.
	 * @return void
	 */
	public static function finalize_listing( int $job_id, array $state ): void {
		// Ensure _case27_listing_type is set so Listing::get() resolves the type object.
		update_post_meta( $job_id, '_case27_listing_type', 'cc' );

		$listing = \MyListing\Src\Listing::get( $job_id );
		if ( ! $listing ) {
			mlog()->error( '[CT Wizard] finalize_listing: could not load Listing object for post #' . $job_id );
			return;
		}

		self::_save_location_native( $job_id, $listing, $state );
		self::_save_work_hours_native( $job_id, $listing, $state );
		self::_save_taxonomies( $job_id, $state );
		self::_save_files_native( $job_id, $state );
		self::_save_links_native( $job_id, $state );
		self::_save_simple_fields( $job_id, $state );
		self::_assign_wc_package( $job_id, $state['listing_package'] ?? 'free' );

		mlog()->info( '[CT Wizard] finalize_listing completed for listing #' . $job_id );
	}

	/**
	 * Persist a single step's data onto an already-published listing.
	 *
	 * Used for steps that come AFTER the early-publish point (the location step).
	 * Unlike finalize_listing() this deliberately routes only to the native saver
	 * relevant to the given step and NEVER touches package assignment — re-running
	 * _assign_wc_package on every step would create duplicate packages and reset the
	 * listing status via MyListing's use-free-package handler.
	 *
	 * @param int    $job_id
	 * @param string $step    The step key that was just saved.
	 * @param array  $state   Full wizard state.
	 * @return void
	 */
	private static function _sync_published_step( int $job_id, string $step, array $state ): void {
		switch ( $step ) {
			case 'amenities':
			case 'menu-categories':
				self::_save_taxonomies( $job_id, $state );
				break;

			case 'images':
			case 'menu-upload':
				self::_save_files_native( $job_id, $state );
				break;

			case 'social-links':
				self::_save_links_native( $job_id, $state );
				break;

			case 'hours':
				$listing = \MyListing\Src\Listing::get( $job_id );
				if ( $listing ) {
					self::_save_work_hours_native( $job_id, $listing, $state );
				}
				self::_save_simple_fields( $job_id, $state );
				break;

			case 'contact':
				self::_save_simple_fields( $job_id, $state );
				break;
		}
	}

	/**
	 * Save the listing's geographic location to the mylisting_locations table
	 * by delegating to MyListing's Location_Field class.
	 *
	 * The wizard stores lat/lng/address under $state['data']['location'].
	 * Location_Field::get_posted_value() reads from $_POST['job_location'],
	 * so we inject there, trigger the update, then clean up.
	 *
	 * @param int    $job_id
	 * @param object $listing  MyListing Listing object.
	 * @param array  $state
	 * @return void
	 */
	private static function _save_location_native( int $job_id, $listing, array $state ): void {
		$loc     = $state['data']['location'] ?? [];
		// Field names match the OSM/Leaflet widget (lat/lng/address).
		$lat     = trim( $loc['lat']     ?? '' );
		$lng     = trim( $loc['lng']     ?? '' );
		$address = trim( $loc['address'] ?? '' );

		if ( ! $lat || ! $lng ) {
			mlog()->warning( '[CT Wizard] _save_location_native: missing lat/lng for listing #' . $job_id );
			return;
		}

		$type = \MyListing\Src\Listing_Type::get_by_name( 'cc' );
		if ( ! $type ) {
			mlog()->error( '[CT Wizard] _save_location_native: listing type "cc" not found.' );
			return;
		}

		$fields = $type->get_fields();
		if ( empty( $fields['job_location'] ) ) {
			mlog()->error( '[CT Wizard] _save_location_native: field "job_location" not found on cc type.' );
			return;
		}

		// Location_Field::get_posted_value() expects an array of location objects.
		$_POST['job_location'] = [ [
			'address' => $address,
			'lat'     => $lat,
			'lng'     => $lng,
		] ];

		$field = $fields['job_location'];
		$field->set_listing( $listing );
		$field->update();

		unset( $_POST['job_location'] );

		// Location_Field::update() only writes the mylisting_locations table. The live
		// site's custom features (similar-carts carousel, routing plugin, daily export)
		// read these post meta keys instead, so mirror lat/lng/address into postmeta —
		// matching how the original WP-All-Import migration populated them.
		update_post_meta( $job_id, '_latitude',        $lat );
		update_post_meta( $job_id, '_longitude',       $lng );
		update_post_meta( $job_id, '_location_coffee', $address );
	}

	/**
	 * Save work hours to the mylisting_workhours table via Work_Hours_Field.
	 *
	 * The wizard stores hours under $state['data']['hours'] with short day keys
	 * (sun/mon/tue/…) and an optional day_active sub-array. MyListing expects
	 * full English day names and a status + slot-0 structure.
	 *
	 * @param int    $job_id
	 * @param object $listing
	 * @param array  $state
	 * @return void
	 */
	private static function _save_work_hours_native( int $job_id, $listing, array $state ): void {
		$hours_data = $state['data']['hours'] ?? [];
		if ( empty( $hours_data ) ) {
			return;
		}

		$day_active = (array) ( $hours_data['day_active'] ?? [] );
		$hours      = (array) ( $hours_data['hours']      ?? [] );

		$day_map = [
			'sun' => 'Sunday',
			'mon' => 'Monday',
			'tue' => 'Tuesday',
			'wed' => 'Wednesday',
			'thu' => 'Thursday',
			'fri' => 'Friday',
			'sat' => 'Saturday',
		];

		$work_hours = [ 'timezone' => 'Asia/Jerusalem' ];

		foreach ( $day_map as $wizard_key => $ml_day ) {
			$is_active = ! empty( $day_active[ $wizard_key ] );
			$day_hours = $hours[ $wizard_key ] ?? [];

			if ( $is_active && ! empty( $day_hours['open'] ) && ! empty( $day_hours['close'] ) ) {
				$work_hours[ $ml_day ] = [
					'status' => 'enter-hours',
					0        => [
						'from' => sanitize_text_field( $day_hours['open'] ),
						'to'   => sanitize_text_field( $day_hours['close'] ),
					],
				];
			} else {
				$work_hours[ $ml_day ] = [ 'status' => 'closed-all-day' ];
			}
		}

		$type = \MyListing\Src\Listing_Type::get_by_name( 'cc' );
		if ( ! $type ) {
			return;
		}

		$fields = $type->get_fields();
		if ( empty( $fields['work_hours'] ) ) {
			mlog()->error( '[CT Wizard] _save_work_hours_native: field "work_hours" not found on cc type.' );
			return;
		}

		// Work_Hours_Field::get_posted_value() reads $_POST[$this->key] directly.
		$_POST['work_hours'] = $work_hours;

		$field = $fields['work_hours'];
		$field->set_listing( $listing );
		$field->update();

		unset( $_POST['work_hours'] );
	}

	/**
	 * Save cart type, menu categories, and roadside access to their
	 * respective WordPress taxonomies using wp_set_object_terms().
	 *
	 * Taxonomy mapping:
	 *   cart_type        -> 'type'     (slug-based term lookup)
	 *   menu_categories  -> 'foodtype' (slug-based term lookup)
	 *   ct_roadside      -> 'road'     (first available term in the taxonomy)
	 *
	 * @param int   $job_id
	 * @param array $state
	 * @return void
	 */
	private static function _save_taxonomies( int $job_id, array $state ): void {
		// Cart type -> 'type' taxonomy. Moved onto the basics step; fall back to
		// the legacy cart-type step key for drafts saved before the merge.
		$cart_type_slug = sanitize_key(
			$state['data']['basics']['cart_type']
			?? ( $state['data']['cart-type']['cart_type'] ?? '' )
		);
		if ( $cart_type_slug ) {
			$term = get_term_by( 'slug', $cart_type_slug, 'type' );
			if ( $term instanceof WP_Term ) {
				wp_set_object_terms( $job_id, [ $term->term_id ], 'type' );
			} else {
				mlog()->warning( '[CT Wizard] _save_taxonomies: term not found in "type" for slug=' . $cart_type_slug );
			}
		}

		// Menu categories -> 'foodtype' taxonomy.
		$menu_cats = (array) ( $state['data']['menu-categories']['menu_categories'] ?? [] );
		if ( ! empty( $menu_cats ) ) {
			$term_ids = [];
			foreach ( $menu_cats as $slug ) {
				$term = get_term_by( 'slug', sanitize_key( $slug ), 'foodtype' );
				if ( $term instanceof WP_Term ) {
					$term_ids[] = $term->term_id;
				}
			}
			if ( ! empty( $term_ids ) ) {
				wp_set_object_terms( $job_id, $term_ids, 'foodtype' );
			}
		}

		// Roadside checkbox -> 'road' taxonomy (set first available term when checked).
		$roadside = $state['data']['location']['ct_roadside'] ?? '';
		if ( $roadside ) {
			$road_terms = get_terms( [ 'taxonomy' => 'road', 'hide_empty' => false, 'number' => 1 ] );
			if ( ! empty( $road_terms ) && ! is_wp_error( $road_terms ) ) {
				wp_set_object_terms( $job_id, [ $road_terms[0]->term_id ], 'road' );
			} else {
				mlog()->warning( '[CT Wizard] _save_taxonomies: no terms found in "road" taxonomy.' );
			}
		}

		// Amenities -> 'case27_job_listing_tags' taxonomy (the cc type's "job_tags" field).
		// append=true so the admin-assigned kosher/halacha/army tags (term IDs 209/484/453)
		// the export plugin reads are preserved alongside the wizard's amenity terms.
		$amenities = (array) ( $state['data']['amenities']['amenities'] ?? [] );
		if ( ! empty( $amenities ) ) {
			$term_ids = [];
			foreach ( $amenities as $slug ) {
				$term = get_term_by( 'slug', sanitize_key( $slug ), 'case27_job_listing_tags' );
				if ( $term instanceof WP_Term ) {
					$term_ids[] = $term->term_id;
				} else {
					mlog()->warning( '[CT Wizard] _save_taxonomies: term not found in "case27_job_listing_tags" for slug=' . $slug );
				}
			}
			if ( ! empty( $term_ids ) ) {
				wp_set_object_terms( $job_id, $term_ids, 'case27_job_listing_tags', true );
			}
		}
	}

	/**
	 * Save logo, cover, and gallery images as GUID URLs in postmeta — the
	 * format MyListing's file fields use for storage and retrieval.
	 *
	 * The wizard stores WordPress attachment IDs; we resolve each to its GUID
	 * (canonical upload URL) and ensure the attachment is properly linked to
	 * the listing post (post_parent + publish status).
	 *
	 * @param int   $job_id
	 * @param array $state
	 * @return void
	 */
	private static function _save_files_native( int $job_id, array $state ): void {
		// Logo.
		$logo_id = absint( $state['data']['basics']['job_logo'] ?? 0 );
		if ( $logo_id ) {
			$guid = get_post_field( 'guid', $logo_id );
			if ( $guid ) {
				update_post_meta( $job_id, '_job_logo', $guid );
				wp_update_post( [ 'ID' => $logo_id, 'post_parent' => $job_id, 'post_status' => 'inherit' ] );
			}
		}

		// Cover image.
		$cover_id = absint( $state['data']['images']['cover_image'] ?? 0 );
		if ( $cover_id ) {
			$guid = get_post_field( 'guid', $cover_id );
			if ( $guid ) {
				update_post_meta( $job_id, '_job_cover', $guid );
				wp_update_post( [ 'ID' => $cover_id, 'post_parent' => $job_id, 'post_status' => 'inherit' ] );
			}
		}

		// Gallery (array of attachment IDs).
		$gallery_ids = array_filter( array_map( 'absint', (array) ( $state['data']['images']['gallery'] ?? [] ) ) );
		if ( ! empty( $gallery_ids ) ) {
			$gallery_guids = [];
			foreach ( $gallery_ids as $gid ) {
				$guid = get_post_field( 'guid', $gid );
				if ( $guid ) {
					$gallery_guids[] = $guid;
					wp_update_post( [ 'ID' => $gid, 'post_parent' => $job_id, 'post_status' => 'inherit' ] );
				}
			}
			if ( ! empty( $gallery_guids ) ) {
				update_post_meta( $job_id, '_job_gallery', $gallery_guids );
			}
		}

		// Menu PDF (cc field slug "menupdf"). Only the PDF option maps to a cc field;
		// the menu_image option has no corresponding field and is intentionally skipped.
		$menupdf_id = absint( $state['data']['menu-upload']['menu_pdf'] ?? 0 );
		if ( $menupdf_id ) {
			$guid = get_post_field( 'guid', $menupdf_id );
			if ( $guid ) {
				update_post_meta( $job_id, '_menupdf', $guid );
				wp_update_post( [ 'ID' => $menupdf_id, 'post_parent' => $job_id, 'post_status' => 'inherit' ] );
			}
		}
	}

	/**
	 * Save social network links as a serialized array in _links postmeta
	 * (the format MyListing's Links_Field expects) and TikTok as a
	 * separate custom meta key not supported by MyListing's links field.
	 *
	 * @param int   $job_id
	 * @param array $state
	 * @return void
	 */
	private static function _save_links_native( int $job_id, array $state ): void {
		$social = $state['data']['social-links'] ?? [];
		$links  = [];

		$network_map = [
			'instagram' => 'Instagram',
			'facebook'  => 'Facebook',
			'website'   => 'Website',
		];

		foreach ( $network_map as $wizard_key => $ml_network ) {
			$url = esc_url_raw( trim( $social[ $wizard_key ] ?? '' ) );
			if ( $url ) {
				$links[] = [ 'network' => $ml_network, 'url' => $url ];
			}
		}

		if ( ! empty( $links ) ) {
			// _links is what MyListing's Links_Field stores to / reads from.
			update_post_meta( $job_id, '_links', $links );
		}

		// TikTok: not supported by MyListing's links field, stored separately.
		$tiktok = esc_url_raw( trim( $social['tiktok'] ?? '' ) );
		if ( $tiktok ) {
			update_post_meta( $job_id, '_tiktok_url', $tiktok );
		}
	}

	/**
	 * Save scalar / URL custom fields directly to postmeta.
	 * These fields have no associated MyListing field class — they are either
	 * simple MyListing preset fields (phone) or CT-specific custom fields.
	 *
	 * @param int   $job_id
	 * @param array $state
	 * @return void
	 */
	private static function _save_simple_fields( int $job_id, array $state ): void {
		$contact  = $state['data']['contact']  ?? [];
		$location = $state['data']['location'] ?? [];
		$hours    = $state['data']['hours']    ?? [];
		$terms    = $state['data']['terms']    ?? [];

		// Public-facing phone (MyListing preset: _job_phone).
		$phone = sanitize_text_field( $contact['phone'] ?? '' );
		if ( $phone ) {
			update_post_meta( $job_id, '_job_phone', $phone );
		}

		// WhatsApp contact number. The cc listing type's field slug is "whatsapp_number",
		// so MyListing renders it from _whatsapp_number — not _whatsapp.
		$whatsapp = sanitize_text_field( $contact['whatsapp'] ?? '' );
		if ( $whatsapp ) {
			update_post_meta( $job_id, '_whatsapp_number', $whatsapp );
		}

		// CoffeeTrail admin contact — not shown publicly (custom).
		$admin_phone = sanitize_text_field( $contact['ct_admin_phone'] ?? '' );
		if ( $admin_phone ) {
			update_post_meta( $job_id, '_ct_admin_phone', $admin_phone );
		}

		// Google Maps / Waze location link (custom).
		$loc_link = esc_url_raw( trim( $location['ct_location_link'] ?? '' ) );
		if ( $loc_link ) {
			update_post_meta( $job_id, '_ct_location_link', $loc_link );
		}

		// Google Business profile link shown alongside work hours (custom).
		$google_biz = esc_url_raw( trim( $hours['ct_google_biz_link'] ?? '' ) );
		if ( $google_biz ) {
			update_post_meta( $job_id, '_ct_google_biz_link', $google_biz );
		}

		// Terms agreement: preserve the original timestamp recorded when the user
		// first agreed (in _persist_fields_to_draft).  add_post_meta with $unique=true
		// is a no-op if the key already exists, so the legal trail is not overwritten.
		if ( ! empty( $terms['ct_listing_terms'] ) ) {
			$now    = current_time( 'timestamp' );
			$post   = get_post( $job_id );
			$author = $post ? (int) $post->post_author : 0;
			add_post_meta( $job_id, '_ct_terms_agreed_at', $now, true );
			if ( $author && ! get_user_meta( $author, '_ct_terms_agreed_at', true ) ) {
				update_user_meta( $author, '_ct_terms_agreed_at', $now );
			}
		}
	}

	/**
	 * Assign a WooCommerce/MyListing package to the listing by firing the
	 * native mylisting/payments/submission/use-free-package action, which
	 * registers the listing against the product's usage quota.
	 *
	 * Mirrors the logic in CT_Flow_Terms_Step::assign_package_for_cc().
	 *
	 * @param int    $job_id
	 * @param string $package  'free' | 'pro'
	 * @return void
	 */
	private static function _assign_wc_package( int $job_id, string $package ): void {
		// Early publish runs finalize_listing (and thus this method) after the location
		// step — before payment for PRO. Do NOT register the PRO package against its WC
		// usage quota until a Grow transaction is recorded; the Grow webhook re-runs
		// finalize_listing after payment, at which point this assignment proceeds.
		if ( $package === 'pro' && ! get_post_meta( $job_id, '_ct_grow_transaction_id', true ) ) {
			return;
		}

		$product_id = ( $package === 'pro' ) ? CT_FLOW_PRO_PRODUCT_ID : CT_FLOW_FREE_PRODUCT_ID;

		$listing = \MyListing\Src\Listing::get( $job_id );
		$product = wc_get_product( $product_id );

		if ( ! ( $listing && $product ) ) {
			mlog()->warning(
				'[CT Wizard] _assign_wc_package: listing or product not found'
				. ' — job_id=' . $job_id . ' product_id=' . $product_id
			);
			return;
		}

		// Only assign the package once. use_free_package creates a NEW package post and
		// resets the listing status on every call, so guard against re-running it.
		if ( get_post_meta( $job_id, '_ct_package_assigned', true ) ) {
			return;
		}

		// Catch \Throwable (not just \Exception): MyListing's use_free_package handler can
		// raise a PHP Error (e.g. a type error deep in Package::create), which would
		// otherwise bubble up as a 500 critical error during the wizard step save.
		try {
			do_action( 'mylisting/payments/submission/use-free-package', $listing, $product );
			update_post_meta( $job_id, '_ct_package_assigned', 1 );
		} catch ( \Throwable $e ) {
			mlog()->error(
				'[CT Wizard] _assign_wc_package failed for listing #' . $job_id
				. ': ' . $e->getMessage()
				. ' @ ' . $e->getFile() . ':' . $e->getLine()
			);
		}
	}
}
