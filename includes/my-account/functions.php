<?php

defined( 'ABSPATH' ) || exit;

// Load the register-otp.php file to handle OTP registration functionality.
require_once get_stylesheet_directory()
    . '/includes/my-account/register-otp.php';

/**
 * טעינת קובץ העיצוב של האזור האישי.
 * גרסת הקובץ משתנה אוטומטית בכל שמירה.
 */
add_action( 'wp_enqueue_scripts', function () {

    if (
        ! is_user_logged_in()
        || ! function_exists( 'is_account_page' )
        || ! is_account_page()
    ) {
        return;
    }

    $relative_path = '/includes/my-account/css/my-account.css';
    $file_path     = get_stylesheet_directory() . $relative_path;
    $file_url      = get_stylesheet_directory_uri() . $relative_path;

    wp_enqueue_style(
        'ct-my-account',
        $file_url,
        [],
        file_exists( $file_path ) ? filemtime( $file_path ) : null
    );

    /**
     * JS
     */
    $js_relative_path = '/includes/my-account/js/my-account.js';
    $js_file_path     = get_stylesheet_directory() . $js_relative_path;
    $js_file_url      = get_stylesheet_directory_uri() . $js_relative_path;

    wp_enqueue_script(
        'ct-my-account',
        $js_file_url,
        [],
        file_exists( $js_file_path ) ? filemtime( $js_file_path ) : null,
        true
    );

}, 20 );


/**
 * החלפת תפריט WooCommerce המקורי בתפריט המותאם.
 */
add_action( 'wp_loaded', function () {

    remove_action(
        'woocommerce_account_navigation',
        'woocommerce_account_navigation',
        10
    );

    remove_action(
        'woocommerce_account_navigation',
        'account_menu_per_listing_plan',
        10
    );

    add_action(
        'woocommerce_account_navigation',
        'account_menu_per_listing_plan',
        10
    );

} );


/**
 * רישום endpoints ראשיים:
 *
 * /my-account/free/{page}/
 * /my-account/pro/{page}/
 */
add_action( 'init', function () {

    add_rewrite_endpoint( 'free', EP_ROOT | EP_PAGES );
    add_rewrite_endpoint( 'pro', EP_ROOT | EP_PAGES );

} );


/**
 * רישום ה-endpoints בתוך WooCommerce.
 */
add_filter( 'woocommerce_get_query_vars', function ( $query_vars ) {

    $query_vars['free'] = 'free';
    $query_vars['pro']  = 'pro';

    return $query_vars;

} );


/**
 * איתור ה-listing והתוכנית של המשתמש.
 *
 * @param int $user_id מזהה משתמש.
 * @return array
 */
function ct_get_user_listing_plan( $user_id = 0 ) {

    $user_id = $user_id ?: get_current_user_id();

    $listing_query = new WP_Query( [
        'post_type'      => 'job_listing',
        'author'         => $user_id,
        'post_status'    => [ 'publish', 'pending', 'draft' ],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ] );

    $listing_ids = array_map( 'intval', $listing_query->posts );

    $requested_listing_id = isset( $_GET['listing_id'] )
        ? absint( $_GET['listing_id'] )
        : 0;

    /*
     * מוודאים שהעגלה שנבחרה באמת שייכת למשתמש.
     */
    if (
        $requested_listing_id
        && in_array( $requested_listing_id, $listing_ids, true )
    ) {
        $listing_id = $requested_listing_id;
    } else {
        $listing_id = ! empty( $listing_ids )
            ? $listing_ids[0]
            : 0;
    }

    $package_id = $listing_id
        ? absint( get_post_meta( $listing_id, '_package_id', true ) )
        : 0;

    /**
     * מזהי חבילות PRO.
     * ניתן להוסיף כאן חבילות נוספות בעתיד.
     */
    $pro_package_ids = [ 25 ];

    $is_pro = in_array( $package_id, $pro_package_ids, true );

    return [
        'listing_id'     => $listing_id,
        'listing_ids'    => $listing_ids,
        'package_id'     => $package_id,
        'user_package_id' => $listing_id
            ? absint( get_post_meta( $listing_id, '_user_package_id', true ) )
            : 0,
        'is_pro'         => $is_pro,
        'plan_slug'      => $is_pro ? 'pro' : 'free',
    ];
}


