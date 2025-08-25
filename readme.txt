=== Posts CSV Export with Rank Math ===
Contributors: pedrovillalobos
Tags: rank-math, seo, export, csv, analytics
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.14
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Export WordPress posts with Rank Math SEO data to CSV format including scores, keywords, structured data, and link information.

== Description ==

Posts CSV Export with Rank Math is a comprehensive export solution for WordPress sites using the Rank Math SEO plugin. This plugin allows you to export all essential Rank Math SEO data including scores, keywords, structured data, and link information to CSV format for analysis and reporting.

= Key Features =

* **Complete Rank Math Data Export**: Export all essential Rank Math SEO data including scores, keywords, structured data, and link information
* **Flexible Filtering**: Filter exports by post type, status, and date range
* **Modern Admin Interface**: Clean, responsive admin interface with real-time feedback
* **Secure Export**: AJAX-based export with proper nonce verification and user permissions
* **Comprehensive Data**: Includes all requested fields:
  * Post Title
  * Post URL
  * Author
  * Status (Published, Draft, etc.)
  * Last Edit Date
  * Categories
  * Rank Math Score (xx/100)
  * Rank Math Main Keyword
  * Rank Math Structured Data Type
  * Rank Math Internal Links
  * Rank Math External Links
  * Rank Math Incoming Links

= Rank Math Data Compatibility =

This plugin is designed to work with various versions of Rank Math SEO and includes fallback mechanisms for different meta key naming conventions. It will attempt to retrieve data using multiple possible meta keys for maximum compatibility.

= Security Features =

* **Nonce Verification**: All AJAX requests are protected with WordPress nonces
* **User Permissions**: Only users with `manage_options` capability can access the export tool
* **Input Sanitization**: All user inputs are properly sanitized
* **SQL Prepared Statements**: Database queries use prepared statements to prevent SQL injection

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/posts-csv-export-with-rank-math/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Tools->Posts CSV Export with Rank Math screen to configure and export your data

== Frequently Asked Questions ==

= Do I need Rank Math SEO plugin installed? =

While the plugin will work without Rank Math SEO, you'll get the most benefit when Rank Math SEO is installed and active on your site.

= What data is exported? =

The plugin exports comprehensive post data including:
* Basic post information (title, URL, author, status, date)
* Categories
* All available Rank Math SEO data (scores, keywords, structured data, links)

= Is my data secure? =

Yes, the plugin follows WordPress security best practices including nonce verification, user permission checks, and input sanitization.

= Can I filter the export? =

Yes, you can filter by post type, post status, and date range to export only the data you need.

= What file format is the export? =

The plugin exports data in CSV format, which can be opened in Excel, Google Sheets, or any spreadsheet application.

== Screenshots ==

1. Main export interface with filtering options
2. Export settings and configuration
3. Export information and data fields list

== Changelog ==

= 1.0.14 =
* Fixed text domain mismatch for WordPress.org compliance
* Updated all 47 text domain references from posts-csv-export-rank-math to posts-csv-export-with-rank-math
* Renamed language files to match new text domain
* Updated GitHub URLs in documentation
* Fixed internationalization compliance issues

= 1.0.13 =
* Removed deprecated load_plugin_textdomain() call (WordPress 4.6+)
* Fixed remaining output escaping issues in error messages
* Enhanced nonce validation with proper sanitization and validation
* Replaced date() with gmdate() for timezone safety
* Added table name validation to prevent SQL injection
* Reduced readme.txt tags to comply with WordPress.org guidelines
* All database queries now use proper prepared statements

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.13 =
This version includes additional security improvements and WordPress coding standards compliance fixes. All Plugin Check issues have been resolved.
