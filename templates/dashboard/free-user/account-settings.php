<?php
defined( 'ABSPATH' ) || exit;
?>

<section class="ct-account-page">
<?php if ( $listing_id ) : ?>
    <div class="ct-settings-page">
    <a class="ct-activity-page__back-button" href="/my-account/free/home/">
        → חזרה לבית
    </a>
    <h1 class="ct-settings-page__title">
    הגדרות
    </h1>
    <p class="ct-settings-page__description">
    ניהול החשבון, המנוי וההעדפות שלכם. הגדרות אלו נפרדות מתוכן העמוד שלקוחות רואים.
    </p>
    <h2 class="ct-settings-section__title">
    חשבון
    </h2>
    <div class="ct-settings-panel ct-settings-account">
    <div class="ct-settings-account__profile">
    <span class="ct-settings-account__avatar">
        ד
    </span>
    <div class="ct-settings-account__profile-content">
        <p class="ct-settings-account__name">
        <?php echo esc_html( get_the_title( $listing_id ) ); ?>
        </p>
        <p class="ct-settings-account__role">
        בעלת עגלת קפה <?php echo esc_html( get_the_title( $listing_id ) ); ?>
        </p>
    </div>
    </div>
    <button class="ct-settings-row">
    <span class="ct-settings-row__label">
        שם בעלים
    </span>
    <span class="ct-settings-row__value">
       <?php echo esc_html( get_the_title( $listing_id ) ); ?>
        <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
        </svg>
    </span>
    </button>
    <button class="ct-settings-row">
    <span class="ct-settings-row__label">
        אימייל
    </span>
    <span class="ct-settings-row__value">
        <span class="ct-settings-row__ltr-value">
        dana@example.com
        </span>
        <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
        </svg>
    </span>
    </button>
    <button class="ct-settings-row">
    <span class="ct-settings-row__label">
        סיסמה
    </span>
    <span class="ct-settings-row__value">
        שינוי סיסמה
        <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
        </svg>
    </span>
    </button>
    <button class="ct-settings-row ct-settings-row--last">
    <span class="ct-settings-row__label">
        שפת ממשק
    </span>
    <span class="ct-settings-row__value">
        עברית
        <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
        </svg>
    </span>
    </button>
    </div>
    <h2 class="ct-settings-section__title">
    מנוי ותשלומים
    </h2>
    <div class="ct-settings-plan-card">
    <div class="ct-settings-plan-card__header">
    <div class="ct-settings-plan-card__content">
        <div class="ct-settings-plan-card__name-row">
        <span class="ct-settings-plan-card__name">
        חינמי
        </span>
        <span class="ct-settings-plan-card__status">
        פעיל
        </span>
        </div>
        <p class="ct-settings-plan-card__description">
        מופיעים בקופיטרייל לפי מיקום בלבד
        </p>
    </div>
    <div class="ct-settings-plan-card__price-wrap">
        <div class="ct-settings-plan-card__price">
        ₪0
        </div>
        <div class="ct-settings-plan-card__price-note">
        חינם לתמיד
        </div>
    </div>
    </div>
    <button class="ct-settings-plan-card__button">
    <svg aria-hidden="true" class="ct-settings-plan-card__button-icon" fill="none" focusable="false" height="17" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="17">
        <path d="M3 7h13l-3-3M21 17H8l3 3">
        </path>
    </svg>
    שינוי תוכנית
    </button>
    </div>
    <div class="ct-settings-panel ct-settings-billing">
    <button class="ct-settings-row">
    <span class="ct-settings-row__label">
        אמצעי תשלום
    </span>
    <span class="ct-settings-row__value">
        <span class="ct-settings-payment-method">
        <span class="ct-settings-payment-method__card">
        </span>
        &bull;&bull;&bull;&bull; 3456
        </span>
        <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
        </svg>
    </span>
    </button>
    <button class="ct-settings-row ct-settings-row--last">
    <span class="ct-settings-row__label">
        חשבוניות וקבלות
    </span>
    <span class="ct-settings-row__value">
        צפייה
        <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
        </svg>
    </span>
    </button>
    <div class="ct-settings-billing-email">
    <div class="ct-settings-billing-email__inner">
        <div class="ct-settings-billing-email__content">
        <p class="ct-settings-billing-email__title">
        מייל נוסף לחשבוניות וקבלות
        </p>
        <p class="ct-settings-billing-email__description">
        חשבוניות וקבלות יישלחו גם לכתובת הזו &mdash; נוח למשל לשליחה להנהלת החשבונות.
        </p>
        </div>
        <button class="ct-settings-billing-email__button">
        <svg aria-hidden="true" class="ct-settings-billing-email__button-icon" fill="none" focusable="false" height="15" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" viewbox="0 0 24 24" width="15">
        <path d="M12 5v14M5 12h14">
        </path>
        </svg>
        הוספת מייל
        </button>
    </div>
    </div>
    </div>
    <h2 class="ct-settings-section__title">
    ניהול העגלה
    </h2>
    <div class="ct-settings-panel ct-settings-cart">
    <button class="ct-settings-cart__contact-button">
    <div class="ct-settings-cart__contact-content">
        <div class="ct-settings-cart__contact-heading">
        <span class="ct-settings-cart__contact-title">
        איש קשר מטעם העגלה
        </span>
        <span class="ct-settings-cart__private-badge">
        לא מוצג ללקוחות
        </span>
        </div>
        <p class="ct-settings-cart__contact-description">
        משמש לזיהוי החשבון ולעדכונים מקופיטרייל בלבד.
        </p>
    </div>
    <span class="ct-settings-cart__contact-value">
        050-123-4567
        <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
        </svg>
    </span>
    </button>
    </div>
    <div class="ct-settings-panel ct-settings-legal">
    <button class="ct-settings-row">
    <span class="ct-settings-row__label">
        תנאי שימוש
    </span>
    <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
    </svg>
    </button>
    <button class="ct-settings-row">
    <span class="ct-settings-row__label">
        מדיניות פרטיות
    </span>
    <svg aria-hidden="true" class="ct-settings-row__arrow" fill="none" focusable="false" height="16" stroke="#C9BFAD" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
        <polyline points="15 18 9 12 15 6">
        </polyline>
    </svg>
    </button>
    <button class="ct-settings-logout">
    <svg aria-hidden="true" class="ct-settings-logout__icon" fill="none" focusable="false" height="17" stroke="#5E574B" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" viewbox="0 0 24 24" width="17">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4">
        </path>
        <polyline points="16 17 21 12 16 7">
        </polyline>
        <line x1="21" x2="9" y1="12" y2="12">
        </line>
    </svg>
    <span class="ct-settings-logout__label">
        יציאה מהחשבון
    </span>
    </button>
    </div>
    <button class="ct-settings-delete-account">
    מחיקת חשבון
    </button>
    </div>
 <?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>   