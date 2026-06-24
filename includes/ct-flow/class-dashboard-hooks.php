<?php
/**
 * CT_Flow_Dashboard
 *
 * Injects CoffeeTrail-specific UI elements into the user dashboard
 * (My Listings) using existing MyListing action hooks — no template override.
 *
 *  mylisting/user-listings/before   → "Create New Listing" button row.
 *  mylisting/user-listings/actions  → "Pending changes" badge per listing.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CT_Flow_Dashboard {

	public static function init() {
		add_action( 'mylisting/user-listings/before', [ __CLASS__, 'render_add_listing_button' ] );
		add_action( 'mylisting/user-listings/actions', [ __CLASS__, 'render_pending_changes_badge' ], 10, 1 );
	}

	// -------------------------------------------------------------------------
	// "Create New Listing" button
	// -------------------------------------------------------------------------

	/**
	 * Render a row above the listing grid with a button linking the user back
	 * to the add-listing flow.  Shows two buttons — one per plan — so returning
	 * users can pick their tier.
	 *
	 * @return void
	 */
	public static function render_add_listing_button() {
		$add_listing_page_id = absint( c27()->get_setting( 'general_add_listing_page' ) );
		if ( ! $add_listing_page_id ) {
			return;
		}
		$base_url = get_permalink( $add_listing_page_id );

		$free_url = add_query_arg(
			[ 'listing_type' => 'cc', 'listing_package' => CT_FLOW_FREE_PRODUCT_ID, 'skip_selection' => 1 ],
			$base_url
		);
		$pro_url  = add_query_arg(
			[ 'listing_type' => 'cc', 'listing_package' => CT_FLOW_PRO_PRODUCT_ID, 'skip_selection' => 1 ],
			$base_url
		);
		?>
		<div class="ct-dashboard-add-listing-row" dir="rtl">
			<a href="<?php echo esc_url( $free_url ) ?>" class="buttons button-5 ct-add-listing-btn">
				<i class="mi add_circle_outline"></i>
				הוסף עגלה חדשה &mdash; חינמי
			</a>
			<a href="<?php echo esc_url( $pro_url ) ?>" class="buttons button-2 ct-add-listing-btn ct-add-listing-btn--pro">
				<i class="mi add_circle_outline"></i>
				הוסף עגלה חדשה &mdash; PRO
			</a>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// "Pending changes" badge
	// -------------------------------------------------------------------------

	/**
	 * Render a "pending changes" action link in the per-listing action list
	 * when the listing has field changes awaiting admin approval.
	 *
	 * @param \MyListing\Src\Listing $listing
	 * @return void
	 */
	public static function render_pending_changes_badge( $listing ) {
		$has_pending = get_post_meta( $listing->get_id(), '_ct_has_pending_changes', true );

		if ( ! $has_pending ) {
			return;
		}

		$pending_count = 0;
		$pending_data  = get_post_meta( $listing->get_id(), '_ct_pending_changes', true );
		if ( is_array( $pending_data ) ) {
			$pending_count = count( $pending_data );
		}
		?>
		<li class="ct-pending-changes-badge">
			<span class="ct-pending-badge" title="ממתין לאישור">
				<i class="material-icons">hourglass_empty</i>
				<?php if ( $pending_count > 0 ) : ?>
					<?php echo esc_html( $pending_count ) ?> שינוי<?php echo $pending_count > 1 ? 'ים' : '' ?> ממתין<?php echo $pending_count > 1 ? 'ים' : '' ?> לאישור
				<?php else : ?>
					שינויים ממתינים לאישור
				<?php endif ?>
			</span>
		</li>
		<?php
	}
}
