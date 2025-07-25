# Implementation Plan - Country Data WordPress Plugin

## Phase 1: Core Plugin Structure

### 1.1 Main Plugin File (`country-data.php`)
- **WordPress Plugin Header**: Name, version, description, author
- **Security Check**: Prevent direct access (`defined('ABSPATH') || exit`)
- **Plugin Activation/Deactivation Hooks**: Setup/cleanup transients if needed
- **Include Core Files**: Load data manager and shortcode handler

### 1.2 Data Manager (`includes/data-manager.php`)
**Functions to implement:**
- `fetch_country_data()`: Fetch CSV from Google Sheets URL
- `get_cached_data()`: Check WordPress transients for cached data
- `cache_data($data)`: Store data in WordPress transients (1-hour TTL)
- `parse_csv_data($csv_content)`: Convert CSV to associative array with slug as primary key
- `get_field_value($country_code, $field_name)`: Lookup specific field

**Configuration:**
- Hard-coded Google Sheets URL
- Cache key pattern: `country_data_cache`
- Cache TTL: 3600 seconds (1 hour)

### 1.3 Shortcode Handler (`includes/shortcode-handler.php`)
**Core functionality:**
- `register_dynamic_shortcodes()`: Hook into WordPress shortcode system
- `handle_country_shortcode($atts, $content, $tag)`: Process any `country_*` shortcode
- `get_country_slug_from_post()`: Use post slug as lookup key
- `load_shortcode_file($shortcode_name)`: Auto-load from `/shortcodes/` directory

## Phase 2: Shortcode Implementation Files

### 2.1 Country Title (`shortcodes/country-title.php`)
**Function:** `process_country_title($country_code)`
- Use country slug (post slug) to lookup country name from spreadsheet array
- Apply title template: "Send a fax to {country_name}"
- Return formatted title or error message

### 2.2 Country Currency (`shortcodes/country-currency.php`)
**Function:** `process_country_currency($country_code)`
- Use country slug (post slug) as key to lookup currency field from spreadsheet array
- Return currency value or error message

### 2.3 Country Price (`shortcodes/country-price.php`)
**Function:** `process_country_price($country_code)`
- Use country slug (post slug) as key to lookup price field from spreadsheet array
- Return price value or error message

## Phase 3: Error Handling & Edge Cases

### 3.1 Error Messages
- Post slug not found as key in spreadsheet data array
- Country code not found in data source
- Field not available for country
- Google Sheets connection failure
- Invalid CSV data format

### 3.2 Fallback Strategy
- Use cached data when Google Sheets unavailable
- Display explicit error messages (no default values)
- Log errors in debug mode

## Phase 4: Data Source Setup

### 4.1 Google Sheets Configuration
**Required structure:**
```
slug     | field_name    | field_value
jordan   | currency      | Jordanian Dinar
jordan   | price         | $3.50
jordan   | country_name  | Jordan
usa      | currency      | US Dollar
usa      | price         | $2.75
usa      | country_name  | United States
```

### 4.2 CSV Export URL Format
`https://docs.google.com/spreadsheets/d/{SHEET_ID}/export?format=csv&gid=0`

## Phase 5: WordPress Integration

### 5.1 Plugin Standards
- WordPress coding standards compliance
- Proper sanitization and validation
- WordPress transients for caching
- WordPress shortcode API usage

### 5.2 File Structure Validation
```
edible-country-fields/
├── country-data.php
├── includes/
│   ├── data-manager.php
│   └── shortcode-handler.php
├── shortcodes/
│   ├── country-title.php
│   ├── country-currency.php
│   └── country-price.php
└── docs/
    ├── plan.md
    └── implementation-plan.md
```

## Implementation Order

1. **Core Infrastructure**: Main plugin file + data manager
2. **Shortcode System**: Dynamic shortcode handler
3. **Basic Shortcodes**: Title, currency, price implementations
4. **Testing**: Manual testing with sample data
5. **Error Handling**: Comprehensive error messages
6. **Documentation**: Code comments and usage examples

## Testing Strategy

### Manual Testing Checklist
- [ ] Post slug used directly as lookup key
- [ ] Google Sheets data fetching
- [ ] Caching functionality (check transients)
- [ ] Shortcode rendering in posts
- [ ] Error handling for missing data
- [ ] Cache expiration and refresh

### Test Data Requirements
- Sample Google Sheets with multiple countries
- Test posts with various title formats
- Edge cases: missing country codes, invalid data

## Performance Considerations

- **Caching**: 1-hour transient cache to minimize API calls
- **Lazy Loading**: Only fetch data when shortcodes are used
- **Error Prevention**: Validate data before processing
- **Memory Usage**: Parse CSV efficiently without loading entire dataset into memory if large