<?php

// Load necessary files
require_once get_stylesheet_directory() . '/includes/explore_terms_priority.php';
require_once get_stylesheet_directory() . '/includes/similar_carts_single_listing.php';
require_once get_stylesheet_directory() . '/includes/single_listing_terms_priority.php';
require_once get_stylesheet_directory() . '/includes/ct-flow/ct-flow.php';

add_action('wp_footer', function() {
    if (!is_page(absint(c27()->get_setting('general_add_listing_page')))) {
        $pid = absint(c27()->get_setting('general_add_listing_page'));
        echo '<!-- FAIL: is_page check. Setting returns: ' . $pid . ', current post_id: ' . get_the_ID() . ' -->';
    } elseif (!is_user_logged_in()) {
        echo '<!-- FAIL: not logged in -->';
    } elseif (!file_exists(CT_FLOW_DIR . '/templates/wizard-shell.php')) {
        echo '<!-- FAIL: wizard-shell.php missing at: ' . CT_FLOW_DIR . '/templates/wizard-shell.php -->';
    } else {
        echo '<!-- ALL CONDITIONS PASS -->';
    }
});

// Tag visibility (admin + explore term queries). 
add_action(
	'after_setup_theme',
	static function (): void {
		require_once get_stylesheet_directory() . '/includes/tag_visibility_options.php';
	},
	20
);


// friday and saturday tags sync with cart workhours (real-time on meta write + daily batch).
add_action(
	'after_setup_theme',
	static function (): void {
		require_once get_stylesheet_directory() . '/includes/work-hours-tag-sync.php';
	},
	21
);


// Enqueue child theme style.css
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'child-style',
        get_stylesheet_uri(),
        array(),
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    if ( is_rtl() ) {
    	wp_enqueue_style( 'mylisting-rtl', get_template_directory_uri() . '/rtl.css', [], wp_get_theme()->get('Version') );
    }
}, 500 );

// Happy Coding :)


/**
 * Hide “Similar Listings” section on paid job_listing pages.
 *
 * Customization by Yanir.
 *
 * Checks if we’re on a single job_listing and its priority is non-zero,
 * then injects a small JS snippet to hide the .similar-listings container
 * as soon as the DOM is ready.
 *
 * @return void
 */
function david_hide_similar_listings_listingpage() {
    // רק בדפי תג יחיד מסוג 'job_listing'
    if ( ! is_singular( 'job_listing' ) ) {
        return;
    }

    // קבל את אובייקט ההרשמה ובדוק תקינות
    $listing = \MyListing\Src\Listing::get( get_the_ID() );
    if ( ! $listing instanceof \MyListing\Src\Listing ) {
        return;
    }

    // אם זו רשימה בתשלום (priority ≠ 0), נסיר את הסקשן
    if ( 0 === $listing->get_priority() ) {
        return;
    }

    // הזרקת JS ל-footer
    ?>
    <script type="text/javascript">
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            var similar = document.querySelector('.similar-listings');
            if ( similar ) {
                similar.style.display = 'none';
            }
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'david_hide_similar_listings_listingpage', 20 );

/**
 * Hide buttons on free single listing pages.
 *
 * Customization by Ori.
 * @return void
 */
