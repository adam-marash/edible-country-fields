<?php

defined('ABSPATH') || exit;

function ecf_get_active_countries() {
    $data = fetch_country_data();
    
    if ($data === false) {
        return array();
    }
    
    $active_countries = array();
    foreach ($data as $slug => $fields) {
        // Check if country is marked as active
        if (isset($fields['active']) && strtolower(trim($fields['active'])) === 'true') {
            $active_countries[$slug] = $fields;
        }
    }
    
    return $active_countries;
}

function ecf_create_country_post($country_data) {
    $slug = key($country_data); // Get the slug (array key)
    $fields = current($country_data); // Get the field data
    
    // Get country name for title
    $country_name = isset($fields['name']) ? $fields['name'] : $slug;
    $title = $country_name;
    
    // Basic post content with shortcodes
    $content = '[country_name] | [country_currency] | [country_price]';
    
    $post_data = array(
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'country'
    );
    
    // Check if post already exists
    $existing_post = get_page_by_path($slug, OBJECT, 'country');
    if ($existing_post) {
        // Update existing post
        $post_data['ID'] = $existing_post->ID;
        return wp_update_post($post_data);
    } else {
        // Create new post
        return wp_insert_post($post_data);
    }
}

function ecf_queue_all_country_posts() {
    if (!function_exists('as_schedule_single_action')) {
        error_log('ECF: Action Scheduler not available');
        return 0;
    }
    
    $active_countries = ecf_get_active_countries();
    $queued_count = 0;
    
    // Queue individual job for each active country
    foreach ($active_countries as $slug => $fields) {
        $country_data = array($slug => $fields);
        
        as_schedule_single_action(
            time(), // Schedule immediately
            'ecf_create_country_post_job',
            array($country_data),
            'ecf_country_posts'
        );
        
        $queued_count++;
    }
    
    // Also queue cleanup job to run after all posts are created
    as_schedule_single_action(
        time(), // Schedule immediately - Action Scheduler handles job ordering
        'ecf_cleanup_orphaned_posts_job',
        array(),
        'ecf_country_posts'
    );
    
    return $queued_count;
}

function ecf_cleanup_orphaned_posts() {
    $active_countries = ecf_get_active_countries();
    $active_slugs = array_keys($active_countries);
    
    // Get all existing country posts
    $existing_posts = get_posts(array(
        'post_type' => 'country',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids'
    ));
    
    $deleted_count = 0;
    
    foreach ($existing_posts as $post_id) {
        $post_slug = get_post_field('post_name', $post_id);
        
        // If this post's slug is not in the active countries list, delete it
        if (!in_array($post_slug, $active_slugs)) {
            if (wp_delete_post($post_id, true)) {
                $deleted_count++;
                error_log('ECF: Deleted orphaned country post ID ' . $post_id . ' (slug: ' . $post_slug . ')');
            }
        }
    }
    
    error_log('ECF: Cleanup completed. Deleted ' . $deleted_count . ' orphaned posts.');
    return $deleted_count;
}
?>