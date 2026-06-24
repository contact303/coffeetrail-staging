<?php

    function single_listing_terms_priority() {
        if (!is_singular('job_listing')) return;

        wp_register_script('single-listing-terms-priority', get_stylesheet_directory_uri() . '/assets/js/single_listing_terms_priority.js', array('jquery'), null, true);
        wp_enqueue_script('single-listing-terms-priority');
        
        $tags_terms = get_terms(array(
            'taxonomy' => 'case27_job_listing_tags',
            'hide_empty' => false,
        ));

        if (is_wp_error($tags_terms) || empty($tags_terms)) {
            return; // Exit if no terms or an error occurred
        }
        // Create an array: TermSlug => Priority
        $tags_term_data = array();
        foreach ($tags_terms as $term) {
            $priority = get_term_meta($term->term_id, 'explore_priority', true);
            $tags_term_data[$term->slug] = $priority === '' ? '0' : $priority;
        }

        wp_localize_script('single-listing-terms-priority', 'termsData', array(
            'priorities' => $tags_term_data,
        ));


        
    }
    add_action('wp_enqueue_scripts', 'single_listing_terms_priority');
?>