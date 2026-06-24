<?php
/**
 * CT_Flow_Terms_Step
 *
 * Injects two custom steps into the MyListing Add Listing form:
 *
 *  ct-terms (priority 7) — scroll-through terms agreement, rendered before
 *    the listing form (priority 10).  Validates required checkboxes and saves
 *    agreement timestamp to user meta.
 *
 *  ct-payment-placeholder (priority 22) — informational payment step for the
 *    PRO package, inserted after the listing form and before the package-
 *    processing step (priority 25).  Acts as a UI pass-through; its handler
 *    just advances to the next step.  Only added for PRO (product ID 25) when
 *    the user is actually submitting (submit_job or continue is set).
 *
 * Additionally removes the built-in "preview" step for cc listing type
 * submissions so the flow goes directly to package processing and the done page.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Terms_Step {

	public static function init() {
		// Always register submission-step hooks for all cc requests.
		//
		// For new cc submissions that the wizard intercepts (via template_redirect
		// or the mylisting/show-add-listing-widget filter), Add_Listing_Form::render()
		// is never called, so these hooks register but never fire — no conflict.
		//
		// For the rare case where wizard interception fails entirely, these hooks
		// activate automatically as a graceful fallback, giving the user the legacy
		// terms + form flow instead of a completely broken bare form.
		//
		// For edit/switch actions, the listing_type === 'cc' guards inside each
		// step callback ensure they only fire when appropriate.

		// Inject our custom steps.  Priority 15 runs after the default steps
		// are set up but before the paid-listings controller at priority 20.
		add_filter( 'mylisting/submission-steps', [ __CLASS__, 'register_steps' ], 15 );

		// Remove preview step for cc listing type (we go straight to done).
		add_filter( 'mylisting/submission-steps', [ __CLASS__, 'remove_preview_for_cc' ], 25 );

		// Remove the paid-listings wc-process-package step for cc listing type.
		add_filter( 'mylisting/submission-steps', [ __CLASS__, 'remove_wc_process_for_cc' ], 30 );

		// Assign the WC package to new cc listings so _user_package_id is set.
		add_action( 'mylisting/submission/done', [ __CLASS__, 'assign_package_for_cc' ], 10 );
	}

	// -------------------------------------------------------------------------
	// Step registration
	// -------------------------------------------------------------------------

	/**
	 * Register the terms and payment-placeholder steps.
	 *
	 * @param array $steps
	 * @return array
	 */
	public static function register_steps( $steps ) {
		$steps['ct-terms'] = [
			'name'     => 'הסכמה לתנאים',
			'view'     => [ __CLASS__, 'render_terms_view' ],
			'handler'  => [ __CLASS__, 'handle_terms_submission' ],
			'priority' => 7,
		];

		// Payment placeholder: only for Pro + only during actual submission.
		$listing_package = ! empty( $_REQUEST['listing_package'] )
			? absint( c27()->get_package_id_for_validation( $_REQUEST['listing_package'] ) )
			: 0;

		$is_submitting = (
			( isset( $_REQUEST['submit_job'] ) && $_REQUEST['submit_job'] !== 'save--no-preview' )
			|| ( ! empty( $_REQUEST['continue'] ) )
		);

		if ( $listing_package === CT_FLOW_PRO_PRODUCT_ID && $is_submitting ) {
			$steps['ct-payment-placeholder'] = [
				'name'     => 'תשלום',
				'view'     => [ __CLASS__, 'render_payment_view' ],
				'handler'  => [ __CLASS__, 'handle_payment_submission' ],
				'priority' => 22,
			];
		}

		return $steps;
	}

	/**
	 * Remove the preview step for cc listing type submissions so the flow
	 * goes: terms → form → [payment for PRO] → done.
	 *
	 * @param array $steps
	 * @return array
	 */
	public static function remove_preview_for_cc( $steps ) {
		$listing_type = ! empty( $_REQUEST['listing_type'] )
			? sanitize_text_field( $_REQUEST['listing_type'] )
			: '';

		if ( $listing_type === 'cc' ) {
			unset( $steps['preview'] );
		}

		return $steps;
	}

	/**
	 * Remove the paid-listings wc-process-package step for cc listing type.
	 *
	 * Without this, the paid-listings controller redirects users to WC checkout
	 * after form submission because products 24/25 are priced items.  For the cc
	 * flow the Grow payment gateway is not yet integrated; listing status is
	 * handled by payments_disabled_submission_handler() in the done() step, which
	 * sets the listing to pending/publish based on the "Require Approval" setting.
	 *
	 * @param array $steps
	 * @return array
	 */
	public static function remove_wc_process_for_cc( $steps ) {
		$listing_type = ! empty( $_REQUEST['listing_type'] )
			? sanitize_text_field( $_REQUEST['listing_type'] )
			: '';

		if ( $listing_type === 'cc' ) {
			unset( $steps['wc-process-package'] );
		}

		return $steps;
	}

	// -------------------------------------------------------------------------
	// Terms step view
	// -------------------------------------------------------------------------

	/**
	 * Render the terms agreement step.
	 *
	 * @return void
	 */
	public static function render_terms_view() {
		$form        = \MyListing\Src\Forms\Add_Listing_Form::instance();
		$listing_pkg = ! empty( $_REQUEST['listing_package'] )
			? absint( c27()->get_package_id_for_validation( $_REQUEST['listing_package'] ) )
			: 0;
		$is_pro  = ( $listing_pkg === CT_FLOW_PRO_PRODUCT_ID );
		// Errors are already echoed by Base_Form::show_errors() before this view
		// is called. Pass an empty array so the template's own block stays silent.
		$errors  = [];

		require CT_FLOW_DIR . '/templates/terms-step.php';
	}

	// -------------------------------------------------------------------------
	// Terms step handler
	// -------------------------------------------------------------------------

	/**
	 * Validate the terms checkboxes and advance to the next step.
	 *
	 * @return void
	 */
	public static function handle_terms_submission() {
		// Only process on actual POST submissions from our form.
		if ( empty( $_POST['job_manager_form'] ) ) {
			return;
		}

		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();

		$listing_pkg = ! empty( $_REQUEST['listing_package'] )
			? absint( c27()->get_package_id_for_validation( $_REQUEST['listing_package'] ) )
			: 0;
		$is_pro = ( $listing_pkg === CT_FLOW_PRO_PRODUCT_ID );

		$valid = true;

		if ( $is_pro ) {
			if ( empty( $_POST['ct_listing_terms'] ) ) {
				$form->add_error( 'יש לאשר את תנאי השימוש של מסלול PRO.' );
				$valid = false;
			}
			if ( empty( $_POST['ct_cancellation_fee'] ) ) {
				$form->add_error( 'יש לאשר את תנאי הביטול וסיום ההתקשרות.' );
				$valid = false;
			}
		} else {
			if ( empty( $_POST['ct_listing_terms'] ) ) {
				$form->add_error( 'יש לאשר את תנאי השימוש.' );
				$valid = false;
			}
		}

		if ( ! $valid ) {
			return; // form will re-render the current step with errors
		}

		// Record agreement timestamp in user meta (listing will also get post meta later).
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), '_ct_terms_agreed_at', current_time( 'timestamp' ) );
			update_user_meta( get_current_user_id(), '_ct_terms_plan', $is_pro ? 'pro' : 'free' );
		}

		// Store in session so we know agreement happened (used by selective-approval).
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}
		$_SESSION['ct_terms_agreed'] = [
			'timestamp' => current_time( 'timestamp' ),
			'plan'      => $is_pro ? 'pro' : 'free',
		];

		$form->next_step();
	}

	// -------------------------------------------------------------------------
	// Payment placeholder view
	// -------------------------------------------------------------------------

	/**
	 * Render the PRO payment placeholder step.
	 *
	 * @return void
	 */
	public static function render_payment_view() {
		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		require CT_FLOW_DIR . '/templates/payment-placeholder.php';
	}

	// -------------------------------------------------------------------------
	// Payment step handler (Grow)
	// -------------------------------------------------------------------------

	/**
	 * Validate that Grow has authorized a payment before advancing the form.
	 *
	 * The webhook (CT_Grow_Webhook) sets `_ct_grow_transaction_id` when Grow
	 * confirms the pre-auth.  The browser submits this form (via ct-grow-wallet.js)
	 * right after the SDK fires its success event.  To handle the rare race where
	 * the webhook has not yet been processed, we call get_transaction_status()
	 * as a direct server-side fallback before showing an error.
	 *
	 * When credentials are not yet configured (development), the pass-through
	 * form in payment-placeholder.php sends ct_grow_paid=0; we advance anyway.
	 *
	 * @return void
	 */
	public static function handle_payment_submission() {
		// Only process on actual POST submissions from our form.
		if ( empty( $_POST['job_manager_form'] ) ) {
			return;
		}

		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();

		// If Grow is not integrated yet (no class or unconfigured), pass through.
		if ( ! class_exists( 'CT_Grow_Payment' ) || ! CT_Grow_Payment::is_configured() ) {
			$form->next_step();
			return;
		}

		$listing_id = $form->get_job_id();

		if ( ! $listing_id ) {
			$form->add_error( 'שגיאה: מזהה הרישום חסר. אנא חזרו לשלב הקודם.' );
			return;
		}

		// Fast path: webhook already confirmed and stored the transaction ID.
		if ( CT_Grow_Payment::has_charged_payment( $listing_id ) ) {
			$form->next_step();
			return;
		}

		// Slow path: webhook race — query Grow directly.
		$status = CT_Grow_Payment::get_transaction_status( $listing_id );

		if ( ! is_wp_error( $status ) ) {
			// statusCode 2 = paid/authorized in Grow's parameter-mapping.
			$status_code    = isset( $status['statusCode'] ) ? (int) $status['statusCode'] : -1;
			$transaction_id = $status['transactionId'] ?? '';

			if ( $status_code === 2 && $transaction_id ) {
				// Save the transaction ID so capture can run later.
				update_post_meta( $listing_id, '_ct_grow_transaction_id',    sanitize_text_field( $transaction_id ) );
				update_post_meta( $listing_id, '_ct_grow_transaction_token', sanitize_text_field( $status['transactionToken'] ?? '' ) );
				$form->next_step();
				return;
			}
		}

		$form->add_error( 'התשלום טרם אושר. אנא המתינו מספר שניות ונסו שוב.' );
	}

	// -------------------------------------------------------------------------
	// Package assignment
	// -------------------------------------------------------------------------

	/**
	 * Assign the WC listing package to a newly-submitted cc listing.
	 *
	 * Fires on `mylisting/submission/done` (priority 10), AFTER
	 * payments_disabled_submission_handler() has set the listing to
	 * pending/publish.  Hooking here (instead of save-listing-data) avoids a
	 * flow-breaking race condition: use-free-package sets listing status to
	 * `pending`, but the Add_Listing_Form constructor only accepts preview/draft
	 * as valid statuses — so if status was set to `pending` before the
	 * payment-placeholder form is submitted, the constructor resets job_id to 0
	 * and the flow snaps back to step 0 (terms step).
	 *
	 * Delegates to the paid-listings `use-free-package` action which:
	 *   - Creates a case27_user_package record with featured/verified settings
	 *     sourced from the WC product.
	 *   - Sets _user_package_id on the listing so package-conditioned fields
	 *     (logo, images, PRO-only fields) are visible in the admin edit screen.
	 *   - Calls wp_update_post(pending) — already pending at this point, so
	 *     redundant but harmless.
	 *
	 * @param int $listing_id  Newly-created listing post ID.
	 * @return void
	 */
	public static function assign_package_for_cc( $listing_id ) {
		$listing_type = sanitize_text_field( $_REQUEST['listing_type'] ?? '' );
		if ( $listing_type !== 'cc' ) {
			return;
		}

		$product_id = ! empty( $_REQUEST['listing_package'] )
			? absint( c27()->get_package_id_for_validation( $_REQUEST['listing_package'] ) )
			: 0;

		if ( ! $product_id ) {
			return;
		}

		$listing = \MyListing\Src\Listing::get( $listing_id );
		$product = wc_get_product( $product_id );

		if ( ! ( $listing && $product ) ) {
			return;
		}

		try {
			do_action( 'mylisting/payments/submission/use-free-package', $listing, $product );
		} catch ( \Exception $e ) {
			mlog()->error( '[CT Flow] Failed to assign package for cc listing #' . $listing_id . ': ' . $e->getMessage() );
		}
	}
}
