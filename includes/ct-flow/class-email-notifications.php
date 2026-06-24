<?php
/**
 * CT_Flow_Email_Notifications
 *
 * Sends Hebrew transactional emails for the CoffeeTrail listing flow.
 *
 * Emails:
 *  1. ct_flow/listing_unpublished       → "הרישום שלך הוסר" to listing owner.
 *  2. ct_flow/field_approved            → "שינוי אושר" to listing owner.
 *  3. ct_flow/field_rejected            → "שינוי נדחה" to listing owner.
 *  4. ct_flow/pending_changes_queued    → "יש שינויים ממתינים לאישור" to admin.
 *  5. ct_flow/grow/payment_charged      → "הרישום שלך עלה לאוויר!" to listing owner.
 *
 * All emails use wp_mail() with a simple HTML wrapper styled inline.
 * The theme's own WooCommerce email templates handle order-related emails;
 * this class only covers the listing-flow-specific notifications.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Email_Notifications {

	public static function init() {
		// Admin unpublishes a listing.
		add_action( 'ct_flow/listing_unpublished',    [ __CLASS__, 'send_listing_unpublished' ], 10, 2 );

		// Selective field approval results.
		add_action( 'ct_flow/field_approved',         [ __CLASS__, 'send_field_approved' ],   10, 3 );
		add_action( 'ct_flow/field_rejected',         [ __CLASS__, 'send_field_rejected' ],   10, 3 );

		// Admin notification when new pending changes are queued.
		add_action( 'ct_flow/pending_changes_queued', [ __CLASS__, 'notify_admin_pending_changes' ], 10, 2 );

		// Confirmation to owner when PRO payment is charged and listing goes live.
		add_action( 'ct_flow/grow/payment_charged',   [ __CLASS__, 'send_listing_live' ], 10, 2 );
	}

	// -------------------------------------------------------------------------
	// Email: listing unpublished by admin
	// -------------------------------------------------------------------------

	/**
	 * @param int    $listing_id
	 * @param string $reason
	 * @return void
	 */
	public static function send_listing_unpublished( $listing_id, $reason ) {
		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! $listing ) { return; }

		$owner = get_userdata( get_post_field( 'post_author', $listing_id ) );
		if ( ! $owner ) { return; }

		$title       = esc_html( $listing->get_title() );
		$subject     = "⚠ הרישום שלך הוסר מהאתר — {$title}";
		$reason_html = $reason
			? '<p><strong>סיבה:</strong> ' . esc_html( $reason ) . '</p>'
			: '';

		$body = self::_wrap( "
			<h2>הרישום שלך הוסר זמנית</h2>
			<p>שלום {$owner->display_name},</p>
			<p>הדף של <strong>{$title}</strong> הוסר מ-CoffeeTrail על ידי הצוות שלנו.</p>
			{$reason_html}
			<p>אם יש שאלות או אם תרצו לפרסם את הרישום מחדש, פנו אלינו.</p>
			<p>בברכה,<br>צוות CoffeeTrail</p>
		" );

		self::_send( $owner->user_email, $subject, $body );
	}

	// -------------------------------------------------------------------------
	// Email: PRO listing published after payment
	// -------------------------------------------------------------------------

	/**
	 * @param int   $listing_id
	 * @param array $payload  Grow webhook payload.
	 * @return void
	 */
	public static function send_listing_live( $listing_id, $payload ) {
		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! $listing ) { return; }

		$owner = get_userdata( get_post_field( 'post_author', $listing_id ) );
		if ( ! $owner ) { return; }

		$title   = esc_html( $listing->get_title() );
		$link    = esc_url( $listing->get_link() );
		$subject = "🎉 הרישום שלך עלה לאוויר! — {$title}";

		$body = self::_wrap( "
			<h2>הרישום שלך עלה לאוויר! 🎉</h2>
			<p>שלום {$owner->display_name},</p>
			<p>הדף של <strong>{$title}</strong> פורסם ב-CoffeeTrail ולקוחות יכולים כבר למצוא אתכם.</p>
			<p>
				<a href='{$link}' style='background:#219156;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none;display:inline-block;'>
					צפייה בדף שלי &rarr;
				</a>
			</p>
			<p>בהצלחה,<br>צוות CoffeeTrail</p>
		" );

		self::_send( $owner->user_email, $subject, $body );
	}

	// -------------------------------------------------------------------------
	// Email: field change approved
	// -------------------------------------------------------------------------

	/**
	 * @param int    $listing_id
	 * @param string $field_key
	 * @param mixed  $new_value
	 * @return void
	 */
	public static function send_field_approved( $listing_id, $field_key, $new_value ) {
		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! $listing ) { return; }

		$owner = get_userdata( get_post_field( 'post_author', $listing_id ) );
		if ( ! $owner ) { return; }

		$title   = esc_html( $listing->get_title() );
		$subject = "✅ שינוי אושר — {$title}";

		$body = self::_wrap( "
			<h2>השינוי שלך אושר</h2>
			<p>שלום {$owner->display_name},</p>
			<p>השינוי בשדה <strong>" . esc_html( $field_key ) . "</strong> בדף <strong>{$title}</strong> אושר ויושם.</p>
			<p>בברכה,<br>צוות CoffeeTrail</p>
		" );

		self::_send( $owner->user_email, $subject, $body );
	}

	// -------------------------------------------------------------------------
	// Email: field change rejected
	// -------------------------------------------------------------------------

	/**
	 * @param int    $listing_id
	 * @param string $field_key
	 * @param mixed  $old_value
	 * @return void
	 */
	public static function send_field_rejected( $listing_id, $field_key, $old_value ) {
		$listing = \MyListing\Src\Listing::get( $listing_id );
		if ( ! $listing ) { return; }

		$owner = get_userdata( get_post_field( 'post_author', $listing_id ) );
		if ( ! $owner ) { return; }

		$title   = esc_html( $listing->get_title() );
		$subject = "❌ שינוי נדחה — {$title}";

		$body = self::_wrap( "
			<h2>השינוי שלך נדחה</h2>
			<p>שלום {$owner->display_name},</p>
			<p>השינוי שהגשת לשדה <strong>" . esc_html( $field_key ) . "</strong> בדף <strong>{$title}</strong> נדחה.
			הדף ממשיך להופיע עם הנתונים המקוריים.</p>
			<p>אם יש שאלות, צרו קשר.</p>
			<p>בברכה,<br>צוות CoffeeTrail</p>
		" );

		self::_send( $owner->user_email, $subject, $body );
	}

	// -------------------------------------------------------------------------
	// Email: admin — pending changes queued
	// -------------------------------------------------------------------------

	/**
	 * @param int   $listing_id
	 * @param array $new_pending  Keyed by field_key.
	 * @return void
	 */
	public static function notify_admin_pending_changes( $listing_id, $new_pending ) {
		$admin_email = get_option( 'admin_email' );
		$listing     = \MyListing\Src\Listing::get( $listing_id );
		$title       = $listing ? esc_html( $listing->get_title() ) : "#{$listing_id}";
		$panel_url   = add_query_arg(
			[ 'page' => CT_Flow_Admin_Panel::PAGE_SLUG, 'post_type' => 'job_listing', 'ct_tab' => 'pending_changes' ],
			admin_url( 'edit.php' )
		);
		$count   = count( $new_pending );
		$subject = "⏳ {$count} שינוי חדש ממתין לאישור — {$title}";

		$fields_list = '';
		foreach ( array_keys( $new_pending ) as $k ) {
			$fields_list .= '<li>' . esc_html( $k ) . '</li>';
		}

		$body = self::_wrap( "
			<h2>יש שינויים ממתינים לאישור</h2>
			<p>הדף <strong>{$title}</strong> הוגש עם השינויים הבאים:</p>
			<ul>{$fields_list}</ul>
			<p>
				<a href='" . esc_url( $panel_url ) . "' style='background:#219156;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none;display:inline-block;'>
					לוח ניהול CoffeeTrail &rarr;
				</a>
			</p>
		" );

		self::_send( $admin_email, $subject, $body );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Send an HTML email.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $html_body
	 * @return void
	 */
	private static function _send( $to, $subject, $html_body ) {
		if ( ! $to ) { return; }

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: CoffeeTrail <' . get_option('admin_email') . '>',
		];

		wp_mail( $to, $subject, $html_body, $headers );
	}

	/**
	 * Wrap content in a minimal HTML email shell (RTL-aware).
	 *
	 * @param string $content  Inner HTML.
	 * @return string          Full HTML email body.
	 */
	private static function _wrap( $content ) {
		return '<!DOCTYPE html><html dir="rtl" lang="he"><head><meta charset="UTF-8"></head><body
			style="font-family:Arial,sans-serif;direction:rtl;text-align:right;background:#f9f9f9;margin:0;padding:0;">
			<div style="max-width:580px;margin:40px auto;background:#fff;border-radius:8px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.08);">
				<div style="text-align:center;margin-bottom:24px;">
					<span style="font-size:24px;">☕</span>
					<strong style="font-size:18px;color:#219156;margin-right:8px;">CoffeeTrail</strong>
				</div>
				' . $content . '
				<hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
				<p style="font-size:12px;color:#999;text-align:center;">
					CoffeeTrail &mdash; כל עגלות הקפה במקום אחד
				</p>
			</div>
		</body></html>';
	}
}
