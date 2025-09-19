<?php
/**
 * Plugin Name: Spun Web Archive Pro
 * Plugin URI: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
 * Description: Professional WordPress plugin for automatically submitting content to the Internet Archive (Wayback Machine). Includes individual post submission, auto submission, and advanced archiving tools.
 * Version: 0.3.5
 * Author: Ryan Dickie Thompson
 * Author URI: https://spunwebtechnology.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: spun-web-archive-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.7.1
 * Requires PHP: 8.1
 * Network: false
 * Update URI: https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/
 * Requires Plugins: 
 *
 * @package SpunWebArchivePro
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// WordPress version compatibility check
if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: %s: required WordPress version */
            esc_html__('Spun Web Archive Pro requires WordPress %s or higher. Please update WordPress.', 'spun-web-archive-pro'),
            '5.0'
        );
        echo '</p></div>';
    });
    return;
}

// PHP version compatibility check
if (version_compare(PHP_VERSION, '8.1', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: %s: required PHP version */
            esc_html__('Spun Web Archive Pro requires PHP %s or higher. Please update PHP.', 'spun-web-archive-pro'),
            '8.1'
        );
        echo '</p></div>';
    });
    return;
}

// Define plugin constants
define('SWAP_VERSION', '0.3.5');
define('SWAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SWAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SWAP_PLUGIN_FILE', __FILE__);
define('SWAP_AUTHOR', 'Ryan Dickie Thompson');
define('SWAP_AUTHOR_URI', 'https://spunwebtechnology.com');
define('SWAP_SUPPORT_EMAIL', 'support@spunwebtechnology.com');

/**
 * Main Spun Web Archive Pro Class
 */
class SpunWebArchivePro {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Archive.org API settings
     */
    private $api_settings;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
        
        // Auto submission hooks
        add_action('publish_post', array($this, 'auto_submit_post'), 10, 2);
        add_action('publish_page', array($this, 'auto_submit_page'), 10, 2);
        add_action('post_updated', array($this, 'auto_submit_updated_content'), 10, 3);
        
        // Scheduled tasks
        add_action('swap_process_queue', array($this, 'process_submission_queue'));
        add_action('swap_retry_failed', array($this, 'retry_failed_submissions'));
        
        // AJAX hooks for legacy support (if needed)
        // Individual post submission is handled by SWAP_Post_Actions class
        
        // Plugin activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load WordPress compatibility helper first
        require_once SWAP_PLUGIN_DIR . 'includes/wordpress-compat.php';
        
        require_once SWAP_PLUGIN_DIR . 'includes/class-archive-api.php';
        require_once SWAP_PLUGIN_DIR . 'includes/class-auto-submitter.php';
        require_once SWAP_PLUGIN_DIR . 'includes/class-post-actions.php';
        require_once SWAP_PLUGIN_DIR . 'includes/class-admin-page.php';
        require_once SWAP_PLUGIN_DIR . 'includes/class-submission-tracker.php';
        require_once SWAP_PLUGIN_DIR . 'includes/class-documentation-page.php';
        require_once SWAP_PLUGIN_DIR . 'includes/class-api-callback.php';
        require_once SWAP_PLUGIN_DIR . 'includes/class-credentials-page.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('spun-web-archive-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Load API settings
        $this->api_settings = get_option('swap_api_settings', array());
        
