<?php
defined( 'ABSPATH' ) || exit;
?>

<section class="ct-account-page">
<?php if ( $listing_id ) : ?>
<div class="ct-analytics-page">
 <button class="ct-analytics-page__back-button">
  &rarr; חזרה לבית
 </button>
 <h1 class="ct-analytics-page__title">
  פעילות בעמוד
 </h1>
 <p class="ct-analytics-page__description">
  ב 90 הימים האחרונים אנשים גילו אתכם, ביקשו ניווט וצפו בתפריט &mdash; דרך קופיטרייל.
 </p>
 <div class="ct-analytics-summary">
  <div class="ct-analytics-summary__item">
   <div class="ct-analytics-summary__value">
    691
   </div>
   <div class="ct-analytics-summary__label">
    גילו אתכם
   </div>
  </div>
  <div class="ct-analytics-summary__divider">
  </div>
  <div class="ct-analytics-summary__item">
   <div class="ct-analytics-summary__value ct-analytics-summary__value--highlight">
    26
   </div>
   <div class="ct-analytics-summary__label">
    ביקשו ניווט
   </div>
  </div>
  <div class="ct-analytics-summary__divider">
  </div>
  <div class="ct-analytics-summary__item">
   <div class="ct-analytics-summary__value">
    25
   </div>
   <div class="ct-analytics-summary__label">
    צפו בתפריט
   </div>
  </div>
 </div>
 <button class="ct-analytics-pango">
  <svg aria-hidden="true" class="ct-analytics-pango__icon" fill="none" focusable="false" height="15" stroke="#8A8175" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewbox="0 0 24 24" width="15">
   <path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11z">
   </path>
   <circle cx="12" cy="10" r="2.6">
   </circle>
  </svg>
  <span class="ct-analytics-pango__text">
   רוצים להופיע גם על המפה של PANGO ולהגדיל חשיפה?
  </span>
  <span class="ct-analytics-pango__action">
   גלו איך &larr;
  </span>
 </button>
 <h2 class="ct-analytics-section__title">
  מאיפה אנשים הגיעו
 </h2>
 <div class="ct-analytics-sources">
  <div class="ct-analytics-source">
   <span class="ct-analytics-source__label">
    חיפוש בקופיטרייל
   </span>
   <span class="ct-analytics-source__track">
    <span class="ct-analytics-source__bar">
    </span>
   </span>
   <span class="ct-analytics-source__value">
    71%
   </span>
  </div>
  <div class="ct-analytics-source">
   <span class="ct-analytics-source__label">
    Google
   </span>
   <span class="ct-analytics-source__track">
    <span class="ct-analytics-source__bar">
    </span>
   </span>
   <span class="ct-analytics-source__value">
    29%
   </span>
  </div>
 </div>
 <div class="ct-analytics-trend__header">
  <h2 class="ct-analytics-section__title">
   פעילות לאורך הזמן
  </h2>
  <div class="ct-analytics-range">
   <button class="ct-analytics-range__button ct-analytics-range__button--active">
    3 חודשים
   </button>
   <button class="ct-analytics-range__button">
    6 חודשים
   </button>
   <button class="ct-analytics-range__button">
    שנה
   </button>
  </div>
 </div>
 <div class="ct-analytics-chart-card">
  <svg aria-hidden="true" class="ct-analytics-chart" focusable="false" preserveaspectratio="none" viewbox="0 0 600 150">
   <defs>
    <lineargradient id="ctArea" x1="0" x2="0" y1="0" y2="1">
     <stop offset="0" stop-color="#1F9254" stop-opacity="0.16">
     </stop>
     <stop offset="1" stop-color="#1F9254" stop-opacity="0">
     </stop>
    </lineargradient>
   </defs>
   <line stroke="#F2EDE3" stroke-width="1" vector-effect="non-scaling-stroke" x1="0" x2="600" y1="38" y2="38">
   </line>
   <line stroke="#F2EDE3" stroke-width="1" vector-effect="non-scaling-stroke" x1="0" x2="600" y1="75" y2="75">
   </line>
   <line stroke="#F2EDE3" stroke-width="1" vector-effect="non-scaling-stroke" x1="0" x2="600" y1="112" y2="112">
   </line>
   <path d="M0.0,94.9 L54.5,77.0 L109.1,87.7 L163.6,62.7 L218.2,69.8 L272.7,48.4 L327.3,55.5 L381.8,37.6 L436.4,44.8 L490.9,23.3 L545.5,30.5 L600.0,9.0 L600,150 L0,150 Z" fill="url(#ctArea)">
   </path>
   <path d="M0.0,94.9 L54.5,77.0 L109.1,87.7 L163.6,62.7 L218.2,69.8 L272.7,48.4 L327.3,55.5 L381.8,37.6 L436.4,44.8 L490.9,23.3 L545.5,30.5 L600.0,9.0" fill="none" stroke="#1F9254" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" vector-effect="non-scaling-stroke">
   </path>
   <circle cx="600" cy="9" fill="#1F9254" r="4.5" stroke="#FFFDF9" stroke-width="2.5" vector-effect="non-scaling-stroke">
   </circle>
  </svg>
  <div class="ct-analytics-chart__labels">
   <span class="ct-analytics-chart__label">
    אפריל
   </span>
   <span class="ct-analytics-chart__label">
    מאי
   </span>
   <span class="ct-analytics-chart__label">
    יוני
   </span>
  </div>
 </div>
 <div class="ct-analytics-cta">
  <h3 class="ct-analytics-cta__title">
   כל אדם שמגלה אתכם הוא לקוח בדרך
  </h3>
  <p class="ct-analytics-cta__description">
   העמוד שלכם עובד בשבילכם מסביב לשעון. כמה צעדים קטנים עוזרים ליותר אנשים למצוא אתכם ולבחור להגיע.
  </p>
  <div class="ct-analytics-cta__actions">
   <button class="ct-analytics-cta__button ct-analytics-cta__button--primary">
    הגדלת חשיפה
   </button>
   <button class="ct-analytics-cta__button ct-analytics-cta__button--secondary">
    עדכון העמוד
   </button>
  </div>
 </div>
</div>
 <?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>