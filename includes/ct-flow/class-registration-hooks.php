<?php
/**
 * CT_Flow_Registration
 *
 * Customises the WooCommerce My Account registration form for the
 * CoffeeTrail listing-owner registration flow:
 *
 *  - Redirects non-logged-in visitors of the add-listing page (when a
 *    listing_package param is present) to the registration page, with a
 *    redirect back to the add-listing URL after successful sign-up.
 *  - Displays a Hebrew "coffee-cart owners only" notice above the form.
 *  - Shows the selected plan (Free / PRO) as a badge.
 *  - Adds a phone number field and a marketing-consent checkbox to the form.
 *    T&C checkboxes live on the dedicated terms step (class-terms-step.php).
 *  - Surfaces social-login buttons on the Register tab (same hook the theme
 *    already uses for the Login tab).
 *  - Defaults the My Account page to the Register tab when arriving from an
 *    add-listing URL with a plan parameter.
 *  - After successful registration, WooCommerce reads $_REQUEST['redirect'] and
 *    returns the user to the add-listing page automatically.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Registration {

	public static function init() {
		// Redirect unauthenticated add-listing visitors to the custom register page.
		add_action( 'template_redirect', [ __CLASS__, 'redirect_guests_to_register' ] );
	}

	// -------------------------------------------------------------------------
	// Auth redirect
	// -------------------------------------------------------------------------

	/**
	 * Redirect non-logged-in users away from the add-listing page to the
	 * WooCommerce registration form, carrying the current URL as the post-
	 * registration redirect target.
	 *
	 * Fires on template_redirect (before any output).  Only activates when:
	 *  - The current page is the configured add-listing page.
	 *  - A listing_package parameter is present (user arrived via a plan button).
	 *  - The user is not logged in.
	 *
	 * @return void
	 */
	public static function redirect_guests_to_register() {
		if ( is_user_logged_in() ) {
			return;
		}

		// Only fire for our specific add-listing flow URLs — all three params must
		// be present (listing_type + listing_package + skip_selection).  This is
		// specific enough to avoid misfiring on any other page, and does not rely
		// on is_page() / c27 setting keys which may return 0.
		if ( empty( $_REQUEST['listing_package'] )
			|| empty( $_REQUEST['listing_type'] )
			|| empty( $_REQUEST['skip_selection'] )
		) {
			return;
		}

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		// Use the custom full-page registration template if it exists; fall back
		// to the standard WooCommerce My Account registration page.
		$register_page = get_page_by_path( 'ct-register' );
		$base_url      = $register_page ? get_permalink( $register_page ) : \MyListing\get_register_url();

		$redirect_url = add_query_arg( 'redirect', rawurlencode( $current_url ), $base_url );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Return the WC product ID (24 = Free, 25 = Pro) inferred from the
	 * redirect URL that was passed to the registration page, or 0 if unknown.
	 *
	 * @return int
	 */
	public static function get_plan_from_redirect() {
		if ( empty( $_REQUEST['redirect'] ) ) {
			return 0;
		}
		$redirect = urldecode( sanitize_text_field( $_REQUEST['redirect'] ) );
		parse_str( (string) parse_url( $redirect, PHP_URL_QUERY ), $params );
		$raw_pkg = $params['listing_package'] ?? 0;
		return $raw_pkg ? absint( c27()->get_package_id_for_validation( $raw_pkg ) ) : 0;
	}

	/**
	 * Return the URL for a given policy page.
	 *
	 * Configurable via filters in functions.php so you never need to touch this
	 * file again once real pages are created.  Fallbacks:
	 *   - ct_privacy_policy_url  → WordPress built-in privacy page URL
	 *   - ct_terms_url_free      → privacy policy URL (same page until you split them)
	 *   - ct_terms_url_pro       → privacy policy URL
	 *   - ct_cancellation_url_pro → '#' (no page yet)
	 *
	 * Add in functions.php once pages exist:
	 *   add_filter( 'ct_terms_url_free',       fn() => 'https://example.com/terms-free' );
	 *   add_filter( 'ct_terms_url_pro',        fn() => 'https://example.com/terms-pro' );
	 *   add_filter( 'ct_cancellation_url_pro', fn() => 'https://example.com/cancellation' );
	 *   add_filter( 'ct_privacy_policy_url',   fn() => 'https://example.com/privacy' );
	 *
	 * @param string $key  One of: privacy, terms_free, terms_pro, cancellation_pro
	 * @return string  Absolute URL.
	 */
	public static function get_policy_url( $key ) {
		$privacy_default = get_privacy_policy_url() ?: '#';

		switch ( $key ) {
			case 'privacy':
				return esc_url( (string) apply_filters( 'ct_privacy_policy_url', $privacy_default ) );
			case 'terms_free':
				return esc_url( (string) apply_filters( 'ct_terms_url_free', $privacy_default ) );
			case 'terms_pro':
				return esc_url( (string) apply_filters( 'ct_terms_url_pro', $privacy_default ) );
			case 'cancellation_pro':
				return esc_url( (string) apply_filters( 'ct_cancellation_url_pro', '#' ) );
			default:
				return '#';
		}
	}

	// -------------------------------------------------------------------------
	// Hooks
	// -------------------------------------------------------------------------

	/**
	 * Banner at the top of the registration form:
	 *  - "Coffee cart / food truck owners only" notice.
	 *  - Selected plan badge with a link to switch plans.
	 *
	 * @return void
	 */
	public static function render_owner_notice() {
		$pkg_id     = self::get_plan_from_redirect();
		$is_pro     = ( $pkg_id === CT_FLOW_PRO_PRODUCT_ID );
		$plan_label = $is_pro ? 'PRO' : 'חינמי';
		$plan_class = $is_pro ? 'ct-plan-badge--pro' : 'ct-plan-badge--free';

		// Link to switch to the other plan (both land on the same add-listing page).
		$add_listing_page_id = absint( c27()->get_setting( 'general_add_listing_page' ) );
		$add_listing_url     = $add_listing_page_id ? get_permalink( $add_listing_page_id ) : home_url( '/' );
		$switch_pkg_id       = $is_pro ? CT_FLOW_FREE_PRODUCT_ID : CT_FLOW_PRO_PRODUCT_ID;
		$switch_url = add_query_arg(
			[ 'listing_type' => 'cc', 'listing_package' => $switch_pkg_id, 'skip_selection' => 1 ],
			$add_listing_url
		);
		$switch_label = $is_pro ? 'החלפה למסלול חינמי' : 'שדרג למסלול PRO';
		?>
		<div class="ct-register-notice" dir="rtl">
			<div class="ct-register-notice__alert">
				<i class="material-icons">info_outline</i>
				<strong>ההרשמה מיועדת לבעלי עגלות קפה / פוד טראקים בלבד.</strong>
			</div>

			<?php if ( $pkg_id ) : ?>
				<div class="ct-register-notice__plan">
					מסלול שנבחר:&nbsp;
					<span class="ct-plan-badge <?php echo esc_attr( $plan_class ) ?>"><?php echo esc_html( $plan_label ) ?></span>
					&nbsp;&mdash;&nbsp;
					<a href="<?php echo esc_url( $switch_url ) ?>" class="ct-plan-switch-link">
						<?php echo esc_html( $switch_label ) ?>
					</a>
				</div>
			<?php endif ?>
		</div>
		<?php
	}

	/**
	 * Render the phone number input field.
	 *
	 * Saves to `billing_phone` — the WooCommerce standard meta key, so it
	 * auto-populates the user's billing details as well.
	 *
	 * @return void
	 */
	public static function render_phone_field() {
		$value = sanitize_text_field( $_POST['billing_phone'] ?? '' );
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" id="billing_phone_field" dir="rtl">
			<label for="billing_phone">
				טלפון נייד&nbsp;<abbr class="required" title="שדה חובה">*</abbr>
			</label>
			<input
				type="tel"
				class="woocommerce-Input woocommerce-Input--text input-text"
				name="billing_phone"
				id="billing_phone"
				value="<?php echo esc_attr( $value ) ?>"
				autocomplete="tel"
			>
		</p>
		<?php
	}

	/**
	 * Render plan-specific T&C + marketing consent checkboxes.
	 *
	 * @return void
	 */
	public static function render_custom_checkboxes() {
		$pkg_id = self::get_plan_from_redirect();
		?>
		<div class="ct-register-checkboxes" dir="rtl">

			<div class="ct-checkbox-group">
				<label class="ct-checkbox-label ct-checkbox-label--optional">
					<input type="checkbox" name="ct_marketing_consent" value="1"
						<?php checked( ! empty( $_POST['ct_marketing_consent'] ) ) ?>>
					<span>
						אני מאשר/ת לקבל עדכונים, מבצעים ותכנים שיווקיים מ-CoffeeTrail.
					</span>
				</label>
			</div>

			<?php
			// Carry the plan parameter so the server can use it during post-registration processing.
			if ( $pkg_id ) : ?>
				<input type="hidden" name="ct_listing_package" value="<?php echo esc_attr( $pkg_id ) ?>">
			<?php endif ?>

		</div>
		<?php
	}

	/**
	 * Validate phone number and plan-specific checkboxes on form submission.
	 *
	 * @param string    $username  Submitted username.
	 * @param string    $email     Submitted email.
	 * @param \WP_Error $errors    WooCommerce error object.
	 * @return void
	 */
	public static function validate_custom_fields( $username, $email, $errors ) {
		// Phone: required, must look like a valid phone number.
		// T&C acceptance is validated separately on the terms scroll step.
		$phone = sanitize_text_field( $_POST['billing_phone'] ?? '' );
		if ( empty( $phone ) ) {
			$errors->add( 'ct_phone_required', '<strong>שגיאה</strong>: יש להזין מספר טלפון.' );
		} elseif ( ! preg_match( '/^[\d\s\+\-\(\)]{7,20}$/', $phone ) ) {
			$errors->add( 'ct_phone_invalid', '<strong>שגיאה</strong>: מספר הטלפון אינו תקין.' );
		}
	}

	/**
	 * Save marketing consent and plan info to user meta on successful registration.
	 *
	 * @param int   $customer_id  New user ID.
	 * @param array $new_customer_data  Customer data array.
	 * @param bool  $password_generated  Whether password was auto-generated.
	 * @return void
	 */
	public static function save_user_meta( $customer_id, $new_customer_data, $password_generated ) {
		// Phone — save to billing_phone (WooCommerce standard key).
		$phone = sanitize_text_field( $_POST['billing_phone'] ?? '' );
		if ( $phone ) {
			update_user_meta( $customer_id, 'billing_phone', $phone );
		}

		// Marketing consent.
		$marketing_consent = ! empty( $_POST['ct_marketing_consent'] ) ? 1 : 0;
		update_user_meta( $customer_id, '_ct_marketing_consent', $marketing_consent );
		update_user_meta( $customer_id, '_ct_marketing_consent_date', current_time( 'timestamp' ) );

		// Registered plan.
		$pkg_id = ! empty( $_POST['ct_listing_package'] ) ? absint( $_POST['ct_listing_package'] ) : 0;
		if ( $pkg_id ) {
			update_user_meta( $customer_id, '_ct_registered_plan', $pkg_id === CT_FLOW_PRO_PRODUCT_ID ? 'pro' : 'free' );
		}
	}

	/**
	 * When arriving at the My Account page from an add-listing registration URL,
	 * JS-switch the tab to "Register" so new users don't see the login form first.
	 *
	 * @return void
	 */
	public static function maybe_default_register_tab() {
		if ( ! is_account_page() ) {
			return;
		}
		// Only activate when a listing_package param is present in the redirect.
		if ( empty( $_REQUEST['redirect'] ) ) {
			return;
		}
		$redirect = urldecode( sanitize_text_field( $_REQUEST['redirect'] ) );
		if ( strpos( $redirect, 'listing_package' ) === false ) {
			return;
		}
		?>
		<script>
		(function() {
			// Activate the Register tab on the My Account page automatically.
			var registerTab = document.querySelector('.mylisting-register, .u-column2 .tab-pane, [data-tab="register"]');
			if ( registerTab ) {
				return; // Already visible, nothing to do.
			}
			// WooCommerce / MyListing typically shows tabs; click the register tab trigger.
			var triggers = document.querySelectorAll('a[href*="register"], .register-tab-trigger, [data-tab="register"]');
			if ( triggers.length ) {
				triggers[0].click();
			}
		})();
		</script>
		<?php
	}
}
