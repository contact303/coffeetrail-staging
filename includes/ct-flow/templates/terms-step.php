<?php
/**
 * Terms agreement step template.
 *
 * Variables available:
 *   @var \MyListing\Src\Forms\Add_Listing_Form $form
 *   @var bool   $is_pro        Whether this is the PRO plan.
 *   @var array  $errors        Array of error strings from the form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="i-section ct-terms-section" dir="rtl">
	<div class="container">

		<div class="row section-title">
			<h2 class="case27-primary-text">
				<?php echo $is_pro ? 'תנאי שימוש — מסלול PRO' : 'תנאי שימוש' ?>
			</h2>
			<p class="ct-terms-subtitle">
				יש לקרוא את התנאים עד הסוף ולאשר לפני המשך התהליך.
			</p>
		</div>

		<?php if ( ! empty( $errors ) ) : ?>
			<div class="ct-form-errors" role="alert">
				<?php foreach ( $errors as $error ) : ?>
					<p class="ct-error-message"><?php echo esc_html( $error ) ?></p>
				<?php endforeach ?>
			</div>
		<?php endif ?>

		<form method="post" id="ct-terms-form">

			<div class="element form-section">

				<!-- --------------------------------------------------------
				     Scrollable terms box
				     The submit button is disabled until the user scrolls to
				     the bottom.  ct-terms-scroll.js handles this.
				     -------------------------------------------------------- -->
				<div class="ct-terms-scroll-box" id="ct-terms-scroll-box" tabindex="0"
					data-scroll-target="#ct-terms-scroll-box">

					<?php if ( $is_pro ) : ?>

						<h3>תנאי שימוש — מסלול PRO</h3>
						<p>
							ברוכים הבאים ל-CoffeeTrail. על ידי הרשמה למסלול PRO הנכם מסכימים לתנאים הבאים:
						</p>
						<h4>1. שירות ותשלום</h4>
						<p>
							המנוי למסלול PRO הוא שנתי וכרוך בתשלום כפי שמוצג בעת ההרשמה.
							החיוב יתבצע לאחר אישור רישומכם על ידי צוות CoffeeTrail.
						</p>
						<h4>2. אישור הרישום</h4>
						<p>
							כל רישום חדש עובר בדיקה על ידי הצוות שלנו לפני שיהיה גלוי לציבור.
							נשלח לכם אישור בדוא"ל תוך 2–3 ימי עסקים.
						</p>
						<h4>3. ביטול והפסקת שירות</h4>
						<p>
							ביטול המנוי אפשרי בכל עת. החזר כספי מלא יינתן תוך 14 יום מתאריך החיוב הראשון.
							לאחר 14 יום לא יינתן החזר על התקופה שנותרה.
						</p>
						<h4>4. תוכן ואחריות</h4>
						<p>
							האחריות על תוכן הדף (תמונות, שעות, מיקום) היא של בעל העגלה / הפוד טראק.
							CoffeeTrail אינה אחראית לדיוק הפרטים שהוזנו.
						</p>
						<h4>5. שינויים בתנאים</h4>
						<p>
							CoffeeTrail שומרת לעצמה את הזכות לעדכן את התנאים בכל עת.
							המשך השימוש בשירות לאחר שינוי התנאים מהווה הסכמה אוטומטית.
						</p>
						<p><em>— צוות CoffeeTrail</em></p>

					<?php else : ?>

						<h3>תנאי שימוש — מסלול חינמי</h3>
						<p>
							ברוכים הבאים ל-CoffeeTrail. על ידי הרשמה למסלול החינמי הנכם מסכימים לתנאים הבאים:
						</p>
						<h4>1. השירות</h4>
						<p>
							המסלול החינמי מאפשר פרסום דף בסיסי לעגלת הקפה / פוד טראק שלכם ללא עלות.
							פרטים נוספים ותכונות מתקדמות זמינים במסלול PRO.
						</p>
						<h4>2. אישור הרישום</h4>
						<p>
							כל רישום חדש עובר בדיקה על ידי הצוות שלנו לפני שיהיה גלוי לציבור.
							נשלח לכם אישור בדוא"ל תוך 2–3 ימי עסקים.
						</p>
						<h4>3. תוכן ואחריות</h4>
						<p>
							האחריות על תוכן הדף (שם, מיקום, סוג) היא של בעל העגלה / הפוד טראק.
							CoffeeTrail אינה אחראית לדיוק הפרטים שהוזנו.
						</p>
						<h4>4. שימוש מקובל</h4>
						<p>
							אסור להזין פרטים כוזבים, תוכן פוגעני או מטעה.
							CoffeeTrail שומרת לעצמה את הזכות להסיר רישומים שאינם עומדים בתנאים.
						</p>
						<h4>5. שינויים בתנאים</h4>
						<p>
							CoffeeTrail שומרת לעצמה את הזכות לעדכן את התנאים בכל עת.
						</p>
						<p><em>— צוות CoffeeTrail</em></p>

					<?php endif ?>

				</div><!-- .ct-terms-scroll-box -->

				<div class="ct-terms-scroll-hint" id="ct-terms-scroll-hint">
					<span>
						<i class="material-icons" style="vertical-align:middle;font-size:16px;">arrow_downward</i>
						גללו עד הסוף כדי לאשר
					</span>
				</div>

				<!-- --------------------------------------------------------
				     Checkboxes
				     -------------------------------------------------------- -->
				<div class="ct-terms-checkboxes">

					<div class="ct-checkbox-group">
						<label class="ct-checkbox-label">
							<input type="checkbox" name="ct_listing_terms" id="ct_listing_terms" value="1"
								class="ct-terms-unlock-required"
								<?php checked( ! empty( $_POST['ct_listing_terms'] ) ) ?>>
							<span>
								<?php if ( $is_pro ) : ?>
									קראתי את <strong>תנאי השימוש של מסלול PRO</strong>
									ואני מסכים/ה להם.
									<span class="ct-required-asterisk">*</span>
								<?php else : ?>
									קראתי את <strong>תנאי השימוש</strong>
									ואני מסכים/ה להם.
									<span class="ct-required-asterisk">*</span>
								<?php endif ?>
							</span>
						</label>
					</div>

					<?php if ( $is_pro ) : ?>
						<div class="ct-checkbox-group">
							<label class="ct-checkbox-label">
								<input type="checkbox" name="ct_cancellation_fee" id="ct_cancellation_fee" value="1"
									class="ct-terms-unlock-required"
									<?php checked( ! empty( $_POST['ct_cancellation_fee'] ) ) ?>>
								<span>
									הבנתי ואני מסכים/ה ל<strong>מדיניות הביטול</strong>:
									החזר מלא תוך 14 יום מהחיוב; לאחר מכן לא יינתן החזר.
									<span class="ct-required-asterisk">*</span>
								</span>
							</label>
						</div>
					<?php endif ?>

				</div><!-- .ct-terms-checkboxes -->

			</div><!-- .element.form-section -->

			<!-- Hidden inputs to carry the flow state through POST. -->
			<div class="hidden">
				<input type="hidden" name="job_manager_form" value="submit-listing">
				<input type="hidden" name="step"            value="<?php echo esc_attr( $form->get_step() ) ?>">
				<?php if ( ! empty( $_REQUEST['job_id'] ) ) : ?>
					<input type="hidden" name="job_id" value="<?php echo esc_attr( absint( $_REQUEST['job_id'] ) ) ?>">
				<?php endif ?>
				<?php if ( ! empty( $_REQUEST['listing_type'] ) ) : ?>
					<input type="hidden" name="listing_type" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['listing_type'] ) ) ?>">
				<?php endif ?>
				<?php if ( ! empty( $_REQUEST['listing_package'] ) ) : ?>
					<input type="hidden" name="listing_package" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['listing_package'] ) ) ?>">
				<?php endif ?>
				<?php if ( ! empty( $_REQUEST['skip_selection'] ) ) : ?>
					<input type="hidden" name="skip_selection" value="1">
				<?php endif ?>
			</div>

			<div class="ct-terms-submit-wrap">
				<button type="submit" id="ct-terms-submit-btn" class="buttons button-2 ct-terms-submit-btn"
					disabled>
					<i class="fa fa-arrow-left"></i>
					המשך
				</button>
				<p class="ct-terms-submit-hint" id="ct-terms-submit-hint">
					יש לגלול עד הסוף ולסמן את כל השדות החובה לפני המשך.
				</p>
			</div>

		</form>
	</div>
</section>
