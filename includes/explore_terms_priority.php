<?php

    function enqueue_explore_terms_priority_scripts_ct() {
        // Check if the current page has the slug 'map-explore' if not, exit the function.
        if ( !is_page('map-explore') ) {
            return;
        }

        

        // Register and enqueue the script
        wp_register_script('explore-terms-priorities', get_stylesheet_directory_uri() . '/assets/js/explore_terms_priority.js?v=1.0.8', array('jquery'), null, true);
        wp_enqueue_script('explore-terms-priorities');
        
        // Get all the terms for the 'case27_job_listing_tags' taxonomy (Tags)
        $tags_terms = get_terms(array(
            'taxonomy' => 'case27_job_listing_tags',
            'hide_empty' => false,
        ));

        // Create an array design: TermSlug: Priority
        $tags_term_data = array();
        foreach ($tags_terms as $term) {
            $priority = get_term_meta($term->term_id, 'explore_priority', true);
            $tags_term_data[$term->slug] = $priority === '' ? '0' : $priority; // Use '0' if not set -- Term meta data accept only native numbers {1, 2, 3, ...} (By definition)
        }

        // Get all the terms for the 'road' taxonomy (Roads)
        $roads_terms = get_terms(array(
            'taxonomy' => 'road',
            'hide_empty' => false,
        ));

        // Create an array design: TermSlug: Priority
        $roads_term_data = array();
        foreach ($roads_terms as $term) {
            $priority = get_term_meta($term->term_id, 'explore_priority', true);
            $roads_term_data[$term->slug] = $priority === '' ? '0' : $priority; // Use '0' if not set -- Term meta data accept only native numbers {1, 2, 3, ...} (By definition)
        }

        // Localize the script with both arrays
        wp_localize_script('explore-terms-priorities', 'explore_terms', array(
            'tags' => $tags_term_data,
            'roads' => $roads_term_data,
        ));

    }
    add_action('wp_enqueue_scripts', 'enqueue_explore_terms_priority_scripts_ct');
    

?>