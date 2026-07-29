<?php
defined( 'ABSPATH' ) || exit;
?>

<section class="ct-account-page">
<?php if ( $listing_id ) : ?>
<div class="ct-dashboard-home">
 <div class="ct-dashboard-welcome">
  <button class="scp0 ct-dashboard-welcome__dismiss" title="אחר כך">
   <svg aria-hidden="true" class="ct-dashboard-welcome__dismiss-icon" fill="none" focusable="false" height="15" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewbox="0 0 24 24" width="15">
    <path d="M18 6L6 18M6 6l12 12">
    </path>
   </svg>
  </button>
  <h1 class="ct-dashboard-welcome__title">
   🎉 העמוד שלכם באוויר
  </h1>
  <p class="ct-dashboard-welcome__description">
   הגיע הזמן לספר לכולם שאתם בקופיטרייל.
  </p>
  <button class="scp1 ct-dashboard-welcome__share-button">
   בחרו איך לשתף את העמוד
   <svg aria-hidden="true" class="ct-dashboard-welcome__share-icon" fill="none" focusable="false" height="17" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" viewbox="0 0 24 24" width="17">
    <polyline points="6 9 12 15 18 9">
    </polyline>
   </svg>
  </button>
 </div>
 <div class="ct-dashboard-tasks">
  <h2 class="ct-dashboard-section__title">
   כדאי לעשות עכשיו
  </h2>
  <div class="ct-dashboard-tasks__list">
   <div class="ct-dashboard-task-card">
    <div class="ct-dashboard-task-card__content">
     <span class="ct-dashboard-task-card__icon">
      <svg aria-hidden="true" class="ct-dashboard-task-card__svg" fill="none" focusable="false" height="22" stroke="#1F9254" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" viewbox="0 0 24 24" width="22">
       <path d="M12 20h9">
       </path>
       <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z">
       </path>
      </svg>
     </span>
     <div class="ct-dashboard-task-card__text">
      <h3 class="ct-dashboard-task-card__title">
       ספרו את הסיפור שלכם
      </h3>
      <p class="ct-dashboard-task-card__description">
       כמה משפטים על מי אתם ועל החוויה שמחכה אצלכם עוזרים ללקוחות להתחבר.
      </p>
     </div>
    </div>
    <button class="ct-dashboard-task-card__button">
     הוספת סיפור
    </button>
   </div>
   <div class="ct-dashboard-task-card">
    <div class="ct-dashboard-task-card__content">
     <span class="ct-dashboard-task-card__icon">
      <svg aria-hidden="true" class="ct-dashboard-task-card__svg" fill="none" focusable="false" height="22" stroke="#1F9254" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" viewbox="0 0 24 24" width="22">
       <path d="M9 20l-6-3V4l6 3 6-3 6 3v13l-6-3-6 3Z">
       </path>
       <path d="M9 7v13M15 4v13">
       </path>
      </svg>
     </span>
     <div class="ct-dashboard-task-card__text">
      <h3 class="ct-dashboard-task-card__title">
       טיולים בסביבה והתאמה למשפחות
      </h3>
      <p class="ct-dashboard-task-card__description">
       עברו על הטיולים בסביבתכם וסמנו אם יש לכם מקום לתינוק לשחק על שמיכה.
      </p>
     </div>
    </div>
    <button class="ct-dashboard-task-card__button">
     למעבר על הפרטים
    </button>
   </div>
  </div>
 </div>
 <div class="ct-dashboard-edit">
  <div class="ct-dashboard-section__header">
   <h2 class="ct-dashboard-section__title">
    עריכת העמוד
   </h2>
   <button class="ct-dashboard-section__link">
    לכל אפשרויות העריכה &larr;
   </button>
  </div>
  <div class="ct-dashboard-edit__grid">
   <button class="ct-dashboard-edit-card">
    <svg aria-hidden="true" class="ct-dashboard-edit-card__icon" fill="none" focusable="false" height="22" stroke="#5E574B" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="22">
     <circle cx="12" cy="12" r="9">
     </circle>
     <path d="M12 7v5l3 2">
     </path>
    </svg>
    <span class="ct-dashboard-edit-card__label">
     שעות פתיחה
    </span>
   </button>
   <button class="ct-dashboard-edit-card">
    <svg aria-hidden="true" class="ct-dashboard-edit-card__icon" fill="none" focusable="false" height="22" stroke="#5E574B" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="22">
     <path d="M4 6h16M4 12h16M4 18h10">
     </path>
    </svg>
    <span class="ct-dashboard-edit-card__label">
     תפריט
    </span>
   </button>
   <button class="ct-dashboard-edit-card">
    <svg aria-hidden="true" class="ct-dashboard-edit-card__icon" fill="none" focusable="false" height="22" stroke="#5E574B" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="22">
     <rect height="18" rx="3" width="18" x="3" y="3">
     </rect>
     <circle cx="8.5" cy="8.5" r="1.6">
     </circle>
     <path d="M21 15l-5-5L5 21">
     </path>
    </svg>
    <span class="ct-dashboard-edit-card__label">
     תמונות
    </span>
   </button>
   <button class="ct-dashboard-edit-card">
    <svg aria-hidden="true" class="ct-dashboard-edit-card__icon" fill="none" focusable="false" height="22" stroke="#5E574B" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="22">
     <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z">
     </path>
     <path d="M14 3v6h6M8 13h8M8 17h6">
     </path>
    </svg>
    <span class="ct-dashboard-edit-card__label">
     פרטי העמוד
    </span>
   </button>
  </div>
 </div>
 <div class="ct-dashboard-exposure">
  <div class="ct-dashboard-exposure__header">
   <h2 class="ct-dashboard-exposure__title">
    רוצים להגיע ליותר אנשים?
   </h2>
   <button class="ct-dashboard-exposure__link">
    לכל ההזדמנויות &larr;
   </button>
  </div>
  <p class="ct-dashboard-exposure__description">
   הכירו דרכים נוספות להגדיל את החשיפה של העגלה שלכם.
  </p>
  <div class="ct-dashboard-exposure__list">
   <button class="ct-dashboard-opportunity ct-dashboard-opportunity--featured">
    <span class="ct-dashboard-opportunity__icon ct-dashboard-opportunity__icon--featured">
     <svg aria-hidden="true" class="ct-dashboard-opportunity__svg" fill="none" focusable="false" height="20" stroke="#1F9254" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewbox="0 0 24 24" width="20">
      <path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11z">
      </path>
      <circle cx="12" cy="10" r="2.6">
      </circle>
     </svg>
    </span>
    <span class="ct-dashboard-opportunity__content">
     <span class="ct-dashboard-opportunity__title ct-dashboard-opportunity__title--featured">
      הצטרפו ל-Pango והופיעו במפת השירותים
     </span>
     <span class="ct-dashboard-opportunity__description ct-dashboard-opportunity__description--featured">
      העניקו ללקוחות Pango הגדלת קפה חם במתנה
     </span>
    </span>
    <span class="ct-dashboard-opportunity__badge">
     חדש
    </span>
   </button>
   <button class="ct-dashboard-opportunity">
    <span class="ct-dashboard-opportunity__icon">
     <svg aria-hidden="true" class="ct-dashboard-opportunity__svg" fill="none" focusable="false" height="19" stroke="#5E574B" stroke-width="1.7" viewbox="0 0 24 24" width="19">
      <rect height="18" rx="5" width="18" x="3" y="3">
      </rect>
      <circle cx="12" cy="12" r="4">
      </circle>
      <circle cx="17.5" cy="6.5" r="1">
      </circle>
     </svg>
    </span>
    <span class="ct-dashboard-opportunity__content">
     <span class="ct-dashboard-opportunity__title">
      אינסטגרם
     </span>
     <span class="ct-dashboard-opportunity__description">
      תייגו את קופיטרייל ואולי נשתף אתכם עם הקהילה שלנו.
     </span>
    </span>
    <span class="ct-dashboard-opportunity__action">
     איך זה עובד &larr;
    </span>
   </button>
   <button class="ct-dashboard-opportunity">
    <span class="ct-dashboard-opportunity__icon">
     <svg aria-hidden="true" class="ct-dashboard-opportunity__svg" fill="none" focusable="false" height="19" stroke="#5E574B" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewbox="0 0 24 24" width="19">
      <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z">
      </path>
     </svg>
    </span>
    <span class="ct-dashboard-opportunity__content">
     <span class="ct-dashboard-opportunity__title">
      פייסבוק
     </span>
     <span class="ct-dashboard-opportunity__description">
      שתפו עדכונים ואירועים בקבוצת הפייסבוק של אוהבי הקפה שלנו.
     </span>
    </span>
    <span class="ct-dashboard-opportunity__action">
     איך זה עובד &larr;
    </span>
   </button>
  </div>
 </div>
</div>
 <?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>