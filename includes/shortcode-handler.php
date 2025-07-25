<?php

defined('ABSPATH') || exit;

function register_dynamic_shortcodes() {
    // Get mappings and register each shortcode
    $shortcode_mappings = get_shortcode_mappings();
    
    foreach ($shortcode_mappings as $shortcode => $csv_field) {
        add_shortcode($shortcode, 'handle_country_shortcode');
    }
}

function handle_country_shortcode($atts, $content = null, $tag = '') {
    $country_slug = get_country_slug_from_post();
    if (empty($country_slug)) {
        return 'Error: Unable to determine country slug from post';
    }
    
    // Get shortcode to CSV field mappings
    $shortcode_mappings = get_shortcode_mappings();
    
    // Get the CSV field name for this shortcode
    if (!isset($shortcode_mappings[$tag])) {
        return 'Error: Shortcode "' . esc_html($tag) . '" not configured';
    }
    
    $field_name = $shortcode_mappings[$tag];
    
    // Check for special handler file first
    $special_handler = load_special_handler($tag, $country_slug);
    if ($special_handler !== false) {
        return $special_handler;
    }
    
    // Default: return raw field value
    return get_field_value($country_slug, $field_name);
}

function get_shortcode_mappings() {
    return array(
        'country_price' => 'price_first_pages',
        'country_price_next_page' => 'price_next_page',
        'country_name' => 'name',
        'country_name_prefix' => 'name_prefix',
        'country_code' => 'idd',
        'country_demonym_plural' => 'demonym',
        'country_language' => 'language',
        'country_currency' => 'currency_name',
        'country_currency_symbol' => 'currency_symbol',
        'country_currency_code' => 'currency_code',
        'country_region' => 'region',
        'country_status' => 'status',
        'country_area_codes_table' => 'area_code_table',
        'country_neighbors_list' => 'neighbors'
    );
}

function get_country_slug_from_post() {
    global $post;
    
    if (!$post) {
        return '';
    }
    
    return trim($post->post_name);
}

function load_special_handler($shortcode_name, $country_slug) {
    $shortcode_file = ECF_PLUGIN_DIR . 'shortcodes/' . $shortcode_name . '.php';
    
    if (!file_exists($shortcode_file)) {
        return false;
    }
    
    ob_start();
    include $shortcode_file;
    $output = ob_get_clean();
    
    return $output;
}