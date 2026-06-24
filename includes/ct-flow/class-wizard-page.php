<?php
/**
 * CT_Flow_Wizard_Page
 *
 * Takes over the Add Listing page and renders the multi-step wizard instead
 * of the default MyListing submission form (for 'cc' listing type).
 *
 * Responsibilities:
 *  - Hooks `template_redirect` at priority 1 to output the wizard shell before
 *    any template system (Elementor, WooCommerce, etc.) can interfere.
 *    Fires for /add-listing/ when listing_type is 'cc' or absent.
 *  - Provides the AJAX action `ct_wizard_load_step` used by ct-wizard.js to
 *    fetch the HTML of the next/previous step.
 *  - Handles draft-resume detection: if the user has an in-progress transient
 *    and visits /add-listing/, skips the landing page.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Wizard_Page {

	/**
	 * Set to true when template_redirect successfully takes over the full response.
	 * Prevents the inline filter fallback from double-rendering the wizard.
	 *
	 * @var bool
	 */
	private static $_taken_over = false;

	/**
	 * Set to true while wizard-shell.php is rendering (standalone HTML document).
	 * Used to strip Elementor/theme assets that expect a full Elementor page DOM.
	 *
	 * @var bool
	 */
	private static $_wizard_shell_mode = false;

	public static function init() {
		add_action( 'wp_ajax_ct_wizard_load_step',        [ __CLASS__, 'ajax_load_step' ] );
		add_action( 'wp_ajax_nopriv_ct_wizard_load_step', [ __CLASS__, 'ajax_load_step_nopriv' ] );

		// Primary interception: full-page takeover before Elementor renders.
		add_action( 'template_redirect', [ __CLASS__, 'maybe_output_wizard' ], 1 );

		// Fallback interception: fires inside the Elementor widget render pipeline.
		// Handles the common staging/dev case where the MyListing theme setting
		// 'general_add_listing_page' stores an ID that doesn't match the actual
		// page (e.g. after a DB import), causing is_page() to fail above.
		// Priority 20: runs AFTER the user-roles controller (priority 10) so we
		// respect any upstream decision to hide the widget (e.g. role restrictions).
		add_filter( 'mylisting/show-add-listing-widget', [ __CLASS__, 'maybe_intercept_widget' ], 20 );

		// Resume banner on My Listings account page.
		add_action( 'woocommerce_account_my-listings_endpoint', [ __CLASS__, 'render_resume_banner' ] );

		// Clear wizard state when a listing is fully published (clean slate).
		add_action( 'ct_flow/listing_submitted_free', [ __CLASS__, 'handle_free_published' ] );
		add_action( 'ct_flow/grow/payment_charged',   [ __CLASS__, 'handle_pro_published' ], 10, 2 );
	}

	// =========================================================================
	// Wizard output (template_redirect)
	// =========================================================================

	/**
	 * Output the wizard shell and exit before any template system runs.
	 *
	 * Hooked to `template_redirect` at priority 1 — earlier than Elementor,
	 * WooCommerce, and any other plugin that competes on `template_include`.
	 * By including the shell and calling exit here we fully own the response.
	 *
	 * Only fires when:
	 *  - We are on the add-listing page (checked by ID from settings, or slug fallback).
	 *  - The listing_type in the request is 'cc' or absent (defaults to cc).
	 *  - The user is logged in (unauthenticated visitors are handled by
	 *    CT_Flow_Registration::redirect_guests_to_register at priority 10).
	 *
	 * If page detection fails (e.g. stale ID in settings after a DB import),
	 * maybe_intercept_widget() fires as a fallback inside the Elementor pipeline.
	 *
	 * @return void
	 */
	public static function maybe_output_wizard(): void {
		if ( ! self::_is_add_listing_page() ) {
			return;
		}

		// Allow MyListing to keep the edit-listing flow unmodified.
		$action = sanitize_key( $_REQUEST['action'] ?? '' );
		if ( in_array( $action, [ 'edit', 'switch' ], true ) ) {
			return;
		}

		$listing_type = sanitize_text_field( $_REQUEST['listing_type'] ?? 'cc' );
		if ( $listing_type !== 'cc' ) {
			return;
		}

		// Require login. If not logged in, return here and let
		// CT_Flow_Registration::redirect_guests_to_register (priority 10) handle it.
		if ( ! is_user_logged_in() ) {
			return;
		}

		$wizard_shell = CT_FLOW_DIR . '/templates/wizard-shell.php';
		if ( ! file_exists( $wizard_shell ) ) {
			mlog()->error( '[CT Wizard] wizard-shell.php not found at: ' . $wizard_shell );
			return;
		}

		// Signal to the inline fallback (maybe_intercept_widget) that we are
		// already handling the response — prevents double-rendering.
		self::$_taken_over      = true;
		self::$_wizard_shell_mode = true;
		self::_prepare_wizard_shell_assets();

		include $wizard_shell;
		exit;
	}

	// =========================================================================
	// Filter fallback: inline wizard within Elementor page
	// =========================================================================

	/**
	 * Filter callback for `mylisting/show-add-listing-widget` (priority 20).
	 *
	 * This fires inside the MyListing Elementor widget's render path. It is the
	 * belt-and-suspenders fallback for cases where maybe_output_wizard() could
	 * not intercept via template_redirect — the most common cause being that
	 * the 'general_add_listing_page' theme setting stores a post ID that differs
	 * from the actual page (frequent after staging DB imports).
	 *
	 * When active, the wizard is rendered as a full-viewport fixed overlay
	 * (see `.ct-wizard-inline` in ct-wizard.css) inside the Elementor page,
	 * and the default submission form is suppressed by returning false.
	 *
	 * @param  bool $show  Whether to show the default MyListing widget.
	 * @return bool        False suppresses the widget; wizard renders instead.
	 */
	public static function maybe_intercept_widget( bool $show ): bool {
		// template_redirect already owns the response — nothing to do here.
		if ( self::$_taken_over ) {
			return $show;
		}

		// Respect upstream filters (e.g. user-roles controller at priority 10)
		// that have already decided the widget should be hidden.
		if ( ! $show ) {
			return false;
		}

		$action = sanitize_key( $_REQUEST['action'] ?? '' );
		if ( in_array( $action, [ 'edit', 'switch' ], true ) ) {
			return $show;
		}

		$listing_type = sanitize_text_field( $_REQUEST['listing_type'] ?? 'cc' );
		if ( $listing_type !== 'cc' ) {
			return $show;
		}

		// Let CT_Flow_Registration::redirect_guests_to_register (priority 10)
		// handle unauthenticated visitors — do not intercept here.
		if ( ! is_user_logged_in() ) {
			return $show;
		}

		$wizard_inline = CT_FLOW_DIR . '/templates/wizard-inline.php';
		if ( ! file_exists( $wizard_inline ) ) {
			mlog()->error( '[CT Wizard] wizard-inline.php not found at: ' . $wizard_inline );
			return $show;
		}

		include $wizard_inline;
		return false;
	}

	// =========================================================================
	// Private helpers
	// =========================================================================

	/**
	 * Whether the current request is on the add-listing page.
	 *
	 * Checks by post ID (from the 'general_add_listing_page' theme setting) first,
	 * then falls back to the well-known 'add-listing' slug. The slug fallback is
	 * critical for staging/dev environments where a DB import may leave the theme
	 * setting pointing to a stale post ID.
	 *
	 * Must be called after WordPress has set up the main query (template_redirect
	 * or later), since is_page() requires the query object to be populated.
	 *
	 * @return bool
	 */
	private static function _is_add_listing_page(): bool {
		$add_listing_page_id = absint( c27()->get_setting( 'general_add_listing_page' ) );

		if ( $add_listing_page_id && is_page( $add_listing_page_id ) ) {
			return true;
		}

		// Slug-based fallback: works regardless of post ID differences after DB import.
		if ( is_page( 'add-listing' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Logo URL for wizard header/landing templates.
	 * Prefers the MyListing theme logo; falls back to child-theme asset if present.
	 *
	 * @return string
	 */
	public static function get_logo_url(): string {
		if ( function_exists( 'c27' ) ) {
			$theme_logo = c27()->get_site_logo_url();
			if ( $theme_logo ) {
				return $theme_logo;
			}
		}

		$child_logo = get_stylesheet_directory() . '/assets/images/logo-dark.svg';
		if ( file_exists( $child_logo ) ) {
			return get_stylesheet_directory_uri() . '/assets/images/logo-dark.svg';
		}

		return '';
	}

	/**
	 * Register late hooks to strip assets incompatible with wizard-shell.php.
	 *
	 * wizard-shell.php calls wp_head(), which enqueues Elementor frontend JS.
	 * Elementor expects elementorFrontendConfig and its widget DOM — neither
	 * exists in the minimal shell, causing console errors and broken scripts.
	 *
	 * @return void
	 */
	private static function _prepare_wizard_shell_assets(): void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'dequeue_wizard_shell_assets' ], PHP_INT_MAX );
		add_action( 'wp_print_scripts',  [ __CLASS__, 'dequeue_wizard_shell_assets' ], 1 );
		add_action( 'wp_print_styles',   [ __CLASS__, 'dequeue_wizard_shell_assets' ], 1 );
	}

	/**
	 * Dequeue scripts/styles that require a full Elementor page or theme chrome.
	 *
	 * @return void
	 */
	public static function dequeue_wizard_shell_assets(): void {
		if ( ! self::$_wizard_shell_mode ) {
			return;
		}

		if ( class_exists( '\Elementor\Plugin' ) ) {
			$instance = \Elementor\Plugin::$instance;

			// Strip Elementor frontend assets.
			$frontend = $instance->frontend ?? null;
			if ( $frontend ) {
				remove_action( 'wp_enqueue_scripts', [ $frontend, 'register_scripts' ], 5 );
				remove_action( 'wp_enqueue_scripts', [ $frontend, 'register_styles' ], 5 );
				remove_action( 'wp_enqueue_scripts', [ $frontend, 'enqueue_scripts' ], 10 );
				remove_action( 'wp_enqueue_scripts', [ $frontend, 'enqueue_styles' ], 10 );
				remove_action( 'wp_footer', [ $frontend, 'wp_footer' ] );
				// The wp_footer callback also injects popup HTML — remove it with all priorities.
				remove_all_actions( 'elementor/frontend/footer' );
			}

			// Explicitly disable Elementor popup module to prevent popup HTML injection.
			if ( isset( $instance->modules_manager ) ) {
				$popup_module = $instance->modules_manager->get_modules( 'popup' );
				if ( $popup_module && method_exists( $popup_module, 'print_popup_html' ) ) {
					remove_action( 'wp_footer', [ $popup_module, 'print_popup_html' ] );
				}
				// Disable the whole popup layer via filter.
				add_filter( 'elementor/popup/print_popup', '__return_false' );
			}

			// Disable all Elementor modules from injecting into wp_footer.
			remove_all_actions( 'elementor/frontend/before_render' );
			add_filter( 'elementor_pro/popup/should_display_popup', '__return_false' );
		}

		$script_handles = [
			'elementor-frontend',
			'elementor-frontend-modules',
			'elementor-webpack-runtime',
			'elementor-waypoints',
			'swiper',
			'e-swiper',
			'mylisting-listing-form',
			'mylisting-add-listing',
		];

		foreach ( $script_handles as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}

		$style_handles = [
			'elementor-frontend',
			'elementor-post',
			'elementor-global',
			'elementor-icons',
			'mylisting-add-listing',
		];

		foreach ( $style_handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	// =========================================================================
	// AJAX: load step HTML
	// =========================================================================

	/**
	 * AJAX: return the rendered HTML for a wizard step.
	 *
	 * Expected POST: nonce, step, package, job_id
	 * Returns JSON: { success: true, html: string }
	 *
	 * @return void
	 */
	public static function ajax_load_step() {
		check_ajax_referer( CT_Flow_Wizard_Controller::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'יש להתחבר לחשבון.' ], 401 );
		}

		$step    = sanitize_key( $_POST['step']    ?? '' );
		$package = sanitize_key( $_POST['package'] ?? 'free' );
		$package = in_array( $package, [ 'free', 'pro' ], true ) ? $package : 'free';
		$job_id  = absint( $_POST['job_id'] ?? 0 );

		if ( ! array_key_exists( $step, CT_Flow_Wizard_Controller::STEPS ) ) {
			wp_send_json_error( [ 'message' => 'שלב לא ידוע.' ], 400 );
		}

		$state = CT_Flow_Wizard_Controller::get_state( get_current_user_id() );
		if ( $job_id ) {
			$state['job_id'] = $job_id;
		}

		$html = self::render_step( $step, $package, $state, $job_id );

		wp_send_json_success( [
			'html'     => $html,
			'label'    => CT_Flow_Wizard_Controller::STEPS[ $step ]['label'] ?? '',
			'progress' => CT_Flow_Wizard_Controller::get_progress( $step, $package ),
		] );
	}

	/**
	 * AJAX: non-logged-in users — redirect to login.
	 *
	 * @return void
	 */
	public static function ajax_load_step_nopriv() {
		wp_send_json_error( [ 'message' => 'יש להתחבר לחשבון.', 'redirect' => wp_login_url() ], 401 );
	}

	// =========================================================================
	// Step rendering
	// =========================================================================

	/**
	 * Render a step template and return its HTML.
	 *
	 * @param string $step_key
	 * @param string $listing_package
	 * @param array  $state
	 * @param int    $job_id
	 * @return string  Rendered HTML.
	 */
	public static function render_step(
		string $step_key,
		string $listing_package,
		array  $state,
		int    $job_id = 0
	): string {
		$template_name = CT_Flow_Wizard_Controller::get_template( $step_key );
		$template_path = CT_FLOW_DIR . '/templates/wizard/' . $template_name . '.php';

		if ( ! file_exists( $template_path ) ) {
			return '<p class="ct-step-error">תבנית לא נמצאה: ' . esc_html( $template_name ) . '</p>';
		}

		// Variables available to all templates (explicitly declared to survive future refactors).
		$current_step    = $step_key;
		$listing_package = $listing_package;
		$state           = $state;
		$job_id          = $job_id;
		$has_draft       = CT_Flow_Wizard_Controller::has_draft();

		ob_start();
		include $template_path;
		return ob_get_clean();
	}

	// =========================================================================
	// Resume banner
	// =========================================================================

	/**
	 * Render a "resume your listing" banner on the My Listings account tab.
	 *
	 * @return void
	 */
	public static function render_resume_banner() {
		if ( ! CT_Flow_Wizard_Controller::has_draft() ) {
			return;
		}

		$add_listing_url = home_url( '/add-listing/' );
		$resume_url      = add_query_arg( 'ct_resume', '1', $add_listing_url );
		?>
		<div class="ct-resume-banner" dir="rtl" style="background:var(--ct-free-bg,#f0faf5);border:1px solid var(--ct-green,#219156);border-radius:8px;padding:16px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
			<div>
				<strong>יש לכם רישום שלא הושלם</strong>
				<p style="margin:4px 0 0;font-size:13px;color:#555;">ניתן להמשיך מאיפה שעצרתם.</p>
			</div>
			<a href="<?php echo esc_url( $resume_url ) ?>"
				style="background:var(--ct-green,#219156);color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;white-space:nowrap;">
				המשיכו להרשמה &larr;
			</a>
		</div>
		<?php
	}

	// =========================================================================
	// State cleanup on publish
	// =========================================================================

	/** @param int $listing_id */
	public static function handle_free_published( int $listing_id ): void {
		$post = get_post( $listing_id );
		if ( $post ) {
			CT_Flow_Wizard_Controller::clear_state( (int) $post->post_author );
		}
	}

	/**
	 * @param int   $listing_id
	 * @param array $payload
	 */
	public static function handle_pro_published( int $listing_id, array $payload ): void {
		self::handle_free_published( $listing_id );
	}
}
