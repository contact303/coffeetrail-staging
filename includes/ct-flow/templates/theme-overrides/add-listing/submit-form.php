<?php
/**
 * Listing submission form template -- CoffeeTrail child theme override.
 *
 * Merges parent theme submit-form.php (package ID resolution, section tracking,
 * char counter, switch-package button, listing_package_id hidden input) with
 * child theme additions (Google Sync header for edit mode, hide "(optional)" in
 * edit mode) and new ct-flow features (locked PRO-field overlay).
 *
 * IMPORTANT: This file lives in ct-flow/templates/theme-overrides/add-listing/
 * and must be manually copied to my-listing-child/templates/add-listing/
 * for the override to take effect (child theme path is picked up automatically
 * by mylisting_locate_template / locate_template).
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_print_styles( 'mylisting-add-listing' );
wp_enqueue_script( 'jquery-ui-sortable' );

// ---------------------------------------------------------------------------
// Resolve package ID (product ID) for field conditions and validation.
// In edit mode use the listing's own product; in add mode read from request.
// ---------------------------------------------------------------------------
$submit_type = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
$pckg_id     = null;

if ( $submit_type === 'edit' ) {
	if ( $job_id > 0 && \MyListing\Src\Listing::get( $job_id ) ) {
		$pckg_id = $listing_pckg_id; // passed by Edit_Listing_Form via template vars
	}
} else {
	$pckg_id = ! empty( $_REQUEST['listing_package'] )
		? c27()->get_package_id_for_validation( $_REQUEST['listing_package'] )
		: '';
}

$can_post       = is_user_logged_in() || ! mylisting_get_setting( 'submission_requires_account' );
$listing_status = 'draft';
if ( $job_id > 0 ) {
	$listing_status = get_post_status( $job_id );
}

// Build the PRO upgrade URL used inside locked-field overlays.
$_add_listing_page = absint( c27()->get_setting( 'general_add_listing_page' ) );
$upgrade_url = add_query_arg(
	[
		'listing_type'    => sanitize_text_field( $_REQUEST['listing_type'] ?? 'cc' ),
		'listing_package' => CT_FLOW_PRO_PRODUCT_ID,
		'skip_selection'  => 1,
	],
	$_add_listing_page ? get_permalink( $_add_listing_page ) : home_url( '/' )
);
?>

<div class="i-section">
	<div class="container">
		<div class="row section-title">
			<h2 class="case27-primary-text"><?php _ex( 'Your listing details', 'Add listing form', 'my-listing' ) ?></h2>
		</div>
		<form action="<?php echo esc_url( $action ); ?>" method="post" id="submit-job-form"
			class="job-manager-form light-forms c27-submit-listing-form" enctype="multipart/form-data"
			<?php if ( mylisting_get_setting( 'recaptcha_show_in_submission' ) ): ?>
				data-recaptcha="true"
				data-recaptcha-action="add_listing"
			<?php endif ?>
		>

			<?php
			/**
			 * Display login/register message at the top of the add-listing form.
			 * Resolved through child-theme path first (our auth.php override).
			 */
			require locate_template( 'templates/add-listing/auth.php' ); ?>

			<?php if ( $can_post || \MyListing\Src\Listing::user_can_edit( $job_id ) ) : ?>

				<?php
				// If the first real field is a form-heading, the listing type provides
				// its own section header, so we skip opening the default "General" section.
				$first_field          = reset( $fields );
				$skip_default_section = $first_field && $first_field->get_type() === 'form-heading';
				$section_open         = false;

				if ( ! $skip_default_section ) :
					$section_open = true; ?>
					<div class="form-section-wrapper" id="form-section-general">
						<div class="element form-section">

							<?php if ( $form === 'edit-listing' ) : ?>
								<!-- Google Sync header (edit mode) -->
								<?php $google_id_field = \MyListing\Src\Listing::get( $job_id )?->get_field( 'google-places-id' ); ?>
								<style>
									.google-sync-header{background-color:#219156;padding:20px 24px;border-radius:8px;margin-bottom:0}
									.google-sync-content{display:flex;align-items:center;justify-content:space-between;gap:16px;direction:rtl}
									.google-sync-text{color:#fff;margin:0;font-size:15px;line-height:1.6;flex:1;text-align:right}
									.google-sync-icon{background:#fff;width:48px;height:48px;min-width:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
									.google-sync-icon svg{width:24px;height:24px}
									@media(max-width:768px){.google-sync-header{padding:16px 20px}.google-sync-content{gap:12px}.google-sync-text{font-size:14px}.google-sync-icon{width:42px;height:42px;min-width:42px}.google-sync-icon svg{width:22px;height:22px}}
									@media(max-width:480px){.google-sync-header{padding:14px 16px}.google-sync-text{font-size:13px}.google-sync-icon{width:38px;height:38px;min-width:38px}.google-sync-icon svg{width:20px;height:20px}}
								</style>

								<?php if ( $google_id_field ) : ?>
									<div class="google-sync-header">
										<div class="google-sync-content">
											<h5 class="google-sync-text"><?php _ex( 'השעות בדף זה מסונכרנות עם ה Google map שלכם. הסנכרון מתבצע כל יום בשעה 23:00. לעדכון מיידי של השעות עדכנו בדף פה.', 'Add listing form', 'my-listing' ) ?></h5>
											<div class="google-sync-icon">
												<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
													<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
													<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
													<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
													<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
												</svg>
											</div>
										</div>
									</div>
								<?php else : ?>
									<div class="google-sync-header">
										<div class="google-sync-content">
											<h5 class="google-sync-text"><?php _ex( 'השעות בדף זה אינן מסונכרנות עם Google map היות ואין לכם דף מעודכן ב Google map. במידה ויהיה דף תעדכנו אותנו ונפעיל את הסינכרון', 'Add listing form', 'my-listing' ) ?></h5>
											<div class="google-sync-icon">
												<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
													<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
													<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
													<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
													<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
												</svg>
											</div>
										</div>
									</div>
								<?php endif; ?>

							<?php else : ?>
								<!-- Add mode: standard General section header -->
								<div class="pf-head round-icon">
									<div class="title-style-1">
										<i class="icon-pencil-2"></i>
										<h5><?php _ex( 'General', 'Add listing form', 'my-listing' ) ?></h5>
									</div>
								</div>
							<?php endif; ?>

							<div class="pf-body">
				<?php endif; ?>

				<?php do_action( 'mylisting/add-listing/form-fields/start' ) ?>

				<?php foreach ( $fields as $key => $field ) : ?>

					<?php if ( $field->get_type() === 'form-heading' ) :
						if ( $section_open ) : ?>
							</div></div></div>
						<?php endif;
						$section_open = true; ?>
						<div class="form-section-wrapper" id="form-section-<?php echo esc_attr( ! empty( $key ) ? $key : \MyListing\Utils\Random_Id::generate(7) ) ?>">
							<div class="element form-section">
								<div class="pf-head round-icon">
									<div class="title-style-1">
										<i class="<?php echo esc_attr( $field->get_prop( 'icon' ) ?: 'icon-pencil-2' ) ?>"></i>
										<h5><?php echo esc_html( $field->get_label() ) ?></h5>
									</div>
									<?php if ( $field->get_prop( 'image' ) || ! empty( $field->get_description() ) ) : ?>
										<div class="heading-content">
											<?php if ( $field->get_prop( 'image' ) ) : ?>
												<img src="<?php echo esc_url( $field->get_prop( 'image' ) ); ?>" alt="<?php echo esc_attr( $field->get_label() ) ?>">
											<?php endif; ?>
											<?php if ( ! empty( $field->get_description() ) ) : ?>
												<div><?php echo $field->get_description() ?></div>
											<?php endif ?>
										</div>
									<?php endif; ?>
								</div>
								<div class="pf-body">

					<?php else :
						$classes   = [];
						$is_locked = class_exists( 'CT_Flow_Locked_Fields' ) && CT_Flow_Locked_Fields::is_locked( $key );

						if ( $field->get_type() === 'term-select' ) {
							$classes[] = 'term-type-' . $field->get_prop( 'terms-template' );
						}
						if ( $is_locked ) {
							$classes[] = 'ct-locked-field';
						}
						?>
						<div class="fieldset-<?php echo esc_attr( $key ) ?> <?php echo esc_attr( 'field-type-' . $field->get_type() ) ?> form-group <?php echo join( ' ', array_map( 'esc_attr', $classes ) ) ?>">

							<?php if ( $is_locked ) : ?>
								<div class="ct-locked-overlay" aria-hidden="true">
									<div class="ct-locked-overlay-inner">
										<span class="ct-pro-badge">
											<i class="material-icons">lock</i>
											זמין במסלול PRO בלבד
										</span>
										<a href="<?php echo esc_url( $upgrade_url ) ?>" class="buttons button-2 ct-upgrade-btn">
											שדרג עכשיו
										</a>
									</div>
								</div>
							<?php endif; ?>

							<div class="field-head">
								<label for="<?php echo esc_attr( $key ) ?>">
									<?php
									echo $field->get_label();
									// Hide "(optional)" in edit mode or for locked fields (they can't be filled anyway).
									if ( $form !== 'edit-listing' && ! $is_locked ) {
										echo apply_filters(
											'mylisting/submission/required-field-label',
											! $field->is_required()
												? ' <small>' . _x( '(optional)', 'Add listing form', 'my-listing' ) . '</small>'
												: '',
											$field
										);
									}
									?>
								</label>

								<?php c27()->ml_display_field_char_counter( $field ); ?>

								<?php if ( ! empty( $field->get_description() ) ) : ?>
									<small class="description"><?php echo $field->get_description() ?></small>
								<?php endif ?>
							</div>

							<div class="field <?php echo $field->is_required() ? 'required-field' : ''; ?> <?php echo $is_locked ? 'ct-field-disabled' : '' ?>">
								<?php
								mylisting_locate_template(
									'templates/add-listing/form-fields/' . $field->get_type() . '-field.php',
									[ 'key' => $key, 'field' => $field, 'pckg_id' => $pckg_id ]
								);
								?>
							</div>

						</div>
					<?php endif ?>

				<?php endforeach; ?>

				<?php do_action( 'mylisting/add-listing/form-fields/end' ) ?>

				<?php if ( $section_open ) : ?>
					</div>
				</div>
			</div>
				<?php endif; ?>

				<div class="form-section-wrapper form-footer" id="form-section-submit">
					<div class="form-section">
						<div class="pf-body">
							<div class="hidden">
								<input type="hidden" name="job_manager_form" value="<?php echo esc_attr( $form ) ?>">
								<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ) ?>">
								<input type="hidden" name="step" value="<?php echo esc_attr( $step ) ?>">
								<?php if ( ! empty( $_REQUEST['listing_type'] ) ) : ?>
									<input type="hidden" name="listing_type" value="<?php echo esc_attr( $_REQUEST['listing_type'] ) ?>">
								<?php endif ?>
								<?php if ( ! empty( $_REQUEST['listing_package'] ) ) : ?>
									<input type="hidden" name="listing_package" value="<?php echo esc_attr( $_REQUEST['listing_package'] ) ?>">
								<?php endif ?>
								<input type="hidden" name="listing_package_id" value="<?php echo esc_attr( $pckg_id ?? '' ) ?>">
								<input type="hidden" name="submit_type" value="<?php echo esc_attr( $submit_type ) ?>">
							</div>

							<?php /* Auto-save status indicator */ ?>
							<div class="ct-autosave-status" style="display:none;" aria-live="polite"></div>

							<div class="listing-form-submit-btn">

								<?php if ( $form === 'submit-listing' ) : ?>
									<button type="submit" name="submit_job" class="skip-preview-btn buttons button-2" value="submit--no-preview">
										<?php if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'switch' ) : ?>
											<i class="fa fa-exchange-alt"></i>
											<?php echo esc_html( _x( 'Switch package', 'Switch package form', 'my-listing' ) ) ?>
										<?php else : ?>
											<i class="fa fa-paper-plane"></i>
											<?php echo esc_html( _x( 'Submit listing', 'Add listing form', 'my-listing' ) ) ?>
										<?php endif ?>
									</button>
								<?php endif ?>

								<?php if ( ! ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'switch' ) ) : ?>
									<button type="submit" name="submit_job" class="preview-btn buttons button-5" value="submit">
										<i class="mi remove_red_eye"></i>
										<?php echo esc_html( $submit_button_text ) ?>
									</button>
								<?php endif ?>

								<?php if ( $form === 'submit-listing' && 'publish' !== $listing_status ) : ?>
									<button type="submit" name="submit_job" class="save-listing-button buttons button-5" value="save--no-preview">
										<i class="mi save"></i>
										<?php echo esc_html( _x( 'Save as draft', 'Add listing form', 'my-listing' ) ) ?>
									</button>
								<?php endif ?>

							</div>
						</div>
					</div>
				</div>

			<?php endif; ?>
		</form>
	</div>
</div>

<?php wp_enqueue_script( 'mylisting-listing-form' ); ?>

<div class="add-listing-nav">
	<ul class="no-list-style"></ul>
</div>

<div class="loader-bg main-loader add-listing-loader" style="background-color: #fff; display: none;">
	<?php c27()->get_partial( 'spinner', [ 'color' => '#000' ] ) ?>
	<p class="add-listing-loading-message"><?php _ex( 'Please wait while the request is being processed.', 'Add listing form', 'my-listing' ) ?></p>
</div>
