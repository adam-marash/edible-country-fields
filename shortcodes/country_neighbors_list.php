<?php
$neighbors_data = get_field_value($country_slug, 'neighbors');

// If it's an error message, return it
if (strpos($neighbors_data, 'Error:') === 0) {
    echo $neighbors_data;
    return;
}

// Clean up the data if it's wrapped in [neighbors list='...']
if (strpos($neighbors_data, '[neighbors list=') !== false) {
    // Extract content between quotes
    if (preg_match("/\[neighbors list='([^']*)'\]/", $neighbors_data, $matches)) {
        $neighbors_data = $matches[1];
    }
}

// If empty or no neighbors, display appropriate message
if (empty(trim($neighbors_data))) {
    echo 'None';
    return;
}

// Parse the pipe-delimited country codes
$neighbor_codes = explode('|', $neighbors_data);
if (empty($neighbor_codes)) {
    echo 'None';
    return;
}

// Build the links
$neighbor_links = array();

foreach ($neighbor_codes as $neighbor_slug) {
    $neighbor_slug = strtolower(trim($neighbor_slug));
    
    if (!empty($neighbor_slug)) {
        // Get the country name for this neighbor
        $neighbor_name = get_field_value($neighbor_slug, 'name');
        
        // If we couldn't get the name, use the slug as fallback
        if (strpos($neighbor_name, 'Error:') === 0) {
            $neighbor_name = $neighbor_slug;
        }
        
        // Create the link
        $link_url = '/send-fax/' . $neighbor_slug;
        $neighbor_links[] = '<a href="' . esc_url($link_url) . '">' . esc_html($neighbor_name) . '</a>';
    }
}

// Output the links separated by pipes
if (!empty($neighbor_links)) {
    echo implode(' | ', $neighbor_links);
} else {
    echo 'None';
}
?>