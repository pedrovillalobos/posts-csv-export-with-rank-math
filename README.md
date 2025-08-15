# WP Export Rank Math

A professional WordPress plugin to export post information with Rank Math SEO data to CSV format. This plugin provides a comprehensive export solution for WordPress sites using Rank Math SEO plugin.

**Author:** [Pedro Villalobos](https://villalobos.com.br)  
**GitHub:** [https://github.com/pedrovillalobos/wp-export-rank-math](https://github.com/pedrovillalobos/wp-export-rank-math)

## Features

- **Complete Rank Math Data Export**: Export all essential Rank Math SEO data including scores, keywords, structured data, and link information
- **Flexible Filtering**: Filter exports by post type, status, and date range
- **Modern Admin Interface**: Clean, responsive admin interface with real-time feedback
- **Secure Export**: AJAX-based export with proper nonce verification and user permissions
- **Comprehensive Data**: Includes all requested fields:
  - Post Title
  - Post URL
  - Author
  - Status (Published, Draft, etc.)
  - Last Edit Date
  - Categories
  - Rank Math Score (xx/100)
  - Rank Math Main Keyword
  - Rank Math Structured Data Type
  - Rank Math Internal Links
  - Rank Math External Links
  - Rank Math Incoming Links

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Rank Math SEO plugin (recommended for full functionality)

## Installation

1. **Download the plugin files** to your WordPress site's `/wp-content/plugins/wp-export-rank-math/` directory
2. **Activate the plugin** through the 'Plugins' menu in WordPress
3. **Access the export tool** via Tools > Export Rank Math in your WordPress admin

## Usage

1. Navigate to **Tools > Export Rank Math** in your WordPress admin
2. Configure your export settings:
   - **Post Type**: Choose between Posts, Pages, or All Post Types
   - **Post Status**: Filter by Published, Draft, Pending Review, or All Statuses
   - **Date Range**: Set optional date filters for the export (defaults to all dates if not specified)
3. Click **"Export to CSV"** to generate and download your export file
4. The CSV file will be automatically downloaded with a timestamp in the filename

## Rank Math Data Compatibility

This plugin is designed to work with various versions of Rank Math SEO and includes fallback mechanisms for different meta key naming conventions. It will attempt to retrieve data using multiple possible meta keys:

### SEO Score
- `rank_math_seo_score`
- `rank_math_score`
- `rank_math_analytics_score`
- `rank_math_advanced_seo_score`

### Focus Keyword
- `rank_math_focus_keyword`
- `rank_math_keyword`
- `rank_math_primary_keyword`
- `rank_math_target_keyword`

### Structured Data
- `rank_math_schema_type`
- `rank_math_rich_snippet_type`
- `rank_math_schema`
- `rank_math_structured_data_type`

### Link Information
- Internal Links: `rank_math_internal_links`, `rank_math_inlinks`, `rank_math_internal_link_count`, etc.
- External Links: `rank_math_external_links`, `rank_math_outlinks`, `rank_math_external_link_count`, etc.
- Incoming Links: `rank_math_incoming_links`, `rank_math_backlinks`, `rank_math_internal_backlinks`, etc.

## File Structure

```
wp-export-rank-math/
├── wp-export-rank-math.php      # Main plugin file
├── assets/
│   ├── js/
│   │   └── admin.js            # Admin JavaScript
│   └── css/
│       └── admin.css           # Admin styles
├── README.md                   # This file
└── LICENSE                     # GPL License
```

## Security Features

- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **User Permissions**: Only users with `manage_options` capability can access the export tool
- **Input Sanitization**: All user inputs are properly sanitized
- **SQL Prepared Statements**: Database queries use prepared statements to prevent SQL injection

## Customization

### Adding Custom Fields

To add custom fields to the export, modify the `generate_csv_data()` method in the main plugin file:

```php
// Add custom field to headers
$headers = array(
    'Post Title',
    'Post URL',
    // ... existing headers ...
    'Custom Field'  // Add your custom field
);

// Add custom field data
$custom_value = get_post_meta($post->ID, 'your_custom_meta_key', true);
$row[] = $this->escape_csv_value($custom_value);
```

### Styling Customization

The plugin includes CSS classes that can be customized:
- `.wperm-container`: Main container
- `.wperm-card`: Individual cards/sections
- `.wperm-message`: Success/error messages

## Troubleshooting

### No Data in Export
- Ensure Rank Math SEO plugin is installed and activated
- Check that posts have Rank Math data saved
- Verify user permissions (admin access required)

### Export Fails
- Check browser console for JavaScript errors
- Verify server memory limits for large exports
- Ensure proper file permissions on plugin directory

### Missing Rank Math Data
- The plugin includes fallback mechanisms for different Rank Math versions
- Some data may not be available if Rank Math hasn't analyzed the posts
- Check Rank Math settings and ensure posts have been analyzed

## Support

For support, feature requests, or bug reports, please create an issue on the [GitHub repository](https://github.com/pedrovillalobos/wp-export-rank-math).

## Author

**Pedro Villalobos**  
Website: [https://villalobos.com.br](https://villalobos.com.br)  
GitHub: [https://github.com/pedrovillalobos](https://github.com/pedrovillalobos)

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.10
- **Removed additional keywords column**: Simplified export to show only main keyword
- **Fixed structured data fallbacks**: No longer shows default schema types when no data exists
- **Fixed zero value display**: Link counts now properly show "0" instead of empty cells
- **Improved CSV formatting**: Better handling of numeric values in CSV export

### Version 1.0.9
- **Enhanced fallback system**: Added comprehensive fallbacks to post meta when analytics tables don't exist or are empty
- **Table existence checks**: Now checks if Rank Math tables exist before querying them
- **Improved data coverage**: Better handling of posts that don't have entries in analytics tables
- **Fixed empty data issues**: Ensures "0" values are shown instead of empty cells for link counts
- **More reliable data extraction**: Multiple fallback sources for all Rank Math data

### Version 1.0.8
- **Direct analytics table queries**: Now uses `wp_rank_math_analytics_objects` table for score, keywords, and structured data
- **Improved data reliability**: Direct database queries instead of post meta for better accuracy
- **Enhanced debug functionality**: Shows data from both internal meta and analytics tables
- **Better performance**: Faster queries and more reliable data retrieval

### Version 1.0.7
- **Fixed link data display**: Now properly shows "0" for null or empty values from database
- **Fixed Brazilian Portuguese translation**: Compiled .mo file for proper translation loading
- **Fixed keyword separation**: Main keyword now shows only the first keyword, additional keywords show the rest
- **Improved keyword logic**: Better handling of comma-separated keywords

### Version 1.0.6
- **Score display**: Rank Math score now shows without "/100" suffix
- **Additional keywords**: New column for Rank Math additional keywords
- **Link data defaults**: Link counts now show "0" instead of blank when no data exists
- **Date filtering note**: Added helpful note about default date behavior
- **Brazilian Portuguese**: Complete translation to Portuguese (Brazil)
- **Version requirements**: Added minimum PHP and WordPress version requirements
- **Developer attribution**: Updated translator information

### Version 1.0.5
- **Direct database table queries**: Now queries `wp_rank_math_internal_meta` table directly for link data
- **Simplified link detection**: Removed manual content parsing - uses only Rank Math's stored data
- **Cleaner data retrieval**: If data doesn't exist in Rank Math table, returns blank (no fallbacks)
- **Better performance**: Faster export by avoiding content parsing

### Version 1.0.4
- **Complete Rank Math data detection rewrite**: Based on official Rank Math documentation
- **Proper meta key prioritization**: Uses primary meta keys first, then fallbacks
- **Enhanced structured data mapping**: Comprehensive schema type mapping for better display
- **Improved debug functionality**: Shows primary vs secondary meta keys for better troubleshooting
- **Better locale support**: Proper Portuguese structured data type detection

### Version 1.0.3
- **Always visible debug button**: Debug functionality is now always available on the export page
- **Better user guidance**: Added helpful text explaining what the debug button does
- **Improved accessibility**: No need for URL parameters to access debug features

### Version 1.0.2
- **Enhanced structured data detection**: Better detection of Portuguese structured data types like "Artigo (Blog Posting)"
- **Improved locale detection**: Automatically detects Portuguese locale and returns appropriate structured data types
- **Debug functionality**: Added debug button to inspect Rank Math meta data
- **Better Rank Math options integration**: Checks Rank Math settings for default structured data types

### Version 1.0.1
- **Default to all dates**: Export now defaults to include all posts when no date filters are specified
- **Improved date filtering**: Better handling of partial date ranges (from date only or to date only)
- **Enhanced Rank Math data detection**: Better detection of structured data types and link counts

### Version 1.0.0
- Initial release
- Complete Rank Math data export functionality
- Modern admin interface
- AJAX-based export with security features
- Comprehensive fallback mechanisms for Rank Math data
