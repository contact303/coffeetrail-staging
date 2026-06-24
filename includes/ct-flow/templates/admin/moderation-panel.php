<?php
/**
 * CoffeeTrail — Admin Moderation Panel template.
 *
 * Variables:
 *   @var string    $active_tab        'recent_listings' | 'pending_changes'
 *   @var string    $success_notice    Action slug if a redirect just happened.
 *   @var WP_Post[] $recent_listings   Recently published listings (newest first).
 *   @var WP_Post[] $pending_changes   Published listings with queued changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notice_map = [
	'unpublished'        => [ 'warning', 'הרישום בוטל ועבר לטיוטה. בוצע החזר כספי אם רלוונטי.' ],
	'field_approved'     => [ 'success', 'השינוי אושר ויושם.' ],
	'field_rejected'     => [ 'warning', 'השינוי נדחה.' ],
	'all_fields_approved'=> [ 'success', 'כל השינויים אושרו.' ],
	'all_fields_rejected'=> [ 'warning', 'כל השינויים נדחו.' ],
];
?>
<div class="wrap ct-admin-panel" dir="rtl">
	<h1 class="wp-heading-inline">
		☕ CoffeeTrail &mdash; ניהול תוכן
	</h1>

	<?php if ( $success_notice && isset( $notice_map[ $success_notice ] ) ) :
		[ $type, $message ] = $notice_map[ $success_notice ]; ?>
		<div class="notice notice-<?php echo esc_attr( $type ) ?> is-dismissible">
			<p><?php echo esc_html( $message ) ?></p>
		</div>
	<?php endif ?>

	<!-- -----------------------------------------------------------------------
	     Tab navigation
	     ----------------------------------------------------------------------- -->
	<nav class="nav-tab-wrapper ct-tab-nav">
		<a href="<?php echo esc_url( add_query_arg( ['page' => CT_Flow_Admin_Panel::PAGE_SLUG, 'post_type' => 'job_listing', 'ct_tab' => 'recent_listings'], admin_url('edit.php') ) ) ?>"
			class="nav-tab <?php echo $active_tab === 'recent_listings' ? 'nav-tab-active' : '' ?>">
			פורסמו לאחרונה
			<?php if ( ! empty( $recent_listings ) ) : ?>
				<span class="ct-badge"><?php echo count( $recent_listings ) ?></span>
			<?php endif ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( ['page' => CT_Flow_Admin_Panel::PAGE_SLUG, 'post_type' => 'job_listing', 'ct_tab' => 'pending_changes'], admin_url('edit.php') ) ) ?>"
			class="nav-tab <?php echo $active_tab === 'pending_changes' ? 'nav-tab-active' : '' ?>">
			שינויים ממתינים
			<?php if ( ! empty( $pending_changes ) ) : ?>
				<span class="ct-badge"><?php echo count( $pending_changes ) ?></span>
			<?php endif ?>
		</a>
	</nav>

	<!-- -----------------------------------------------------------------------
	     Tab: Recently Published
	     ----------------------------------------------------------------------- -->
	<?php if ( $active_tab === 'recent_listings' ) : ?>

		<div class="ct-tab-content">
			<p class="description" style="margin:12px 0 16px;">
				רשימות שפורסמו לאחרונה. ניתן לבטל פרסום של כל רישום — לרישומי PRO יתבצע גם החזר כספי אוטומטי דרך Grow.
			</p>
			<?php if ( empty( $recent_listings ) ) : ?>
				<p class="ct-empty-notice">אין רישומים פורסמו עדיין.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped ct-mod-table">
					<thead>
						<tr>
							<th>שם העגלה</th>
							<th>בעלים</th>
							<th>תאריך פרסום</th>
							<th>מסלול</th>
							<th>פעולות</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_listings as $post ) :
							$author        = get_userdata( $post->post_author );
							$plan_meta     = get_user_meta( $post->post_author, '_ct_registered_plan', true );
							$plan_label    = $plan_meta === 'pro' ? 'PRO' : 'חינמי';
							$plan_class    = $plan_meta === 'pro' ? 'ct-plan-badge--pro' : 'ct-plan-badge--free';
							$edit_link     = get_edit_post_link( $post->ID );
							$view_link     = get_permalink( $post->ID );
							$refund_failed = get_post_meta( $post->ID, '_ct_grow_refund_failed', true );
							$is_pro_paid   = class_exists( 'CT_Grow_Payment' ) && CT_Grow_Payment::has_charged_payment( $post->ID );
						?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo esc_url( $edit_link ) ?>" target="_blank">
										<?php echo esc_html( $post->post_title ) ?>
									</a>
								</strong>
								<br>
								<small>
									<a href="<?php echo esc_url( $view_link ) ?>" target="_blank">צפייה בדף החי</a>
								</small>
								<?php if ( $refund_failed ) : ?>
									<br><small style="color:#c00;">⚠ החזר Grow נכשל — טפל ידנית</small>
								<?php endif ?>
							</td>
							<td>
								<?php if ( $author ) : ?>
									<a href="<?php echo esc_url( get_edit_user_link( $author->ID ) ) ?>">
										<?php echo esc_html( $author->display_name ) ?>
									</a>
									<br>
									<small><?php echo esc_html( $author->user_email ) ?></small>
								<?php else : ?>
									&mdash;
								<?php endif ?>
							</td>
							<td><?php echo esc_html( get_the_date( 'd/m/Y H:i', $post->ID ) ) ?></td>
							<td>
								<span class="ct-plan-badge <?php echo esc_attr( $plan_class ) ?>">
									<?php echo esc_html( $plan_label ) ?>
								</span>
							</td>
							<td>
								<button type="button" class="button button-secondary ct-btn-unpublish"
									onclick="document.getElementById('ct-unpublish-form-<?php echo $post->ID ?>').style.display='block'">
									בטל פרסום
								</button>

								<div id="ct-unpublish-form-<?php echo esc_attr( $post->ID ) ?>"
									class="ct-reject-form" style="display:none; margin-top:8px;">
									<form method="post">
										<?php wp_nonce_field( CT_Flow_Admin_Panel::NONCE_NAME, 'ct_nonce' ) ?>
										<input type="hidden" name="ct_listing_id" value="<?php echo esc_attr( $post->ID ) ?>">
										<textarea name="ct_unpublish_reason" rows="3"
											style="width:100%;margin-bottom:4px;"
											placeholder="סיבת ביטול הפרסום (תישלח לבעלים)..."></textarea>
										<?php if ( $is_pro_paid ) : ?>
											<p style="font-size:12px;color:#c00;margin:4px 0;">
												⚠ רישום PRO — ביטול הפרסום יגרור החזר כספי אוטומטי דרך Grow.
											</p>
										<?php endif ?>
										<button type="submit" name="ct_admin_action" value="unpublish_listing"
											class="button button-primary"
											onclick="return confirm('לבטל את פרסום הרישום<?php echo $is_pro_paid ? ' ולבצע החזר כספי?' : '?' ?>')">
											אשר ביטול פרסום
										</button>
									</form>
								</div>
							</td>
						</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			<?php endif ?>
		</div>

	<!-- -----------------------------------------------------------------------
	     Tab: Pending Changes
	     ----------------------------------------------------------------------- -->
	<?php elseif ( $active_tab === 'pending_changes' ) : ?>

		<div class="ct-tab-content">
			<?php if ( empty( $pending_changes ) ) : ?>
				<p class="ct-empty-notice">אין שינויים הממתינים לאישור. 🎉</p>
			<?php else : ?>
				<?php foreach ( $pending_changes as $post ) :
					$pending    = CT_Flow_Selective_Approval::get_pending_changes( $post->ID );
					$author     = get_userdata( $post->post_author );
					$edit_link  = get_edit_post_link( $post->ID );
					$view_link  = get_permalink( $post->ID );
				?>
				<div class="ct-pending-listing-block">
					<h3>
						<a href="<?php echo esc_url( $edit_link ) ?>" target="_blank">
							<?php echo esc_html( $post->post_title ) ?>
						</a>
						<small>
							&mdash;
							<a href="<?php echo esc_url( $view_link ) ?>" target="_blank">לצפייה בדף החי</a>
						</small>
					</h3>
					<?php if ( $author ) : ?>
						<p class="ct-author-meta">
							בעלים: <?php echo esc_html( $author->display_name ) ?>
							(<?php echo esc_html( $author->user_email ) ?>)
						</p>
					<?php endif ?>

					<!-- Bulk approve / reject all -->
					<div class="ct-bulk-actions">
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( CT_Flow_Admin_Panel::NONCE_NAME, 'ct_nonce' ) ?>
							<input type="hidden" name="ct_listing_id" value="<?php echo esc_attr( $post->ID ) ?>">
							<button type="submit" name="ct_admin_action" value="approve_all_fields"
								class="button button-primary"
								onclick="return confirm('לאשר את כל השינויים?')">
								אשר הכל
							</button>
							<button type="submit" name="ct_admin_action" value="reject_all_fields"
								class="button button-secondary"
								onclick="return confirm('לדחות את כל השינויים?')">
								דחה הכל
							</button>
						</form>
					</div>

					<!-- Per-field diff table -->
					<table class="wp-list-table widefat fixed striped ct-diff-table">
						<thead>
							<tr>
								<th style="width:15%">שדה</th>
								<th>ערך נוכחי (פעיל)</th>
								<th>ערך חדש (ממתין)</th>
								<th style="width:10%">תאריך</th>
								<th style="width:15%">פעולה</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $pending as $field_key => $change ) :
								$submitted_at = ! empty( $change['submitted_at'] )
									? date_i18n( 'd/m/Y H:i', $change['submitted_at'] )
									: '—';
							?>
							<tr>
								<td><code><?php echo esc_html( $field_key ) ?></code></td>
								<td class="ct-diff-old">
									<?php echo wp_kses_post( ct_flow_format_diff_value( $change['old'] ) ) ?>
								</td>
								<td class="ct-diff-new">
									<?php echo wp_kses_post( ct_flow_format_diff_value( $change['new'] ) ) ?>
								</td>
								<td><?php echo esc_html( $submitted_at ) ?></td>
								<td>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( CT_Flow_Admin_Panel::NONCE_NAME, 'ct_nonce' ) ?>
										<input type="hidden" name="ct_listing_id" value="<?php echo esc_attr( $post->ID ) ?>">
										<input type="hidden" name="ct_field_key" value="<?php echo esc_attr( $field_key ) ?>">
										<button type="submit" name="ct_admin_action" value="approve_field"
											class="button button-primary button-small">אשר</button>
										<button type="submit" name="ct_admin_action" value="reject_field"
											class="button button-secondary button-small">דחה</button>
									</form>
								</td>
							</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
				<?php endforeach ?>
			<?php endif ?>
		</div>

	<?php endif ?>
</div><!-- .ct-admin-panel -->

<?php
/**
 * Format a meta value for display in the diff table.
 *
 * @param mixed $value
 * @return string  HTML-safe representation.
 */
function ct_flow_format_diff_value( $value ) {
	if ( is_null( $value ) || $value === '' ) {
		return '<em style="color:#aaa;">(ריק)</em>';
	}
	if ( is_array( $value ) || is_object( $value ) ) {
		return '<pre style="max-height:120px;overflow:auto;font-size:11px;">'
			. esc_html( json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) )
			. '</pre>';
	}
	if ( is_numeric( $value ) && wp_attachment_is_image( (int) $value ) ) {
		return wp_get_attachment_image( (int) $value, [80, 80] );
	}
	return '<span>' . esc_html( wp_trim_words( (string) $value, 30 ) ) . '</span>';
}
