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

<div class="share-block">
          <h2>בחרו איך לשתף</h2>
          <div class="share-buttons">
            <button class="share-button whatsapp" data-url="<?php echo esc_url( get_permalink( $listing_id ) ); ?>">
                <span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path data-dc-tpl="116" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm0 18.15c-1.53 0-3.03-.41-4.34-1.19l-.31-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.34c0-4.54 3.7-8.23 8.24-8.23 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.82c0 4.54-3.69 8.23-8.23 8.23zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z"></path></svg></span>
                <span class="text">שיתוף לוואטסאפ</span>
                <span class="arrow">›</span>
            </button>
            <button class="share-button facebook" data-url="<?php echo esc_url( get_permalink( $listing_id ) ); ?>">
              <span class="icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path data-dc-tpl="123" d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.9 3.78-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.9h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94z"></path></svg></span>
              <span class="text">שיתוף לפייסבוק</span>
              <span class="arrow">›</span>
            </button>
            <button class="share-button copy-link" data-url="<?php echo esc_url( get_permalink( $listing_id ) ); ?>">
              <span class="icon"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path data-dc-tpl="130" d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path data-dc-tpl="131" d="M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg></span>
              <span class="text">העתקת קישור</span>
              <span class="arrow">›</span>
            </button>
            <button class="share-button qr-code">
                <span class="icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="#fff"><path data-dc-tpl="138" d="M3 3h7v7H3V3zm2 2v3h3V5H5z"></path><path data-dc-tpl="139" d="M14 3h7v7h-7V3zm2 2v3h3V5h-3z"></path><path data-dc-tpl="140" d="M3 14h7v7H3v-7zm2 2v3h3v-3H5z"></path><rect data-dc-tpl="141" x="13" y="13" width="3" height="3"></rect><rect data-dc-tpl="142" x="18" y="13" width="3" height="3"></rect><rect data-dc-tpl="143" x="13" y="18" width="3" height="3"></rect><rect data-dc-tpl="144" x="18" y="18" width="3" height="3"></rect></svg></span>
                <span class="text">הורדת קוד QR</span>
              <span class="arrow">›</span>
            </button>
          </div>

          

          <div class="sep">
            <h3>רעיונות לשיתוף</h3>
            <div class="ideas">
              <div><span class="line">—</span><span>שתפו את העמוד בסטורי.</span></div>
              <div><span class="line">—</span><span>הוסיפו אותו לביו באינסטגרם.</span></div>
              <div><span class="line">—</span><span>שלחו אותו ללקוחות אחרי אירוע.</span></div>
              <div><span class="line">—</span><span>שתפו אותו בקבוצת הוואטסאפ של היישוב או השכונה.</span></div>
            </div>
          </div>
        </div>

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
   <a href="/my-account/pro/my-page/?listing_id=<?php echo esc_attr( $listing_id ); ?>" class="ct-dashboard-section__link">
    לכל אפשרויות העריכה &larr;
   </a>
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
   <a href="/my-account/pro/exposure-opportunities/?listing_id=<?php echo esc_attr( $listing_id ); ?>" class="ct-dashboard-exposure__link">
    לכל ההזדמנויות &larr;
   </a>
  </div>
  <p class="ct-dashboard-exposure__description">
   הכירו דרכים נוספות להגדיל את החשיפה של העגלה שלכם.
  </p>
  <div class="ct-dashboard-exposure__list">
   <a href="/my-account/pro/exposure-opportunities/?listing_id=<?php echo esc_attr( $listing_id ); ?>" class="ct-dashboard-opportunity ct-dashboard-opportunity--featured">
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
   </a>
   <a href="/my-account/pro/exposure-opportunities/?listing_id=<?php echo esc_attr( $listing_id ); ?>" class="ct-dashboard-opportunity">
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
   </a>
   <a href="/my-account/pro/exposure-opportunities/?listing_id=<?php echo esc_attr( $listing_id ); ?>" class="ct-dashboard-opportunity">
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
   </a>
  </div>
 </div>
</div>
 <?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>