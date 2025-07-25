<?php
/**
 * Plugin Name: Edible Country Fields
 * Description: Dynamically populates country-specific data using shortcodes, with data sourced from Google Sheets.
 * Version: 1.0.4
 * Author: Edible
 */

defined('ABSPATH') || exit;

define('ECF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ECF_PLUGIN_URL', plugin_dir_url(__FILE__));

function ecf_init() {
    require_once ECF_PLUGIN_DIR . 'includes/data-manager.php';
    require_once ECF_PLUGIN_DIR . 'includes/shortcode-handler.php';
    require_once ECF_PLUGIN_DIR . 'includes/post-generator.php';
    
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
    $post_id = ecf_create_country_post($country_data);
    
    if ($post_id) {
        error_log('ECF: Created/updated country post ID ' . $post_id);
    } else {
        error_log('ECF: Failed to create country post for ' . print_r($country_data, true));
    }
}

function ecf_process_cleanup_job() {
    $deleted_count = ecf_cleanup_orphaned_posts();
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