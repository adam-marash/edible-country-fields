<?php
$area_codes_data = get_field_value($country_slug, 'area_code_table');

// If it's an error message, return it
if (strpos($area_codes_data, 'Error:') === 0) {
    echo $area_codes_data;
    return;
}

// Clean up the data if it's wrapped in [area_codes list='...']
if (strpos($area_codes_data, '[area_codes list=') !== false) {
    // Extract content between quotes
    if (preg_match("/\[area_codes list='([^']*)'\]/", $area_codes_data, $matches)) {
        $area_codes_data = $matches[1];
    }
}

// If it's "None", just display that
if (trim($area_codes_data) === 'None') {
    echo 'None';
    return;
}

// Parse the pipe-delimited data
$entries = explode(';', $area_codes_data);
if (empty($entries)) {
    echo 'None';
    return;
}

// Build the table
echo '<table class="area-codes-table" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
echo '<thead>';
echo '<tr style="background-color: #f5f5f5; border-bottom: 2px solid #ddd;">';
echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">City</th>';
echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Area Code</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($entries as $entry) {
    $parts = explode('|', $entry);
    if (count($parts) >= 2) {
        $city = trim($parts[0]);
        $area_code = trim($parts[1]);
        
        if (!empty($city) && !empty($area_code)) {
            echo '<tr>';
            echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($city) . '</td>';
            echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($area_code) . '</td>';
            echo '</tr>';
        }
    }
}

echo '</tbody>';
echo '</table>';
?>