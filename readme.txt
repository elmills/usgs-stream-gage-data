=== USGS Stream Gage Data ===
Contributors: elmills
Tags: usgs, stream gage, water data, river, shortcode
Requires at least: 5.0
Tested up to: 6.3
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display USGS stream gage data on your WordPress site with shortcodes, including current and historical water levels and flow data.

== Description ==

The USGS Stream Gage Data plugin allows you to easily display real-time and historical data from USGS stream gages directly on your WordPress website using simple shortcodes. This plugin is perfect for fishing and outdoor websites, environmental organizations, water management authorities, and anyone interested in monitoring water conditions.

= Features =

* **Simple Shortcodes**: Add USGS stream gage data anywhere on your site with an easy-to-use shortcode
* **Real-time Data**: Display current discharge rates and gage heights
* **Historical Data**: Show historical high/low levels over multiple time periods (24 hours, 7 days, 30 days, 1 year)
* **Site Search**: Search for USGS sites by name or use exact site numbers
* **Validation**: Built-in site validation ensures data availability
* **Caching**: Efficient caching system reduces API calls and improves performance
* **Troubleshooting Tools**: API logging system helps diagnose any issues with site validation or searches
* **GitHub Updates**: Support for automatic updates from GitHub repositories using release tags

= Use Cases =

* Fishing websites showing current stream conditions
* Environmental monitoring for conservation organizations
* Local government water resource information
* Weather and outdoor recreation sites
* Educational resources about water systems

== Installation ==

1. Upload the `usgs-stream-gage-data` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'USGS Stream Gages' in your admin menu to configure settings
4. Add USGS stream gage sites by site number or by searching for site names
5. Use the provided shortcodes in your posts or pages

== Frequently Asked Questions ==

= Where do I find USGS Site Numbers? =

You can find USGS site numbers by:
1. Using the plugin's built-in search function (go to USGS Stream Gages > Manage Sites > Search for Site)
2. Visiting the [USGS National Water Information System](https://waterdata.usgs.gov/nwis/rt) website
3. Looking up sites on the [USGS Water Data map](https://maps.waterdata.usgs.gov/)

= How often is the data updated? =

The plugin retrieves data directly from the USGS API with the following cache durations:
* Current data: 15 minutes
* 24-hour data: 30 minutes
* 7-day data: 1 hour
* 30-day data: 2 hours
* 1-year data: 4 hours
* Site validation data: 24 hours

= Why isn't my site number working? =

Some USGS sites may be inactive or may not collect the required parameters (discharge and/or gage height). If you're having trouble, check the "API Logs" tab in the admin area to see detailed information about the API responses and any errors that occurred during site validation or searches.

= Can I customize the display of the data? =

Yes, you can use the shortcode parameters to control which data elements are displayed:

```
[usgs_stream_gage id="site_id" show_discharge="yes" show_gage_height="yes" show_24h="yes" show_7d="yes" show_30d="yes" show_1y="yes"]
```

Each parameter can be set to "yes" or "no" to show or hide specific data elements.

== Screenshots ==

1. Admin interface for managing USGS stream gage sites
2. API Logs interface for troubleshooting
3. Example of stream gage data displayed on a page using the shortcode
4. Site search functionality

== Changelog ==

= 1.2.0 =
* FIX: Corrected initialization order in the admin class to ensure Logger is loaded before API instance
* FIX: Added proper declaration of cache_expiration property in API class
* FIX: Resolved issues with site validation not working correctly
* FIX: Fixed bug that prevented sites from being added to the Current Sites list
* FEATURE: Added GitHub updates support for managing plugin updates directly from a GitHub repository

= 1.1.0 =
* FEATURE: Moved plugin menu from Settings submenu to top-level admin menu for better visibility
* FEATURE: Added comprehensive API logging system for troubleshooting site validation and search issues
* FEATURE: Added "API Logs" tab in admin interface with filtering capabilities
* FEATURE: Log entries are color-coded by level (Info, Debug, Warning, Error) for easier scanning
* FEATURE: Added ability to clear logs when needed

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.2.0 =
This update fixes critical bugs that prevented site validation from working properly. If you've been having issues adding sites, this update should resolve them.

= 1.1.0 =
This update adds a more accessible menu location and powerful logging tools to help troubleshoot any issues with USGS site validation or searches.

== Usage ==

= Basic Shortcode =

```
[usgs_stream_gage id="site_id"]
```

Replace `site_id` with the ID shown in the admin sites table.

= Shortcode with All Parameters =

```
[usgs_stream_gage id="site_id" show_discharge="yes" show_gage_height="yes" show_24h="yes" show_7d="yes" show_30d="yes" show_1y="yes"]
```

= Adding Stream Gage Sites =

1. Go to "USGS Stream Gages" in your admin menu
2. Select the "Manage Sites" tab (default)
3. Add a site using one of two methods:
   - By site number: Enter a USGS site number and click "Validate & Add"
   - By search: Select "Search for Site", enter a search term, and select from results

= Using the Logging System =

If you encounter issues with site validation or searches:

1. Go to "USGS Stream Gages" in your admin menu
2. Select the "API Logs" tab
3. View logs of all API interactions
4. Use the level filter to focus on specific log types:
   - INFO: General information
   - DEBUG: Detailed API request/response data
   - WARNING: Potential issues that didn't cause failure
   - ERROR: Failed operations with error details
5. Click "Show Details" on any log entry to see complete request/response data
6. Use the "Clear Logs" button to reset the log when needed

= Troubleshooting Common Issues =

* **Invalid Site Number**: Check the API Logs for validation errors. Some sites may be inactive or may not have the required data parameters.
* **No Search Results**: The search term may be too specific. Try a broader search term or check the API Logs for details on the search request.
* **Site Validation Failures**: The API Logs will show exactly why validation failed, including API response codes and error messages.

= Setting Up GitHub Updates =

The plugin supports automatic updates from GitHub. The GitHub repository URL is hardcoded in the plugin's main file:

1. Open `usgs-stream-gage-data.php`
2. Locate the line with `$github_repo = 'username/usgs-stream-gage-data';`
3. Replace `username/usgs-stream-gage-data` with your actual GitHub repository in the format `username/repository-name`

After properly setting the repository URL and pushing the plugin to that GitHub repository, WordPress will automatically check for new releases. When you create a new release/tag on GitHub that has a higher version number than the currently installed plugin, WordPress will automatically detect and offer the update.

**Note:** Make sure to create GitHub releases with tag names that match the plugin version (e.g., "1.2.0").

== About USGS Data ==

This plugin uses data from the [USGS Water Services](https://waterservices.usgs.gov/) API. The United States Geological Survey provides this data freely for public use. Please note that while this plugin strives for accuracy, users should verify critical data through official USGS channels.