function hide_buttons_on_free_tier_single_listing_pages() {
    if ( ! is_singular( 'job_listing' ) ) {
        return;
    }

    $listing = \MyListing\Src\Listing::get( get_the_ID() );
    if ( ! $listing instanceof \MyListing\Src\Listing ) {
        return;
    }

    //only on free job_listing pages
    if ( 0 !== $listing->get_priority() ) {
        return;
    }

    ?>
    <script type="text/javascript">
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            const cartElementsToTrack = [];

            // Find elements in listing-main-buttons
            const mainButtonsContainers = document.querySelectorAll('.listing-main-buttons ul');
            mainButtonsContainers.forEach(function(mainButtonsContainer) {
                const mainButtons = mainButtonsContainer.querySelectorAll('li[id^="cta-"]');
                cartElementsToTrack.push(...mainButtons);
            });
            
            // Find elements in quick-listing-actions
            const quickActionsContainers = document.querySelectorAll('.quick-listing-actions ul');
            quickActionsContainers.forEach(function(quickActionsContainer) {
                const quickActions = quickActionsContainer.querySelectorAll('li[id^="qa-"]');
                // Filter out navigation elements
                const actionButtons = Array.from(quickActions).filter(el => 
                    !['cts-prev', 'cts-next'].includes(el.className)
                );
                cartElementsToTrack.push(...actionButtons);
            });

            // Hide buttons
            cartElementsToTrack.forEach(element => {
                element.style.display = 'none';
            });

            // Function to hide job description block
            function hideJobDescriptionBlock() {
                const jobDescriptionBlock = document.querySelector('.col-md-12.block-type-text.block-field-job_description');
                if (jobDescriptionBlock) {
                    jobDescriptionBlock.style.display = 'none';
                }
            }

            // Try to hide immediately
            hideJobDescriptionBlock();

            // Set up mutation observer to watch for dynamically loaded job description block
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Check if job description block was added
                        const jobDescriptionBlock = document.querySelector('.col-md-12.block-type-text.block-field-job_description');
                        if (jobDescriptionBlock && jobDescriptionBlock.style.display !== 'none') {
                            hideJobDescriptionBlock();
                        }
                    }
                });
            });

            // Start observing the document body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    })();
    </script>
    <?php

}
add_action( 'wp_footer', 'hide_buttons_on_free_tier_single_listing_pages', 20 );


/**
 * Adds banners for free preview cards
 *
 * Customization by Ori.
 *
 * @return void
 */
function add_banner_on_free_preview_cards() {
    if (! is_page( 'map-explore' ) && ! is_singular( 'job_listing' )) {
        return;
    }
    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            const observer = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                // Process only added nodes
                for (const node of mutation.addedNodes) {
                    // Skip non-element nodes
                    if (node.nodeType !== 1) continue;
                    
                    // Check if added node is a target card
                    if (node.matches('.listing-preview.type-cc:not(.c27-verified)')) {
                    addWarningBanner(node);
                    }
                    
                    // Check for target cards within added node
                    const cards = node.querySelectorAll('.listing-preview.type-cc:not(.c27-verified)');
                    for (const card of cards) {
                    addWarningBanner(card);
                    }
                }
                }
            });

            // Add warning banner to element if needed
            function addWarningBanner(card) {
                if (card.querySelector('.listing-details-3, .c27-footer-section')) return;
                
                const detailsDiv = document.createElement('div');
                detailsDiv.className = 'listing-details-3 c27-footer-section';
                detailsDiv.innerHTML = `
                <ul class="details-list no-list-style">
                    <li><i class="mi info_outline"></i><span>המידע אינו מאומת עם העסק</span></li>
                </ul>`;
                card.appendChild(detailsDiv);
            }

            // Initial processing of existing cards
            const existingCards = document.querySelectorAll('.listing-preview.type-cc:not(.c27-verified)');
            if (existingCards.length > 0) {
                existingCards.forEach(card => {
                addWarningBanner(card);
                });
            }

            // Start observing document body
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });

    </script>
    <?php
}
add_action( 'wp_footer', 'add_banner_on_free_preview_cards' );

/**
 * Disable all WooCommerce styles and scripts on non‐shop pages.
 *
 * Customization by Yanir.
 *
 * Improves front‐end performance by removing WooCommerce assets
 * when not on WooCommerce, cart or checkout pages.
 *
 * @return void
 */
