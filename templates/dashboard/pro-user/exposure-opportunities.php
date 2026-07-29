<?php
defined( 'ABSPATH' ) || exit;
?>

<section class="ct-account-page">
<?php if ( $listing_id ) : ?>
<div class="ct-growth-page">
 <button class="ct-growth-page__back-button">
  &rarr; חזרה לבית
 </button>
 <h1 class="ct-growth-page__title">
  גדלו עם CoffeeTrail
 </h1>
 <p class="ct-growth-page__description">
  דרכים פשוטות להגיע ליותר אנשים דרך CoffeeTrail.
 </p>
 <div class="ct-growth-options">
  <div class="ct-growth-card">
   <div class="ct-growth-card__inner">
    <span class="ct-growth-card__icon">
     <svg aria-hidden="true" class="ct-growth-card__svg" fill="none" focusable="false" height="24" stroke="#5E574B" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="24">
      <path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11z">
      </path>
      <circle cx="12" cy="10" r="2.6">
      </circle>
     </svg>
    </span>
    <div class="ct-growth-card__content">
     <div class="ct-growth-card__heading-row">
      <h3 class="ct-growth-card__title">
       הופיעו במפת השירותים של Pango
      </h3>
      <span class="ct-growth-card__badge">
       Pango
      </span>
     </div>
     <p class="ct-growth-card__description">
      עזרו לנהגים לגלות את העגלה שלכם בדרך.
     </p>
    </div>
    <button class="ct-growth-card__action">
     למידע נוסף
     <svg aria-hidden="true" class="ct-growth-card__action-icon" fill="none" focusable="false" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewbox="0 0 24 24" width="16">
      <polyline points="6 9 12 15 18 9">
      </polyline>
     </svg>
    </button>
   </div>
  </div>
  <div class="ct-growth-card">
   <div class="ct-growth-card__inner">
    <span class="ct-growth-card__icon">
     <svg aria-hidden="true" class="ct-growth-card__svg" fill="none" focusable="false" height="24" stroke="#5E574B" stroke-width="1.7" viewbox="0 0 24 24" width="24">
      <rect height="18" rx="5" width="18" x="3" y="3">
      </rect>
      <circle cx="12" cy="12" r="4">
      </circle>
      <circle cx="17.5" cy="6.5" r="1">
      </circle>
     </svg>
    </span>
    <div class="ct-growth-card__content">
     <h3 class="ct-growth-card__title">
      קבלו חשיפה דרך אינסטגרם
     </h3>
     <p class="ct-growth-card__description">
      תייגו את CoffeeTrail ואולי נשתף אתכם עם הקהילה שלנו.
     </p>
    </div>
    <button class="ct-growth-card__action">
     איך זה עובד
     <svg aria-hidden="true" class="ct-growth-card__action-icon" fill="none" focusable="false" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewbox="0 0 24 24" width="16">
      <polyline points="6 9 12 15 18 9">
      </polyline>
     </svg>
    </button>
   </div>
  </div>
  <div class="ct-growth-card">
   <div class="ct-growth-card__inner">
    <span class="ct-growth-card__icon">
     <svg aria-hidden="true" class="ct-growth-card__svg" fill="none" focusable="false" height="24" stroke="#5E574B" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="24">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2">
      </path>
      <circle cx="9" cy="7" r="4">
      </circle>
      <path d="M23 21v-2a4 4 0 0 0-3-3.87">
      </path>
     </svg>
    </span>
    <div class="ct-growth-card__content">
     <h3 class="ct-growth-card__title">
      שתפו את העגלה שלכם עם הקהילה
     </h3>
     <p class="ct-growth-card__description">
      פרסמו עדכונים ואירועים בקהילת אוהבי הקפה שלנו.
     </p>
    </div>
    <button class="ct-growth-card__action">
     איך זה עובד
     <svg aria-hidden="true" class="ct-growth-card__action-icon" fill="none" focusable="false" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewbox="0 0 24 24" width="16">
      <polyline points="6 9 12 15 18 9">
      </polyline>
     </svg>
    </button>
   </div>
  </div>
 </div>
</div>
 <?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>