<?php
/**
 * CT_Flow_Fixes
 *
 * Patches existing child-theme behaviour:
 *  1. Replaces yanir_dequeue_woocommerce_assets so that the add-listing page
 *     retains WooCommerce assets (needed for package resolution, notices, etc.).
 *  2. Enqueues ct-flow CSS and JS on the front end.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Fixes {

	public static function init() {
		// Replace the existing dequeue function with a patched version.
		// The original is registered at priority 99 in functions.php, which
		// runs before this file is required, so remove_action works here.
		remove_action( 'wp_enqueue_scripts', 'yanir_dequeue_woocommerce_assets', 99 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'dequeue_woocommerce_assets' ], 99 );

		// Enqueue ct-flow assets on the front end.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ], 100 );

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
	}

	// -------------------------------------------------------------------------
	// WooCommerce asset dequeue (patched)
	// -------------------------------------------------------------------------

	/**
	 * Remove WooCommerce styles and scripts on pages where they are not needed,
	 * but preserve them on the add-listing page and any WooCommerce-native page.
	 *
	 * @return void
	 */
	public static function dequeue_woocommerce_assets() {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		$add_listing_page_id = absint( c27()->get_setting( 'general_add_listing_page' ) );
		$is_add_listing      = $add_listing_page_id && is_page( $add_listing_page_id );

		if ( is_woocommerce() || is_cart() || is_checkout() || $is_add_listing ) {
			return; // Keep WC assets on these pages.
		}

		$styles = [
			'woocommerce-general',
			'woocommerce-layout',
			'woocommerce-smallscreen',
			'woocommerce_frontend_styles',
			'woocommerce_fancybox_styles',
			'woocommerce_chosen_styles',
			'woocommerce_prettyPhoto_css',
		];
		foreach ( $styles as $handle ) {
			wp_dequeue_style( $handle );
		}

		$scripts = [
			'wc_price_slider',
			'wc-single-product',
			'wc-add-to-cart',
			'wc-cart-fragments',
			'wc-checkout',
			'wc-add-to-cart-variation',
			'wc-cart',
			'wc-chosen',
			'woocommerce',
			'prettyPhoto',
			'prettyPhoto-init',
			'jquery-blockui',
			'jquery-placeholder',
			'fancybox',
			'jqueryui',
		];
		foreach ( $scripts as $handle ) {
			wp_dequeue_script( $handle );
		}
	}

	// -------------------------------------------------------------------------
	// Asset enqueuing
	// -------------------------------------------------------------------------

	/**
	 * Enqueue ct-flow front-end CSS and JS.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		// Inter font — required by Figma design. Loaded via Google Fonts.
		wp_enqueue_style(
			'ct-inter-font',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
			[],
			null
		);

		wp_enqueue_style(
			'ct-flow',
			CT_FLOW_URL . '/assets/css/ct-flow.css',
			[ 'ct-inter-font' ],
			CT_FLOW_VERSION
		);

		// Terms scroll-unlock: load on every page — the script self-exits if
		// #ct-terms-scroll-box is absent, so there is no overhead elsewhere.
		wp_enqueue_script(
			'ct-terms-scroll',
			CT_FLOW_URL . '/assets/js/ct-terms-scroll.js',
			[ 'jquery' ],
			CT_FLOW_VERSION,
			true
		);

		// Auth page assets: on the ct-register and ct-login pages.
		if ( is_page( 'ct-register' ) || is_page( 'ct-login' ) ) {
			wp_enqueue_style(
				'ct-auth',
				CT_FLOW_URL . '/assets/css/ct-auth.css',
				[ 'ct-inter-font' ],
				CT_FLOW_VERSION
			);
			wp_enqueue_script(
				'ct-auth',
				CT_FLOW_URL . '/assets/js/ct-auth.js',
				[ 'jquery' ],
				CT_FLOW_VERSION,
				true
			);

			// mylisting-auth contains window.onGoogleLibraryLoad which initialises
			// the .cts-google-signin div. It's only enqueued by form-login.php on
			// the WC account page, so we load it here explicitly.
			wp_enqueue_script( 'mylisting-auth' );

			// GSI script: the Google class hooks into mylisting/after-auth-forms
			// which doesn't fire on our standalone template, so enqueue it directly.
			$_google_client_opt = get_option( 'mylisting_social_login_google_client_id', null );
			$_google_client_id  = is_null( $_google_client_opt )
				? (string) c27()->get_setting( 'social_login_google_client_id' )
				: (string) $_google_client_opt;
			if ( ! empty( $_google_client_id ) ) {
				wp_enqueue_script(
					'google-platform-js',
					'https://accounts.google.com/gsi/client',
					[ 'mylisting-auth' ],
					null,
					true
				);
			}
		}

		// Auto-save + Grow wallet + wizard scripts: only on add-listing page.
		$add_listing_page_id = absint( c27()->get_setting( 'general_add_listing_page' ) );
		if ( ( $add_listing_page_id && is_page( $add_listing_page_id ) ) || is_page( 'add-listing' ) ) {
			wp_enqueue_script(
				'ct-auto-save',
				CT_FLOW_URL . '/assets/js/ct-auto-save.js',
				[ 'jquery' ],
				CT_FLOW_VERSION,
				true
			);
			wp_localize_script( 'ct-auto-save', 'ctAutoSave', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ct_autosave' ),
				'i18n'    => [
					'saving'   => 'שומר...',
					'saved'    => 'נשמר אוטומטית',
					'unsaved'  => 'יש שינויים שלא נשמרו',
					'files'    => 'קבצים יישמרו בעת שליחת הטופס',
				],
			] );

			// Grow wallet JS — self-exits if #ct-grow-wallet-container is absent.
			wp_enqueue_script(
				'ct-grow-wallet',
				CT_FLOW_URL . '/assets/js/ct-grow-wallet.js',
				[ 'jquery' ],
				CT_FLOW_VERSION,
				true
			);
			// jobId is 0 here; the payment template sets ctGrowData.jobId via inline <script>.
			wp_localize_script( 'ct-grow-wallet', 'ctGrowData', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ct_grow_init' ),
				'jobId'   => 0,
			] );

			// Image processing libraries (browser-image-compression, heic2any, Cropper.js).
			wp_enqueue_script(
				'browser-image-compression',
				'https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js',
				[],
				'2.0.2',
				true
			);
			wp_enqueue_script(
				'heic2any',
				'https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js',
				[],
				'0.0.4',
				true
			);
			wp_enqueue_script(
				'cropperjs',
				'https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js',
				[],
				'1.6.2',
				true
			);
			wp_enqueue_style(
				'cropperjs-css',
				'https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css',
				[],
				'1.6.2'
			);

			// SortableJS — drag-to-reorder for the gallery previews.
			wp_enqueue_script(
				'sortablejs',
				'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
				[],
				'1.15.2',
				true
			);

			// Wizard CSS + JS.
			wp_enqueue_style(
				'ct-wizard',
				CT_FLOW_URL . '/assets/css/ct-wizard.css',
				[ 'ct-flow' ],
				CT_FLOW_VERSION
			);
			wp_enqueue_script(
				'ct-wizard',
				CT_FLOW_URL . '/assets/js/ct-wizard.js',
				[ 'jquery', 'browser-image-compression', 'heic2any', 'cropperjs', 'sortablejs' ],
				CT_FLOW_VERSION,
				true
			);
			wp_localize_script( 'ct-wizard', 'ctWizard', [
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'ct_wizard_save' ),
				'myListingsUrl'  => wc_get_account_endpoint_url( 'my-listings' ),
				'i18n'           => [
					'processing'     => 'מתאים את התמונות להעלאה...',
					'uploadError'    => 'שגיאה בהעלאת הקובץ. אנא נסו שוב.',
					'unsupportedFile'=> 'סוג קובץ לא נתמך.',
					'fileTooLarge'   => 'הקובץ גדול מדי (מקסימום 3MB).',
					'saveExit'       => 'האם לשמור ולצאת?',
					'saveExitBody'   => 'הנתונים שהזנת נשמרו. תוכלו להמשיך מאוחר יותר.',
					'saveExitConfirm'=> 'שמירה ויציאה',
					'saveExitCancel' => 'המשך מילוי',
					'saving'         => 'שומר...',
					'saved'          => 'נשמר',
				],
			] );

			// OSM/Leaflet for the location step — uses the theme's registered handles.
			wp_enqueue_script( 'mylisting-openstreetmap' );
			wp_enqueue_style( 'mylisting-openstreetmap' );
		}
	}

	/**
	 * Enqueue admin CSS on the CoffeeTrail admin panel page.
	 *
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ct-flow-moderation' ) === false ) {
			return;
		}
		wp_enqueue_style(
			'ct-flow-admin',
			CT_FLOW_URL . '/assets/css/ct-flow.css',
			[],
			CT_FLOW_VERSION
		);
	}
}
