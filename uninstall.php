<?php
/**
 * Uninstall script for Spun Web Archive Pro
 * 
 * This file is executed when the plugin is deleted from WordPress admin.
 * It removes all plugin data including database tables, options, and metadata.
 *
 * @package SpunWebArchivePro
 * @since 0.2.4
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check - ensure this is a legitimate uninstall
if (!current_user_can('activate_plugins')) {
    return;
}

// Check if the plugin file exists to prevent unauthorized access
$plugin_file = WP_PLUGIN_DIR . '/spun-web-archive-pro/spun-web-archive-pro.php';
if (!file_exists($plugin_file)) {
    return;
}

/**
 * Remove all plugin data
 */
function swap_uninstall_cleanup() {
    global $wpdb;
    
    // Remove database table with proper escaping
    $table_name = $wpdb->prefix . 'swap_submissions';
    $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
    
    // Remove all plugin options
    delete_option('swap_api_settings');
    delete_option('swap_auto_settings');
    delete_option('swap_api_credentials');
    delete_option('swap_callback_token');
    delete_option('swap_api_connection_status');
    delete_option('swap_api_last_test');
    delete_option('swap_plugin_version');
    
    // Remove all post meta data created by the plugin
    delete_post_meta_by_key('_swap_archive_status');
    delete_post_meta_by_key('_swap_archive_url');
    delete_post_meta_by_key('_swap_last_submitted');
    delete_post_meta_by_key('_swap_submission_count');
    delete_post_meta_by_key('_swap_auto_submit');
    delete_post_meta_by_key('_swap_archive_date');
    delete_post_meta_by_key('_swap_archive_error');
    delete_post_meta_by_key('_swap_exclude_from_archive');
    
    // Clear any scheduled cron jobs
    wp_clear_scheduled_hook('swap_process_queue');
    wp_clear_scheduled_hook('swap_retry_failed');
    
    // Remove any transients using prepared statements for security
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_swap_%'));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_swap_%'));
    
    // Remove user meta data (if any) using prepared statement
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", 'swap_%'));
    
    // Clear any cached data
    wp_cache_flush();
    
    // Log the uninstall (if WP_DEBUG is enabled)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Spun Web Archive Pro: Plugin data successfully removed during uninstall');
    }
}

// Execute cleanup
swap_uninstall_cleanup();

// Final security check - ensure we're still in WordPress context
if (!function_exists('add_action')) {
    exit;
}