/**
 * רשימת עמודים מורשים לכל חבילה.
 *
 * שם העמוד חייב להתאים לשם קובץ ה-PHP בתיקיית הטמפלטים.
 *
 * @param string $plan_slug free או pro.
 * @return array
 */
function ct_get_account_plan_pages( $plan_slug ) {


    if ( 'pro' === $plan_slug ) {
        return [
            'home',
            'my-page',
            'page-actions',
            'exposure-opportunities',
            'account-settings',
        ];
    }

    return [
        'home',
        'my-page',
        'page-actions',
        'exposure-opportunities',
        'account-settings',
    ];
}


/**
 * טעינת טמפלט מתוך תיקיית החבילה.
 *
 * FREE:
 * templates/dashboard/free-user/{template}.php
 *
 * PRO:
 * templates/dashboard/pro-user/{template}.php
 *
 * @param string $plan_slug    free או pro.
 * @param string $template_name שם הטמפלט ללא סיומת.
 * @param array  $args          משתנים שיועברו לטמפלט.
 */
function ct_load_dashboard_template( $plan_slug, $template_name, $args = [] ) {

    $directories = [
        'free' => 'free-user',
        'pro'  => 'pro-user',
    ];

    if ( ! isset( $directories[ $plan_slug ] ) ) {
        return;
    }

    $template_name = sanitize_file_name( $template_name );

    $template_path = trailingslashit( get_stylesheet_directory() )
        . 'templates/dashboard/'
        . $directories[ $plan_slug ]
        . '/'
        . $template_name
        . '.php';

    if ( ! file_exists( $template_path ) ) {
        echo '<p>הטמפלט לא נמצא: ' . esc_html( $template_path ) . '</p>';
        return;
    }

    if ( ! empty( $args ) ) {
        extract( $args, EXTR_SKIP );
    }

    include $template_path;
}


/**
 * קבלת שם העמוד מתוך ערך ה-endpoint.
 *
 * לדוגמה:
 * /my-account/free/my-page/
 * מחזיר: my-page
 *
 * @param mixed  $endpoint_value ערך שהתקבל מ-WooCommerce.
 * @param string $default_page   עמוד ברירת מחדל.
 * @return string
 */
function ct_get_nested_account_page( $plan_slug, $default_page = 'home' ) {

    $endpoint_value = get_query_var( $plan_slug );

    if ( is_string( $endpoint_value ) && $endpoint_value !== '' ) {
        $parts = explode( '/', trim( $endpoint_value, '/' ) );

        if ( ! empty( $parts[0] ) ) {
            return sanitize_key( $parts[0] );
        }
    }

    /*
     * Fallback במקרה ש-WooCommerce לא מחזיר את הנתיב הפנימי.
     */
    $request_path = isset( $_SERVER['REQUEST_URI'] )
        ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
        : '';

    if ( $request_path ) {
        $pattern = '#/my-account/' . preg_quote( $plan_slug, '#' ) . '/([^/?]+)/?#';

        if ( preg_match( $pattern, $request_path, $matches ) ) {
            return sanitize_key( $matches[1] );
        }
    }

    return $default_page;
}


/**
 * תוכן עמודי משתמש חינמי.
 */
add_action( 'woocommerce_account_free_endpoint', function ( $endpoint_value ) {

    $plan_data = ct_get_user_listing_plan();

    if ( $plan_data['is_pro'] ) {
        wp_safe_redirect(
            ct_get_account_plan_url(
                'pro',
                'home',
                $plan_data['listing_id']
            )
        );
        exit;
    }

    $page          = ct_get_nested_account_page( 'free' );
    $allowed_pages = ct_get_account_plan_pages( 'free' );

    if ( ! in_array( $page, $allowed_pages, true ) ) {
        $page = 'home';
    }

    $current_user = wp_get_current_user();
    $listing      = $plan_data['listing_id']
        ? get_post( $plan_data['listing_id'] )
        : null;

    ct_load_dashboard_template( 'free', $page, [
        'user_id'      => $current_user->ID,
        'current_user' => $current_user,
        'listing_id'   => $plan_data['listing_id'],
        'listing'      => $listing,
        'listing_ids'    => $plan_data['listing_ids'],
        'package_id'     => $plan_data['package_id'],
        'user_package_id'=> $plan_data['user_package_id'],
        'is_pro'         => false,
    ] );

} );


