<?php
defined( 'ABSPATH' ) || exit;
?>

<section class="ct-account-page my-page">
    <?php if ( $listing_id ) : ?>
<div data-dc-tpl="483" style="max-width: 1000px; margin: 0px auto; padding: 32px 24px 90px;">
      <div data-dc-tpl="484" style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 18px; margin-bottom: 28px;">
        <div data-dc-tpl="485">
          <div data-dc-tpl="486" style="display: inline-flex; align-items: center; gap: 9px;">
            <h1 data-dc-tpl="487" style="font-size: clamp(22px, 6vw, 28px); font-weight: 800; letter-spacing: -0.4px;"><span class="sc-interp"><?php echo esc_html( get_the_title( $listing_id ) ); ?></span></h1>
            <span data-dc-tpl="488" style="display: inline-flex; width: 26px; height: 26px; border-radius: 8px; background: rgb(255, 253, 249); border: 1px solid rgb(228, 220, 205); align-items: center; justify-content: center; color: rgb(138, 129, 117);">
              <svg data-dc-tpl="489" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline data-dc-tpl="490" points="6 9 12 15 18 9"></polyline></svg>
            </span>
          </div>
          <p data-dc-tpl="491" style="margin: 9px 0px 0px; font-size: 16px; color: rgb(138, 129, 117);">כך העמוד שלכם נראה בקופיטרייל.</p>
        </div>
        <div data-dc-tpl="492" style="display: flex; align-items: center; gap: 12px;">
          <button class="link_copy" type="button" data-url="<?php echo esc_url( get_permalink( $listing_id ) ); ?>" data-dc-tpl="493" style="display: inline-flex; align-items: center; gap: 8px; background: none; border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; color: rgb(31, 146, 84); font-weight: 600; font-size: 15px;">
            <svg data-dc-tpl="494" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="495" x="9" y="9" width="11" height="11" rx="2.5"></rect><path data-dc-tpl="496" d="M5 15V6a2 2 0 0 1 2-2h9"></path></svg>
            <span>העתקת קישור</span>
          </button>
          <a href="<?php echo esc_url( get_permalink( $listing_id ) ); ?>" target="_blank" data-dc-tpl="497" title="כך העמוד נראה ללקוחות" style="display: inline-flex; align-items: center; gap: 9px; background: rgb(31, 146, 84); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 15px; padding: 12px 22px; border-radius: 999px; box-shadow: rgba(31, 146, 84, 0.25) 0px 2px 8px;">
            <svg data-dc-tpl="498" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path data-dc-tpl="499" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle data-dc-tpl="500" cx="12" cy="12" r="3"></circle></svg>
            צפו בעמוד
          </a>
        </div>
      </div>

      <div data-dc-tpl="510" style="display: flex; align-items: center; gap: 14px; margin-bottom: 18px;">
        <span data-dc-tpl="511" style="font-size: 13px; font-weight: 700; letter-spacing: 0.5px; color: rgb(138, 129, 117);">תצוגה מקדימה של העמוד הציבורי</span>
        <div data-dc-tpl="512" style="flex: 1 1 0%; height: 1px; background: rgb(228, 220, 205);"></div>
      </div>

      <div data-dc-tpl="513" style="display: flex; flex-direction: column; gap: 18px;">

        
        <div data-dc-tpl="514" style="position: relative; border-radius: 18px; overflow: hidden; border: 1px solid rgb(234, 227, 214); height: 260px; background-image: repeating-linear-gradient(45deg, rgb(237, 230, 217) 0px, rgb(237, 230, 217) 14px, rgb(229, 220, 203) 14px, rgb(229, 220, 203) 28px);">
          
          <div data-dc-tpl="516" style="position: absolute; inset: 0px; z-index: 5; background: rgba(251, 248, 241, 0.82); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; text-align: center; padding: 20px;">
            <span data-dc-tpl="517" style="width: 44px; height: 44px; border-radius: 12px; background: rgb(33, 29, 26); display: flex; align-items: center; justify-content: center;"><svg data-dc-tpl="518" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="519" x="5" y="11" width="14" height="10" rx="2"></rect><path data-dc-tpl="520" d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
            <p data-dc-tpl="521" style="margin: 0px; font-size: 16px; font-weight: 800; color: rgb(43, 38, 34);">תמונת שער — זמין בפרו</p>
            <p data-dc-tpl="522" style="margin: 0px; font-size: 14px; color: rgb(110, 102, 88); max-width: 330px; line-height: 1.5;">הוסיפו תמונת שער שתופיע בראש העמוד שלכם.</p>
            <button data-dc-tpl="523" style="margin-top: 4px; background: rgb(33, 29, 26); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 999px;">פתחו עם פרו</button>
          </div>
          
          <div data-dc-tpl="524" style="position: absolute; inset: 0px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: rgb(138, 129, 117);">
            <svg data-dc-tpl="525" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="526" x="3" y="3" width="18" height="18" rx="3"></rect><circle data-dc-tpl="527" cx="8.5" cy="8.5" r="1.6"></circle><path data-dc-tpl="528" d="M21 15l-5-5L5 21"></path></svg>
            <span data-dc-tpl="529" style="font-size: 13px;">תמונת שער</span>
          </div>
          <div data-dc-tpl="530" style="position: absolute; bottom: 16px; right: 20px; font-size: 21px; font-weight: 800; color: rgb(58, 51, 44); background: rgba(255, 253, 249, 0.85); padding: 6px 16px; border-radius: 10px;"><span class="sc-interp"><?php echo esc_html( get_the_title( $listing_id ) ); ?></span></div>
          <button data-dc-tpl="531" style="position: absolute; bottom: 16px; left: 20px; display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 253, 249, 0.95); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">עריכת תמונת שער</button>
        </div>

        
        <div data-dc-tpl="532" style="background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;">
          <div data-dc-tpl="533" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
            <h2 data-dc-tpl="534" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">על העגלה</h2>
            <button data-dc-tpl="535" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">עריכת פרטי העמוד</button>
          </div>
          <div data-dc-tpl="536" style="display: flex; flex-wrap: wrap; gap: 40px;">
            <div data-dc-tpl="537"><div data-dc-tpl="538" style="font-size: 13px; color: rgb(138, 129, 117); margin-bottom: 3px;">כתובת</div><div data-dc-tpl="539" style="font-size: 15px; color: rgb(58, 51, 44);">קיבוץ רבדים (בחניה של הבריכה)</div></div>
            <div data-dc-tpl="540"><div data-dc-tpl="541" style="font-size: 13px; color: rgb(138, 129, 117); margin-bottom: 3px;">טלפון</div><div data-dc-tpl="542" style="font-size: 15px; color: rgb(58, 51, 44);">050-000-0000</div></div>
          </div>
          <div data-dc-tpl="543" style="border-top: 1px solid rgb(240, 235, 224); margin-top: 18px; padding-top: 18px; display: flex; gap: 9px; flex-wrap: wrap;">
            <span data-dc-tpl="544" style="display: inline-flex; align-items: center; gap: 7px; background: rgb(246, 241, 232); border: 1px solid rgb(236, 229, 216); border-radius: 999px; padding: 8px 16px; font-size: 14px; color: rgb(110, 102, 88);"><svg data-dc-tpl="545" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path data-dc-tpl="546" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>התקשרו</span>
            <span data-dc-tpl="547" style="display: inline-flex; align-items: center; gap: 7px; background: rgb(246, 241, 232); border: 1px solid rgb(236, 229, 216); border-radius: 999px; padding: 8px 16px; font-size: 14px; color: rgb(110, 102, 88);"><svg data-dc-tpl="548" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path data-dc-tpl="549" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>שלחו הודעה</span>
            <span data-dc-tpl="550" style="display: inline-flex; align-items: center; gap: 7px; background: rgb(246, 241, 232); border: 1px solid rgb(236, 229, 216); border-radius: 999px; padding: 8px 16px; font-size: 14px; color: rgb(110, 102, 88);"><svg data-dc-tpl="551" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path data-dc-tpl="552" d="M3 11l19-9-9 19-2-8-8-2z"></path></svg>נווטו לעגלה</span>
          </div>
        </div>

        
        <div data-dc-tpl="553" style="position: relative; overflow: hidden; background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;">
          
          <div data-dc-tpl="555" style="position: absolute; inset: 0px; z-index: 5; background: rgba(251, 248, 241, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; text-align: center; padding: 20px;">
            <span data-dc-tpl="556" style="width: 44px; height: 44px; border-radius: 12px; background: rgb(33, 29, 26); display: flex; align-items: center; justify-content: center;"><svg data-dc-tpl="557" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="558" x="5" y="11" width="14" height="10" rx="2"></rect><path data-dc-tpl="559" d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
            <p data-dc-tpl="560" style="margin: 0px; font-size: 16px; font-weight: 800; color: rgb(43, 38, 34);">הסיפור שלכם — זמין בפרו</p>
            <p data-dc-tpl="561" style="margin: 0px; font-size: 14px; color: rgb(110, 102, 88); max-width: 330px; line-height: 1.5;">ספרו ללקוחות על מי שמאחורי העגלה ועל הדרך שלכם.</p>
            <button data-dc-tpl="562" style="margin-top: 4px; background: rgb(33, 29, 26); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 999px;">פתחו עם פרו</button>
          </div>
          
          <div data-dc-tpl="563" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h2 data-dc-tpl="564" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">הסיפור שלכם</h2>
            <button data-dc-tpl="565" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;"><span class="sc-interp">הוספת סיפור</span></button>
          </div>
          
          
            <div data-dc-tpl="570" style="border: 1.5px dashed rgb(216, 208, 194); border-radius: 14px; padding: 30px 24px; text-align: center; background: rgb(251, 248, 241);">
              <p data-dc-tpl="571" style="margin: 0px 0px 4px; font-size: 16px; font-weight: 600; color: rgb(110, 102, 88);">עדיין לא הוספתם סיפור.</p>
              <p data-dc-tpl="572" style="margin: 0px; font-size: 15px; color: rgb(138, 129, 117);">ספרו לאנשים קצת על עצמכם ועל הדרך שלכם.</p>
            </div>
          
        </div>

        
        <div data-dc-tpl="573" style="position: relative; overflow: hidden; background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;">
          
          <div data-dc-tpl="575" style="position: absolute; inset: 0px; z-index: 5; background: rgba(251, 248, 241, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; text-align: center; padding: 20px;">
            <span data-dc-tpl="576" style="width: 44px; height: 44px; border-radius: 12px; background: rgb(33, 29, 26); display: flex; align-items: center; justify-content: center;"><svg data-dc-tpl="577" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="578" x="5" y="11" width="14" height="10" rx="2"></rect><path data-dc-tpl="579" d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
            <p data-dc-tpl="580" style="margin: 0px; font-size: 16px; font-weight: 800; color: rgb(43, 38, 34);">גלריית תמונות — זמין בפרו</p>
            <p data-dc-tpl="581" style="margin: 0px; font-size: 14px; color: rgb(110, 102, 88); max-width: 330px; line-height: 1.5;">הוסיפו תמונות מהעגלה כדי שלקוחות יראו מה מחכה להם.</p>
            <button data-dc-tpl="582" style="margin-top: 4px; background: rgb(33, 29, 26); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 999px;">פתחו עם פרו</button>
          </div>
          
          <div data-dc-tpl="583" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <h2 data-dc-tpl="584" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">גלריית תמונות</h2>
            <button data-dc-tpl="585" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">ניהול תמונות</button>
          </div>
          <div data-dc-tpl="586" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
            <div data-dc-tpl="587" style="aspect-ratio: 1 / 1; border-radius: 12px; background-image: repeating-linear-gradient(45deg, rgb(237, 230, 217) 0px, rgb(237, 230, 217) 12px, rgb(229, 220, 203) 12px, rgb(229, 220, 203) 24px);"></div>
            <div data-dc-tpl="588" style="aspect-ratio: 1 / 1; border-radius: 12px; background-image: repeating-linear-gradient(45deg, rgb(237, 230, 217) 0px, rgb(237, 230, 217) 12px, rgb(229, 220, 203) 12px, rgb(229, 220, 203) 24px);"></div>
            <div data-dc-tpl="589" style="aspect-ratio: 1 / 1; border-radius: 12px; background-image: repeating-linear-gradient(45deg, rgb(237, 230, 217) 0px, rgb(237, 230, 217) 12px, rgb(229, 220, 203) 12px, rgb(229, 220, 203) 24px);"></div>
            <div data-dc-tpl="590" style="position: relative; aspect-ratio: 1 / 1; border-radius: 12px; background-image: repeating-linear-gradient(45deg, rgb(237, 230, 217) 0px, rgb(237, 230, 217) 12px, rgb(229, 220, 203) 12px, rgb(229, 220, 203) 24px); display: flex; align-items: center; justify-content: center;">
              <span data-dc-tpl="591" style="font-size: clamp(18px, 4.6vw, 20px); font-weight: 700; color: rgb(255, 255, 255); background: rgba(58, 51, 44, 0.5); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; border-radius: 12px;">12+</span>
            </div>
          </div>
        </div>

        
        <div data-dc-tpl="592" style="position: relative; overflow: hidden; background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;min-height: 250px;">
          
          <div data-dc-tpl="594" style="position: absolute; inset: 0px; z-index: 5; background: rgba(251, 248, 241, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; text-align: center; padding: 20px;">
            <span data-dc-tpl="595" style="width: 44px; height: 44px; border-radius: 12px; background: rgb(33, 29, 26); display: flex; align-items: center; justify-content: center;"><svg data-dc-tpl="596" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="597" x="5" y="11" width="14" height="10" rx="2"></rect><path data-dc-tpl="598" d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
            <p data-dc-tpl="599" style="margin: 0px; font-size: 16px; font-weight: 800; color: rgb(43, 38, 34);">קישורים לרשתות — זמין בפרו</p>
            <p data-dc-tpl="600" style="margin: 0px; font-size: 14px; color: rgb(110, 102, 88); max-width: 330px; line-height: 1.5;">חברו את הפרופילים שלכם ברשתות כדי שלקוחות ימצאו אתכם.</p>
            <button data-dc-tpl="601" style="margin-top: 4px; background: rgb(33, 29, 26); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 999px;">פתחו עם פרו</button>
          </div>
          
          <div data-dc-tpl="602" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <h2 data-dc-tpl="603" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">אנחנו גם פה</h2>
            <button data-dc-tpl="604" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">עריכת קישורים</button>
          </div>
          <div data-dc-tpl="605" style="display: flex; gap: 12px; flex-wrap: wrap;">
            <span data-dc-tpl="606" style="display: inline-flex; align-items: center; gap: 10px; background: rgb(246, 241, 232); border: 1px solid rgb(236, 229, 216); border-radius: 12px; padding: 11px 18px; font-size: 15px;"><span data-dc-tpl="607" style="width: 22px; height: 22px; border-radius: 7px; background: rgb(224, 216, 200);"></span>Instagram</span>
            <span data-dc-tpl="608" style="display: inline-flex; align-items: center; gap: 10px; background: rgb(246, 241, 232); border: 1px solid rgb(236, 229, 216); border-radius: 12px; padding: 11px 18px; font-size: 15px;"><span data-dc-tpl="609" style="width: 22px; height: 22px; border-radius: 7px; background: rgb(224, 216, 200);"></span>Facebook</span>
            <span data-dc-tpl="610" style="display: inline-flex; align-items: center; gap: 10px; background: rgb(246, 241, 232); border: 1px solid rgb(236, 229, 216); border-radius: 12px; padding: 11px 18px; font-size: 15px;"><span data-dc-tpl="611" style="width: 22px; height: 22px; border-radius: 7px; background: rgb(224, 216, 200);"></span>TikTok</span>
          </div>
        </div>

        
        <div data-dc-tpl="612" style="background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;">
          <div data-dc-tpl="613" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <h2 data-dc-tpl="614" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">אנחנו על המפה</h2>
            <button data-dc-tpl="615" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">עריכת מיקום</button>
          </div>
          <div data-dc-tpl="616" style="position: relative; height: 200px; border-radius: 14px; overflow: hidden; border: 1px solid rgb(228, 220, 205); background-image: repeating-linear-gradient(45deg, rgb(237, 230, 217) 0px, rgb(237, 230, 217) 14px, rgb(229, 220, 203) 14px, rgb(229, 220, 203) 28px);">
            <span data-dc-tpl="617" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; border-radius: 50%; background: rgb(192, 73, 47); border: 4px solid rgba(255, 253, 249, 0.9);"></span>
          </div>
          <p data-dc-tpl="618" style="margin: 14px 0px 0px; font-size: 15px; color: rgb(94, 87, 75);">קיבוץ רבדים (בחניה של הבריכה) · קרוב לגדרה</p>
        </div>

        
        <div data-dc-tpl="619" style="position: relative; overflow: hidden; background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;">
          
          <div data-dc-tpl="621" style="position: absolute; inset: 0px; z-index: 5; background: rgba(251, 248, 241, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; text-align: center; padding: 20px;">
            <span data-dc-tpl="622" style="width: 44px; height: 44px; border-radius: 12px; background: rgb(33, 29, 26); display: flex; align-items: center; justify-content: center;"><svg data-dc-tpl="623" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="624" x="5" y="11" width="14" height="10" rx="2"></rect><path data-dc-tpl="625" d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
            <p data-dc-tpl="626" style="margin: 0px; font-size: 16px; font-weight: 800; color: rgb(43, 38, 34);">תפריט מלא — זמין בפרו</p>
            <p data-dc-tpl="627" style="margin: 0px; font-size: 14px; color: rgb(110, 102, 88); max-width: 330px; line-height: 1.5;">הציגו תמונה או קובץ PDF של התפריט המלא, מנות מיוחדות ועוד.</p>
            <button data-dc-tpl="628" style="margin-top: 4px; background: rgb(33, 29, 26); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 999px;">פתחו עם פרו</button>
          </div>
          
          <div data-dc-tpl="629" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <h2 data-dc-tpl="630" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">תפריט</h2>
            <button data-dc-tpl="631" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">עריכת תפריט</button>
          </div>
          <div data-dc-tpl="632" style="display: flex; gap: 26px; border-bottom: 1px solid rgb(236, 229, 216); margin-bottom: 6px; font-size: 15px; flex-wrap: wrap;">
            <span data-dc-tpl="633" style="padding-bottom: 12px; font-weight: 700; color: rgb(31, 146, 84); border-bottom: 2px solid rgb(31, 146, 84); margin-bottom: -1px; cursor: pointer;">מנות מיוחדות</span>
            <span data-dc-tpl="634" style="padding-bottom: 12px; color: rgb(138, 129, 117); cursor: pointer;">פופולריות</span>
            <span data-dc-tpl="635" style="padding-bottom: 12px; color: rgb(138, 129, 117); cursor: pointer;">לטבעונים</span>
            <span data-dc-tpl="636" style="padding-bottom: 12px; color: rgb(138, 129, 117); cursor: pointer;">לנמנעים מגלוטן</span>
            <span data-dc-tpl="637" style="display: inline-flex; align-items: center; gap: 6px; padding-bottom: 12px; color: rgb(138, 129, 117); cursor: pointer;">
              כשר
            </span>
          </div>
          <div data-dc-tpl="638">
            <div data-dc-tpl="639" style="padding: 15px 2px; border-bottom: 1px solid rgb(240, 235, 224);">
              <p data-dc-tpl="640" style="margin: 0px 0px 3px; font-size: 16px; color: rgb(58, 51, 44);">כריך סלמון עם איולי לימון</p>
              <p data-dc-tpl="641" style="margin: 0px; font-size: 14px; color: rgb(138, 129, 117);">מוגש בלחם מחמצת טרי</p>
            </div>
            <div data-dc-tpl="642" style="padding: 15px 2px;">
              <p data-dc-tpl="643" style="margin: 0px 0px 3px; font-size: 16px; color: rgb(58, 51, 44);">קרואסון שקדים חם</p>
              <p data-dc-tpl="644" style="margin: 0px; font-size: 14px; color: rgb(138, 129, 117);">נאפה במקום ומוגש חם</p>
            </div>
          </div>
          <button data-dc-tpl="645" style="margin-top: 18px; display: inline-flex; align-items: center; gap: 7px; background: none; border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; color: rgb(31, 146, 84); font-weight: 600; font-size: 15px; padding: 0px;">
            <svg data-dc-tpl="646" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path data-dc-tpl="647" d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><path data-dc-tpl="648" d="M14 3v6h6"></path></svg>
            צפייה בתפריט המלא
          </button>
        </div>

        
        <div data-dc-tpl="649" style="position: relative; overflow: hidden; background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;">
          
          <div data-dc-tpl="651" style="position: absolute; inset: 0px; z-index: 5; background: rgba(251, 248, 241, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; text-align: center; padding: 20px;">
            <span data-dc-tpl="652" style="width: 44px; height: 44px; border-radius: 12px; background: rgb(33, 29, 26); display: flex; align-items: center; justify-content: center;"><svg data-dc-tpl="653" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="654" x="5" y="11" width="14" height="10" rx="2"></rect><path data-dc-tpl="655" d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
            <p data-dc-tpl="656" style="margin: 0px; font-size: 16px; font-weight: 800; color: rgb(43, 38, 34);">שעות פעילות — זמין בפרו</p>
            <p data-dc-tpl="657" style="margin: 0px; font-size: 14px; color: rgb(110, 102, 88); max-width: 330px; line-height: 1.5;">הראו ללקוחות מתי אתם פתוחים — פחות אנשים יגיעו לדלת סגורה.</p>
            <button data-dc-tpl="658" style="margin-top: 4px; background: rgb(33, 29, 26); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 999px;">פתחו עם פרו</button>
          </div>
          
          <div data-dc-tpl="659" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h2 data-dc-tpl="660" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">שעות פעילות</h2>
            <button data-dc-tpl="661" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">עריכת שעות</button>
          </div>
          <div data-dc-tpl="662" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(251, 242, 241); border: 1px solid rgb(240, 216, 212); border-radius: 999px; padding: 6px 14px; margin-bottom: 18px;">
            <span data-dc-tpl="663" style="width: 8px; height: 8px; border-radius: 50%; background: rgb(192, 73, 47);"></span>
            <span data-dc-tpl="664" style="font-size: 14px; font-weight: 600; color: rgb(168, 65, 43);">סגור כעת · נפתח מחר ב-08:00</span>
          </div>
          <div data-dc-tpl="665" style="display: flex; flex-direction: column; gap: 13px; font-size: 16px; max-width: 420px;">
            <div data-dc-tpl="666" style="display: flex; justify-content: space-between;"><span data-dc-tpl="667" style="color: rgb(58, 51, 44);">ראשון–חמישי</span><span data-dc-tpl="668" style="color: rgb(110, 102, 88);">08:00–14:00</span></div>
            <div data-dc-tpl="669" style="height: 1px; background: rgb(240, 235, 224);"></div>
            <div data-dc-tpl="670" style="display: flex; justify-content: space-between;"><span data-dc-tpl="671" style="color: rgb(58, 51, 44);">שישי</span><span data-dc-tpl="672" style="color: rgb(110, 102, 88);">סגור</span></div>
            <div data-dc-tpl="673" style="height: 1px; background: rgb(240, 235, 224);"></div>
            <div data-dc-tpl="674" style="display: flex; justify-content: space-between;"><span data-dc-tpl="675" style="color: rgb(58, 51, 44);">שבת</span><span data-dc-tpl="676" style="color: rgb(110, 102, 88);">09:00–13:00</span></div>
          </div>
        </div>

        
        <div data-dc-tpl="677" style="position: relative; overflow: hidden; background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;">
          
          <div data-dc-tpl="679" style="position: absolute; inset: 0px; z-index: 5; background: rgba(251, 248, 241, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; text-align: center; padding: 20px;">
            <span data-dc-tpl="680" style="width: 44px; height: 44px; border-radius: 12px; background: rgb(33, 29, 26); display: flex; align-items: center; justify-content: center;"><svg data-dc-tpl="681" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="682" x="5" y="11" width="14" height="10" rx="2"></rect><path data-dc-tpl="683" d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
            <p data-dc-tpl="684" style="margin: 0px; font-size: 16px; font-weight: 800; color: rgb(43, 38, 34);">שירותים ומאפיינים — זמין בפרו</p>
            <p data-dc-tpl="685" style="margin: 0px; font-size: 14px; color: rgb(110, 102, 88); max-width: 330px; line-height: 1.5;">סמנו מה מחכה ללקוחות — Wi-Fi, חניה, נגישות ועוד.</p>
            <button data-dc-tpl="686" style="margin-top: 4px; background: rgb(33, 29, 26); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 999px;">פתחו עם פרו</button>
          </div>
          
          <div data-dc-tpl="687" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h2 data-dc-tpl="688" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">שירותים ומאפיינים</h2>
            <button data-dc-tpl="689" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">עריכת מאפיינים</button>
          </div>
          <div data-dc-tpl="690" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            <div data-dc-tpl="691" style="display: flex; align-items: center; gap: 12px;"><span data-dc-tpl="692" style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 11px; background: rgb(244, 239, 230); border: 1px solid rgb(236, 229, 216);"></span><span data-dc-tpl="693" style="font-size: 16px;">Wi-Fi</span></div>
            <div data-dc-tpl="694" style="display: flex; align-items: center; gap: 12px;"><span data-dc-tpl="695" style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 11px; background: rgb(244, 239, 230); border: 1px solid rgb(236, 229, 216);"></span><span data-dc-tpl="696" style="font-size: 16px;">חניה</span></div>
            <div data-dc-tpl="697" style="display: flex; align-items: center; gap: 12px;"><span data-dc-tpl="698" style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 11px; background: rgb(244, 239, 230); border: 1px solid rgb(236, 229, 216);"></span><span data-dc-tpl="699" style="font-size: 16px;">חניה לנכים</span></div>
            <div data-dc-tpl="700" style="display: flex; align-items: center; gap: 12px;"><span data-dc-tpl="701" style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 11px; background: rgb(244, 239, 230); border: 1px solid rgb(236, 229, 216);"></span><span data-dc-tpl="702" style="font-size: 16px;">שירותים</span></div>
            <div data-dc-tpl="703" style="display: flex; align-items: center; gap: 12px;"><span data-dc-tpl="704" style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 11px; background: rgb(244, 239, 230); border: 1px solid rgb(236, 229, 216);"></span><span data-dc-tpl="705" style="font-size: 16px;">נגיש</span></div>
          </div>
        </div>

        
        <div data-dc-tpl="706" style="position: relative; overflow: hidden; background: rgb(255, 253, 249); border: 1px solid rgb(234, 227, 214); border-radius: 18px; padding: 26px 30px; box-shadow: rgba(0, 0, 0, 0.03) 0px 1px 2px;">
          
          <div data-dc-tpl="708" style="position: absolute; inset: 0px; z-index: 5; background: rgba(251, 248, 241, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; text-align: center; padding: 20px;">
            <span data-dc-tpl="709" style="width: 44px; height: 44px; border-radius: 12px; background: rgb(33, 29, 26); display: flex; align-items: center; justify-content: center;"><svg data-dc-tpl="710" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect data-dc-tpl="711" x="5" y="11" width="14" height="10" rx="2"></rect><path data-dc-tpl="712" d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></span>
            <p data-dc-tpl="713" style="margin: 0px; font-size: 16px; font-weight: 800; color: rgb(43, 38, 34);">מה כדאי לדעת — זמין בפרו</p>
            <p data-dc-tpl="714" style="margin: 0px; font-size: 14px; color: rgb(110, 102, 88); max-width: 330px; line-height: 1.5;">ספרו מה מחכה למשפחות, למטיילים ולרוכבי האופניים שמגיעים אליכם.</p>
            <button data-dc-tpl="715" style="margin-top: 4px; background: rgb(33, 29, 26); color: rgb(255, 255, 255); border-width: medium; border-style: none; border-color: currentcolor; border-image: initial; cursor: pointer; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 999px;">פתחו עם פרו</button>
          </div>
          
          <div data-dc-tpl="716" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <h2 data-dc-tpl="717" style="margin: 0px; font-size: clamp(18px, 4.6vw, 20px); font-weight: 700;">מה כדאי לדעת</h2>
            <button data-dc-tpl="718" style="display: inline-flex; align-items: center; gap: 8px; background: rgb(255, 253, 249); color: rgb(94, 87, 75); border: 1px solid rgb(219, 212, 199); cursor: pointer; font-weight: 600; font-size: 13px; padding: 9px 16px; border-radius: 999px;">עריכת המידע</button>
          </div>
          <div data-dc-tpl="719" style="display: flex; gap: 26px; border-bottom: 1px solid rgb(236, 229, 216); margin-bottom: 20px; font-size: 15px;">
            <span data-dc-tpl="720" style="padding-bottom: 12px; font-weight: 700; color: rgb(31, 146, 84); border-bottom: 2px solid rgb(31, 146, 84); margin-bottom: -1px;">בשביל הילדים</span>
            <span data-dc-tpl="721" style="padding-bottom: 12px; color: rgb(138, 129, 117);">בשביל הטיולים</span>
            <span data-dc-tpl="722" style="padding-bottom: 12px; color: rgb(138, 129, 117);">בשביל האופניים</span>
          </div>
          <div data-dc-tpl="723" style="display: flex; flex-direction: column; gap: 13px; max-width: 640px;">
            <div data-dc-tpl="724" style="display: flex; gap: 12px;"><span data-dc-tpl="725" style="flex-shrink: 0; width: 7px; height: 7px; border-radius: 50%; background: rgb(201, 191, 173); margin-top: 9px;"></span><span data-dc-tpl="726" style="font-size: 16px; line-height: 1.6; color: rgb(58, 51, 44);">אצלנו ילדים אוהבים לאכול: טוסט ילדים, בורקס גבינה, כדורי שוקולד ושייקים.</span></div>
            <div data-dc-tpl="727" style="display: flex; gap: 12px;"><span data-dc-tpl="728" style="flex-shrink: 0; width: 7px; height: 7px; border-radius: 50%; background: rgb(201, 191, 173); margin-top: 9px;"></span><span data-dc-tpl="729" style="font-size: 16px; line-height: 1.6; color: rgb(58, 51, 44);">יש פינת משחקים לגיל הרך.</span></div>
            <div data-dc-tpl="730" style="display: flex; gap: 12px;"><span data-dc-tpl="731" style="flex-shrink: 0; width: 7px; height: 7px; border-radius: 50%; background: rgb(201, 191, 173); margin-top: 9px;"></span><span data-dc-tpl="732" style="font-size: 16px; line-height: 1.6; color: rgb(58, 51, 44);">יש מקום נוח לפרוש שמיכת תינוק.</span></div>
          </div>
        </div>

      </div>
    </div>
    <?php endif; ?>
</section>