function yanir_dequeue_woocommerce_assets() {
    // Only dequeue when WooCommerce is active and we're not on shop/cart/checkout
    if ( function_exists( 'is_woocommerce' ) && ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
        // Handles for WooCommerce styles to remove
        $styles = [
            'woocommerce-general',
            'woocommerce-layout',
            'woocommerce-smallscreen',
            'woocommerce_frontend_styles',
            'woocommerce_fancybox_styles',
            'woocommerce_chosen_styles',
            'woocommerce_prettyPhoto_css',
        ];
        foreach ( $styles as $handle ) {
            wp_dequeue_style( $handle );
        }

        // Handles for WooCommerce scripts to remove
        $scripts = [
            'wc_price_slider',
            'wc-single-product',
            'wc-add-to-cart',
            'wc-cart-fragments',
            'wc-checkout',
            'wc-add-to-cart-variation',
            'wc-cart',
            'wc-chosen',
            'woocommerce',
            'prettyPhoto',
            'prettyPhoto-init',
            'jquery-blockui',
            'jquery-placeholder',
            'fancybox',
            'jqueryui',
        ];
        foreach ( $scripts as $handle ) {
            wp_dequeue_script( $handle );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'yanir_dequeue_woocommerce_assets', 99 );







/**
 * Add "On the Way" link to explore page sidebar.
 *
 * Customization by Yanir.
 *
 * Inserts a new div with a link and SVG arrow next to the
 * "קרוב לכביש" filter on the map‐explore page sidebar.
 *
 * @return void
 */
function yanir_add_link_on_the_way_to_map_explore_page() {
    if ( ! is_page( 'map-explore' ) ) {
        return;
    }
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var exploreSidebar = document.getElementById('finderSearch');
        if ( exploreSidebar ) {
            var filters = exploreSidebar.querySelectorAll('div.checkboxes-filter');
            filters.forEach(function (filter) {
                var label = filter.querySelector('label');
                if ( label && label.innerText === 'קרוב לכביש' ) {
                    var container = document.createElement('div');
                    container.style.display = 'flex';
                    container.style.justifyContent = 'space-between';
                    container.innerHTML = label.outerHTML;

                    var arrowLeft = ''
                        + '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 16 16">'
                        + '<polygon points="8,16 0,8 16,8 8,16" fill="#209C62" '
                        + 'stroke="#209C62" stroke-linecap="round" transform="rotate(90,8,8)" '
                        + 'stroke-linejoin="round"/>'
                        + '</svg>';

                    container.innerHTML += ''
                        + '<a href="https://coffeetrail.co.il/on-the-way/" '
                        + 'style="font-size:14px;font-weight:400;color:var(--accent);margin-bottom:-4px;">'
                        + 'מעבר לחיפוש לפי מוצא ויעד ' + arrowLeft
                        + '</a>';

                    label.remove();
                    filter.prepend(container);
                }
            });
        }
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'yanir_add_link_on_the_way_to_map_explore_page', 20 );









/**
 * Add TikTok to socials links.
 *
 * Customization by Yanir.
 *
 * @param array $links Existing social link definitions.
 * @return array Modified list including TikTok.
 */
add_filter( 'mylisting\links-list', function( $links ) {
    // Add new link
    $links['TikTok'] = [
        'name' => 'TikTok',
        'key' => 'TikTok',
        'icon' => 'fab fa-tiktok',
        'color' => '#ff0050',
    ];
         return $links;
} );











/**
 * Add close button to each popup on the explore page map.
 *
 * Customization by Ori.
 *
 * Waits for the Leaflet popup pane to be added, then observes for new popups
 * and injects a close‐button link (<a>) into each popup. Clicking it simulates
 * a map click to close the popup.
 *
 * @return void
 */
function ori_add_close_button_to_explorer_popups() {
    if ( ! is_page( 'map-explore' ) ) {
        return;
    }
    ?>
    <script type="text/javascript">
    (function() {
        document.addEventListener("DOMContentLoaded", function() {
            function waitForPopupPane(callback) {
                var interval = setInterval(function() {
                    var pane = document.querySelector('.leaflet-pane.leaflet-popup-pane');
                    if ( pane ) {
                        clearInterval(interval);
                        callback(pane);
                    }
                }, 100);
            }

            function enableCloseButtons(popupPane) {
                var observer = new MutationObserver(function(mutationsList) {
                    mutationsList.forEach(function(mutation) {
                        if ( mutation.type === 'childList' ) {
                            mutation.addedNodes.forEach(function(node) {
                                if ( node.classList && node.classList.contains('leaflet-popup') ) {
                                    if ( ! node.querySelector('.leaflet-popup-close-button') ) {
                                        var closeButton = document.createElement('a');
                                        closeButton.href = '#';
                                        closeButton.className = 'leaflet-popup-close-button';
                                        closeButton.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            var mapContainer = popupPane.closest(".c27-map");
                                            if ( mapContainer ) {
                                                mapContainer.dispatchEvent(new MouseEvent('click', { bubbles: true }));
                                            }
                                        });
                                        node.appendChild(closeButton);
                                    }
                                }
                            });
                        }
                    });
                });

                observer.observe(popupPane, { childList: true });
            }

            waitForPopupPane(enableCloseButtons);
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'ori_add_close_button_to_explorer_popups', 20 );






/**
 * Fix open\closed status class not being added to the preview card with caching enabled.
 * Customization by Ori
 */
add_filter( 'mylisting/get-preview-card-cache', function( $html, $listing_id ) {
    // Get the listing object and its open/closed status.
    $listing = \MyListing\Src\Listing::get( $listing_id );
    if ( ! $listing || ! $listing->schedule ) {
        return $html;
    }

    $status = $listing->schedule->get_status();
    $status_classes = sprintf( 'open-status listing-status-%s', esc_attr( $status ) );

    // Inject classes into the head button element.
    $html = str_replace(
        'class="lf-head-btn', // Target the button�s class attribute
        sprintf( 'class="lf-head-btn %s', $status_classes ), // Add missing classes
        $html
    );

    return $html;
}, 30, 2 );





/**
 * Strip special chars from the search term and database title when searching for listings.
 * Useful for instance: searching for ??? should still match ??"? ???.
 *
 * Customization by Ori.
 */

// 1) Sanitize the 'title_search' query var before the WP_Query runs.
add_filter( 'pre_get_posts', function( \WP_Query $query ) {
    if ( ! is_admin() && ! empty( $query->query_vars['title_search'] ) ) {
        $chars = ['"', "'", '-', ',', '?', '׳'];
        $sanitized = str_replace( $chars, '', (string) $query->query_vars['title_search'] );
        
        // Normalize multiple spaces to single space
        $sanitized = preg_replace( '/\s+/', ' ', $sanitized );
        $sanitized = trim( $sanitized );
        
        $query->query_vars['title_search'] = $sanitized;
    }
    return $query;
}, 5 );

// 2) Adjust the SQL WHERE clause so the DB title is stripped of the same chars before matching.
add_filter( 'posts_where', function( $where, \WP_Query $query ) {
    global $wpdb;
    if ( ! is_admin() && ! empty( $query->query_vars['title_search'] ) ) {
        $chars = ['"', "'", '-', ',', '?', '׳'];
        $column = $wpdb->posts . '.post_title';

        // Build a nested REPLACE() chain to strip each char.
        $sanitized_col = $column;
        foreach ( $chars as $char ) {
            $sanitized_col = "REPLACE({$sanitized_col}, '" . esc_sql( $char ) . "', '')";
        }
        
        // Normalize multiple spaces to single space in MySQL
        // We need to do this multiple times to catch all consecutive spaces
        $sanitized_col = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE({$sanitized_col}, '  ', ' '), '  ', ' '), '  ', ' '), '  ', ' '))";

        // Use preg_replace to swap the raw column name with our sanitized expression.
        $pattern = '/' . preg_quote( $column, '/' ) . '(?![^(]*\))/';
        $where   = preg_replace( $pattern, $sanitized_col, $where );
    }
    return $where;
}, 31, 2 );


