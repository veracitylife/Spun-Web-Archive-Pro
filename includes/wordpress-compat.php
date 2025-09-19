<?php
/**
 * WordPress Compatibility Helper
 * 
 * Provides compatibility checks and fallbacks for WordPress functions
 * to help with static analysis and ensure robust operation.
 * 
 * @package SpunWebArchivePro
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.3.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verify WordPress environment is properly loaded
 * 
 * @return bool True if WordPress is properly loaded
 */
function swap_verify_wordpress_environment() {
    // Check for essential WordPress functions
    $required_functions = [
        'wp_verify_nonce',
        'get_option',
        'update_option',
        'esc_html',
        'esc_attr',
        'esc_url',
        'sanitize_text_field',
        'current_user_can',
        'wp_die',
        'add_action',
        'add_filter',
        'wp_enqueue_script',
        'wp_enqueue_style'
    ];
    
    foreach ($required_functions as $function) {
        if (!function_exists($function)) {
            return false;
        }
    }
    
    // Check for essential WordPress constants
    $required_constants = [
        'ABSPATH',
        'WP_CONTENT_DIR',
        'WP_PLUGIN_DIR'
    ];
    
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Safe wrapper for WordPress functions with fallbacks
 * 
 * @param string $function Function name to call
 * @param array $args Arguments to pass to function
 * @param mixed $fallback Fallback value if function doesn't exist
 * @return mixed Function result or fallback
 */
function swap_safe_wp_function($function, $args = [], $fallback = null) {
    if (function_exists($function)) {
        return call_user_func_array($function, $args);
    }
    
    return $fallback;
}

/**
 * Initialize WordPress compatibility checks
 */
function swap_init_wordpress_compat() {
    if (!swap_verify_wordpress_environment()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Spun Web Archive Pro: WordPress environment not properly loaded');
        }
        return false;
    }
    
    return true;
}

// Initialize compatibility checks when this file is loaded
add_action('init', 'swap_init_wordpress_compat', 1);