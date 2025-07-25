<?php

defined('ABSPATH') || exit;

function fetch_country_data() {
    $cached_data = get_cached_data();
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    $google_sheets_url = get_option('ecf_google_sheets_url', '');
    
    if (empty($google_sheets_url)) {
        error_log('ECF: No Google Sheets URL configured in settings');
        return false;
    }
    
    $response = wp_remote_get($google_sheets_url, array(
        'timeout' => 30,
        'headers' => array(
            'User-Agent' => 'WordPress/' . get_bloginfo('version')
        )
    ));
    
    if (is_wp_error($response)) {
        error_log('ECF: Failed to fetch Google Sheets data: ' . $response->get_error_message());
        return false;
    }
    
    $csv_content = wp_remote_retrieve_body($response);
    if (empty($csv_content)) {
        error_log('ECF: Empty CSV content received from Google Sheets');
        return false;
    }
    
    $parsed_data = parse_csv_data($csv_content);
    if ($parsed_data !== false) {
        cache_data($parsed_data);
    }
    
    return $parsed_data;
}

function get_cached_data() {
    return get_transient('country_data_cache');
}

function cache_data($data) {
    $result = set_transient('country_data_cache', $data, 0); // Never expires automatically
    if ($result) {
        update_option('ecf_cache_last_updated', time());
    }
    return $result;
}

function parse_csv_data($csv_content) {
    $lines = str_getcsv($csv_content, "\n");
    if (empty($lines)) {
        return false;
    }
    
    $header = str_getcsv(array_shift($lines));
    if (empty($header)) {
        error_log('ECF: Empty CSV header');
        return false;
    }
    
    // Find the slug column index
    $slug_column_index = array_search('slug', $header);
    if ($slug_column_index === false) {
        error_log('ECF: CSV must contain a "slug" column');
        return false;
    }
    
    $data = array();
    
    foreach ($lines as $line) {
        $row = str_getcsv($line);
        if (count($row) >= count($header)) {
            $country_slug = trim($row[$slug_column_index]);
            
            if (!empty($country_slug)) {
                if (!isset($data[$country_slug])) {
                    $data[$country_slug] = array();
                }
                
                // Map all columns as fields (including the slug column)
                for ($i = 0; $i < count($header); $i++) {
                    $field_name = $header[$i];
                    $field_value = isset($row[$i]) ? trim($row[$i]) : '';
                    $data[$country_slug][$field_name] = $field_value;
                }
            }
        }
    }
    
    return $data;
}

function get_field_value($country_slug, $field_name) {
    $data = fetch_country_data();
    
    if ($data === false) {
        return 'Error: Unable to fetch country data';
    }
    
    // Make country slug lookup case insensitive
    $country_slug_lower = strtolower($country_slug);
    $found_country_slug = null;
    
    foreach (array_keys($data) as $existing_slug) {
        if (strtolower($existing_slug) === $country_slug_lower) {
            $found_country_slug = $existing_slug;
            break;
        }
    }
    
    if ($found_country_slug === null) {
        return 'Error: Country slug "' . esc_html($country_slug) . '" not found';
    }
    
    if (!isset($data[$found_country_slug][$field_name])) {
        return 'Error: Field "' . esc_html($field_name) . '" not available for country "' . esc_html($country_slug) . '"';
    }
    
    $field_value = $data[$found_country_slug][$field_name];
    
    // Return empty string if field is empty, rather than showing empty content
    return trim($field_value) === '' ? '' : $field_value;
}