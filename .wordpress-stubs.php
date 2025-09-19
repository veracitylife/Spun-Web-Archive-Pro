<?php
/**
 * WordPress Function Stubs for Static Analysis
 * 
 * This file provides function signatures for WordPress core functions
 * to help static analysis tools and IDEs understand the WordPress API.
 * 
 * This file should NOT be included in the actual plugin execution.
 * It's only for development and static analysis purposes.
 * 
 * @package SpunWebArchivePro
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.3.5
 */

// This file is for static analysis only - prevent execution
if (defined('ABSPATH')) {
    return;
}

// WordPress Core Constants
if (!defined('ABSPATH')) define('ABSPATH', '/path/to/wordpress/');
if (!defined('WP_CONTENT_DIR')) define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
if (!defined('WP_PLUGIN_DIR')) define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');

// WordPress Core Functions - Security
function wp_verify_nonce($nonce, $action = -1) { return true; }
function wp_create_nonce($action = -1) { return 'nonce'; }
function wp_nonce_field($action = -1, $name = "_wpnonce", $referer = true, $echo = true) { return ''; }
function current_user_can($capability, ...$args) { return true; }
function wp_die($message = '', $title = '', $args = array()) { exit; }

// WordPress Core Functions - Options
function get_option($option, $default = false) { return $default; }
function update_option($option, $value, $autoload = null) { return true; }
function delete_option($option) { return true; }

// WordPress Core Functions - Sanitization
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_url($url, $protocols = null, $_context = 'display') { return $url; }
function sanitize_text_field($str) { return $str; }
function sanitize_email($email) { return $email; }

// WordPress Core Functions - Localization
function __($text, $domain = 'default') { return $text; }
function _e($text, $domain = 'default') { echo $text; }
function esc_html__($text, $domain = 'default') { return esc_html(__($text, $domain)); }
function esc_html_e($text, $domain = 'default') { echo esc_html__($text, $domain); }

// WordPress Core Functions - Admin
function admin_url($path = '', $scheme = 'admin') { return 'http://example.com/wp-admin/' . $path; }
function get_admin_page_title() { return 'Admin Page'; }
function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null) { return ''; }
function checked($checked, $current = true, $echo = true) { return ''; }

// WordPress Core Functions - Posts
function get_post_types($args = array(), $output = 'names', $operator = 'and') { return array(); }
function get_post($post = null, $output = OBJECT, $filter = 'raw') { return null; }
function get_permalink($post = 0, $leavename = false) { return ''; }

// WordPress Core Functions - Database
function get_posts($args = null) { return array(); }

// WordPress Core Functions - Hooks
function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) { return true; }
function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1) { return true; }
function do_action($hook_name, ...$arg) { }
function apply_filters($hook_name, $value, ...$args) { return $value; }

// WordPress Core Functions - Scripts and Styles
function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) { }
function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') { }
function wp_localize_script($handle, $object_name, $l10n) { return true; }

// WordPress Core Functions - Plugin
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/'; }
function plugin_basename($file) { return basename(dirname($file)) . '/' . basename($file); }

// WordPress Core Functions - Misc
function wp_remote_get($url, $args = array()) { return array(); }
function wp_remote_post($url, $args = array()) { return array(); }
function wp_remote_retrieve_body($response) { return ''; }
function wp_remote_retrieve_response_code($response) { return 200; }
function is_wp_error($thing) { return false; }

// WordPress Core Classes
class WP_Error {
    public function __construct($code = '', $message = '', $data = '') {}
    public function get_error_code() { return ''; }
    public function get_error_message($code = '') { return ''; }
}

// WordPress Database Global
global $wpdb;
class wpdb {
    public $prefix = 'wp_';
    public function prepare($query, ...$args) { return $query; }
    public function get_results($query, $output = OBJECT) { return array(); }
    public function get_var($query, $x = 0, $y = 0) { return null; }
    public function insert($table, $data, $format = null) { return false; }
    public function update($table, $data, $where, $format = null, $where_format = null) { return false; }
    public function delete($table, $where, $where_format = null) { return false; }
}
$wpdb = new wpdb();