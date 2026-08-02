<?php
defined( 'ABSPATH' ) || exit;

$owner_meta = get_post_meta(
    $listing_id,
    '_coffeecart-owner',
    true
);

$coffeecart_owner = is_array( $owner_meta )
    ? ( $owner_meta[0] ?? '' )
    : $owner_meta;

$owner_image_url = '';

if ( is_numeric( $coffeecart_owner ) ) {
    $owner_image_url = wp_get_attachment_image_url(
        absint( $coffeecart_owner ),
        'thumbnail'
    );
} elseif ( is_string( $coffeecart_owner ) ) {
    $owner_image_url = $coffeecart_owner;
}
?>

<section class="ct-account-page">
<?php if ( $listing_id ) : ?>
    <section class="ct-account-status">
        <div class="ct-account-status__content">
            <h1 class="ct-account-status__title">
                העמוד שלכם פעיל בקופיטרייל
            </h1>

            <p class="ct-account-status__description">
                כרגע אתם מופיעים עם פרופיל בסיסי — לקוחות מוצאים אתכם לפי מיקום בלבד.
            </p>

            <div class="ct-account-status__badge">
                <svg
                    class="ct-account-status__badge-icon"
                    width="15"
                    height="15"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="2.6"></circle>
                </svg>

                <span class="ct-account-status__badge-text">
                    נמצאים בקופיטרייל לפי מיקום בלבד
                </span>
            </div>
        </div>

        <button class="ct-button ct-button--dark ct-account-status__button" type="button">
            שדרוג לפרו
        </button>
    </section>

    <section class="ct-account-preview">
        <div class="ct-account-preview__image">
            <span class="ct-account-preview__image-placeholder">
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
            </span>
        </div>

        <div class="ct-account-preview__content">
            <h2 class="ct-account-preview__title">
                צפו בעמוד שלכם
            </h2>

            <p class="ct-account-preview__description">
                כך העמוד שלכם נראה היום ללקוחות — וראו מה אפשר להוסיף עם פרו כדי למשוך יותר ביקורים.
            </p>

            <a class="ct-button ct-button--green ct-button--rounded" href="<?php echo esc_url( get_permalink( $listing_id ) ); ?>" role="button">
                <svg
                    class="ct-button__icon"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>

                <span>צפו בעמוד שלכם</span>
            </a>
        </div>
    </section>

    <section class="ct-pro-features">
        <h2 class="ct-pro-features__title">
            מה תקבלו בפרו?
        </h2>

        <div class="ct-pro-features__grid">
            <div class="ct-pro-feature">
                <span class="ct-pro-feature__icon">
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <rect x="6" y="11" width="12" height="9" rx="2"></rect>
                        <path d="M9 11V8a3 3 0 0 1 6 0v3"></path>
                    </svg>
                </span>

                <span class="ct-pro-feature__text">
                    שעות פתיחה
                </span>
            </div>

            <div class="ct-pro-feature">
                <span class="ct-pro-feature__icon">
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <rect x="6" y="11" width="12" height="9" rx="2"></rect>
                        <path d="M9 11V8a3 3 0 0 1 6 0v3"></path>
                    </svg>
                </span>

                <span class="ct-pro-feature__text">
                    תפריט מלא
                </span>
            </div>

            <div class="ct-pro-feature">
                <span class="ct-pro-feature__icon">
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <rect x="6" y="11" width="12" height="9" rx="2"></rect>
                        <path d="M9 11V8a3 3 0 0 1 6 0v3"></path>
                    </svg>
                </span>

                <span class="ct-pro-feature__text">
                    תמונות
                </span>
            </div>

            <div class="ct-pro-feature">
                <span class="ct-pro-feature__icon">
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <rect x="6" y="11" width="12" height="9" rx="2"></rect>
                        <path d="M9 11V8a3 3 0 0 1 6 0v3"></path>
                    </svg>
                </span>

                <span class="ct-pro-feature__text">
                    מאפיינים ושירותים
                </span>
            </div>
        </div>
    </section>

    <section class="ct-pro-recommendation">
        <div class="ct-pro-recommendation__content">
            <span class="ct-pro-recommendation__label">
                המלצה מאיתנו
            </span>

            <p class="ct-pro-recommendation__text">
                עמודי פרו מופיעים ביותר חיפושים וסינונים בקופיטרייל.
            </p>
        </div>

        <button class="ct-button ct-button--dark ct-pro-recommendation__button" type="button">
            שדרוג לפרו
        </button>
    </section>

    <section class="ct-social-exposure">
        <div class="ct-social-exposure__content">
            <h2 class="ct-social-exposure__title">
                רוצים יותר חשיפה דרך הרשתות?
            </h2>

            <p class="ct-social-exposure__description">
                עם פרו אנחנו משתפים את העגלה שלכם ברשתות החברתיות ובקהילה שלנו כדי להגיע ליותר אנשים.
            </p>
        </div>

        <button class="ct-button ct-button--green ct-button--rounded ct-social-exposure__button" type="button">
            שדרוג לפרו
        </button>
    </section>
<?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>