/**
 * תוכן עמודי משתמש PRO.
 */
add_action( 'woocommerce_account_pro_endpoint', function ( $endpoint_value ) {

    $plan_data = ct_get_user_listing_plan();

    if ( ! $plan_data['is_pro'] ) {
        wp_safe_redirect(
            ct_get_account_plan_url(
                'free',
                'home',
                $plan_data['listing_id']
            )
        );
        exit;
    }

    $page = ct_get_nested_account_page( 'pro' );
    $allowed_pages = ct_get_account_plan_pages( 'pro' );

    if ( ! in_array( $page, $allowed_pages, true ) ) {
        $page = 'home';
    }

    $current_user = wp_get_current_user();
    $listing      = $plan_data['listing_id']
        ? get_post( $plan_data['listing_id'] )
        : null;

    ct_load_dashboard_template( 'pro', $page, [
        'user_id'      => $current_user->ID,
        'current_user' => $current_user,
        'listing_id'   => $plan_data['listing_id'],
        'listing'      => $listing,
        'listing_ids'    => $plan_data['listing_ids'],
        'package_id'     => $plan_data['package_id'],
        'user_package_id'=> $plan_data['user_package_id'],
        'is_pro'         => true,
    ] );

} );


/**
 * יצירת URL לעמוד פנימי לפי חבילה.
 *
 * @param string $plan_slug free או pro.
 * @param string $page_slug שם העמוד.
 * @return string
 */
function ct_get_account_plan_url( $plan_slug, $page_slug = 'home', $listing_id = 0 ) {

    $base_url = wc_get_account_endpoint_url(
        sanitize_key( $plan_slug )
    );

    $url = trailingslashit( $base_url )
        . trailingslashit( sanitize_key( $page_slug ) );

    if ( $listing_id ) {
        $url = add_query_arg(
            'listing_id',
            absint( $listing_id ),
            $url
        );
    }

    return $url;
}


/**
 * הפניית עמוד /my-account/ הראשי לעמוד הבית של החבילה.
 */
add_action( 'template_redirect', function () {

    if (
        ! is_user_logged_in()
        || ! function_exists( 'is_account_page' )
        || ! is_account_page()
        || ! function_exists( 'is_wc_endpoint_url' )
    ) {
        return;
    }

    if (
        is_wc_endpoint_url()
        || isset( $_GET['lost-password'] )
    ) {
        return;
    }

    $plan_data = ct_get_user_listing_plan();

    wp_safe_redirect(
        ct_get_account_plan_url(
            $plan_data['plan_slug'],
            'home',
            $plan_data['listing_id']
        )
    );
    exit;

}, 20 );


/**
 * הדפסת תפריט האזור האישי לפי החבילה.
 */
