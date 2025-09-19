<?php
/**
 * Admin Page Handler
 * 
 * Handles the plugin's admin interface and settings pages.
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

class SWAP_Admin_Page {
    
    /**
     * Render the admin page
     */
    public function render() {
        // Handle form submissions
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'swap_settings')) {
            $this->save_settings();
        }
        
        // Get current settings
        $api_settings = get_option('swap_api_settings', array());
        $auto_settings = get_option('swap_auto_settings', array());
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper swap-tab-nav">
                <a href="#api-settings" class="nav-tab nav-tab-active"><?php _e('API Settings', 'spun-web-archive-pro'); ?></a>
                <a href="#auto-settings" class="nav-tab"><?php _e('Auto Submission', 'spun-web-archive-pro'); ?></a>
                <a href="#submissions" class="nav-tab"><?php _e('Submission History', 'spun-web-archive-pro'); ?></a>
                <a href="#documentation" class="nav-tab"><?php _e('Documentation', 'spun-web-archive-pro'); ?></a>
            </nav>
            
            <div class="swap-admin-container">
                <div class="swap-main-content">
                    <form method="post" action="">
                        <?php wp_nonce_field('swap_settings'); ?>
                        
                        <!-- API Settings Tab -->
                        <div class="swap-tab-content" id="api-settings">
                            <h2><?php _e('Archive.org Submission Settings', 'spun-web-archive-pro'); ?></h2>
                            <p><?php _e('Choose your submission method and configure settings for archiving content.', 'spun-web-archive-pro'); ?></p>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Submission Method', 'spun-web-archive-pro'); ?></th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><?php _e('Choose submission method', 'spun-web-archive-pro'); ?></legend>
                                            <label>
                                                <input type="radio" name="swap_api_settings[submission_method]" value="simple" 
                                                       <?php checked($api_settings['submission_method'] ?? 'simple', 'simple'); ?> />
                                                <?php _e('Simple Submission (Default)', 'spun-web-archive-pro'); ?>
                                            </label>
                                            <p class="description" style="margin-left: 25px; margin-bottom: 10px;">
                                                <?php _e('Submit URLs directly to web.archive.org/save/ without API credentials. No setup required.', 'spun-web-archive-pro'); ?>
                                            </p>
                                            
                                            <label>
                                                <input type="radio" name="swap_api_settings[submission_method]" value="api" 
                                                       <?php checked($api_settings['submission_method'] ?? 'simple', 'api'); ?> />
                                                <?php _e('API Submission (Advanced)', 'spun-web-archive-pro'); ?>
                                            </label>
                                            <p class="description" style="margin-left: 25px;">
                                                <?php _e('Use Archive.org S3 API with your credentials for advanced features. Requires API key and secret.', 'spun-web-archive-pro'); ?>
                                            </p>
                                        </fieldset>
                                        
                                        <div style="margin-top: 15px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #0073aa; border-radius: 3px;">
                                            <h4 style="margin-top: 0; color: #0073aa;"><?php _e('Submission Method Comparison', 'spun-web-archive-pro'); ?></h4>
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
                                                <div>
                                                    <strong><?php _e('Simple Submission (Recommended)', 'spun-web-archive-pro'); ?></strong>
                                                    <ul style="margin: 8px 0 0 20px; font-size: 13px;">
                                                        <li><?php _e('No setup required - works immediately', 'spun-web-archive-pro'); ?></li>
                                                        <li><?php _e('Free to use with no API limits', 'spun-web-archive-pro'); ?></li>
                                                        <li><?php _e('Submits directly to web.archive.org/save/', 'spun-web-archive-pro'); ?></li>
                                                        <li><?php _e('Perfect for most users and websites', 'spun-web-archive-pro'); ?></li>
                                                    </ul>
                                                </div>
                                                <div>
                                                    <strong><?php _e('API Submission (Advanced)', 'spun-web-archive-pro'); ?></strong>
                                                    <ul style="margin: 8px 0 0 20px; font-size: 13px;">
                                                        <li><?php _e('Requires Archive.org account and API setup', 'spun-web-archive-pro'); ?></li>
                                                        <li><?php _e('Uses S3 API for enhanced control', 'spun-web-archive-pro'); ?></li>
                                                        <li><?php _e('May have higher success rates for some content', 'spun-web-archive-pro'); ?></li>
                                                        <li><?php _e('Fallback to simple method if API fails', 'spun-web-archive-pro'); ?></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <div id="api-credentials-section" style="<?php echo ($api_settings['submission_method'] ?? 'simple') === 'simple' ? 'display: none;' : ''; ?>">
                                <h3><?php _e('API Credentials', 'spun-web-archive-pro'); ?></h3>
                                <p><?php _e('Configure your Archive.org S3 API credentials for advanced submission features.', 'spun-web-archive-pro'); ?></p>
                                
                                <?php
                                $credentials_configured = class_exists('SWAP_Credentials_Page') && SWAP_Credentials_Page::has_credentials();
                                ?>
                                
                                <div class="credentials-status-section" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px; margin: 15px 0;">
                                    <?php if ($credentials_configured): ?>
                                        <div style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                                            <strong>✓ <?php _e('API Credentials Configured', 'spun-web-archive-pro'); ?></strong>
                                            <p style="margin: 5px 0 0 0;"><?php _e('Your Archive.org S3 API credentials are configured and ready to use.', 'spun-web-archive-pro'); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                                            <strong>⚠ <?php _e('API Credentials Required', 'spun-web-archive-pro'); ?></strong>
                                            <p style="margin: 5px 0 0 0;"><?php _e('API credentials are required when using API submission method.', 'spun-web-archive-pro'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p>
                                        <a href="<?php echo admin_url('admin.php?page=spun-web-archive-pro-credentials'); ?>" class="button button-primary">
                                            <?php _e('Manage API Credentials', 'spun-web-archive-pro'); ?>
                                        </a>
                                        <?php if ($credentials_configured): ?>
                                            <a href="<?php echo admin_url('admin.php?page=spun-web-archive-pro-credentials'); ?>" class="button button-secondary">
                                                <?php _e('Test Connection', 'spun-web-archive-pro'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <p class="description">
                                        <?php _e('Use the API Credentials page to securely configure and test your Archive.org S3 API credentials.', 'spun-web-archive-pro'); ?>
                                    </p>
                                </div>
                            </div> <!-- End API credentials section -->
                        </div>
                        
                        <!-- Auto Submission Settings -->
                        <div class="swap-tab-content" id="auto-settings">
                            <h2><?php _e('Auto Submission Settings', 'spun-web-archive-pro'); ?></h2>
                            <p><?php _e('Configure automatic submission of new and updated content to the archive.', 'spun-web-archive-pro'); ?></p>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Auto Submission', 'spun-web-archive-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="swap_auto_settings[enabled]" value="1" 
                                                   <?php checked($auto_settings['enabled'] ?? false); ?> />
                                            <?php _e('Automatically submit new posts and pages to the archive', 'spun-web-archive-pro'); ?>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Post Types', 'spun-web-archive-pro'); ?></th>
                                    <td>
                                        <?php
                                        $post_types = get_post_types(array('public' => true), 'objects');
                                        $selected_types = $auto_settings['post_types'] ?? array('post', 'page');
                                        
                                        foreach ($post_types as $post_type) {
                                            $checked = in_array($post_type->name, $selected_types);
                                            echo '<label><input type="checkbox" name="swap_auto_settings[post_types][]" value="' . esc_attr($post_type->name) . '" ' . checked($checked, true, false) . ' /> ' . esc_html($post_type->label) . '</label><br>';
                                        }
                                        ?>
                                        <p class="description">
                                            <?php _e('Select which post types should be automatically archived.', 'spun-web-archive-pro'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Submit Updates', 'spun-web-archive-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="swap_auto_settings[submit_updates]" value="1" 
                                                   <?php checked($auto_settings['submit_updates'] ?? false); ?> />
                                            <?php _e('Also submit when existing content is updated', 'spun-web-archive-pro'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Enable this to archive content every time it\'s updated, not just when first published.', 'spun-web-archive-pro'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="auto_delay"><?php _e('Submission Delay', 'spun-web-archive-pro'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="auto_delay" name="swap_auto_settings[delay]" 
                                               value="<?php echo esc_attr($auto_settings['delay'] ?? 0); ?>" 
                                               min="0" max="3600" class="small-text" />
                                        <span><?php _e('seconds', 'spun-web-archive-pro'); ?></span>
                                        <p class="description">
                                            <?php _e('Delay before submitting to archive (0 = immediate). Useful to allow content to be fully processed.', 'spun-web-archive-pro'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        

                        
                        <!-- Submissions Tab -->
                        <div id="submissions" class="swap-tab-content" style="display: none;">
                            <h2><?php _e('Submission History', 'spun-web-archive-pro'); ?></h2>
                            <p><?php _e('View all archive submissions made by this plugin.', 'spun-web-archive-pro'); ?></p>
                            
                            <?php $this->render_submissions_table(); ?>
                        </div>
                        
                        <!-- Documentation Tab -->
                        <div id="documentation" class="swap-tab-content" style="display: none;">
                            <?php SWAP_Documentation_Page::display_documentation(); ?>
                        </div>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>
                
                <div class="swap-sidebar">
                    <div class="swap-info-box">
                        <h3><?php _e('Getting Started', 'spun-web-archive-pro'); ?></h3>
                        <ol>
                            <li><?php _e('Get your Archive.org API credentials from your account settings', 'spun-web-archive-pro'); ?></li>
                            <li><?php _e('Enter your API key and secret in the API Settings tab', 'spun-web-archive-pro'); ?></li>
                            <li><?php _e('Test your API connection', 'spun-web-archive-pro'); ?></li>
                            <li><?php _e('Configure auto submission settings', 'spun-web-archive-pro'); ?></li>
                            <li><?php _e('Use individual submission links for existing content', 'spun-web-archive-pro'); ?></li>
                        </ol>
                    </div>
                    
                    <div class="swap-info-box">
                        <h3><?php _e('Archive Status', 'spun-web-archive-pro'); ?></h3>
                        <?php $this->render_archive_stats(); ?>
                    </div>
                    
                    <div class="swap-info-box">
                        <h3><?php _e('Plugin Information', 'spun-web-archive-pro'); ?></h3>
                        <p><strong><?php _e('Version:', 'spun-web-archive-pro'); ?></strong> 
                            <a href="https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/" target="_blank">
                                <?php echo esc_html(SWAP_VERSION); ?>
                            </a>
                        </p>
                        <p><strong><?php _e('Plugin Page:', 'spun-web-archive-pro'); ?></strong> 
                            <a href="https://spunwebtechnology.com/spun-web-archive-pro-wordpress-wayback-archive/" target="_blank">
                                <?php _e('Visit Plugin Page', 'spun-web-archive-pro'); ?>
                            </a>
                        </p>
                        <p><strong><?php _e('Author:', 'spun-web-archive-pro'); ?></strong> 
                            <a href="<?php echo esc_url(SWAP_AUTHOR_URI); ?>" target="_blank">
                                <?php echo esc_html(SWAP_AUTHOR); ?>
                            </a>
                        </p>
                    </div>
                    
                    <div class="swap-info-box">
                        <h3><?php _e('Support', 'spun-web-archive-pro'); ?></h3>
                        <p><?php _e('Need help? Check our documentation or contact support.', 'spun-web-archive-pro'); ?></p>
                        <p>
                            <a href="https://spunwebtechnology.com/spun-web-archive-pro-end-user-documentation/" class="button button-secondary" target="_blank">
                                <?php _e('Documentation', 'spun-web-archive-pro'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Plugin Footer -->
            <div class="swap-footer">
                <div class="swap-footer-content">
                    <p>
                        <strong><?php echo esc_html(get_admin_page_title()); ?></strong> 
                        <?php printf(__('v%s by %s', 'spun-web-archive-pro'), 
                            esc_html(SWAP_VERSION), 
                            '<a href="' . esc_url(SWAP_AUTHOR_URI) . '" target="_blank">' . esc_html(SWAP_AUTHOR) . '</a>'
                        ); ?>
                    </p>
                    <p>
                        <a href="<?php echo esc_url(SWAP_AUTHOR_URI); ?>" target="_blank"><?php _e('Visit Plugin Homepage', 'spun-web-archive-pro'); ?></a> | 
                        <a href="mailto:<?php echo esc_attr(SWAP_SUPPORT_EMAIL); ?>"><?php _e('Support', 'spun-web-archive-pro'); ?></a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Save API settings (credentials are now handled by the centralized credentials page)
        if (isset($_POST['swap_api_settings'])) {
            $api_settings = array(
                'submission_method' => sanitize_text_field($_POST['swap_api_settings']['submission_method'] ?? 'simple'),
                'endpoint' => esc_url_raw($_POST['swap_api_settings']['endpoint'] ?? 'https://web.archive.org/save/')
            );
            update_option('swap_api_settings', $api_settings);
        }
        
        // Save auto submission settings
        if (isset($_POST['swap_auto_settings'])) {
            $auto_settings = array(
                'enabled' => isset($_POST['swap_auto_settings']['enabled']),
                'post_types' => isset($_POST['swap_auto_settings']['post_types']) ? array_map('sanitize_text_field', $_POST['swap_auto_settings']['post_types']) : array(),
                'submit_updates' => isset($_POST['swap_auto_settings']['submit_updates']),
                'delay' => intval($_POST['swap_auto_settings']['delay'])
            );
            update_option('swap_auto_settings', $auto_settings);
        }
        
        add_settings_error('swap_settings', 'settings_updated', __('Settings saved successfully!', 'spun-web-archive-pro'), 'updated');
    }
    
    /**
     * Render submissions table
     */
    private function render_submissions_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<p>' . __('No submissions found. The submissions table has not been created yet.', 'spun-web-archive-pro') . '</p>';
            return;
        }
        
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Get total count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);
        
        // Get submissions
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, p.post_title, p.post_type 
             FROM $table_name s 
             LEFT JOIN {$wpdb->posts} p ON s.post_id = p.ID 
             ORDER BY s.submitted_at DESC 
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        if (empty($submissions)) {
            echo '<p>' . __('No submissions found.', 'spun-web-archive-pro') . '</p>';
            return;
        }
        
        ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_items, 'spun-web-archive-pro'), number_format_i18n($total_items)); ?></span>
                <a href="<?php echo esc_url(add_query_arg(array('action' => 'swap_export_csv', 'nonce' => wp_create_nonce('swap_export_csv')), admin_url('admin.php'))); ?>" 
                   class="button" style="margin-left: 10px;">
                    <?php _e('Download CSV', 'spun-web-archive-pro'); ?>
                </a>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <?php
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                echo $page_links;
                ?>
            </div>
            <?php endif; ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Post/Page', 'spun-web-archive-pro'); ?></th>
                    <th scope="col"><?php _e('URL', 'spun-web-archive-pro'); ?></th>
                    <th scope="col"><?php _e('Status', 'spun-web-archive-pro'); ?></th>
                    <th scope="col"><?php _e('Archive URL', 'spun-web-archive-pro'); ?></th>
                    <th scope="col"><?php _e('Submitted', 'spun-web-archive-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                <tr>
                    <td>
                        <?php if ($submission->post_title): ?>
                            <strong>
                                <a href="<?php echo esc_url(get_edit_post_link($submission->post_id)); ?>">
                                    <?php echo esc_html($submission->post_title); ?>
                                </a>
                            </strong>
                            <br>
                            <small><?php echo esc_html(ucfirst($submission->post_type)); ?> (ID: <?php echo esc_html($submission->post_id); ?>)</small>
                        <?php else: ?>
                            <em><?php _e('Post not found', 'spun-web-archive-pro'); ?></em>
                            <br>
                            <small>ID: <?php echo esc_html($submission->post_id); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($submission->url); ?>" target="_blank">
                            <?php echo esc_html($submission->url); ?>
                        </a>
                    </td>
                    <td>
                        <?php
                        $status_class = '';
                        switch ($submission->status) {
                            case 'success':
                                $status_class = 'status-success';
                                $status_text = __('Success', 'spun-web-archive-pro');
                                break;
                            case 'failed':
                                $status_class = 'status-failed';
                                $status_text = __('Failed', 'spun-web-archive-pro');
                                break;
                            case 'pending':
                                $status_class = 'status-pending';
                                $status_text = __('Pending', 'spun-web-archive-pro');
                                break;
                            default:
                                $status_class = 'status-unknown';
                                $status_text = esc_html($submission->status);
                        }
                        ?>
                        <span class="submission-status <?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($submission->archive_url): ?>
                            <a href="<?php echo esc_url($submission->archive_url); ?>" target="_blank">
                                <?php _e('View Archive', 'spun-web-archive-pro'); ?>
                            </a>
                        <?php else: ?>
                            <em><?php _e('Not available', 'spun-web-archive-pro'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->submitted_at))); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo $page_links; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <style>
        .submission-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-success {
            background-color: #d4edda;
            color: #155724;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-unknown {
            background-color: #e2e3e5;
            color: #383d41;
        }
        </style>
        <?php
    }
    
    /**
     * Render archive statistics
     */
    private function render_archive_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'swap_submissions';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<p>' . __('No submissions yet.', 'spun-web-archive-pro') . '</p>';
            return;
        }
        
        $total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $successful_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'");
        $failed_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'");
        $pending_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        
        echo '<ul>';
        echo '<li>' . sprintf(__('Total Submissions: %d', 'spun-web-archive-pro'), $total_submissions) . '</li>';
        echo '<li>' . sprintf(__('Successful: %d', 'spun-web-archive-pro'), $successful_submissions) . '</li>';
        echo '<li>' . sprintf(__('Failed: %d', 'spun-web-archive-pro'), $failed_submissions) . '</li>';
        echo '<li>' . sprintf(__('Pending: %d', 'spun-web-archive-pro'), $pending_submissions) . '</li>';
        echo '</ul>';
    }
}