/**
 * Shortcode to display content only if listing has a specific tag.
 * Adds a marker for JavaScript targeting.
 * 
 * Usage: [if_has_tag tag="your-tag-slug"]Your message here[/if_has_tag]
 *
 * @return string
 */
function coffeetrail_conditional_tag_content( $atts, $content = null ) {
    // Return empty if no content provided
    if ( empty( $content ) ) {
        return '<span class="no-tag-content"></span>';
    }
    
    // Get attributes
    $atts = shortcode_atts( [
        'tag' => '', // Required: tag slug to check for
    ], $atts );
    
    // Return empty if no tag specified
    if ( empty( $atts['tag'] ) ) {
        return '<span class="no-tag-content"></span>';
    }
    
    // Check if we're on a single listing page
    if ( ! is_singular( 'job_listing' ) ) {
        return '<span class="no-tag-content"></span>';
    }
    
    // Get the current listing
    $listing = \MyListing\Src\Listing::get( get_the_ID() );
    if ( ! $listing instanceof \MyListing\Src\Listing ) {
        return '<span class="no-tag-content"></span>';
    }
    
    // Check if the listing has the specified tag
    if ( has_term( $atts['tag'], 'case27_job_listing_tags', $listing->get_id() ) ) {
        // Tag found - return content
        $content = do_shortcode( $content );
        return wpautop( $content );
    }
    
    // Tag not found - return hidden marker
    return '<span class="no-tag-content"></span>';
}
add_shortcode( 'if_has_tag', 'coffeetrail_conditional_tag_content' );


