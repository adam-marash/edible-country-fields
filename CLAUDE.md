# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "Edible Country Fields" that dynamically displays country-specific data using shortcodes. The plugin uses post slugs as lookup keys and fetches corresponding field data from a Google Sheets data source. It also includes an admin interface for configuration and can automatically generate country posts using background processing.

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
│   ├── shortcode-handler.php       # Dynamic shortcode interception & routing
│   ├── admin.php                   # Admin interface (presentation layer only)
│   └── post-generator.php          # Business logic for post creation & management
└── shortcodes/
    ├── country_area_codes_table.php # Special handler for area codes table formatting
    └── country_neighbors_list.php   # Special handler for neighbors list with links
```

### Data Source Configuration
- **Google Sheets**: Public sheet with CSV export capability
- **URL Pattern**: `https://docs.google.com/spreadsheets/d/{sheet_id}/export?format=csv&gid=0`
- **Data Structure**: Wide CSV format with columns: `slug`, `active`, `name`, `currency`, `price_first_pages`, etc.
- **Admin Configuration**: Google Sheets URL is configurable via WordPress admin (Settings → Country Fields)
- **Required Columns**: 
  - `slug` - Used as post slug and lookup key
  - `active` - Boolean (true/false) to control which countries are processed
  - Additional columns map to shortcode fields

### Shortcode System
- **Explicit Registration**: All shortcodes are registered using `add_shortcode()` based on mappings in `get_shortcode_mappings()`
- **CSV Field Mapping**: Each shortcode maps to a specific CSV column (e.g., `country_price` → `price_first_pages`)
- **Special Handlers**: Complex shortcodes like `country_area_codes_table` have dedicated PHP files in `/shortcodes/`
- **Default Behavior**: Most shortcodes return raw field values from CSV data
- **Error Handling**: Missing country codes or field data display explicit error messages (no fallback values)
- **Available Shortcodes**: `country_name`, `country_currency`, `country_price`, `country_code`, `country_region`, etc.

## Development Notes

### Adding New Fields
1. Add new column to Google Sheets CSV
2. Add shortcode mapping to `get_shortcode_mappings()` in `shortcode-handler.php`
3. For complex formatting, create PHP file in `/shortcodes/` directory
4. Most fields work automatically with raw CSV data

### Caching Strategy
- Cache key pattern: `country_data_cache`
- Manual cache refresh via admin interface (Settings → Country Fields)
- WordPress transients API used for caching (never expires automatically)
- Cache status and last update time displayed in admin
- Data is cached indefinitely until manually refreshed - suitable for slowly-changing country data

### Post Generation System
- **Background Processing**: Uses Action Scheduler for bulk operations
- **Active Filtering**: Only countries marked `active=true` in CSV are processed
- **Post Template**: Uses country name as title (e.g., "Jordan")
- **Post Structure**: Custom post type 'country' with basic shortcode content
- **Cleanup**: Automatic removal of posts for countries no longer marked as active

### Admin Interface Features
- **Settings Configuration**: Google Sheets URL management
- **Cache Management**: Force refresh and status monitoring
- **Test Post Generation**: Create single random country post for testing
- **Bulk Generation**: Queue jobs for all active countries
- **Notification System**: Success/error feedback with cleanup to prevent stacking

## WordPress Plugin Context

This plugin operates within a WordPress environment at `/wp-content/plugins/edible-country-fields/`. It follows WordPress plugin development standards including proper plugin headers, WordPress coding standards, and WordPress API usage (transients, shortcodes, Action Scheduler).

### Dependencies
- **Action Scheduler**: Required for background post generation (install as separate plugin)
- **Custom Post Type**: Assumes 'country' post type exists (created elsewhere)

## Code style
- Don't create OOP wrappers for standard WordPress API functions.
- Use OOP only where appropriate outside WP functions for maintainability, reusability.

## Admin Architecture
- **Separation of concerns**: Admin files should handle presentation only, business logic should be in separate files
- **Admin files (`includes/admin.php`)**: Forms, page layouts, button handlers, notifications, redirects
- **Business logic files**: Data processing, post creation, API calls, complex operations
- **Exception**: JavaScript/jQuery can be included locally in admin files when needed for UI interactions
- **Pattern**: Admin handlers should be thin wrappers that call business logic functions from other files

## Release Management
- **Version Bumping**: Always bump the patch version number in the main plugin file (`edible-country-fields.php`) before every push to maintain proper version tracking
- **Commit Messages**: Use clean, professional commit messages without "Generated with Claude Code" or "Co-Authored-By" footers