function account_menu_per_listing_plan() {

    if ( ! is_user_logged_in() ) {
        return;
    }

    $plan_data = ct_get_user_listing_plan();
    $listing_id = (int) $plan_data['listing_id'];
    $is_pro    = $plan_data['is_pro'];
    $plan_slug = $plan_data['plan_slug'];

    if ( $is_pro ) {

        $menu_items = [
            'home'                   => 'בית',
            'my-page'                => 'העמוד שלי',
            'page-actions'           => 'פעולות בעמוד',
            'exposure-opportunities' => 'הזדמנויות חשיפה',
            'account-settings'       => 'הגדרות',
        ];

    } else {

        $menu_items = [
            'home'                   => 'בית',
            'my-page'                => 'העמוד שלי',
            'page-actions'           => 'פעולות בעמוד',
            'exposure-opportunities' => 'הזדמנויות חשיפה',
            'account-settings'       => 'הגדרות',
        ];
    }

    $current_endpoint_value = get_query_var( $plan_slug );
    $current_page           = ct_get_nested_account_page( $plan_slug );

    $owner_meta = get_post_meta( $listing_id, '_coffeecart-owner', true );

    if ( empty( $owner_meta ) ) {
        $owner_meta = get_post_meta( $listing_id, '_job_logo', true );
    }

    $owner_value = '';

    if ( is_array( $owner_meta ) ) {
        $owner_value = $owner_meta[0] ?? '';
    } else {
        $owner_value = $owner_meta;
    }

    if ( is_string( $owner_value ) ) {
        $maybe_unserialized = maybe_unserialize( $owner_value );

        if ( is_array( $maybe_unserialized ) ) {
            $owner_value = $maybe_unserialized[0] ?? '';
        }
    }

    $owner_image_url = is_string( $owner_value )
        ? $owner_value
        : '';
    
    ?>

    <nav class="woocommerce-MyAccount-navigation ct-account-navigation">
        <ul class="ct-account-navigation__menu">
            <?php foreach ( $menu_items as $page_slug => $label ) : ?>
                <?php
                $classes = [
                    'woocommerce-MyAccount-navigation-link',
                    'woocommerce-MyAccount-navigation-link--' . sanitize_html_class( $page_slug ),
                ];

                if ( $current_page === $page_slug ) {
                    $classes[] = 'is-active';
                }
                ?>

                <li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
                    <a href="<?php echo esc_url( ct_get_account_plan_url( $plan_slug, $page_slug, $listing_id ) ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="ct-account-navigation__tools">
            <div class="ct-account-help">
                <button
                    type="button"
                    class="ct-account-help__toggle"
                    aria-expanded="false"
                    aria-controls="ct-account-help-panel"
                >
                    <span class="ct-account-help__toggle-text">עזרה</span>

                    <span class="ct-account-help__toggle-icon" aria-hidden="true">
                        ?
                    </span>
                </button>

                <div
                    id="ct-account-help-panel"
                    class="ct-account-help__panel"
                    hidden
                >
                    <p class="ct-account-help__title">
                        צריכים עזרה?
                    </p>

                    <p class="ct-account-help__description">
                        הצוות שלנו זמין לכל שאלה — נשמח לעזור.
                    </p>

                    <a
                        href="mailto:help@coffeetrail.co.il"
                        class="ct-account-help__contact"
                    >
                        <span class="ct-account-help__contact-icon" aria-hidden="true">
                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                <path d="M3 7l9 6 9-6"></path>
                            </svg>
                        </span>

                        <span class="ct-account-help__contact-content">
                            <span class="ct-account-help__contact-label">
                                אימייל
                            </span>

                            <span class="ct-account-help__contact-value">
                                help@coffeetrail.co.il
                            </span>
                        </span>
                    </a>

                    <a
                        href="https://wa.me/972500000000"
                        class="ct-account-help__contact"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="ct-account-help__contact-icon" aria-hidden="true">
                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M21 11.5a8.5 8.5 0 0 1-12.3 7.6L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5z"></path>
                                <path d="M8.5 9c0 4 2.5 6.5 6.5 6.5l1-2-2.2-1-1 1c-1.1-.5-2.3-1.7-2.8-2.8l1-1-1-2.2z"></path>
                            </svg>
                        </span>

                        <span class="ct-account-help__contact-content">
                            <span class="ct-account-help__contact-label">
                                וואטסאפ
                            </span>

                            <span class="ct-account-help__contact-value">
                                050-000-0000
                            </span>
                        </span>
                    </a>
                </div>

            </div>
            
            <div class="ct-account-owner-image">
                <?php if ( $owner_image_url ) : ?>
                    <img
                        src="<?php echo esc_url( $owner_image_url ); ?>"
                        class="ct-account-owner-image__img"
                        alt="<?php echo esc_attr( get_the_title( $listing_id ) ); ?>"
                    >
                    <?php else : ?>
                        <?php
                        $logo_url = wp_get_attachment_image_url(
                            get_theme_mod('custom_logo'),
                            'full'
                        );

                        if ( $logo_url ) :
                        ?>
                        <img
                            src="<?php echo esc_url( $logo_url ); ?>"
                            class="ct-account-owner-image__img"
                            alt="<?php echo esc_attr( get_the_title( $listing_id ) ); ?>"
                        >
                    <?php endif; ?>                
                <?php endif; ?>
                </div>            
        </div>
    </nav>
    <?php
}
