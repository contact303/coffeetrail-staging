<?php

 function add_similar_carts_single_listing_page() {

    if ( ! is_singular( 'job_listing' ) ) {
        return;
    }

    wp_enqueue_script( 'mylisting-owl' ); 
    wp_enqueue_script( 'mylisting-background-carousel' ); 
    wp_enqueue_script( 'mylisting-listing-feed-carousel' ); 

    $main_listing_id = get_the_ID();
    $main_listing_location = [ get_post_meta( $main_listing_id, '_latitude', true ), get_post_meta( $main_listing_id, '_longitude', true ) ];
    
    $main_listing_instance = \MyListing\Src\Listing::get( $main_listing_id );
    if ( ! $main_listing_instance instanceof \MyListing\Src\Listing ) {
        return;
    }

    if ( $main_listing_instance && $main_listing_instance->type->get_slug() !== 'cc' ) {
        return;
    }

    $main_listing_priority = $main_listing_instance->get_priority();
    $assoc_array_by_location = get_assoc_array_of_carts_with_location();
    $location_filtered_carts = [];

    // Check if we have valid location data (from staging version)
    if ( empty( $main_listing_location[0] ) || empty( $main_listing_location[1] ) ) {
        return;
    }

    $max_km = 100;
    foreach ( $assoc_array_by_location as $ID => $location ) {
        // Skip listings with missing or invalid coordinates
        if ( empty( $location['_latitude'] ) || empty( $location['_longitude'] ) || 
             ! is_numeric( $location['_latitude'] ) || ! is_numeric( $location['_longitude'] ) ) {
            continue;
        }
        
        $distance = coordinatesToDistance( $main_listing_location[0], $main_listing_location[1], $location['_latitude'], $location['_longitude'] );
        if( $distance <= $max_km ) {
            $location_filtered_carts[$ID] = $distance;
        }
    }

    // sort Array by Distance
    asort($location_filtered_carts);

    if($main_listing_priority != 0) {
        // paid listings
    }
    else {

        echo '<div class="ct-closest-carts" style="width:100%">';

            /**
             * Carousel layout using the listing feed template (from staging version)
            */

            $listings['html'] = [];
            $count = 1; $max_iterations = 6;
            foreach( $location_filtered_carts as $id => $distance ) {

                if( $count > $max_iterations ) {
                    break;
                }
                $listings['html'][] = '<div> ' . \MyListing\get_preview_card( $id ) . ' </div>';

                ++$count;
            }
            $listings['html'] = implode( '', $listings['html'] );
            $data = [
                'template'      => 'carousel', // or 'grid'
                'hide_priority' => false, // or true
                'owl_m'         => 1,     // slides to show on mobile
                'owl_t'         => 2,     // slides to show on tablet
                'owl_d'         => 3,     // slides to show on desktop
                'owl_speed'     => 5,   // carousel speed (seconds)
                'owl_loop'      => true,  // loop carousel
                'owl_autoplay'  => true,  // autoplay carousel
                'invert_nav'    => false, // invert nav style
                'nav_mode'      => 'nav', // nav mode (e.g., 'nav' or other)
            ];

            ?>
            <section class="i-section listing-feed-2 <?php echo $data['hide_priority'] ? 'hide-priority' : '' ?>">
                <div class="container">
                    <div class="row section-body">
                        <div class="owl-carousel listing-feed-carousel c27-owl-nav" owl-mobile="<?php echo $data['owl_m'] ?: 1 ?>" owl-tablet="<?php echo $data['owl_t'] ?: 2 ?>" owl-desktop="<?php echo $data['owl_d'] ?: 3 ?>" owl-speed="<?php echo $data['owl_speed'] ?: 2.5 ?>" owl-loop="<?php echo $data['owl_loop'] ? true : false ?>" owl-autoplay="<?php echo $data['owl_autoplay'] ? true : false ?>" nav-style="<?php echo $data['invert_nav'] ? 'light':'' ?>" nav-mode="<?php echo $data['nav_mode'] ?: 'nav' ?>">
                            <?php echo $listings['html'] ?? '' ?>

                        </div>
                    </div>
                </div>
            </section>
            <?php

            /**
             * legacy grid layout (commented out)
            */

            // $count = 1; $max_iterations = 3;
            // foreach( $location_filtered_carts as $id => $distance ) {

            //     if( $count > $max_iterations ) {
            //         break;
            //     }

            //     echo "
            //         <div data-cart-id='$id' class='col-lg-4 col-md-4 col-sm-4 col-xs-12 grid-item'>

            //             " . \MyListing\get_preview_card( $id ) . "

            //         </div>
            //     ";

            //     ++$count;

            // }
            
        echo "</div>";

        ?>

            <script>

                document.addEventListener('DOMContentLoaded', () => {

                    const cartsObjectByLocation = <?php echo json_encode( $assoc_array_by_location ); ?>;
                    const mainListingLocation = <?php echo json_encode( $main_listing_location ); ?>;
                    const similiarListingNode = document.querySelector('.similar-listings .container .section-body');
                    const closestCarts = document.querySelector('.ct-closest-carts');

                    if (similiarListingNode && closestCarts) {
                        similiarListingNode.innerHTML = '';
                        similiarListingNode.appendChild(closestCarts);
                        closestCarts.style.position = "relative";
                    }

                });

            </script>

        <?php

    }
    
}
add_action( 'wp_footer', 'add_similar_carts_single_listing_page' );

function get_assoc_array_of_carts_with_location() {

    $args = array(
        'post_type'      => 'job_listing',    
        'posts_per_page' => 100, // Limit to prevent memory issues on live servers
        'post_status'    => 'publish',         
        'meta_query'     => array(
            'relation' => 'AND',               
            array(
                'key'     => '_case27_listing_type',
                'value'   => 'cc',
                'compare' => '='             
            ),
            array(
                'key'     => '_featured',
                'value'   => '0',
                'compare' => '!='            
            ),
        )
    );
    
    $job_listings = new WP_Query($args);
    $listing_data = [];
    
    // Check if there are any posts
    if ($job_listings->have_posts()) {

        while ($job_listings->have_posts()) {

            $job_listings->the_post();
            $post_id = get_the_ID();
    
            $longitude = get_post_meta($post_id, '_longitude', true);
            $latitude = get_post_meta($post_id, '_latitude', true);
    
            // Only add if we have valid coordinates
            if ( ! empty( $latitude ) && ! empty( $longitude ) && 
                 is_numeric( $latitude ) && is_numeric( $longitude ) ) {
                $listing_data[$post_id] = [
                    '_latitude' => $latitude,
                    '_longitude'  => $longitude,
                ];
            }

        }

    }
    
    wp_reset_postdata();

    return $listing_data;

}

function coordinatesToDistance($lat1, $lng1, $lat2, $lng2) {

    // Earth radius in kilometers
    $R = 6371;

    // Difference in coordinates
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lng2 - $lng1);

    // Haversine formula
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $d = $R * $c;

    return $d;
}