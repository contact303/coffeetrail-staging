<?php

defined( 'ABSPATH' ) || exit;


/**
 * הצפנת ערך רגיש לשמירה זמנית ב-transient.
 */
function ct_register_encrypt_value( $value ) {

    if ( ! function_exists( 'openssl_encrypt' ) ) {
        return new WP_Error(
            'ct_openssl_missing',
            'לא ניתן להתחיל את תהליך האימות כרגע.'
        );
    }

    $key = hash( 'sha256', wp_salt( 'auth' ), true );
    $iv  = random_bytes( 12 );
    $tag = '';

    $encrypted = openssl_encrypt(
        (string) $value,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ( false === $encrypted ) {
        return new WP_Error(
            'ct_encrypt_failed',
            'לא ניתן להתחיל את תהליך האימות כרגע.'
        );
    }

    return base64_encode( $iv . $tag . $encrypted );
}


/**
 * פענוח ערך מוצפן.
 */
function ct_register_decrypt_value( $payload ) {

    if ( ! function_exists( 'openssl_decrypt' ) ) {
        return '';
    }

    $decoded = base64_decode( (string) $payload, true );

    if ( false === $decoded || strlen( $decoded ) < 29 ) {
        return '';
    }

    $key       = hash( 'sha256', wp_salt( 'auth' ), true );
    $iv        = substr( $decoded, 0, 12 );
    $tag       = substr( $decoded, 12, 16 );
    $encrypted = substr( $decoded, 28 );

    $decrypted = openssl_decrypt(
        $encrypted,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return false === $decrypted ? '' : $decrypted;
}


/**
 * מפתח transient לפי token.
 */
function ct_register_get_otp_key( $token ) {

    return 'ct_register_otp_' . hash(
        'sha256',
        (string) $token
    );
}


/**
 * שליחת OTP באימייל.
 */
function ct_register_send_otp_email( $email, $code ) {

    $subject = sprintf(
        'קוד האימות שלך ל-%s',
        wp_specialchars_decode(
            get_bloginfo( 'name' ),
            ENT_QUOTES
        )
    );

    $template_path = locate_template(
        'templates/emails/ct-register-otp.php',
        false,
        false
    );

    if ( $template_path ) {

        $otp_code  = $code;
        $otp_email = $email;

        ob_start();

        include $template_path;

        $message = ob_get_clean();

    } else {

        $message = sprintf(
            '<div dir="rtl" style="font-family:Arial,sans-serif">
                <div class="ct-auth-divider">
                    <span class="">
                        אימות כתובת האימייל
                    </span>
                </div>
                <p>קוד האימות שלך הוא:</p>

                <p style="
                    font-size:32px;
                    font-weight:700;
                    letter-spacing:8px;
                    direction:ltr;
                    text-align:center;
                ">
                    %s
                </p>

                <p>הקוד תקף למשך 10 דקות.</p>
            </div>',
            esc_html( $code )
        );
    }

    return wp_mail(
        $email,
        $subject,
        $message,
        [
            'Content-Type: text/html; charset=UTF-8',
            'From: '
                . wp_specialchars_decode(
                    get_bloginfo( 'name' ),
                    ENT_QUOTES
                )
                . ' <'
                . get_option( 'admin_email' )
                . '>',
        ]
    );
}


/**
 * state התחלתי של ההרשמה.
 */
function ct_get_default_registration_state() {

    return [
        'errors'     => [],
        'old'        => [],
        'otp_stage'  => false,
        'otp_token'  => '',
        'otp_email'  => '',
        'otp_notice' => '',
    ];
}


/**
 * טיפול בכל תהליך ההרשמה + OTP.
 */
function ct_process_account_registration() {

    $state = ct_get_default_registration_state();

    if ( empty( $_POST['ct_register_nonce'] ) ) {
        return $state;
    }

    $nonce_valid = wp_verify_nonce(
        sanitize_text_field(
            wp_unslash(
                $_POST['ct_register_nonce']
            )
        ),
        'ct_register'
    );

    if ( ! $nonce_valid ) {

        $state['errors']['_form'] =
            'בקשה לא תקינה. אנא רעננו את הדף ונסו שוב.';

        return $state;
    }

    $action = sanitize_key(
        $_POST['ct_register_action'] ?? 'request_otp'
    );


    /**
     * שליחה חוזרת של OTP.
     */
    if ( 'resend_otp' === $action ) {

        $state['otp_stage'] = true;

        $state['otp_token'] = sanitize_text_field(
            wp_unslash(
                $_POST['ct_otp_token'] ?? ''
            )
        );

        $otp_key = ct_register_get_otp_key(
            $state['otp_token']
        );

        $pending = get_transient( $otp_key );

        if (
            ! is_array( $pending )
            || empty( $pending['email'] )
        ) {

            $state['errors']['_form'] =
                'תהליך האימות פג. יש להתחיל את ההרשמה מחדש.';

            $state['otp_stage'] = false;

            return $state;
        }

        $state['otp_email'] = sanitize_email(
            $pending['email']
        );

        $resend_count = (int) (
            $pending['resend_count'] ?? 0
        );

        $next_resend_at = (int) (
            $pending['next_resend_at'] ?? 0
        );

        if ( $resend_count >= 3 ) {

            $state['errors']['_form'] =
                'הגעתם למספר המרבי של שליחות חוזרות. יש להתחיל את ההרשמה מחדש.';

            return $state;
        }

        if ( time() < $next_resend_at ) {

            $state['errors']['_form'] =
                'יש להמתין לפני שליחת קוד נוסף.';

            return $state;
        }

        $new_code = (string) random_int(
            100000,
            999999
        );

        $pending['otp_hash'] =
            wp_hash_password( $new_code );

        $pending['attempts'] = 0;

        $pending['resend_count'] =
            $resend_count + 1;

        $pending['next_resend_at'] =
            time() + 30;

        if (
            ct_register_send_otp_email(
                $state['otp_email'],
                $new_code
            )
        ) {

            set_transient(
                $otp_key,
                $pending,
                10 * MINUTE_IN_SECONDS
            );

            $state['otp_notice'] =
                'קוד אימות חדש נשלח לאימייל.';

        } else {

            $state['errors']['_form'] =
                'שליחת הקוד נכשלה. יש לנסות שוב בעוד מספר דקות.';
        }

        return $state;
    }


    /**
     * אימות OTP + יצירת המשתמש.
     */
    if ( 'verify_otp' === $action ) {

        $state['otp_stage'] = true;

        $state['otp_token'] = sanitize_text_field(
            wp_unslash(
                $_POST['ct_otp_token'] ?? ''
            )
        );

        $otp_code = preg_replace(
            '/\D+/',
            '',
            wp_unslash(
                $_POST['ct_otp_code'] ?? ''
            )
        );

        $otp_key = ct_register_get_otp_key(
            $state['otp_token']
        );

        $pending = get_transient( $otp_key );

        if (
            ! is_array( $pending )
            || empty( $pending['email'] )
        ) {

            $state['errors']['_form'] =
                'תהליך האימות פג. יש לחזור לטופס ההרשמה ולנסות שוב.';

            $state['otp_stage'] = false;

            return $state;
        }

        $state['otp_email'] = sanitize_email(
            $pending['email']
        );

        if (
            empty( $otp_code )
            || 6 !== strlen( $otp_code )
        ) {

            $state['errors']['otp'] =
                'יש להזין קוד בן 6 ספרות.';

            return $state;
        }

        if (
            (int) (
                $pending['attempts'] ?? 0
            ) >= 5
        ) {

            delete_transient( $otp_key );

            $state['errors']['_form'] =
                'בוצעו יותר מדי ניסיונות. יש להתחיל את ההרשמה מחדש.';

            $state['otp_stage'] = false;

            return $state;
        }

        if (
            ! wp_check_password(
                $otp_code,
                $pending['otp_hash']
            )
        ) {

            $pending['attempts'] =
                (int) (
                    $pending['attempts'] ?? 0
                ) + 1;

            set_transient(
                $otp_key,
                $pending,
                10 * MINUTE_IN_SECONDS
            );

            $state['errors']['otp'] =
                'קוד האימות שהוזן אינו תקין.';

            return $state;
        }

        if (
            email_exists(
                $state['otp_email']
            )
        ) {

            delete_transient( $otp_key );

            $state['errors']['_form'] =
                'כתובת האימייל הזו כבר רשומה. יש להתחבר לחשבון הקיים.';

            $state['otp_stage'] = false;

            return $state;
        }

        $password = ct_register_decrypt_value(
            $pending['password'] ?? ''
        );

        if ( empty( $password ) ) {

            delete_transient( $otp_key );

            $state['errors']['_form'] =
                'תהליך ההרשמה פג. יש להתחיל מחדש.';

            $state['otp_stage'] = false;

            return $state;
        }


        /**
         * התאמה ל-WooCommerce / MyListing.
         */
        $_POST['email'] =
            $state['otp_email'];

        $_POST['user_email'] =
            $state['otp_email'];

        $_POST['username'] =
            $state['otp_email'];


        $new_customer_id =
            wc_create_new_customer(
                $state['otp_email'],
                '',
                $password,
                [
                    'first_name' =>
                        sanitize_text_field(
                            $pending['first_name'] ?? ''
                        ),

                    'last_name' =>
                        sanitize_text_field(
                            $pending['last_name'] ?? ''
                        ),
                ]
            );


        if ( is_wp_error( $new_customer_id ) ) {

            $state['errors']['_form'] =
                $new_customer_id
                    ->get_error_message();

            if (
                function_exists(
                    'wc_clear_notices'
                )
            ) {
                wc_clear_notices();
            }

            return $state;
        }


    delete_transient( $otp_key );

    /**
     * שמירת הסכמה לשיווק.
     */
    $marketing = ! empty( $pending['marketing'] );

    update_user_meta(
        $new_customer_id,
        '_ct_marketing_consent',
        $marketing ? 1 : 0
    );

    update_user_meta(
        $new_customer_id,
        '_ct_marketing_consent_date',
        current_time( 'timestamp' )
    );

    /**
     * התחברות אוטומטית.
     */
    wc_set_customer_auth_cookie( $new_customer_id );

    /**
     * אין בחירת מסלול לפני ההרשמה — כל הרשמה חדשה מתחילה במסלול החינמי.
     * שדרוג ל-PRO מתבצע בתוך האשף עצמו, במסך intro-2.
     */
    $redirect = add_query_arg(
        [
            'listing_type'    => 'cc',
            'listing_package' => CT_FLOW_FREE_PRODUCT_ID,
            'skip_selection'  => 1,
        ],
        home_url( '/add-listing/' )
    );

    /**
     * AJAX - מחזירים את הכתובת ל-JS.
     */
    if ( wp_doing_ajax() ) {
        return [
            'success'  => true,
            'redirect' => $redirect,
            'errors'   => [],
        ];
    }

    /**
     * fallback ללא AJAX.
     */
    wp_safe_redirect( $redirect );
    exit;
    }


    /**
     * שלב ראשון:
     * בדיקת הטופס ושליחת OTP.
     */
if ( 'request_otp' === $action ) {

    $email = sanitize_email(
        wp_unslash( $_POST['email'] ?? '' )
    );

    $marketing = ! empty(
        $_POST['ct_marketing_consent']
    );

    $redirect = wp_validate_redirect(
        esc_url_raw(
            wp_unslash(
                $_POST['redirect'] ?? ''
            )
        ),
        wc_get_page_permalink( 'myaccount' )
    );

    $state['old'] = [
        'email' => $email,
    ];


    if ( ! is_email( $email ) ) {
        $state['errors']['email'] =
            'יש להזין כתובת אימייל תקינה';
    }


    if (
        empty( $state['errors'] )
        && email_exists( $email )
    ) {
        $state['errors']['email'] =
            'כתובת האימייל הזו כבר רשומה.';
    }


    $rate_key =
        'ct_register_otp_rate_'
        . md5( strtolower( $email ) );


    if (
        empty( $state['errors'] )
        && get_transient( $rate_key )
    ) {
        $state['errors']['_form'] =
            'קוד כבר נשלח לאימייל הזה. יש להמתין כדקה לפני ניסיון נוסף.';
    }


    if ( ! empty( $state['errors'] ) ) {
        return $state;
    }


    /**
     * אין שדה סיסמה בטופס.
     * יוצרים סיסמה אוטומטית בצד השרת.
     */
    $password = wp_generate_password(
        20,
        true,
        true
    );


    $encrypted_password =
        ct_register_encrypt_value( $password );


    if ( is_wp_error( $encrypted_password ) ) {

        $state['errors']['_form'] =
            $encrypted_password->get_error_message();

        return $state;
    }


    $otp_code = (string) random_int(
        100000,
        999999
    );

    $otp_token = bin2hex(
        random_bytes( 32 )
    );

    $otp_key = ct_register_get_otp_key(
        $otp_token
    );


    $pending = [
        'email'          => $email,
        'first_name'     => '',
        'last_name'      => '',
        'password'       => $encrypted_password,
        'redirect'       => $redirect,
        'marketing'      => $marketing ? 1 : 0,
        'otp_hash'       => wp_hash_password( $otp_code ),
        'attempts'       => 0,
        'resend_count'   => 0,
        'next_resend_at' => time() + 30,
    ];


    set_transient(
        $otp_key,
        $pending,
        10 * MINUTE_IN_SECONDS
    );

    set_transient(
        $rate_key,
        1,
        MINUTE_IN_SECONDS
    );


    if (
        ct_register_send_otp_email(
            $email,
            $otp_code
        )
    ) {

        $state['otp_stage'] = true;
        $state['otp_token'] = $otp_token;
        $state['otp_email'] = $email;

    } else {

        delete_transient( $otp_key );

        $state['errors']['_form'] =
            'שליחת קוד האימות נכשלה. יש לנסות שוב בעוד מספר דקות.';
    }

    return $state;
}


    return $state;
}


/**
 * הפעלת תהליך ההרשמה לפני הדפסת העמוד.
 */
add_action(
    'template_redirect',
    function () {

        if (
            is_user_logged_in()
            || ! function_exists(
                'is_account_page'
            )
            || ! is_account_page()
        ) {
            return;
        }


        if (
            empty(
                $_POST['ct_register_nonce']
            )
        ) {
            return;
        }


        $GLOBALS['ct_register_state'] =
            ct_process_account_registration();
    }
);


/**
 * קבלת state של טופס ההרשמה.
 */
function ct_get_registration_state() {

    return is_array(
        $GLOBALS['ct_register_state']
            ?? null
    )
        ? $GLOBALS['ct_register_state']
        : ct_get_default_registration_state();
}


/**
 * helper לקלאס שדה עם שגיאה.
 */
function ct_auth_field_class(
    $field,
    $errors
) {

    return 'ct-field'
        . (
            isset(
                $errors[ $field ]
            )
                ? ' ct-field--error'
                : ''
        );
}


/**
 * הדפסת טמפלט ההרשמה.
 */
function ct_render_account_registration() {

    if ( is_user_logged_in() ) {
        return;
    }


    $state = ct_get_registration_state();


    $errors = $state['errors'];

    $old = $state['old'];

    $otp_stage =
        $state['otp_stage'];

    $otp_token =
        $state['otp_token'];

    $otp_email =
        $state['otp_email'];

    $otp_notice =
        $state['otp_notice'];


    $template_path =
        get_stylesheet_directory()
        . '/templates/auth/register-otp.php';


    if (
        ! file_exists(
            $template_path
        )
    ) {

        echo '<p>טמפלט ההרשמה לא נמצא.</p>';

        return;
    }


    include $template_path;
}

add_action( 'wp_ajax_nopriv_ct_register_otp', 'ct_ajax_register_otp' );

function ct_ajax_register_otp() {

    $state = ct_process_account_registration();

    /**
     * הרשמה הסתיימה בהצלחה.
     */
    if (
        ! empty( $state['success'] )
        && ! empty( $state['redirect'] )
    ) {
        wp_send_json_success( [
            'redirect' => $state['redirect'],
        ] );
    }

    /**
     * אחרת מרנדרים מחדש את טופס ה-OTP.
     */
    $errors     = $state['errors'] ?? [];
    $old        = $state['old'] ?? [];
    $otp_stage  = ! empty( $state['otp_stage'] );
    $otp_token  = $state['otp_token'] ?? '';
    $otp_email  = $state['otp_email'] ?? '';
    $otp_notice = $state['otp_notice'] ?? '';

    ob_start();

    include get_stylesheet_directory()
        . '/templates/auth/register-otp.php';

    $html = ob_get_clean();

    wp_send_json_success( [
        'html' => $html,
    ] );
}