/**
 * Shortcode to display content only if a checkbox field has a specific value checked.
 * 
 * Usage: [if_checkbox_checked field="your-field-key" value="1"]Your content here[/if_checkbox_checked]
 *
 * @return string
 */
function coffeetrail_conditional_checkbox_content( $atts, $content = null ) {
    // Return empty if no content provided
    if ( empty( $content ) ) {
        return '<span class="no-checkbox-content"></span>';
    }
    
    // Get attributes
    $atts = shortcode_atts( [
        'field' => '', // Required: checkbox field key
        'value' => '1', // Default to '1' for yes/no checkboxes
    ], $atts );
    
    // Return empty if no field specified
    if ( empty( $atts['field'] ) ) {
        return '<span class="no-checkbox-content"></span>';
    }
    
    // Check if we're on a single listing page
    if ( ! is_singular( 'job_listing' ) ) {
        return '<span class="no-checkbox-content"></span>';
    }
    
    // Get the current listing
    $listing = \MyListing\Src\Listing::get( get_the_ID() );
    if ( ! $listing instanceof \MyListing\Src\Listing ) {
        return '<span class="no-checkbox-content"></span>';
    }
    
    // Get the checkbox field value (always an array)
    $checkbox_value = $listing->get_field( $atts['field'] );
    
    // Checkbox values are stored as arrays, check if the specified value is in the array
    if ( is_array( $checkbox_value ) && in_array( $atts['value'], $checkbox_value, true ) ) {
        // Checkbox is checked - return content
        $content = do_shortcode( $content );
        return wpautop( $content );
    }
    
    // Checkbox not checked - return hidden marker
    return '<span class="no-checkbox-content"></span>';
}
add_shortcode( 'if_checkbox_checked', 'coffeetrail_conditional_checkbox_content' );

/**
 * Hide conditional tag and checkbox blocks that have no matching conditions.
 * Customization by Ori.
 */
function hide_empty_conditional_blocks() {
    if ( ! is_singular( 'job_listing' ) ) {
        return;
    }
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Find all blocks with conditional markers (tags or checkboxes)
        var markers = document.querySelectorAll('.no-tag-content, .no-checkbox-content');
        
        markers.forEach(function(marker) {
            // Find the parent block wrapper and hide it
            var block = marker.closest('[class*="block-type-"]');
            if (block) {
                block.style.display = 'none';
            }
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'hide_empty_conditional_blocks', 20 );

/**
 * Adds Pango badge to single listing page title
 * 
 * Displays a Pango verification badge next to the listing title on single listing pages
 * when the 'coupon_check' field is set to '1' (yes). Uses inline SVG for performance
 * and CDN compatibility.
 *
 * Customization by Ori.
 *
 * @return void
 */
function add_pango_badge_single_listing() {
    if ( ! is_singular('job_listing') ) {
        return;
    }
    
    $listing = \MyListing\Src\Listing::get( get_post() );
    if ( ! $listing ) {
        return;
    }
    
    $coupon_check = $listing->get_field('coupon_check');
    $show_badge = ! empty( $coupon_check ) && in_array( '1', (array) $coupon_check, true );
    
    if ( ! $show_badge ) {
        return;
    }
    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            function addPangoBadge() {
                const titleElements = document.querySelectorAll('.single-job-listing h1.case27-primary-text');
                
                titleElements.forEach(function(titleEl) {
                    if (titleEl.querySelector('.pango-badge-icon')) return;
                    
                    const badge = document.createElement('span');
                    badge.className = 'pango-badge tooltip-element';
                    badge.style.cssText = 'margin-left: 8px; display: inline-block;';
                    badge.innerHTML = `
                        <svg class="pango-badge-icon" width="21" height="21" viewBox="0 0 116 160" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle; margin-bottom: 4px;">
                            <path d="M31.9265 123.269H0V159.818H31.9265V123.269Z" fill="#2E69E7"/>
                            <path d="M98.1833 16.7645C87.7671 6.40997 73.4372 0 57.5664 0C25.7631 0 0 25.7631 0 57.5663V115.133H57.5664C89.3696 115.133 115.133 89.3696 115.133 57.5663C115.133 41.6339 108.661 27.1807 98.1833 16.7645ZM55.9947 88.9998C39.6308 88.9998 26.3795 75.7484 26.3795 59.3845C26.3795 43.0207 39.6308 29.7693 55.9947 29.7693C72.3586 29.7693 85.6099 43.0207 85.6099 59.3845C85.6099 75.7484 72.3586 88.9998 55.9947 88.9998Z" fill="#2E69E7"/>
                        </svg>
                        <span class="tooltip-container">\u05DE\u05D0\u05D5\u05DE\u05EA \u05D1\u05E4\u05E0\u05D2\u05D5</span>
                    `;
                    
                    titleEl.appendChild(badge);
                });
            }
            
            addPangoBadge();
            setTimeout(addPangoBadge, 500);
            
            const observer = new MutationObserver(addPangoBadge);
            const mobileContainer = document.querySelector('.main-info-mobile');
            if (mobileContainer) {
                observer.observe(mobileContainer, { childList: true, subtree: true });
            }
        });
    </script>
    <?php
}
add_action( 'wp_footer', 'add_pango_badge_single_listing' );

