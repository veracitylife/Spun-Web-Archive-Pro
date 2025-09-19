<?php
/**
 * Auto Submitter Handler
 * 
 * Handles automatic submission of new posts and pages to the Internet Archive.
 * 
 * @package SpunWebArchivePro
 * @subpackage Includes
 * @author Ryan Dickie Thompson
 * @copyright 2024 Spun Web Technology
 * @license GPL-2.0-or-later
 * @since 0.0.1
 * @version 0.3.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SWAP_Auto_Submitter {
    
    /**
     * Archive API instance
     */
    private $archive_api;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->archive_api = new SWAP_Archive_API();
    }
    
    /**
     * Submit content to archive
     */
    public function submit_content($post_id, $post_type = 'post') {
        // Validate post
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        
        // Get post URL
        $url = get_permalink($post_id);
        if (!$url) {
            return false;
        }
        
        // Check if already submitted recently
        if ($this->is_recently_submitted($post_id)) {
            return false;
        }
        
        // Get auto submission settings
        $auto_settings = get_option('swap_auto_settings', array());
        $delay = isset($auto_settings['delay']) ? intval($auto_settings['delay']) : 0;
        
        if ($delay > 0) {
            // Schedule delayed submission
            wp_schedule_single_event(time() + $delay, 'swap_delayed_submission', array($post_id, $url));
            
            // Log pending submission
            $this->log_submission($post_id, $url, 'pending', '', 'Scheduled for delayed submission');
            
            return true;
        } else {
            // Submit immediately
            return $this->submit_immediately($post_id, $url);
        }
    }
    
    /**
     * Submit immediately to archive
     */
    public function submit_immediately($post_id, $url) {
        // Prepare submission options
        $options = array(
            'capture_all' => 'on',
            'capture_outlinks' => 'off',
            'capture_screenshot' => 'off'
        );
        
        // Submit to archive
        $result = $this->archive_api->submit_url($url, $options);
        
        if ($result['success']) {
            // Log successful submission
            $this->log_submission(
                $post_id, 
                $url, 
                'success', 
                $result['archive_url'] ?? '', 
                json_encode($result)
            );
            
            // Update post meta
            update_post_meta($post_id, '_swap_archive_status', 'archived');
            update_post_meta($post_id, '_swap_archive_url', $result['archive_url'] ?? '');
            update_post_meta($post_id, '_swap_archive_date', current_time('mysql'));
            
            // Trigger action for other plugins
            do_action('swap_content_archived', $post_id, $url, $result['archive_url'] ?? '');
            
            return true;
        } else {
            // Log failed submission
            $this->log_submission(
                $post_id, 
                $url, 
                'failed', 
                '', 
                json_encode($result)
            );
            
            // Update post meta
            update_post_meta($post_id, '_swap_archive_status', 'failed');
            update_post_meta($post_id, '_swap_archive_error', $result['error'] ?? 'Unknown error');
            
            // Trigger action for failed submissions
            do_action('swap_content_archive_failed', $post_id, $url, $result['error'] ?? 'Unknown error');
            
            return false;
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
     * Log submission to database
     */
    private function log_submission($post_id, $url, $status, $archive_url = '', $response_data = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions';
        
        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'url' => $url,
                'status' => $status,
                'archive_url' => $archive_url,
                'response_data' => $response_data,
                'submitted_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Process delayed submission
     */
    public function process_delayed_submission($post_id, $url) {
        // Validate post still exists and is published
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        
        // Submit to archive
        return $this->submit_immediately($post_id, $url);
    }
    
    /**
     * Retry failed submissions
     */
    public function retry_failed_submissions($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions';
        
        // Get failed submissions from the last 24 hours
        $failed_submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE status = 'failed' 
             AND submitted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY submitted_at DESC 
             LIMIT %d",
            $limit
        ));
        
        $retry_results = array();
        
        foreach ($failed_submissions as $submission) {
            // Validate post still exists
            $post = get_post($submission->post_id);
            if (!$post || $post->post_status !== 'publish') {
                continue;
            }
            
            // Retry submission
            $result = $this->submit_immediately($submission->post_id, $submission->url);
            $retry_results[] = array(
                'post_id' => $submission->post_id,
                'url' => $submission->url,
                'success' => $result
            );
            
            // Add delay between retries
            sleep(2);
        }
        
        return $retry_results;
    }
    
    /**
     * Get submission status for a post
     */
    public function get_submission_status($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions';
        
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE post_id = %d 
             ORDER BY submitted_at DESC 
             LIMIT 1",
            $post_id
        ));
        
        if ($submission) {
            return array(
                'status' => $submission->status,
                'archive_url' => $submission->archive_url,
                'submitted_at' => $submission->submitted_at,
                'response_data' => $submission->response_data
            );
        }
        
        return false;
    }
    
    /**
     * Check if content should be auto-submitted
     */
    public function should_auto_submit($post_id, $post_type) {
        $auto_settings = get_option('swap_auto_settings', array());
        
        // Check if auto submission is enabled
        if (!isset($auto_settings['enabled']) || !$auto_settings['enabled']) {
            return false;
        }
        
        // Check if post type is enabled
        if (!isset($auto_settings['post_types']) || !is_array($auto_settings['post_types'])) {
            return false;
        }
        
        if (!in_array($post_type, $auto_settings['post_types'])) {
            return false;
        }
        
        // Check if post is excluded via meta
        $excluded = get_post_meta($post_id, '_swap_exclude_from_archive', true);
        if ($excluded) {
            return false;
        }
        
        // Allow filtering
        return apply_filters('swap_should_auto_submit', true, $post_id, $post_type);
    }
    
    /**
     * Add archive status column to post list
     */
    public function add_archive_status_column($columns) {
        $columns['archive_status'] = __('Archive Status', 'spun-web-archive-pro');
        return $columns;
    }
    
    /**
     * Display archive status in post list column
     */
    public function display_archive_status_column($column, $post_id) {
        if ($column === 'archive_status') {
            $status = $this->get_submission_status($post_id);
            
            if ($status) {
                switch ($status['status']) {
                    case 'success':
                        echo '<span style="color: green;">✓ ' . __('Archived', 'spun-web-archive-pro') . '</span>';
                        if ($status['archive_url']) {
                            echo '<br><a href="' . esc_url($status['archive_url']) . '" target="_blank" style="font-size: 11px;">' . __('View Archive', 'spun-web-archive-pro') . '</a>';
                        }
                        break;
                    case 'failed':
                        echo '<span style="color: red;">✗ ' . __('Failed', 'spun-web-archive-pro') . '</span>';
                        break;
                    case 'pending':
                        echo '<span style="color: orange;">⏳ ' . __('Pending', 'spun-web-archive-pro') . '</span>';
                        break;
                }
            } else {
                echo '<span style="color: #666;">—</span>';
            }
        }
    }
    
    /**
     * Add meta box to post edit screen
     */
    public function add_archive_meta_box() {
        $post_types = get_post_types(array('public' => true));
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'swap_archive_status',
                __('Archive Status', 'spun-web-archive-pro'),
                array($this, 'render_archive_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Render archive meta box
     */
    public function render_archive_meta_box($post) {
        $status = $this->get_submission_status($post->ID);
        $excluded = get_post_meta($post->ID, '_swap_exclude_from_archive', true);
        
        wp_nonce_field('swap_meta_box', 'swap_meta_box_nonce');
        
        echo '<div class="swap-meta-box">';
        
        if ($status) {
            echo '<p><strong>' . __('Status:', 'spun-web-archive-pro') . '</strong> ';
            switch ($status['status']) {
                case 'success':
                    echo '<span style="color: green;">' . __('Archived', 'spun-web-archive-pro') . '</span>';
                    break;
                case 'failed':
                    echo '<span style="color: red;">' . __('Failed', 'spun-web-archive-pro') . '</span>';
                    break;
                case 'pending':
                    echo '<span style="color: orange;">' . __('Pending', 'spun-web-archive-pro') . '</span>';
                    break;
            }
            echo '</p>';
            
            if ($status['archive_url']) {
                echo '<p><a href="' . esc_url($status['archive_url']) . '" target="_blank" class="button button-secondary">' . __('View in Archive', 'spun-web-archive-pro') . '</a></p>';
            }
            
            echo '<p><small>' . sprintf(__('Last submitted: %s', 'spun-web-archive-pro'), $status['submitted_at']) . '</small></p>';
        } else {
            echo '<p>' . __('Not yet submitted to archive.', 'spun-web-archive-pro') . '</p>';
        }
        
        echo '<p>';
        echo '<label><input type="checkbox" name="swap_exclude_from_archive" value="1" ' . checked($excluded, true, false) . ' /> ';
        echo __('Exclude from automatic archiving', 'spun-web-archive-pro') . '</label>';
        echo '</p>';
        
        if ($post->post_status === 'publish') {
            echo '<p><button type="button" class="button button-secondary" onclick="swapSubmitSingle(' . $post->ID . ')">' . __('Submit to Archive Now', 'spun-web-archive-pro') . '</button></p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_box_data($post_id) {
        if (!isset($_POST['swap_meta_box_nonce']) || !wp_verify_nonce($_POST['swap_meta_box_nonce'], 'swap_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $excluded = isset($_POST['swap_exclude_from_archive']) ? true : false;
        update_post_meta($post_id, '_swap_exclude_from_archive', $excluded);
    }
}