<?php
/**
 * Uninstall file for WP Export Rank Math plugin
 * 
 * This file is executed when the plugin is deleted from WordPress admin.
 * It cleans up any plugin-specific data from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up any plugin-specific options
delete_option('wperm_version');
delete_option('wperm_settings');

// Clean up any transients
delete_transient('wperm_export_cache');

// Note: We don't delete any post meta data as it belongs to Rank Math plugin
// and should not be removed when this export plugin is uninstalled
