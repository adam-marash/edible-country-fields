<?php
/**
 * Plugin Name: Edible Country Fields
 * Description: Dynamically populates country-specific data using shortcodes, with data sourced from Google Sheets.
 * Version: 1.0.0
 * Author: Edible
 */

defined('ABSPATH') || exit;

define('ECF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ECF_PLUGIN_URL', plugin_dir_url(__FILE__));

function ecf_init() {
    require_once ECF_PLUGIN_DIR . 'includes/data-manager.php';
    require_once ECF_PLUGIN_DIR . 'includes/shortcode-handler.php';
    
    if (is_admin()) {
        require_once ECF_PLUGIN_DIR . 'includes/admin.php';
        ecf_admin_init();
    }
    
    register_dynamic_shortcodes();
    ecf_register_action_scheduler_hooks();
}

function ecf_register_action_scheduler_hooks() {
    add_action('ecf_create_country_post_job', 'ecf_process_country_post_job');
    add_action('ecf_cleanup_orphaned_posts_job', 'ecf_process_cleanup_job');
}

function ecf_process_country_post_job($country_data) {
    // Include admin functions if not already loaded
    if (!function_exists('ecf_create_country_post')) {
        require_once ECF_PLUGIN_DIR . 'includes/admin.php';
    }
    
    $post_id = ecf_create_country_post($country_data);
    
    if ($post_id) {
        error_log('ECF: Created/updated country post ID ' . $post_id);
    } else {
        error_log('ECF: Failed to create country post for ' . print_r($country_data, true));
    }
}

function ecf_process_cleanup_job() {
    // Include admin functions if not already loaded
    if (!function_exists('ecf_get_active_countries')) {
        require_once ECF_PLUGIN_DIR . 'includes/admin.php';
    }
    
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
    
    error_log('ECF: Cleanup job completed. Deleted ' . $deleted_count . ' orphaned posts.');
}

function ecf_activation() {
    delete_transient('country_data_cache');
}

function ecf_deactivation() {
    delete_transient('country_data_cache');
}

register_activation_hook(__FILE__, 'ecf_activation');
register_deactivation_hook(__FILE__, 'ecf_deactivation');

add_action('init', 'ecf_init');