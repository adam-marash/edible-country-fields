# Edible Country Fields

A WordPress plugin that dynamically populates country-specific data using shortcodes, with data sourced from Google Sheets.

## Features

- **Dynamic Shortcodes**: Automatically displays country-specific information based on post slug
- **Google Sheets Integration**: Fetches data from a Google Sheets CSV export with manual cache refresh
- **Admin Interface**: Easy configuration and cache management through WordPress admin
- **Background Processing**: Uses Action Scheduler for efficient bulk post generation
- **Country Post Generation**: Automatically creates WordPress posts for active countries

## Installation

1. Upload the plugin files to `/wp-content/plugins/edible-country-fields/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the Google Sheets CSV URL in Settings → Country Fields

## Configuration

### Google Sheets Setup

1. Create a Google Sheets document with country data
2. Ensure your sheet has a 'slug' column and an 'active' column
3. Publish the sheet as CSV (File → Share → Publish to web → CSV)
4. Copy the CSV URL to the plugin settings

### Required CSV Columns

- `slug` - Used as the post slug and lookup key
- `active` - Boolean (true/false) to control which countries are processed
- Additional columns map to shortcode fields (name, currency, price, etc.)

## Available Shortcodes

### Basic Information
- `[country_name]` - Country name
- `[country_code]` - International dialing code
- `[country_currency]` - Currency name
- `[country_currency_symbol]` - Currency symbol
- `[country_currency_code]` - Currency code
- `[country_region]` - Geographic region
- `[country_price]` - Price for first pages
- `[country_price_next_page]` - Price for additional pages

### Special Formatting
- `[country_area_codes_table]` - Formatted table of area codes
- `[country_neighbors_list]` - Linked list of neighboring countries

## Usage

1. Create or edit a post with post type 'country'
2. Set the post slug to match a country slug from your CSV data
3. Use shortcodes in the post content - they will automatically display the correct country data

## Admin Features

- **Cache Management**: Manually refresh data from Google Sheets (cache never expires automatically)
- **Test Post Generation**: Create a single random country post for testing
- **Bulk Generation**: Queue background jobs to create posts for all active countries
- **Cache Status**: View cache information and last update time

## Architecture

The plugin follows a clean separation of concerns:

- `data-manager.php` - Handles Google Sheets integration and caching
- `shortcode-handler.php` - Processes dynamic shortcodes
- `post-generator.php` - Business logic for post creation and management
- `admin.php` - Admin interface presentation layer

## Requirements

- WordPress 5.0+
- Action Scheduler plugin (for background processing)
- Active internet connection for Google Sheets access

## Development

See `CLAUDE.md` for detailed development guidelines and architecture documentation.
