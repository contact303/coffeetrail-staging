<?php
/**
 * Enhanced Footer template for MyListing Child Theme.
 *
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure $data is defined as an array
$data = isset( $data ) && is_array( $data ) ? $data : [];

// Enqueue footer stylesheet
wp_enqueue_style( 'mylisting-footer' );

// Merge default settings into $data
$data = c27()->merge_options( [
    'footer_background' => c27()->get_setting( 'footer_background_color', '#fff' ),
    'footer_text'       => c27()->get_setting( 'footer_text', '' ),
    'show_widgets'      => (bool) c27()->get_setting( 'footer_show_widgets', true ),
    'show_footer_menu'  => (bool) c27()->get_setting( 'footer_show_menu', true ),
], $data );

// Build custom CSS for footer background
$custom_css = '';
if ( ! empty( $data['footer_background'] ) ) {
    $bg_color = sanitize_hex_color( $data['footer_background'] );
    if ( $bg_color ) {
        $custom_css .= sprintf(
            'footer.footer { background: %s; }',
            $bg_color
        );
    }
}

// Inject inline CSS if needed
if ( $custom_css ) {
    wp_add_inline_style( 'mylisting-footer', $custom_css );
}
?>

<footer class="footer">
    <?php if ( $data['show_widgets'] ) : ?>
        <div class="footer-widgets">
            <?php if ( is_active_sidebar( 'footer-1' ) ) {
                dynamic_sidebar( 'footer-1' );
            } ?>
        </div>
    <?php endif; ?>

    <?php if ( $data['show_footer_menu'] ) : ?>
        <nav class="footer-menu">
            <?php
            wp_nav_menu( [
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'footer-nav',
            ] );
            ?>
        </nav>
    <?php endif; ?>

    <?php if ( ! empty( $data['footer_text'] ) ) : ?>
        <div class="footer-text">
            <?php echo wp_kses_post( $data['footer_text'] ); ?>
        </div>
    <?php endif; ?>
</footer>
