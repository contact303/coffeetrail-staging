<div class="lf-item <?php echo esc_attr( 'lf-item-'.$options['template'] ) ?>" data-template="default">
    <a href="<?php echo esc_url( $listing->get_link() ) ?>">

        <?php
        /**
         * Include section overlay template.
         *
         * @since 1.0
         */
        require locate_template( 'templates/single-listing/previews/partials/overlay.php' ) ?>

        <?php if ($options['background']['type'] == 'gallery' && ( $gallery = $listing->get_field( 'gallery' ) ) ): ?>
            <div class="pc-slider">
                <div class="pc-slides">
                    <?php foreach ( array_slice( $gallery, 0, $gallery_count ) as $gallery_image ): ?>
                        <img alt="Gallery image" src="<?php echo esc_url( c27()->get_resized_image( $gallery_image, $bg_size ) ) ?>" class="single-slide">
                    <?php endforeach ?>
                </div>
                <div class="gallery-nav">
                    <ul>
                        <li><span aria-label="Prev" href="#" class="pc-slide-prev"><i class="mi keyboard_arrow_left"></i></span></li>
                        <li><span aria-label="Next" href="#" class="pc-slide-next"><i class="mi keyboard_arrow_right"></i></span></li>
                    </ul>
                </div>
            </div>
        <?php else: $options['background']['type'] = 'image'; endif; // Fallback to cover image if no gallery images are present ?>

        <?php if ($options['background']['type'] == 'image' && ( $cover = $listing->get_cover_image( $bg_size ) ) ): ?>
            <div class="lf-background" style="background-image: url('<?php echo esc_url( $cover ) ?>');"></div>
        <?php endif ?>

        <div class="lf-item-info">
            <?php if ( $logo = $listing->get_logo() ): ?>
                <div class="lf-avatar" style="background-image: url('<?php echo esc_url( $logo ) ?>')"></div>
            <?php endif ?>

            <h4 class="case27-primary-text listing-preview-title">
                <?php echo $listing->get_name() ?>
                <?php if ( $listing->is_verified() ): ?>
                    <img height="18" width="18" alt="<?php echo esc_attr( _ex( 'Verified listing', 'Alt text for verified icon', 'my-listing' ) ) ?>" class="verified-listing" src="<?php echo esc_url( c27()->image('tick.svg') ) ?>">
                <?php endif ?>
                <?php 
                    $coupon_check = $listing->get_field('coupon_check');
                    if ( ! empty( $coupon_check ) && in_array( '1', (array) $coupon_check, true ) ): 
                    ?>
                        <svg class="pango-badge-icon" width="18" height="18" viewBox="0 0 116 160" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle; margin-left: 5px;">
                            <path d="M31.9265 123.269H0V159.818H31.9265V123.269Z" fill="#2E69E7"/>
                            <path d="M98.1833 16.7645C87.7671 6.40997 73.4372 0 57.5664 0C25.7631 0 0 25.7631 0 57.5663V115.133H57.5664C89.3696 115.133 115.133 89.3696 115.133 57.5663C115.133 41.6339 108.661 27.1807 98.1833 16.7645ZM55.9947 88.9998C39.6308 88.9998 26.3795 75.7484 26.3795 59.3845C26.3795 43.0207 39.6308 29.7693 55.9947 29.7693C72.3586 29.7693 85.6099 43.0207 85.6099 59.3845C85.6099 75.7484 72.3586 88.9998 55.9947 88.9998Z" fill="#2E69E7"/>
                        </svg>
                <?php endif ?>
            </h4>

            <?php
            /**
             * Include info fields template.
             *
             * @since 1.0
             */
            require locate_template( 'templates/single-listing/previews/partials/info-fields.php' ) ?>
        </div>

    </a>
        <?php
        /**
         * Include head buttons template.
         *
         * @since 1.0
         */
        require locate_template( 'templates/single-listing/previews/partials/head-buttons.php' ) ?>
</div>

<?php
/**
 * Include footer sections template.
 *
 * @since 1.0
 */
require locate_template( 'templates/single-listing/previews/partials/footer-sections.php' ) ?>
