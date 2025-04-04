# USGS Stream Gage Data WordPress Plugin

This WordPress plugin integrates USGS water services data into your WordPress site, allowing you to display current and historical stream gage information.

## Description

The USGS Stream Gage Data plugin connects to the United States Geological Survey (USGS) water services API to provide real-time and historical data from stream gages across the United States. With this plugin, you can:

* Display current water conditions including discharge (flow rate) and gage height
* Show historical high and low values for multiple time periods
* Search for stream gages by name or location
* Validate USGS site numbers
* Use shortcodes to easily embed gage data in any post or page

Perfect for outdoor recreation sites, environmental monitoring, educational websites, and local community resources.

## Features

- Search for USGS stream gage sites by name or site number
- Display current discharge and gage height readings
- Show historical data for different time periods (24 hours, 7 days, 30 days, 1 year)
- Shortcode support for easy embedding in pages and posts
- Data caching to minimize API requests and improve performance
- Detailed logging for troubleshooting
- Compatible with WordPress 5.0+
- No API key required

## Installation

1. Upload the plugin files to the `/wp-content/plugins/usgs-stream-gage-data` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the shortcode attributes to customize the display.

## Usage

### Basic Shortcode

```
[usgs_stream_gage site="12345678"]
```

### Shortcode with Options

```
[usgs_stream_gage site="12345678" title="My Local River" show_discharge="true" show_gage_height="true" show_historical="true" periods="24h,7d,30d"]
```

### Available Options

- `site`: USGS site number (required)
- `title`: Custom title for the widget (optional)
- `show_discharge`: Show discharge data, true/false (default: true)
- `show_gage_height`: Show gage height data, true/false (default: true)
- `show_historical`: Show historical data, true/false (default: true)
- `periods`: Comma-separated list of periods to display - 24h, 7d, 30d, 1y (default: all periods)

## Frequently Asked Questions

### Where can I find USGS site numbers?

You can search for sites directly from the plugin's settings page, or visit the [USGS Water Data site](https://waterdata.usgs.gov/nwis/rt) to search for monitoring stations by state, watershed, or other criteria.

### How often is the data updated?

The plugin caches data for performance. Current readings are cached for 15 minutes, while historical data is cached for periods ranging from 30 minutes to 4 hours, depending on the time range.

### Does this plugin work outside the United States?

No, this plugin specifically connects to the USGS water services API, which only provides data for sites within the United States.

## API Reference

This plugin uses the USGS Water Services API:
- [USGS Water Services](https://waterservices.usgs.gov/)
- [USGS Instantaneous Values Web Service](https://waterservices.usgs.gov/rest/IV-Service.html)
- [USGS Site Web Service](https://waterservices.usgs.gov/rest/Site-Service.html)

## Changelog

### 1.2.5
- FEATURE: Enhanced visual distinction between time period headers with improved color contrast
- FEATURE: Improved readability of data tables with better styling for headers and rows
- FEATURE: Optimized CSS for better visual hierarchy in shortcode output
- FIX: Corrected admin menu icon display issue using Font Awesome water icon
- FIX: Improved SVG icon handling in WordPress admin interface

### 1.2.4
- FIX: Improved data caching mechanism for better performance
- FIX: Resolved compatibility issues with PHP 8.1
- FIX: Enhanced error handling for API timeout scenarios
- FEATURE: Added more detailed logging for API requests
- FEATURE: Optimized database queries for settings retrieval

### 1.2.3
- Fixed site validation cache handling
- Improved error checking for cached validation data
- Better handling of malformed cached data
- Converted README to Markdown format for better GitHub display

### 1.2.2
- FIX: Improved site validation process with better error handling
- FIX: Enhanced error handling in admin JavaScript interface
- FIX: Resolved UI issues when adding stream gage sites by number or search
- FIX: Optimized AJAX request processing for better performance
- FIX: Added robust type checking to prevent crashes with malformed data
- FEATURE: Updated automatic GitHub updates library to latest version

### 1.2.1
- FIX: Added robust error handling in JavaScript to prevent crashes when adding site numbers
- FIX: Improved type checking in admin JavaScript to handle different data formats
- FIX: Added consistent logging across all API methods for better troubleshooting

### 1.2.0
- FIX: Corrected initialization order in the admin class to ensure Logger is loaded before API instance
- FIX: Added proper declaration of cache_expiration property in API class
- FIX: Resolved issues with site validation not working correctly
- FIX: Fixed bug that prevented sites from being added to the Current Sites list
- FEATURE: Added GitHub updates support for managing plugin updates directly from a GitHub repository

### 1.1.0
- FEATURE: Moved plugin menu from Settings submenu to top-level admin menu for better visibility
- FEATURE: Added comprehensive API logging system for troubleshooting site validation and search issues
- FEATURE: Added "API Logs" tab in admin interface with filtering capabilities
- FEATURE: Log entries are color-coded by level (Info, Debug, Warning, Error) for easier scanning
- FEATURE: Added ability to clear logs when needed

### 1.0.0
- Initial release

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Blue Boat Partners LLC
