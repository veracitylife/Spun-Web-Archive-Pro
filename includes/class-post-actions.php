<?php
/**
 * Post Actions Handler
 * 
 * Handles individual post submission actions on WordPress admin posts/pages lists.
 * 
 * @package SpunWebArchivePro
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.3.2
 * @version 0.3.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Post_Actions {
    
    /**
     * Auto submitter instance
     */
    private $auto_submitter;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->auto_submitter = new SWAP_Auto_Submitter();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Add individual submission links to post row actions
        add_filter('post_row_actions', array($this, 'add_post_action'), 10, 2);
        add_filter('page_row_actions', array($this, 'add_post_action'), 10, 2);
        
        // Handle individual submission requests
        add_action('admin_action_swap_submit_post', array($this, 'handle_post_submission'));
        
        // Add admin notices for submission results
        add_action('admin_notices', array($this, 'show_submission_notices'));
        
        // Add AJAX handler for submission status
        add_action('wp_ajax_swap_post_submission', array($this, 'ajax_post_submission'));
        
        // Enqueue scripts for post actions
        add_action('admin_enqueue_scripts', array($this, 'enqueue_post_scripts'));
    }
    
    /**
     * Add individual submission action to post row actions
     */
    public function add_post_action($actions, $post) {
        // Only add for published posts
        if ($post->post_status !== 'publish') {
            return $actions;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            return $actions;
        }
        
        // Check if post was recently submitted
        if ($this->is_recently_submitted($post->ID)) {
            $actions['swap_submit'] = '<span style="color: #666;">' . __('Recently Submitted', 'spun-web-archive-pro') . '</span>';
        } else {
            // Create submission URL with nonce
            $submit_url = wp_nonce_url(
                admin_url('admin.php?action=swap_submit_post&post=' . $post->ID),
                'swap_submit_post_' . $post->ID
            );
            
            $actions['swap_submit'] = '<a href="' . esc_url($submit_url) . '" class="swap-submit-link">' . 
                                     __('Submit to Archive', 'spun-web-archive-pro') . '</a>';
        }
        
        return $actions;
    }
    
    /**
     * Handle individual post submission
     */
    public function handle_post_submission() {
        // Verify nonce
        $post_id = intval($_GET['post']);
        if (!wp_verify_nonce($_GET['_wpnonce'], 'swap_submit_post_' . $post_id)) {
            wp_die(__('Security check failed.', 'spun-web-archive-pro'));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'spun-web-archive-pro'));
        }
        
        // Validate post
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            wp_die(__('Invalid post or post is not published.', 'spun-web-archive-pro'));
        }
        
        // Submit to archive using auto submitter functionality
        $result = $this->auto_submitter->submit_content($post_id, $post->post_type);
        
        // Determine redirect URL
        $redirect_url = admin_url('edit.php');
        if ($post->post_type === 'page') {
            $redirect_url = admin_url('edit.php?post_type=page');
        }
        
        // Add result parameters to redirect URL
        if ($result) {
            $redirect_url = add_query_arg(array(
                'swap_submitted' => 1,
                'post_id' => $post_id
            ), $redirect_url);
        } else {
            $redirect_url = add_query_arg(array(
                'swap_submit_failed' => 1,
                'post_id' => $post_id
            ), $redirect_url);
        }
        
        // Redirect back to posts list
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Show admin notices for submission results
     */
    public function show_submission_notices() {
        if (!empty($_GET['swap_submitted'])) {
            $post_id = intval($_GET['post_id']);
            $post_title = get_the_title($post_id);
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(
                __('"%s" has been successfully submitted to the Internet Archive.', 'spun-web-archive-pro'),
                esc_html($post_title)
            ) . '</p>';
            echo '</div>';
        }
        
        if (!empty($_GET['swap_submit_failed'])) {
            $post_id = intval($_GET['post_id']);
            $post_title = get_the_title($post_id);
            
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . sprintf(
                __('Failed to submit "%s" to the Internet Archive. Please try again later.', 'spun-web-archive-pro'),
                esc_html($post_title)
            ) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Check if post was recently submitted
     */
    private function is_recently_submitted($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions';
        
        // Check if submitted in the last hour
        $recent_submission = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE post_id = %d 
             AND submitted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             ORDER BY submitted_at DESC 
             LIMIT 1",
            $post_id
        ));
        
        return !empty($recent_submission);
    }
    
    /**
     * AJAX handler for post submission
     */
    public function ajax_post_submission() {
        check_ajax_referer('swap_post_submission', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'spun-web-archive-pro'));
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post || $post->post_status !== 'publish') {
            wp_send_json_error(__('Invalid post or post is not published.', 'spun-web-archive-pro'));
        }
        
        // Submit to archive
        $result = $this->auto_submitter->submit_content($post_id, $post->post_type);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('"%s" has been successfully submitted to the Internet Archive.', 'spun-web-archive-pro'),
                    get_the_title($post_id)
                )
            ));
        } else {
            wp_send_json_error(sprintf(
                __('Failed to submit "%s" to the Internet Archive.', 'spun-web-archive-pro'),
                get_the_title($post_id)
            ));
        }
    }
    
    /**
     * Enqueue scripts for post actions
     */
    public function enqueue_post_scripts($hook) {
        if ($hook !== 'edit.php') {
            return;
        }
        
        wp_enqueue_script(
            'swap-post-actions',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/post-actions.js',
            array('jquery'),
            SWAP_VERSION,
            true
        );
        
        wp_localize_script('swap-post-actions', 'swapPostActions', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swap_post_submission'),
            'strings' => array(
                'submitting' => __('Submitting...', 'spun-web-archive-pro'),
                'submitted' => __('Submitted', 'spun-web-archive-pro'),
                'failed' => __('Failed', 'spun-web-archive-pro'),
                'confirm' => __('Are you sure you want to submit this post to the Internet Archive?', 'spun-web-archive-pro')
            )
        ));
    }
}