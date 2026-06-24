<?php
if ( ! defined('ABSPATH') ) {
	exit;
}

// Get the Instagram embed HTML
$instagram_html = $listing->get_field_value( 'instagram-reel' );

if ( empty( $instagram_html ) ) {
	return;
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element content-block">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">
			<?php echo $instagram_html ?>
		</div>
	</div>
</div>