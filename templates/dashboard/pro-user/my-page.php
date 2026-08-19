<?php
defined( 'ABSPATH' ) || exit;
$location_coffee = get_post_meta( $listing_id, '_location_coffee', true );
$job_phone = get_post_meta( $listing_id, '_job_phone', true );
$current_user_id = get_current_user_id();

$listings_query = new WP_Query( [
    'post_type'      => 'job_listing',
    'author'         => $current_user_id,
    'post_status'    => [ 'publish', 'pending', 'draft' ],
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
] );

$listing_ids    = array_map( 'intval', $listings_query->posts );
$listings_count = count( $listing_ids );
$has_multiple   = $listings_count > 1;
?>

<section class="ct-account-page my-page">
<?php if ( $listing_id ) : ?>
<div class="ct-public-page">
            <div class="ct-public-preview__header">
              <div>
                <div class="ct-public-preview__title-row">
                  <h1 class="ct-public-preview__title"><span class="sc-interp"><?php echo esc_html( get_the_title( $listing_id ) ); ?></span></h1>
                  <?php if ( $has_multiple ) : ?>
                  <span class="ct-public-preview__title-toggle">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </span>                    

                        <div class="ct-user-listings" hidden>

                            <?php foreach ( $listing_ids as $user_listing_id ) : ?>
                                <?php

                                  $listing_package_id = absint(
                                      get_post_meta(
                                          $user_listing_id,
                                          '_package_id',
                                          true
                                      )
                                  );

                                  $listing_plan_slug = in_array(
                                      $listing_package_id,
                                      [ 24 ],
                                      true
                                  ) ? 'pro' : 'free';

                                  $listing_manage_url = ct_get_account_plan_url(
                                      $listing_plan_slug,
                                      'home',
                                      $user_listing_id
                                  );
                                ?>
                                <div
                                    class="ct-user-listings__item <?php echo $user_listing_id === (int) $listing_id ? 'is-current' : ''; ?>"
                                >
                                  <a href="<?php echo esc_url( $listing_manage_url ); ?>">
                                      <?php echo esc_html( get_the_title( $user_listing_id ) ); ?>
                                  </a>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>
                </div>
                <p class="ct-public-preview__subtitle">כך העמוד שלכם נראה בקופיטרייל.</p>
              </div>
              <div class="ct-public-preview__actions">
                <button class="link_copy ct-public-preview__copy-link" type="button" data-url="<?php echo esc_url( get_permalink( $listing_id ) ); ?>">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2.5"></rect><path d="M5 15V6a2 2 0 0 1 2-2h9"></path></svg>
                  <span>העתקת קישור</span>
                </button>
                <a href="<?php echo esc_url( get_permalink( $listing_id ) ); ?>" target="_blank" class="ct-public-preview__view-link" title="כך העמוד נראה ללקוחות">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  צפו בעמוד
                </a>
              </div>
            </div>
 <div class="ct-public-update">
  <div class="ct-public-update__content">
   <h3 class="ct-public-update__title">
    יש משהו חדש שחשוב ללקוחות לדעת?
   </h3>
   <p class="ct-public-update__description">
    ספרו על שעות מיוחדות, סגירה זמנית או משהו חדש שמחכה להם.
   </p>
  </div>
  <button class="ct-public-update__button">
   <svg aria-hidden="true" class="ct-public-update__button-icon" fill="none" focusable="false" height="17" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="17">
    <path d="M3 11l16-5v13L3 14z">
    </path>
    <path d="M11.5 17.5a2.6 2.6 0 0 1-5 .5">
    </path>
   </svg>
   פרסום עדכון
  </button>
 </div>
 <div class="ct-public-preview-label">
  <span class="ct-public-preview-label__text">
   תצוגה מקדימה של העמוד הציבורי
  </span>
  <div class="ct-public-preview-label__line">
  </div>
 </div>
 <div class="ct-public-sections">
  <div class="ct-public-cover">
   <div class="ct-public-cover__placeholder">
    <svg aria-hidden="true" class="ct-public-cover__placeholder-icon" fill="none" focusable="false" height="32" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" viewbox="0 0 24 24" width="32">
     <rect height="18" rx="3" width="18" x="3" y="3">
     </rect>
     <circle cx="8.5" cy="8.5" r="1.6">
     </circle>
     <path d="M21 15l-5-5L5 21">
     </path>
    </svg>
    <span class="ct-public-cover__placeholder-label">
     תמונת שער
    </span>
   </div>
   <div class="ct-public-cover__title">
    עגלת קפה רוולה
   </div>
   <button class="ct-public-cover__edit-button">
    עריכת תמונת שער
   </button>
  </div>
  <div class="ct-public-card ct-public-about">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     על העגלה
    </h2>
    <a href="<?php echo esc_url( CT_Flow_Wizard_Edit::get_card_edit_url( 'about', $listing_id ) ); ?>" class="ct-public-card__edit-button">
     עריכת פרטי העמוד
    </a>
   </div>
   <div class="ct-public-about__details">
    <div class="ct-public-about__detail">
     <div class="ct-public-about__label">
      כתובת
     </div>
     <div class="ct-public-about__value">
      קיבוץ רבדים (בחניה של הבריכה)
     </div>
    </div>
    <div class="ct-public-about__detail">
     <div class="ct-public-about__label">
      טלפון
     </div>
     <div class="ct-public-about__value">
      050-000-0000
     </div>
    </div>
   </div>
   <div class="ct-public-about__actions">
    <span class="ct-public-about__action">
     <svg aria-hidden="true" class="ct-public-about__action-icon" fill="none" focusable="false" height="14" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="14">
      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z">
      </path>
     </svg>
     התקשרו
    </span>
    <span class="ct-public-about__action">
     <svg aria-hidden="true" class="ct-public-about__action-icon" fill="none" focusable="false" height="14" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="14">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z">
      </path>
     </svg>
     שלחו הודעה
    </span>
    <span class="ct-public-about__action">
     <svg aria-hidden="true" class="ct-public-about__action-icon" fill="none" focusable="false" height="14" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="14">
      <path d="M3 11l19-9-9 19-2-8-8-2z">
      </path>
     </svg>
     נווטו לעגלה
    </span>
   </div>
  </div>
  <div class="ct-public-card ct-public-story">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     הסיפור שלכם
    </h2>
    <button class="ct-public-card__edit-button">
     הוספת סיפור
    </button>
   </div>
   <div class="ct-public-empty-state">
    <p class="ct-public-empty-state__title">
     עדיין לא הוספתם סיפור.
    </p>
    <p class="ct-public-empty-state__description">
     ספרו לאנשים קצת על עצמכם ועל הדרך שלכם.
    </p>
   </div>
  </div>
  <div class="ct-public-card ct-public-gallery">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     גלריית תמונות
    </h2>
    <button class="ct-public-card__edit-button">
     ניהול תמונות
    </button>
   </div>
   <div class="ct-public-gallery__grid">
    <div class="ct-public-gallery__item">
    </div>
    <div class="ct-public-gallery__item">
    </div>
    <div class="ct-public-gallery__item">
    </div>
    <div class="ct-public-gallery__item ct-public-gallery__item--more">
     <span class="ct-public-gallery__more-count">
      12+
     </span>
    </div>
   </div>
  </div>
  <div class="ct-public-card ct-public-social">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     אנחנו גם פה
    </h2>
    <button class="ct-public-card__edit-button">
     עריכת קישורים
    </button>
   </div>
   <div class="ct-public-social__links">
    <span class="ct-public-social__link">
     <span class="ct-public-social__icon">
     </span>
     Instagram
    </span>
    <span class="ct-public-social__link">
     <span class="ct-public-social__icon">
     </span>
     Facebook
    </span>
    <span class="ct-public-social__link">
     <span class="ct-public-social__icon">
     </span>
     TikTok
    </span>
   </div>
  </div>
  <div class="ct-public-card ct-public-map">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     אנחנו על המפה
    </h2>
    <button class="ct-public-card__edit-button">
     עריכת מיקום
    </button>
   </div>
   <div class="ct-public-map__canvas">
    <span class="ct-public-map__marker">
    </span>
   </div>
   <p class="ct-public-map__address">
    קיבוץ רבדים (בחניה של הבריכה) &middot; קרוב לגדרה
   </p>
  </div>
  <div class="ct-public-card ct-public-menu">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     תפריט
    </h2>
    <button class="ct-public-card__edit-button">
     עריכת תפריט
    </button>
   </div>
   <div class="ct-public-tabs">
    <span class="ct-public-tabs__item ct-public-tabs__item--active">
     מנות מיוחדות
    </span>
    <span class="ct-public-tabs__item">
     פופולריות
    </span>
    <span class="ct-public-tabs__item">
     לטבעונים
    </span>
    <span class="ct-public-tabs__item">
     לנמנעים מגלוטן
    </span>
    <span class="ct-public-tabs__item">
     כשר
    </span>
   </div>
   <div class="ct-public-menu__items">
    <div class="ct-public-menu__item">
     <p class="ct-public-menu__item-title">
      כריך סלמון עם איולי לימון
     </p>
     <p class="ct-public-menu__item-description">
      מוגש בלחם מחמצת טרי
     </p>
    </div>
    <div class="ct-public-menu__item">
     <p class="ct-public-menu__item-title">
      קרואסון שקדים חם
     </p>
     <p class="ct-public-menu__item-description">
      נאפה במקום ומוגש חם
     </p>
    </div>
   </div>
   <button class="ct-public-menu__view-full">
    <svg aria-hidden="true" class="ct-public-menu__view-full-icon" fill="none" focusable="false" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24" width="16">
     <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z">
     </path>
     <path d="M14 3v6h6">
     </path>
    </svg>
    צפייה בתפריט המלא
   </button>
  </div>
  <div class="ct-public-card ct-public-hours">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     שעות פעילות
    </h2>
    <button class="ct-public-card__edit-button">
     עריכת שעות
    </button>
   </div>
   <div class="ct-public-hours__status">
    <span class="ct-public-hours__status-dot">
    </span>
    <span class="ct-public-hours__status-text">
     סגור כעת &middot; נפתח מחר ב-08:00
    </span>
   </div>
   <div class="ct-public-hours__list">
    <div class="ct-public-hours__row">
     <span class="ct-public-hours__day">
      ראשון&ndash;חמישי
     </span>
     <span class="ct-public-hours__time">
      08:00&ndash;14:00
     </span>
    </div>
    <div class="ct-public-hours__separator">
    </div>
    <div class="ct-public-hours__row">
     <span class="ct-public-hours__day">
      שישי
     </span>
     <span class="ct-public-hours__time">
      סגור
     </span>
    </div>
    <div class="ct-public-hours__separator">
    </div>
    <div class="ct-public-hours__row">
     <span class="ct-public-hours__day">
      שבת
     </span>
     <span class="ct-public-hours__time">
      09:00&ndash;13:00
     </span>
    </div>
   </div>
  </div>
  <div class="ct-public-card ct-public-features">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     שירותים ומאפיינים
    </h2>
    <button class="ct-public-card__edit-button">
     עריכת מאפיינים
    </button>
   </div>
   <div class="ct-public-features__grid">
    <div class="ct-public-features__item">
     <span class="ct-public-features__icon">
     </span>
     <span class="ct-public-features__label">
      Wi-Fi
     </span>
    </div>
    <div class="ct-public-features__item">
     <span class="ct-public-features__icon">
     </span>
     <span class="ct-public-features__label">
      חניה
     </span>
    </div>
    <div class="ct-public-features__item">
     <span class="ct-public-features__icon">
     </span>
     <span class="ct-public-features__label">
      חניה לנכים
     </span>
    </div>
    <div class="ct-public-features__item">
     <span class="ct-public-features__icon">
     </span>
     <span class="ct-public-features__label">
      שירותים
     </span>
    </div>
    <div class="ct-public-features__item">
     <span class="ct-public-features__icon">
     </span>
     <span class="ct-public-features__label">
      נגיש
     </span>
    </div>
   </div>
  </div>
  <div class="ct-public-card ct-public-info">
   <div class="ct-public-card__header">
    <h2 class="ct-public-card__title">
     מה כדאי לדעת
    </h2>
    <button class="ct-public-card__edit-button">
     עריכת המידע
    </button>
   </div>
   <div class="ct-public-info__tabs">
    <span class="ct-public-info__tab ct-public-info__tab--active">
     בשביל הילדים
    </span>
    <span class="ct-public-info__tab">
     בשביל הטיולים
    </span>
    <span class="ct-public-info__tab">
     בשביל האופניים
    </span>
   </div>
   <div class="ct-public-info__list">
    <div class="ct-public-info__item">
     <span class="ct-public-info__bullet">
     </span>
     <span class="ct-public-info__text">
      אצלנו ילדים אוהבים לאכול: טוסט ילדים, בורקס גבינה, כדורי שוקולד ושייקים.
     </span>
    </div>
    <div class="ct-public-info__item">
     <span class="ct-public-info__bullet">
     </span>
     <span class="ct-public-info__text">
      יש פינת משחקים לגיל הרך.
     </span>
    </div>
    <div class="ct-public-info__item">
     <span class="ct-public-info__bullet">
     </span>
     <span class="ct-public-info__text">
      יש מקום נוח לפרוש שמיכת תינוק.
     </span>
    </div>
   </div>
  </div>
 </div>
</div>
 <?php else : ?>
    <p>לא נמצאה עגלת קפה עבור המשתמש.</p>
<?php endif; ?>
</section>