/**
 * Add Tags filter dropdown to admin listings table
 */
add_action('restrict_manage_posts', 'add_tags_filter_dropdown', 11);
function add_tags_filter_dropdown() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'edit-job_listing') {
        // Get currently selected tag if any
        $selected = !empty($_GET['listing_tag']) ? get_term_by('slug', $_GET['listing_tag'], 'case27_job_listing_tags') : false;
        ?>
        <select class="custom-select" name="listing_tag" id="listing_tag_filter" 
                data-mylisting-ajax="true" 
                data-mylisting-ajax-url="mylisting_list_terms"
                data-mylisting-ajax-params="<?php echo c27()->encode_attr(['taxonomy' => 'case27_job_listing_tags', 'term-value' => 'slug']); ?>"
                placeholder="<?php echo esc_attr("\u{05D1}\u{05D7}\u{05E8} \u{05EA}\u{05D2}"); // ??? ?? - Select Tag ?>">
            <option></option>
            <?php if ($selected instanceof \WP_Term): ?>
                <option value="<?php echo esc_attr($selected->slug); ?>" selected="selected">
                    <?php echo esc_html($selected->name); ?>
                </option>
            <?php endif; ?>
        </select>
        <?php
    }
}

/**
 * Filter listings by selected tag in admin
 */
add_filter('parse_query', 'filter_listings_by_tag_admin');
function filter_listings_by_tag_admin($query) {
    global $typenow;
    
    // Only run on admin listings page
    if ($typenow !== 'job_listing' || !is_admin() || empty($_GET['listing_tag'])) {
        return $query;
    }
    
    // Add tax query for the selected tag
    $query->query_vars['tax_query'][] = [
        'taxonomy' => 'case27_job_listing_tags',
        'field' => 'slug',
        'terms' => sanitize_text_field($_GET['listing_tag']),
    ];
    
    return $query;
}

add_action(
	'after_setup_theme',
	static function (): void {
		require_once get_stylesheet_directory() . '/includes/filters/class-days-of-week-filter.php';
	},
	20
);
add_filter(
	'mylisting/listing-types/register-filters',
	static function ( array $filters ): array {
		$filters[] = \MyListing\Src\Listing_Types\Filters\Days_Of_Week::class;
		return $filters;
	}
);

/**
 * Draw a results radius on regional Explore searches by Eran.
 */
add_action(
    'wp_enqueue_scripts',
    static function (): void {
        if (!is_page('map-explore')) {
            return;
        }

        $relative_path = '/assets/js/ct-region-results-radius.js';
        $file_path     = get_stylesheet_directory() . $relative_path;

        if (!file_exists($file_path)) {
            return;
        }

        wp_enqueue_script(
            'ct-region-results-radius',
            get_stylesheet_directory_uri() . $relative_path,
            ['jquery'],
            (string) filemtime($file_path),
            true
        );
    },
    600
);