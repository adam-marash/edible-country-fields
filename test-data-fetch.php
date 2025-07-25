<?php
/**
 * Test script to verify Google Sheets CSV data fetching
 * Run this from WordPress admin or via WP-CLI to test data access
 */

// Include WordPress if running standalone
if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Include our data manager
require_once('includes/data-manager.php');

echo "<h2>Testing Google Sheets Data Fetch</h2>\n";

// Test direct URL access
$google_sheets_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSFO5B-kafItWU7N4o7dRVjG-eim0ymNo3eTD4czYa7DM10zeP7-ufTVMCOT0xToeHw-IFqfs9KYfuU/pub?gid=441532140&single=true&output=csv';

echo "<h3>1. Direct URL Test</h3>\n";
$response = wp_remote_get($google_sheets_url, array('timeout' => 30));

if (is_wp_error($response)) {
    echo "<p style='color: red;'>ERROR: " . $response->get_error_message() . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "<p>HTTP Status: " . $status_code . "</p>\n";
    
    if ($status_code === 200) {
        $csv_content = wp_remote_retrieve_body($response);
        echo "<p style='color: green;'>SUCCESS: Retrieved " . strlen($csv_content) . " bytes</p>\n";
        echo "<h4>First 500 characters of CSV:</h4>\n";
        echo "<pre>" . esc_html(substr($csv_content, 0, 500)) . "</pre>\n";
    } else {
        echo "<p style='color: red;'>HTTP Error: " . $status_code . "</p>\n";
    }
}

echo "<h3>2. Data Manager Test</h3>\n";

// Clear cache to force fresh fetch
delete_transient('country_data_cache');

$data = fetch_country_data();

if ($data === false) {
    echo "<p style='color: red;'>ERROR: fetch_country_data() returned false</p>\n";
} else {
    echo "<p style='color: green;'>SUCCESS: Data fetched and parsed</p>\n";
    echo "<p>Number of countries: " . count($data) . "</p>\n";
    
    echo "<h4>Available countries:</h4>\n";
    echo "<ul>\n";
    foreach (array_keys($data) as $country_slug) {
        $fields = array_keys($data[$country_slug]);
        echo "<li><strong>" . esc_html($country_slug) . "</strong>: " . implode(', ', array_map('esc_html', $fields)) . "</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h4>Sample data lookup tests:</h4>\n";
    $sample_tests = array(
        array('jordan', 'currency_name'),
        array('usa', 'currency_name'),
        array('invalid', 'currency_name'),
        array('jordan', 'invalid_field')
    );
    
    foreach ($sample_tests as $test) {
        $result = get_field_value($test[0], $test[1]);
        echo "<p><code>get_field_value('" . esc_html($test[0]) . "', '" . esc_html($test[1]) . "')</code> = " . esc_html($result) . "</p>\n";
    }
}
?>