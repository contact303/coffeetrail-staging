<?php
/**
 * Template for rendering a `tags` block in single listing page.
 *
 * Child-theme override of my-listing/templates/single-listing/content-blocks/tags-block.php.
 *
 * Extends the parent template by skipping any case27_job_listing_tags terms that carry
 * one of the two visibility meta keys managed by tag_visibility_options.php:
 *
 *   - exclude_from_single_listing      (string '1') — permanent immediate exclusion
 *   - exclude_from_single_listing_date (string 'YYYY-MM-DD') — scheduled exclusion;
 *     the tag is hidden on and after the stored date (compared against WordPress local date)
 *
 * All other logic is identical to the parent template.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Compute today's date once in WordPress local timezone (Settings > General > Timezone).
$ct_today = current_time( 'Y-m-d' );

// Get list of tags assigned to this listing.
$terms = $listing->get_field( 'tags' );

// Bail early if there are no terms to display.
if ( empty( $terms ) || is_wp_error( $terms ) ) {
	return;
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">

			<?php mylisting_locate_template(
				'templates/single-listing/content-blocks/lists/outlined-list.php', [
				'items' => array_filter( array_map( function( $raw_term ) use ( $ct_today ) {

					// Resolve a numeric term ID from whatever get_field('tags') returns
					// (WP_Term object or integer term ID).
					$term_id = ( $raw_term instanceof \WP_Term )
						? (int) $raw_term->term_id
						: absint( $raw_term );

					// Skip if the tag is permanently excluded from single listing pages.
					if ( get_term_meta( $term_id, 'exclude_from_single_listing', true ) ) {
						return false;
					}

					// Skip if a scheduled removal date is set and that date has been reached.
					$exclude_date = (string) get_term_meta( $term_id, 'exclude_from_single_listing_date', true );
					if ( $exclude_date && $ct_today >= $exclude_date ) {
						return false;
					}

					// Resolve the MyListing Term wrapper (provides get_link, get_color, etc.).
					$term = \MyListing\Src\Term::get( $raw_term );
					if ( ! $term ) {
						return false;
					}

					return [
						'link'       => $term->get_link(),
						'name'       => $term->get_name(),
						'color'      => $term->get_color(),
						'text_color' => $term->get_text_color(),
						'icon'       => $term->get_icon( [ 'background' => false, 'color' => false ] ),
					];

				}, $terms ) )
			] ) ?>

		</div>
	</div>
</div>
