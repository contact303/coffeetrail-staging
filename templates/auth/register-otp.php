<?php
defined( 'ABSPATH' ) || exit;

$errors     = isset( $errors ) && is_array( $errors ) ? $errors : [];
$old        = isset( $old ) && is_array( $old ) ? $old : [];
$otp_stage  = ! empty( $otp_stage );
$otp_token  = isset( $otp_token ) ? (string) $otp_token : '';
$otp_email  = isset( $otp_email ) ? (string) $otp_email : '';
$otp_notice = isset( $otp_notice ) ? (string) $otp_notice : '';

$page_url = wc_get_page_permalink( 'myaccount' );

$redirect_url = isset( $_REQUEST['redirect'] )
    ? wp_validate_redirect(
        esc_url_raw( wp_unslash( $_REQUEST['redirect'] ) ),
        wc_get_page_permalink( 'myaccount' )
    )
    : wc_get_page_permalink( 'myaccount' );
?>

<div class="ct-email-register">

    <?php if ( ! empty( $errors['_form'] ) ) : ?>
        <div class="ct-auth-notice ct-auth-notice--error" role="alert">
            <?php echo wp_kses_post( $errors['_form'] ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $otp_notice ) ) : ?>
        <div class="ct-auth-notice ct-auth-notice--success" role="status">
            <?php echo esc_html( $otp_notice ); ?>
        </div>
    <?php endif; ?>


    <?php if ( $otp_stage ) : ?>

        <div
            class="ct-auth-variant ct-auth-variant--active"
            id="ct-auth-otp"
            aria-hidden="false"
        >

            <div class="ct-auth-divider">
                <span class="">
                    אימות כתובת האימייל
                </span>
            </div>

            <p class="ct-auth-subtitle">
                שלחנו קוד בן 6 ספרות אל
                <strong dir="ltr">
                    <?php echo esc_html( $otp_email ); ?>
                </strong>
            </p>


            <form
                method="post"
                action="<?php echo esc_url( $page_url ); ?>"
                class="ct-auth-form"
                id="ct-form-otp"
                novalidate
            >

                <?php wp_nonce_field( 'ct_register', 'ct_register_nonce' ); ?>

                <input
                    type="hidden"
                    name="ct_register_action"
                    value="verify_otp"
                >

                <input
                    type="hidden"
                    name="ct_otp_token"
                    value="<?php echo esc_attr( $otp_token ); ?>"
                >


                <div class="ct-field-group">

                    <input
                        type="text"
                        name="ct_otp_code"
                        id="ct-otp-code"
                        class="<?php echo esc_attr(
                            ct_auth_field_class( 'otp', $errors )
                        ); ?>"
                        placeholder="קוד אימות"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        dir="ltr"
                        autofocus
                    >

                    <?php if ( isset( $errors['otp'] ) ) : ?>
                        <span class="ct-field-error" role="alert">
                            <?php echo esc_html( $errors['otp'] ); ?>
                        </span>
                    <?php endif; ?>

                </div>


                <button
                    type="submit"
                    class="ct-auth-btn"
                >
                    אימות והמשך
                </button>

            </form>


            <form
                method="post"
                action="<?php echo esc_url( $page_url ); ?>"
                class="ct-otp-resend-form"
                id="ct-form-otp-resend"
            >

                <?php wp_nonce_field( 'ct_register', 'ct_register_nonce' ); ?>

                <input
                    type="hidden"
                    name="ct_register_action"
                    value="resend_otp"
                >

                <input
                    type="hidden"
                    name="ct_otp_token"
                    value="<?php echo esc_attr( $otp_token ); ?>"
                >

                <button
                    type="submit"
                    class="ct-auth-switch-btn"
                    id="ct-otp-resend-btn"
                    disabled
                >
                    שליחת קוד מחדש
                    <span id="ct-otp-resend-timer">(30)</span>
                </button>

            </form>

        </div>


    <?php else : ?>

        <div
            class="ct-auth-variant ct-auth-variant--active"
            id="ct-auth-email"
        >

            <div class="ct-auth-divider">
                <span>הרשמה באמצעות אימייל</span>
            </div>


            <form
                method="post"
                action="<?php echo esc_url( $page_url ); ?>"
                class="ct-auth-form"
                id="ct-form-email-register"
                novalidate
            >

                <?php wp_nonce_field( 'ct_register', 'ct_register_nonce' ); ?>

                <input
                    type="hidden"
                    name="ct_register_action"
                    value="request_otp"
                >

                <input
                    type="hidden"
                    name="ct_form_type"
                    value="gmail_email"
                >

                <input
                    type="hidden"
                    name="redirect"
                    value="<?php echo esc_attr( $redirect_url ); ?>"
                >


                <div class="ct-field-group">

                    <input
                        type="email"
                        name="email"
                        id="ct-register-email"
                        class="<?php echo esc_attr(
                            ct_auth_field_class( 'email', $errors )
                        ); ?>"
                        placeholder="אימייל"
                        value="<?php echo esc_attr(
                            $old['email'] ?? ''
                        ); ?>"
                        autocomplete="email"
                        dir="rtl"
                    >

                    <?php if ( isset( $errors['email'] ) ) : ?>
                        <span class="ct-field-error" role="alert">
                            <?php echo wp_kses_post( $errors['email'] ); ?>
                        </span>
                    <?php endif; ?>

                </div>


                <label class="ct-checkbox-label">

                    <input
                        type="checkbox"
                        name="ct_marketing_consent"
                        value="1"
                        <?php checked(
                            ! empty( $_POST['ct_marketing_consent'] )
                        ); ?>
                    >

                    <span>
                        אשמח לקבל עדכונים, טיפים והצעות מקופיטרייל
                    </span>

                </label>


                <button
                    type="submit"
                    class="ct-auth-btn"
                >
                    הרשמה
                </button>
                <div class="ct-auth-tos" dir="rtl">
                    בבחירת <strong>הסכמה והמשך</strong>, אני מסכים/ה ל
                    <a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener">תנאי השירות</a>
                    של עגלות קפה ול
                    <a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener">מדיניות הפרטיות</a>.
                </div>
            </form>

        </div>

    <?php endif; ?>

</div>