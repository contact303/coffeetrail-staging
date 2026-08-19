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
      <div class="ct-public-preview">
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

            <div class="ct-public-preview__label-row">
              <span class="ct-public-preview__label">תצוגה מקדימה של העמוד הציבורי</span>
              <div class="ct-public-preview__divider"></div>
            </div>

            <div class="ct-public-preview__sections">

              
              <div class="ct-preview-cover">
                
                <div class="ct-pro-overlay ct-pro-overlay--cover">
                  <span class="ct-pro-overlay__icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
                  <p class="ct-pro-overlay__title">תמונת שער — זמין בפרו</p>
                  <p class="ct-pro-overlay__description">הוסיפו תמונת שער שתופיע בראש העמוד שלכם.</p>
                  <button class="ct-pro-overlay__button">פתחו עם פרו</button>
                </div>
                
                <div class="ct-preview-cover__placeholder">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"></rect><circle cx="8.5" cy="8.5" r="1.6"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                  <span class="ct-preview-cover__placeholder-label">תמונת שער</span>
                </div>
                <div class="ct-preview-cover__listing-title"><span class="sc-interp"><?php echo esc_html( get_the_title( $listing_id ) ); ?></span></div>
                <button class="ct-preview-cover__edit-button">עריכת תמונת שער</button>
              </div>

              
              <div class="ct-preview-card ct-preview-about">
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">על העגלה</h2>
                  <a href="<?php echo esc_url( CT_Flow_Wizard_Edit::get_card_edit_url( 'about', $listing_id ) ); ?>" class="ct-preview-card__edit-button">עריכת פרטי העמוד</a>
                </div>
                <div class="ct-preview-about__details">
                  <?php if ( ! empty( $location_coffee ) ) : ?>
                    <div><div class="ct-preview-about__detail-label">כתובת</div><div class="ct-preview-about__detail-value"><?php echo esc_html( $location_coffee ); ?></div></div>
                  <?php endif; ?>
                  <?php if ( ! empty( $job_phone ) ) : ?>
                    <div><div class="ct-preview-about__detail-label">טלפון</div><div class="ct-preview-about__detail-value"><?php echo esc_html( $job_phone ); ?></div></div>
                  <?php endif; ?>                
                </div>
                <div class="ct-preview-about__actions">
                  <span class="ct-preview-about__action"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>התקשרו</span>
                  <span class="ct-preview-about__action"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>שלחו הודעה</span>
                  <span class="ct-preview-about__action"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"></path></svg>נווטו לעגלה</span>
                </div>
              </div>

              
              <div class="ct-preview-card ct-preview-card--locked ct-preview-story">
                
                <div class="ct-pro-overlay">
                  <span class="ct-pro-overlay__icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
                  <p class="ct-pro-overlay__title">הסיפור שלכם — זמין בפרו</p>
                  <p class="ct-pro-overlay__description">ספרו ללקוחות על מי שמאחורי העגלה ועל הדרך שלכם.</p>
                  <button class="ct-pro-overlay__button">פתחו עם פרו</button>
                </div>
                
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">הסיפור שלכם</h2>
                  <button class="ct-preview-card__edit-button"><span class="sc-interp">הוספת סיפור</span></button>
                </div>
                
                
                  <div class="ct-preview-empty-state">
                    <p class="ct-preview-empty-state__title">עדיין לא הוספתם סיפור.</p>
                    <p class="ct-preview-empty-state__text">ספרו לאנשים קצת על עצמכם ועל הדרך שלכם.</p>
                  </div>
                
              </div>

              
              <div class="ct-preview-card ct-preview-card--locked ct-preview-gallery">
                
                <div class="ct-pro-overlay">
                  <span class="ct-pro-overlay__icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
                  <p class="ct-pro-overlay__title">גלריית תמונות — זמין בפרו</p>
                  <p class="ct-pro-overlay__description">הוסיפו תמונות מהעגלה כדי שלקוחות יראו מה מחכה להם.</p>
                  <button class="ct-pro-overlay__button">פתחו עם פרו</button>
                </div>
                
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">גלריית תמונות</h2>
                  <button class="ct-preview-card__edit-button">ניהול תמונות</button>
                </div>
                <div class="ct-preview-gallery__grid">
                  <div class="ct-preview-gallery__item"></div>
                  <div class="ct-preview-gallery__item"></div>
                  <div class="ct-preview-gallery__item"></div>
                  <div class="ct-preview-gallery__item ct-preview-gallery__item--more">
                    <span class="ct-preview-gallery__more-count">12+</span>
                  </div>
                </div>
              </div>

              
              <div class="ct-preview-card ct-preview-card--locked ct-preview-social">
                
                <div class="ct-pro-overlay">
                  <span class="ct-pro-overlay__icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
                  <p class="ct-pro-overlay__title">קישורים לרשתות — זמין בפרו</p>
                  <p class="ct-pro-overlay__description">חברו את הפרופילים שלכם ברשתות כדי שלקוחות ימצאו אתכם.</p>
                  <button class="ct-pro-overlay__button">פתחו עם פרו</button>
                </div>
                
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">אנחנו גם פה</h2>
                  <button class="ct-preview-card__edit-button">עריכת קישורים</button>
                </div>
                <div class="ct-preview-social__links">
                  <span class="ct-preview-social__link"><span class="ct-preview-social__icon"></span>Instagram</span>
                  <span class="ct-preview-social__link"><span class="ct-preview-social__icon"></span>Facebook</span>
                  <span class="ct-preview-social__link"><span class="ct-preview-social__icon"></span>TikTok</span>
                </div>
              </div>

              <?php if ( ! empty( $location_coffee ) ) : ?>
              <div class="ct-preview-card ct-preview-map">
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">אנחנו על המפה</h2>
                  <button class="ct-preview-card__edit-button">עריכת מיקום</button>
                </div>                
                    <div class="ct-listing-map">
                        <iframe
                            src="https://www.google.com/maps?q=<?php echo rawurlencode( $location_coffee ); ?>&output=embed"
                            width="100%"
                            height="350"
                            style="border:0;"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="<?php echo esc_attr( 'מפה עבור ' . $location_coffee ); ?>"
                        ></iframe>
                    </div>                
                <p class="ct-preview-map__address"><?php echo esc_html( $location_coffee ); ?></p>
              </div>
              <?php endif; ?>

              
              <div class="ct-preview-card ct-preview-card--locked ct-preview-menu">
                
                <div class="ct-pro-overlay">
                  <span class="ct-pro-overlay__icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
                  <p class="ct-pro-overlay__title">תפריט מלא — זמין בפרו</p>
                  <p class="ct-pro-overlay__description">הציגו תמונה או קובץ PDF של התפריט המלא, מנות מיוחדות ועוד.</p>
                  <button class="ct-pro-overlay__button">פתחו עם פרו</button>
                </div>
                
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">תפריט</h2>
                  <button class="ct-preview-card__edit-button">עריכת תפריט</button>
                </div>
                <div class="ct-preview-tabs">
                  <span class="ct-preview-tabs__item ct-preview-tabs__item--active">מנות מיוחדות</span>
                  <span class="ct-preview-tabs__item">פופולריות</span>
                  <span class="ct-preview-tabs__item">לטבעונים</span>
                  <span class="ct-preview-tabs__item">לנמנעים מגלוטן</span>
                  <span class="ct-preview-tabs__item">
                    כשר
                  </span>
                </div>
                <div>
                  <div class="ct-preview-menu__item">
                    <p class="ct-preview-menu__item-title">כריך סלמון עם איולי לימון</p>
                    <p class="ct-preview-menu__item-description">מוגש בלחם מחמצת טרי</p>
                  </div>
                  <div class="ct-preview-menu__item">
                    <p class="ct-preview-menu__item-title">קרואסון שקדים חם</p>
                    <p class="ct-preview-menu__item-description">נאפה במקום ומוגש חם</p>
                  </div>
                </div>
                <button class="ct-preview-menu__view-full">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><path d="M14 3v6h6"></path></svg>
                  צפייה בתפריט המלא
                </button>
              </div>

              
              <div class="ct-preview-card ct-preview-card--locked ct-preview-hours">
                
                <div class="ct-pro-overlay">
                  <span class="ct-pro-overlay__icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
                  <p class="ct-pro-overlay__title">שעות פעילות — זמין בפרו</p>
                  <p class="ct-pro-overlay__description">הראו ללקוחות מתי אתם פתוחים — פחות אנשים יגיעו לדלת סגורה.</p>
                  <button class="ct-pro-overlay__button">פתחו עם פרו</button>
                </div>
                
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">שעות פעילות</h2>
                  <button class="ct-preview-card__edit-button">עריכת שעות</button>
                </div>
                <div class="ct-preview-hours__status">
                  <span class="ct-preview-hours__status-dot"></span>
                  <span class="ct-preview-hours__status-text">סגור כעת · נפתח מחר ב-08:00</span>
                </div>
                <div class="ct-preview-hours__list">
                  <div class="ct-preview-hours__row"><span class="ct-preview-hours__day">ראשון–חמישי</span><span class="ct-preview-hours__time">08:00–14:00</span></div>
                  <div class="ct-preview-hours__separator"></div>
                  <div class="ct-preview-hours__row"><span class="ct-preview-hours__day">שישי</span><span class="ct-preview-hours__time">סגור</span></div>
                  <div class="ct-preview-hours__separator"></div>
                  <div class="ct-preview-hours__row"><span class="ct-preview-hours__day">שבת</span><span class="ct-preview-hours__time">09:00–13:00</span></div>
                </div>
              </div>

              
              <div class="ct-preview-card ct-preview-card--locked ct-preview-features">
                
                <div class="ct-pro-overlay">
                  <span class="ct-pro-overlay__icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
                  <p class="ct-pro-overlay__title">שירותים ומאפיינים — זמין בפרו</p>
                  <p class="ct-pro-overlay__description">סמנו מה מחכה ללקוחות — Wi-Fi, חניה, נגישות ועוד.</p>
                  <button class="ct-pro-overlay__button">פתחו עם פרו</button>
                </div>
                
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">שירותים ומאפיינים</h2>
                  <button class="ct-preview-card__edit-button">עריכת מאפיינים</button>
                </div>
                <div class="ct-preview-features__grid">
                  <div class="ct-preview-features__item"><span class="ct-preview-features__icon"></span><span class="ct-preview-features__label">Wi-Fi</span></div>
                  <div class="ct-preview-features__item"><span class="ct-preview-features__icon"></span><span class="ct-preview-features__label">חניה</span></div>
                  <div class="ct-preview-features__item"><span class="ct-preview-features__icon"></span><span class="ct-preview-features__label">חניה לנכים</span></div>
                  <div class="ct-preview-features__item"><span class="ct-preview-features__icon"></span><span class="ct-preview-features__label">שירותים</span></div>
                  <div class="ct-preview-features__item"><span class="ct-preview-features__icon"></span><span class="ct-preview-features__label">נגיש</span></div>
                </div>
              </div>

              
              <div class="ct-preview-card ct-preview-card--locked ct-preview-info">
                
                <div class="ct-pro-overlay">
                  <span class="ct-pro-overlay__icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
                  <p class="ct-pro-overlay__title">מה כדאי לדעת — זמין בפרו</p>
                  <p class="ct-pro-overlay__description">ספרו מה מחכה למשפחות, למטיילים ולרוכבי האופניים שמגיעים אליכם.</p>
                  <button class="ct-pro-overlay__button">פתחו עם פרו</button>
                </div>
                
                <div class="ct-preview-card__header">
                  <h2 class="ct-preview-card__title">מה כדאי לדעת</h2>
                  <button class="ct-preview-card__edit-button">עריכת המידע</button>
                </div>
                <div class="ct-preview-info__tabs">
                  <span class="ct-preview-info__tab ct-preview-info__tab--active">בשביל הילדים</span>
                  <span class="ct-preview-info__tab">בשביל הטיולים</span>
                  <span class="ct-preview-info__tab">בשביל האופניים</span>
                </div>
                <div class="ct-preview-info__list">
                  <div class="ct-preview-info__item"><span class="ct-preview-info__bullet"></span><span class="ct-preview-info__text">אצלנו ילדים אוהבים לאכול: טוסט ילדים, בורקס גבינה, כדורי שוקולד ושייקים.</span></div>
                  <div class="ct-preview-info__item"><span class="ct-preview-info__bullet"></span><span class="ct-preview-info__text">יש פינת משחקים לגיל הרך.</span></div>
                  <div class="ct-preview-info__item"><span class="ct-preview-info__bullet"></span><span class="ct-preview-info__text">יש מקום נוח לפרוש שמיכת תינוק.</span></div>
                </div>
              </div>

            </div>
      </div>
    <?php endif; ?>
</section>
