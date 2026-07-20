<?php
defined( 'ABSPATH' ) || exit;
?>

<section class="ct-account-page">
<?php if ( $listing_id ) : ?>
<div class="ct-exposure-page">
    <a class="ct-activity-page__back-button" href="/my-account/free/home/">
        → חזרה לבית
    </a>

    <h1 class="ct-exposure-page__title">
        הזדמנויות חשיפה
    </h1>

    <p class="ct-exposure-page__description">
        כרגע אתם מופיעים עם פרופיל חינמי. עם פרו נציג אתכם לקהלים חדשים — דרך מפת Pango, אינסטגרם והקהילה שלנו.
    </p>

    <section class="ct-exposure-card">
        <span class="ct-exposure-card__lock-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="4" y="11" width="16" height="10" rx="2"></rect>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
            </svg>
        </span>

        <h3 class="ct-exposure-card__title">
            הגיעו ליותר אנשים עם פרו
        </h3>

        <p class="ct-exposure-card__description">
            חברי פרו נחשפים בערוצים שמביאים לקוחות חדשים. הנה מה שייפתח לכם:
        </p>

        <div class="ct-exposure-card__list">
            <div class="ct-exposure-item">
                <span class="ct-exposure-item__icon">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11z"></path>
                        <circle cx="12" cy="10" r="2.6"></circle>
                    </svg>
                </span>

                <div class="ct-exposure-item__content">
                    <p class="ct-exposure-item__title">
                        מפת השירותים של Pango
                    </p>

                    <p class="ct-exposure-item__description">
                        הופיעו לנהגים שמחפשים חניה בקרבת מקום.
                    </p>
                </div>
            </div>

            <div class="ct-exposure-item">
                <span class="ct-exposure-item__icon">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                        <circle cx="12" cy="12" r="4"></circle>
                        <circle cx="17.5" cy="6.5" r="1"></circle>
                    </svg>
                </span>

                <div class="ct-exposure-item__content">
                    <p class="ct-exposure-item__title">
                        שיתופים באינסטגרם
                    </p>

                    <p class="ct-exposure-item__description">
                        נשתף עגלות נבחרות עם הקהילה שלנו.
                    </p>
                </div>
            </div>

            <div class="ct-exposure-item">
                <span class="ct-exposure-item__icon">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    </svg>
                </span>

                <div class="ct-exposure-item__content">
                    <p class="ct-exposure-item__title">
                        הקהילה שלנו
                    </p>

                    <p class="ct-exposure-item__description">
                        שתפו עדכונים ואירועים עם אוהבי הקפה.
                    </p>
                </div>
            </div>
        </div>

        <button class="ct-exposure-card__upgrade-button" type="button">
            שדרוג לפרו
        </button>
    </section>
</div>
<?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>  