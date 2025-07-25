# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "Country Data Fields" that dynamically displays country-specific data using shortcodes. The plugin extracts country codes from post titles and fetches corresponding field data from a Google Sheets data source.

## Core Architecture

### Data Flow
1. **Country Slug Lookup**: Plugin uses post slugs as lookup keys (e.g., "jordan" from post slug)
2. **Shortcode Interception**: Any shortcode starting with `country_` is intercepted by the plugin's shortcode handler
3. **Data Lookup**: Uses (country_code, field_name) structure to fetch data from Google Sheets
4. **Caching**: WordPress transients cache Google Sheets data with 1-hour TTL

### File Structure Pattern
```
edible-country-fields/
├── edible-country-fields.php                 # Main plugin file & WordPress plugin header
├── includes/
│   ├── data-manager.php            # Google Sheets CSV fetching & caching logic
│   └── shortcode-handler.php       # Dynamic shortcode interception & routing
└── shortcodes/
    ├── country-title.php           # [country_title] implementation
    ├── country-currency.php        # [country_currency] implementation
    ├── country-price.php           # [country_price] implementation
    └── [additional field files]    # Auto-loaded based on shortcode name
```

### Data Source Configuration
- **Google Sheets**: Public sheet with CSV export capability
- **URL Pattern**: `https://docs.google.com/spreadsheets/d/{sheet_id}/export?format=csv&gid=0`
- **Data Structure**: `slug | field_name | field_value` columns
- **Hard-coded Configuration**: Google Sheets URL and title template are configured directly in plugin code

### Shortcode System
- **Dynamic Loading**: Shortcode files in `/shortcodes/` directory are auto-loaded based on shortcode name
- **Naming Convention**: `[country_fieldname]` maps to `/shortcodes/country-fieldname.php`
- **Error Handling**: Missing country codes or field data display explicit error messages (no fallback values)

## Development Notes

### Adding New Fields
1. Add data rows to Google Sheets (slug, field_name, field_value)
2. Create corresponding PHP file in `/shortcodes/` directory
3. No changes required to core plugin files

### Caching Strategy
- Cache key pattern: `country_data_cache`
- Manual cache refresh capability required
- WordPress transients API used for caching

### Title Template
Hard-coded template: `'Send a fax to {country_name}'` - modify in plugin configuration as needed.

## WordPress Plugin Context

This plugin operates within a WordPress environment at `/wp-content/plugins/edible-country-fields/`. It follows WordPress plugin development standards including proper plugin headers, WordPress coding standards, and WordPress API usage (transients, shortcodes).

## Code style
- Don't create OOP wrappers for standard WordPress API functions.
- Use OOP only where appropriate outside WP functions for maintainability, reusability.