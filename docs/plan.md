# Country Data WordPress Plugin - Simplified Specification

## Overview
A WordPress plugin that dynamically populates country-specific data using shortcodes, with data sourced from Google Sheets.

## Core Functionality

### Post Slug Structure
Posts use country slugs AS the slug (e.g., slug is "jordan" for Jordan). The plugin uses the post slug as the lookup key to access data in the spreadsheet array.

### Shortcode System
- **Pattern**: Any shortcode starting with `country_` gets intercepted by the plugin
- **Field mapping**: `[country_currency]` maps to the "currency" field in the data source
- **Lookup**: Uses post slug as lookup key to access spreadsheet data array, then retrieves field_name value

### Data Source
**Google Sheets Integration**:
- Public Google Sheet with CSV export capability
- URL format: `https://docs.google.com/spreadsheets/d/{sheet_id}/export?format=csv&gid=0`
- Data structure:
  ```
  slug    | field_name | field_value
  jordan  | currency   | Jordanian Dinar
  jordan  | price      | $3.50
  usa     | currency   | US Dollar
  ```

## File Structure

### Core Files
```
edible-country-fields/
├── country-data.php                 # Main plugin file
├── includes/
│   ├── data-manager.php            # Google Sheets fetching & caching
│   └── shortcode-handler.php       # Shortcode interception logic
└── shortcodes/
    ├── country-title.php           # [country_title] implementation
    ├── country-currency.php        # [country_currency] implementation
    ├── country-price.php           # [country_price] implementation
    └── [additional field files]
```

### Shortcode File Pattern
Each shortcode gets its own file in `/shortcodes/` directory for maintainability. Files are auto-loaded based on shortcode name.

## Technical Implementation

### Data Source Configuration
Hard-coded in plugin configuration:
```php
$data_source = 'https://docs.google.com/spreadsheets/d/{sheet_id}/export?format=csv&gid=0';
```

### Caching Strategy
- Use WordPress transients for Google Sheets data
- Cache TTL: 1 hour (configurable)
- Cache key pattern: `country_data_{timestamp}`
- Manual cache refresh capability

### Title Template
Hard-coded in plugin for easy maintenance:
```php
$title_template = 'Send a fax to {country_name}';
```

### Error Handling
- Missing country code in title: Display error message
- Missing field data: Display error message
- Google Sheets unavailable: Use cached data or display error
- No fallback values - explicit error messages only

## Data Management

### Google Sheets Setup
1. Create public Google Sheet
2. Structure with columns: country_code, field_name, field_value
3. Generate CSV export URL
4. Configure URL in plugin code

### Field Management
Add new fields by:
1. Adding rows to Google Sheet
2. Creating corresponding shortcode file in `/shortcodes/` directory
3. No code changes to core plugin required

## Workflow Example

1. User creates post with slug "jordan" (the country slug IS the lookup key)
2. User adds `[country_title]` → outputs "Send a fax to Jordan"
3. User adds `[country_currency]` → outputs "Jordanian Dinar"
4. Plugin uses slug "jordan" as key to lookup data in spreadsheet array
5. If data missing, displays error message

## Configuration Options

- Google Sheets URL (in code)
- Cache TTL duration  
- Debug mode for error logging