        // Initialize components with error handling
        try {
            $this->archive_api = new SWAP_Archive_API();
            $this->admin_page = new SWAP_Admin_Page();
            $this->auto_submitter = new SWAP_Auto_Submitter($this->archive_api);

            $this->post_actions = new SWAP_Post_Actions();
            $this->submission_tracker = new SWAP_Submission_Tracker();
            $this->documentation_page = new SWAP_Documentation_Page();
            $this->api_callback = new SWAP_API_Callback();
            $this->credentials_page = new SWAP_Credentials_Page();
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    /* translators: %s: error message */
                    esc_html__('Spun Web Archive Pro initialization error: %s', 'spun-web-archive-pro'),
                    esc_html($e->getMessage())
                );
                echo '</p></div>';
            });
        }
    }
    

    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin page
        if ($hook !== 'tools_page_spun-web-archive-pro') {
            return;
        }
        
        wp_enqueue_style(
            'swap-admin-css',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            array(),
            SWAP_VERSION
        );
        
        wp_enqueue_script(
            'swap-admin-js',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        wp_localize_script('swap-admin-js', 'swap_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_ajax_nonce'),
            'strings' => array(
                'testing' => __('Testing connection...', 'spun-web-archive-pro'),
                'error' => __('Connection failed', 'spun-web-archive-pro'),
                'success' => __('Connection successful', 'spun-web-archive-pro')
            )
        ));
    }
    
    // API testing is now handled by the centralized credentials page
    
    /**
     * AJAX handler for getting posts
     */

    
    // Individual post submission is handled by SWAP_Post_Actions class
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        $main_page = add_menu_page(
            __('Spun Web Archive Pro', 'spun-web-archive-pro'),
            __('Web Archive Pro', 'spun-web-archive-pro'),
            'manage_options',
            'spun-web-archive-pro',
            array($this, 'admin_page_callback'),
            'dashicons-archive',
            30
        );
        
        // Add submenu for main settings (duplicate of main page)
        add_submenu_page(
            'spun-web-archive-pro',
            __('Settings', 'spun-web-archive-pro'),
            __('Settings', 'spun-web-archive-pro'),
            'manage_options',
            'spun-web-archive-pro',
            array($this, 'admin_page_callback')
        );
        
        // Add submenu for API Credentials
        add_submenu_page(
            'spun-web-archive-pro',
            __('API Credentials', 'spun-web-archive-pro'),
            __('API Credentials', 'spun-web-archive-pro'),
            'manage_options',
            'spun-web-archive-pro-credentials',
            array($this, 'credentials_page_callback')
        );
        
        // Add submenu for Documentation
        add_submenu_page(
            'spun-web-archive-pro',
            __('Documentation', 'spun-web-archive-pro'),
            __('Documentation', 'spun-web-archive-pro'),
            'manage_options',
            'spun-web-archive-pro-docs',
            array($this, 'documentation_page_callback')
        );
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=spun-web-archive-pro') . '">' . __('Settings', 'spun-web-archive-pro') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Add plugin row meta
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $row_meta = array(
                'docs' => '<a href="https://spunwebtechnology.com/spun-web-archive-pro-end-user-documentation/" target="_blank">' . __('Documentation', 'spun-web-archive-pro') . '</a>',
                'support' => '<a href="mailto:' . SWAP_SUPPORT_EMAIL . '">' . __('Support', 'spun-web-archive-pro') . '</a>'
            );
            return array_merge($links, $row_meta);
        }
        return $links;
    }
    
    /**
     * Admin page callback
     */
    public function admin_page_callback() {
        $admin_page = new SWAP_Admin_Page();
        $admin_page->render();
    }
    
    /**
     * Credentials page callback
     */
    public function credentials_page_callback() {
        $credentials_page = new SWAP_Credentials_Page();
        $credentials_page->render_page();
    }
    
    /**
     * Documentation page callback
     */
    public function documentation_page_callback() {
        $documentation_page = new SWAP_Documentation_Page();
        $documentation_page->render();
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('swap_settings', 'swap_api_settings');
        register_setting('swap_settings', 'swap_auto_settings');

        
        // Handle CSV export
        if (isset($_GET['action']) && $_GET['action'] === 'swap_export_csv') {
            $this->handle_csv_export();
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        // Check if we're on any of our plugin pages
        $plugin_pages = array(
            'toplevel_page_spun-web-archive-pro',
            'web-archive-pro_page_spun-web-archive-pro-credentials',
            'web-archive-pro_page_spun-web-archive-pro-docs'
        );
        
        if (!in_array($hook, $plugin_pages)) {
            return;
        }
        
        wp_enqueue_script(
            'swap-admin',
            SWAP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        wp_enqueue_style(
            'swap-admin',
            SWAP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SWAP_VERSION
        );
        
        wp_localize_script('swap-admin', 'swap_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_ajax_nonce'),
            'strings' => array(
                'testing' => __('Testing API connection...', 'spun-web-archive-pro'),
                'success' => __('API connection successful!', 'spun-web-archive-pro'),
                'error' => __('API connection failed. Please check your credentials.', 'spun-web-archive-pro'),
                'submitting' => __('Submitting to archive...', 'spun-web-archive-pro'),
                'submitted' => __('Successfully submitted to archive!', 'spun-web-archive-pro'),
                'failed' => __('Submission failed. Please try again.', 'spun-web-archive-pro'),
                'credentials_testing' => __('Testing credentials...', 'spun-web-archive-pro'),
                'credentials_pass' => __('PASS', 'spun-web-archive-pro'),
                'credentials_fail' => __('FAIL', 'spun-web-archive-pro'),
                'credentials_saved' => __('Credentials saved successfully!', 'spun-web-archive-pro')
            )
        ));
    }
    
    /**
     * Auto submit new post
     */
    public function auto_submit_post($post_id, $post) {
        if ($this->should_auto_submit('post')) {
            $auto_submitter = new SWAP_Auto_Submitter();
            $auto_submitter->submit_content($post_id, 'post');
        }
    }
    
    /**
     * Auto submit new page
     */
    public function auto_submit_page($post_id, $post) {
        if ($this->should_auto_submit('page')) {
            $auto_submitter = new SWAP_Auto_Submitter();
            $auto_submitter->submit_content($post_id, 'page');
        }
    }
    
    /**
     * Auto submit updated content
     */
    public function auto_submit_updated_content($post_id, $post_after, $post_before) {
        $auto_settings = get_option('swap_auto_settings', array());
        
        if (isset($auto_settings['submit_updates']) && $auto_settings['submit_updates']) {
            if ($this->should_auto_submit($post_after->post_type)) {
                $auto_submitter = new SWAP_Auto_Submitter();
                $auto_submitter->submit_content($post_id, $post_after->post_type);
            }
        }
    }
    
    /**
     * Check if content should be auto-submitted
     */
    private function should_auto_submit($post_type) {
        $auto_settings = get_option('swap_auto_settings', array());
        
        if (!isset($auto_settings['enabled']) || !$auto_settings['enabled']) {
            return false;
        }
        
        if (!isset($auto_settings['post_types']) || !is_array($auto_settings['post_types'])) {
            return false;
        }
        
        return in_array($post_type, $auto_settings['post_types']);
    }
    

    
    // Post loading is now handled by individual post actions
    
    /**
     * Process submission queue
     */
    public function process_submission_queue() {
        if (isset($this->auto_submitter)) {
            $this->auto_submitter->process_queue();
        }
    }
    
    /**
     * Retry failed submissions
     */
    public function retry_failed_submissions() {
        if (isset($this->auto_submitter)) {
            $this->auto_submitter->retry_failed_submissions();
        }
    }
    
    // Individual submissions are now handled by SWAP_Post_Actions class
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $default_api_settings = array(
            'api_key' => '',
            'api_secret' => '',
            'endpoint' => 'https://web.archive.org/save/'
        );
        add_option('swap_api_settings', $default_api_settings);
        
        $default_auto_settings = array(
            'enabled' => false,
            'post_types' => array('post', 'page'),
            'submit_updates' => false,
            'delay' => 0
        );
        add_option('swap_auto_settings', $default_auto_settings);
        

        
        // Schedule cron jobs
        if (!wp_next_scheduled('swap_process_queue')) {
            wp_schedule_event(time(), 'hourly', 'swap_process_queue');
        }
        
        if (!wp_next_scheduled('swap_retry_failed')) {
            wp_schedule_event(time(), 'daily', 'swap_retry_failed');
        }
    }
    
    /**
     * Handle CSV export of submission history
     */
    private function handle_csv_export() {
        // Security checks
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'swap_export_csv')) {
            wp_die(__('Security check failed', 'spun-web-archive-pro'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'spun-web-archive-pro'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'swap_submissions';
        
        // Get all submissions
        $submissions = $wpdb->get_results(
            "SELECT s.*, p.post_title, p.post_type 
             FROM $table_name s 
             LEFT JOIN {$wpdb->posts} p ON s.post_id = p.ID 
             ORDER BY s.submitted_at DESC"
        );
        
        // Set headers for CSV download
        $filename = 'spun-web-archive-submissions-' . date('Y-m-d-H-i-s') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, array(
            __('Post/Page Title', 'spun-web-archive-pro'),
            __('Local URL', 'spun-web-archive-pro'),
            __('Archive.org URL', 'spun-web-archive-pro'),
            __('Status', 'spun-web-archive-pro'),
            __('Submission Date', 'spun-web-archive-pro')
        ));
        
        // Add data rows
        foreach ($submissions as $submission) {
            $archive_url = !empty($submission->archive_url) ? $submission->archive_url : __('Not available', 'spun-web-archive-pro');
            
            fputcsv($output, array(
                $submission->post_title ?: __('Unknown', 'spun-web-archive-pro'),
                $submission->url,
                $archive_url,
                $submission->status,
                $submission->submitted_at
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('swap_process_queue');
        wp_clear_scheduled_hook('swap_retry_failed');
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            url varchar(255) NOT NULL,
            status varchar(50) NOT NULL,
            archive_url varchar(255) DEFAULT '',
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            response_data text,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
function swap_init() {
    return SpunWebArchivePro::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'swap_init');