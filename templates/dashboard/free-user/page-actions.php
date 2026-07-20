<?php
defined( 'ABSPATH' ) || exit;
?>

<section class="ct-account-page">
<?php if ( $listing_id ) : ?>
    <div class="ct-activity-page">
    <a class="ct-activity-page__back-button" href="/my-account/free/home/">
        → חזרה לבית
    </a>

    <h1 class="ct-activity-page__title">
        פעילות בעמוד
    </h1>

    <p class="ct-activity-page__description">
        כרגע אתם מופיעים עם פרופיל חינמי — לקוחות מוצאים אתכם לפי מיקום. עם פרו תראו בדיוק כמה אנשים מגלים אתכם ומה הם עושים.
    </p>

    <section class="ct-activity-card">
        <div class="ct-activity-card__preview">
            <div class="ct-activity-stats">
                <div class="ct-activity-stat">
                    <div class="ct-activity-stat__value">691</div>
                    <div class="ct-activity-stat__label">גילו אתכם</div>
                </div>

                <div class="ct-activity-stats__divider"></div>

                <div class="ct-activity-stat">
                    <div class="ct-activity-stat__value ct-activity-stat__value--highlight">26</div>
                    <div class="ct-activity-stat__label">ביקשו ניווט</div>
                </div>

                <div class="ct-activity-stats__divider"></div>

                <div class="ct-activity-stat">
                    <div class="ct-activity-stat__value">25</div>
                    <div class="ct-activity-stat__label">צפו בתפריט</div>
                </div>
            </div>

            <svg class="ct-activity-chart" viewBox="0 0 600 90" preserveAspectRatio="none" aria-hidden="true">
                <path
                    d="M0,78 L55,66 L109,70 L164,55 L218,58 L273,44 L327,48 L382,33 L436,37 L491,22 L545,26 L600,12"
                    fill="none"
                    stroke="#1F9254"
                    stroke-width="2.5"
                    vector-effect="non-scaling-stroke"
                ></path>
            </svg>
        </div>

        <div class="ct-activity-card__overlay">
            <span class="ct-activity-card__lock-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="4" y="11" width="16" height="10" rx="2"></rect>
                    <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
                </svg>
            </span>

            <h3 class="ct-activity-card__title">
                ראו מי מגלה אתכם
            </h3>

            <p class="ct-activity-card__description">
                עם פרו תראו כמה אנשים גילו אתכם, ביקשו ניווט וצפו בתפריט — ואיך זה גדל לאורך הזמן.
            </p>

            <button class="ct-activity-card__upgrade-button" type="button">
                שדרוג לפרו
            </button>
        </div>
    </section>
</div>
 <?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>   