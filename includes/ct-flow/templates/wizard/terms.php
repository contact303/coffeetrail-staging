<?php
/**
 * Terms Step — Wizard version
 *
 * Adapted from the existing terms-step.php.
 * Keeps scroll-to-unlock functionality.
 * PRO and Free show slightly different content.
 *
 * Variables:
 *   @var string $current_step
 *   @var string $listing_package
 *   @var array  $state
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_pro   = ( $listing_package === 'pro' );
$d        = $state['data']['terms'] ?? [];
$agreed   = ! empty( $d['ct_listing_terms'] );
$agreed_cancel = ! empty( $d['ct_cancellation_fee'] );
?>
<div class="ct-step" id="ct-step-terms">

	<h2 class="ct-step__title">
		<?php echo $is_pro ? 'תנאי שימוש — מסלול PRO' : 'תנאי שימוש' ?>
	</h2>
	<p class="ct-step__subtitle">
		יש לקרוא את התנאים עד הסוף ולאשר לפני המשך התהליך.
	</p>

	<!-- Scrollable terms box -->
	<div class="ct-terms-scroll-box" id="ct-terms-scroll-box" tabindex="0"
		style="max-height:300px;overflow-y:auto;border:1px solid #E5E7EB;border-radius:var(--cw-radius-sm);padding:16px;margin-bottom:16px;font-size:14px;line-height:1.6;">

		<?php if ( $is_pro ) : ?>
			<h3 style="font-size:16px;margin-top:0;">תנאי שימוש — מסלול PRO</h3>
			<p>ברוכים הבאים ל-CoffeeTrail. על ידי הרשמה למסלול PRO הנכם מסכימים לתנאים הבאים:</p>
			<h4>1. שירות ותשלום</h4>
			<p>המנוי למסלול PRO הוא חודשי וכרוך בתשלום כפי שמוצג בעת ההרשמה. החיוב יתבצע מיד עם אישור העמוד.</p>
			<h4>2. פרסום מיידי</h4>
			<p>עמודכם יפורסם מיד לאחר השלמת התשלום ויהיה גלוי ללקוחות.</p>
			<h4>3. ביטול והפסקת שירות</h4>
			<p>ביטול המנוי אפשרי בכל עת. החזר כספי מלא יינתן תוך 14 יום מתאריך החיוב הראשון. לאחר 14 יום לא יינתן החזר על התקופה שנותרה.</p>
			<h4>4. תוכן ואחריות</h4>
			<p>האחריות על תוכן הדף (תמונות, שעות, מיקום) היא של בעל העגלה. CoffeeTrail אינה אחראית לדיוק הפרטים שהוזנו.</p>
			<h4>5. שינויים בתנאים</h4>
			<p>CoffeeTrail שומרת לעצמה את הזכות לעדכן את התנאים בכל עת.</p>
			<p><em>— צוות CoffeeTrail</em></p>
		<?php else : ?>
			<h3 style="font-size:16px;margin-top:0;">תנאי שימוש — מסלול חינמי</h3>
			<p>ברוכים הבאים ל-CoffeeTrail. על ידי הרשמה למסלול החינמי הנכם מסכימים לתנאים הבאים:</p>
			<h4>1. השירות</h4>
			<p>המסלול החינמי מאפשר פרסום דף בסיסי לעגלת הקפה שלכם ללא עלות. פרטים נוספים ותכונות מתקדמות זמינים במסלול PRO.</p>
			<h4>2. פרסום מיידי</h4>
			<p>עמודכם יפורסם מיד עם השלמת ההרשמה ויהיה גלוי ללקוחות.</p>
			<h4>3. תוכן ואחריות</h4>
			<p>האחריות על תוכן הדף (שם, מיקום, סוג) היא של בעל העגלה. CoffeeTrail אינה אחראית לדיוק הפרטים שהוזנו.</p>
			<h4>4. שימוש מקובל</h4>
			<p>אסור להזין פרטים כוזבים, תוכן פוגעני או מטעה. CoffeeTrail שומרת לעצמה את הזכות להסיר רישומים שאינם עומדים בתנאים.</p>
			<h4>5. שינויים בתנאים</h4>
			<p>CoffeeTrail שומרת לעצמה את הזכות לעדכן את התנאים בכל עת.</p>
			<p><em>— צוות CoffeeTrail</em></p>
		<?php endif ?>

	</div><!-- .ct-terms-scroll-box -->

	<div class="ct-terms-scroll-hint" id="ct-terms-scroll-hint"
		style="text-align:center;font-size:13px;color:var(--cw-text-muted);margin-bottom:16px;">
		↓ גללו עד הסוף כדי לאשר
	</div>

	<!-- Checkboxes -->
	<div class="ct-checkbox-group">
		<label class="ct-checkbox-item">
			<input type="checkbox"
				name="ct_listing_terms"
				id="ct_listing_terms"
				value="1"
				class="ct-terms-unlock-required"
				<?php checked( $agreed ) ?>>
			<span>
				<?php if ( $is_pro ) : ?>
					קראתי את <strong>תנאי השימוש של מסלול PRO</strong> ואני מסכים/ה להם. <span style="color:var(--cw-error);">*</span>
				<?php else : ?>
					קראתי את <strong>תנאי השימוש</strong> ואני מסכים/ה להם. <span style="color:var(--cw-error);">*</span>
				<?php endif ?>
			</span>
		</label>

		<?php if ( $is_pro ) : ?>
			<label class="ct-checkbox-item">
				<input type="checkbox"
					name="ct_cancellation_fee"
					id="ct_cancellation_fee"
					value="1"
					class="ct-terms-unlock-required"
					<?php checked( $agreed_cancel ) ?>>
				<span>
					הבנתי ואני מסכים/ה ל<strong>מדיניות הביטול</strong>: החזר מלא תוך 14 יום מהחיוב; לאחר מכן לא יינתן החזר.
					<span style="color:var(--cw-error);">*</span>
				</span>
			</label>
		<?php endif ?>

		<!-- Pass listing_package for server-side validation -->
		<input type="hidden" name="listing_package" value="<?php echo esc_attr( $listing_package ) ?>">
	</div>

</div>

<?php include CT_FLOW_DIR . '/templates/wizard/footer.php'; ?>

<script>
// Re-enable scroll hint: hide arrow once scroll box is fully scrolled.
(function () {
    var box  = document.getElementById('ct-terms-scroll-box');
    var hint = document.getElementById('ct-terms-scroll-hint');
    if (!box || !hint) { return; }

    box.addEventListener('scroll', function () {
        var atBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 5;
        hint.style.display = atBottom ? 'none' : '';
    });
})();
</script>
