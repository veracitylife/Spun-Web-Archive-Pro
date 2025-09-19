<?php
/**
 * Archive.org API Handler
 * 
 * Handles communication with Archive.org S3 API for submitting URLs
 * to the Wayback Machine.
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

class SWAP_Archive_API {
    
    /**
     * API credentials
     */
    private $api_key;
    private $api_secret;
    
    /**
     * API endpoints
     */
    private $save_endpoint = 'https://web.archive.org/save/';
    private $availability_endpoint = 'https://archive.org/wayback/available';
    
    /**
     * Constructor
     */
    public function __construct($api_key = '', $api_secret = '') {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        
        // Load from centralized credentials if not provided
        if (empty($this->api_key) || empty($this->api_secret)) {
            // First try the new centralized credentials
            if (class_exists('SWAP_Credentials_Page')) {
                $this->api_key = SWAP_Credentials_Page::get_access_key();
                $this->api_secret = SWAP_Credentials_Page::get_secret_key();
            }
            
            // Fallback to old settings for backward compatibility
            if (empty($this->api_key) || empty($this->api_secret)) {
                $api_settings = get_option('swap_api_settings', array());
                $this->api_key = $this->api_key ?: ($api_settings['api_key'] ?? '');
                $this->api_secret = $this->api_secret ?: ($api_settings['api_secret'] ?? '');
            }
        }
    }
    
    /**
     * Test API connection with callback support
     */
    public function test_connection($api_key = null, $api_secret = null, $callback_enabled = false) {
        // Generate test ID for callback tracking
        $test_id = $callback_enabled ? uniqid('test_', true) : null;
        $start_time = microtime(true);
        
        // Use provided credentials or instance credentials
        $test_key = $api_key ?: $this->api_key;
        $test_secret = $api_secret ?: $this->api_secret;
        
        if (empty($test_key) || empty($test_secret)) {
            $result = array(
                'success' => false,
                'message' => __('API key and secret are required.', 'spun-web-archive-pro'),
                'test_id' => $test_id
            );
            
            if ($callback_enabled) {
                $this->store_test_result($test_id, $result, array(
                    'error_type' => 'missing_credentials',
                    'response_time' => round((microtime(true) - $start_time) * 1000, 2)
                ));
            }
            
            return $result;
        }
        
        // Create temporary instance if testing different credentials
        if ($api_key && $api_secret && ($api_key !== $this->api_key || $api_secret !== $this->api_secret)) {
            $temp_api = new self($test_key, $test_secret);
            return $temp_api->test_connection(null, null, $callback_enabled);
        }
        
        // Test with Archive.org S3 API - use a simple bucket list request
        $test_endpoint = 'https://s3.us.archive.org/';
        
        // Create authorization header using AWS S3 signature
        $date = gmdate('D, d M Y H:i:s T');
        $string_to_sign = "GET\n\n\n{$date}\n/";
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $test_secret, true));
        
        $headers = array(
            'Date' => $date,
            'Authorization' => 'AWS ' . $test_key . ':' . $signature,
            'User-Agent' => 'SpunWebArchivePro/' . SWAP_VERSION
        );
        
        // Log connection attempt if callback enabled
        if ($callback_enabled) {
            $this->log_connection_attempt($test_id, $test_endpoint, $headers);
        }
        
        $response = wp_remote_get($test_endpoint, array(
            'headers' => $headers,
            'timeout' => 15,
            'sslverify' => true
        ));
        
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        if (is_wp_error($response)) {
            $error_code = $response->get_error_code();
            $error_message = $response->get_error_message();
            
            // Provide more specific error messages for common connection issues
            $user_message = '';
            $error_type = 'wp_error';
            
            if (strpos($error_code, 'timeout') !== false || strpos($error_message, 'timeout') !== false) {
                $user_message = __('Archive.org connection timed out. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-pro');
                $error_type = 'timeout';
            } elseif (strpos($error_message, 'resolve host') !== false || strpos($error_message, 'name resolution') !== false) {
                $user_message = __('Archive.org cannot be reached. Please check your internet connection and try again.', 'spun-web-archive-pro');
                $error_type = 'dns_failure';
            } elseif (strpos($error_message, 'connect') !== false) {
                $user_message = __('Cannot connect to Archive.org. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-pro');
                $error_type = 'connection_refused';
            } elseif (strpos($error_message, 'SSL') !== false || strpos($error_message, 'certificate') !== false) {
                $user_message = __('SSL/Certificate error connecting to Archive.org. Please check your server configuration.', 'spun-web-archive-pro');
                $error_type = 'ssl_error';
            } else {
                $user_message = sprintf(__('Connection failed: %s', 'spun-web-archive-pro'), $error_message);
            }
            
            $result = array(
                'success' => false,
                'message' => $user_message,
                'test_id' => $test_id,
                'error_type' => $error_type,
                'redirect_to_settings' => true
            );
            
            if ($callback_enabled) {
                $this->store_test_result($test_id, $result, array(
                    'error_type' => $error_type,
                    'error_details' => $error_message,
                    'error_code' => $error_code,
                    'response_time' => $response_time,
                    'endpoint' => $test_endpoint,
                    'headers' => $headers
                ));
            }
            
            return $result;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Check for successful authentication
        if ($response_code === 200) {
            $result = array(
                'success' => true,
                'message' => __('API connection successful!', 'spun-web-archive-pro'),
                'test_id' => $test_id
            );
            
            if ($callback_enabled) {
                $this->store_test_result($test_id, $result, array(
                    'response_code' => $response_code,
                    'response_time' => $response_time,
                    'endpoint' => $test_endpoint,
                    'headers' => $headers,
                    'response_body_length' => strlen($response_body)
                ));
                
                // Update connection status
                update_option('swap_api_connection_status', 'connected');
                update_option('swap_api_last_test', current_time('timestamp'));
            }
            
            return $result;
        } elseif ($response_code === 403) {
            $result = array(
                'success' => false,
                'message' => __('Authentication failed. Please check your API credentials.', 'spun-web-archive-pro'),
                'test_id' => $test_id
            );
            
            if ($callback_enabled) {
                $this->store_test_result($test_id, $result, array(
                    'error_type' => 'authentication_failed',
                    'response_code' => $response_code,
                    'response_time' => $response_time,
                    'endpoint' => $test_endpoint,
                    'headers' => $headers
                ));
                
                update_option('swap_api_connection_status', 'disconnected');
            }
            
            return $result;
        } elseif ($response_code === 401) {
            $result = array(
                'success' => false,
                'message' => __('Invalid API credentials.', 'spun-web-archive-pro'),
                'test_id' => $test_id
            );
            
            if ($callback_enabled) {
                $this->store_test_result($test_id, $result, array(
                    'error_type' => 'invalid_credentials',
                    'response_code' => $response_code,
                    'response_time' => $response_time,
                    'endpoint' => $test_endpoint,
                    'headers' => $headers
                ));
                
                update_option('swap_api_connection_status', 'disconnected');
            }
            
            return $result;
        } else {
            $result = array(
                'success' => false,
                'message' => sprintf(__('Unexpected response code: %d', 'spun-web-archive-pro'), $response_code),
                'test_id' => $test_id
            );
            
            if ($callback_enabled) {
                $this->store_test_result($test_id, $result, array(
                    'error_type' => 'unexpected_response',
                    'response_code' => $response_code,
                    'response_time' => $response_time,
                    'endpoint' => $test_endpoint,
                    'headers' => $headers,
                    'response_body' => substr($response_body, 0, 500) // First 500 chars
                ));
                
                update_option('swap_api_connection_status', 'disconnected');
            }
            
            return $result;
        }
    }
    
    /**
     * Submit URL to Internet Archive
     */
    public function submit_url($url, $options = array()) {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return array(
                'success' => false,
                'error' => __('Invalid URL provided', 'spun-web-archive-pro')
            );
        }
        
        // Get submission method from settings
        $api_settings = get_option('swap_api_settings', array());
        $submission_method = $api_settings['submission_method'] ?? 'simple';
        
        if ($submission_method === 'simple') {
            // Use simple submission method (no API credentials required)
            return $this->submit_to_wayback_simple($url, $options);
        } else {
            // Use API submission method (requires credentials)
            if (empty($this->api_key) || empty($this->api_secret)) {
                return array(
                    'success' => false,
                    'error' => __('API credentials not configured', 'spun-web-archive-pro')
                );
            }
            
            // First, try using the Wayback Machine Save API with credentials
            $save_result = $this->submit_to_wayback($url, $options);
            if ($save_result['success']) {
                return $save_result;
            }
            
            // If Wayback fails, try creating an item using S3 API
            return $this->create_archive_item($url, $options);
        }
    }
    
    /**
     * Submit URL to Wayback Machine Save API
     */
    private function submit_to_wayback($url, $options = array()) {
        $save_endpoint = 'https://web.archive.org/save/' . $url;
        
        $headers = array(
            'User-Agent' => 'SpunWebArchivePro/' . SWAP_VERSION
        );
        
        // Add authentication if available
        if (!empty($this->api_key) && !empty($this->api_secret)) {
            $headers['Authorization'] = 'LOW ' . $this->api_key . ':' . $this->api_secret;
        }
        
        $response = wp_remote_get($save_endpoint, array(
            'headers' => $headers,
            'timeout' => 60,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            $error_code = $response->get_error_code();
            $error_message = $response->get_error_message();
            
            // Provide more specific error messages for common connection issues
            $user_message = '';
            
            if (strpos($error_code, 'timeout') !== false || strpos($error_message, 'timeout') !== false) {
                $user_message = __('Archive.org connection timed out. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-pro');
            } elseif (strpos($error_message, 'resolve host') !== false || strpos($error_message, 'name resolution') !== false) {
                $user_message = __('Archive.org cannot be reached. Please check your internet connection and try again.', 'spun-web-archive-pro');
            } elseif (strpos($error_message, 'connect') !== false) {
                $user_message = __('Cannot connect to Archive.org. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-pro');
            } elseif (strpos($error_message, 'SSL') !== false || strpos($error_message, 'certificate') !== false) {
                $user_message = __('SSL/Certificate error connecting to Archive.org. Please check your server configuration.', 'spun-web-archive-pro');
            } else {
                $user_message = sprintf(__('Archive submission failed: %s', 'spun-web-archive-pro'), $error_message);
            }
            
            return array(
                'success' => false,
                'error' => $user_message,
                'error_type' => 'connection_error',
                'redirect_to_settings' => true
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        if ($response_code >= 200 && $response_code < 400) {
            // Extract archive URL from response headers
            $archive_url = '';
            if (isset($response_headers['content-location'])) {
                $archive_url = $response_headers['content-location'];
            } elseif (isset($response_headers['location'])) {
                $archive_url = $response_headers['location'];
            } else {
                // Construct likely archive URL
                $archive_url = 'https://web.archive.org/web/' . date('YmdHis') . '/' . $url;
            }
            
            return array(
                'success' => true,
                'archive_url' => $archive_url,
                'response_code' => $response_code,
                'method' => 'wayback'
            );
        }
        
        return array(
            'success' => false,
            'error' => sprintf(__('Wayback submission failed with code: %d', 'spun-web-archive-pro'), $response_code)
        );
    }
    
    /**
     * Submit URL to Wayback Machine Save API without authentication (Simple method)
     */
    private function submit_to_wayback_simple($url, $options = array()) {
        $save_endpoint = 'https://web.archive.org/save/' . $url;
        
        $headers = array(
            'User-Agent' => 'SpunWebArchivePro/' . SWAP_VERSION
        );
        
        $response = wp_remote_get($save_endpoint, array(
            'headers' => $headers,
            'timeout' => 60,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            $error_code = $response->get_error_code();
            $error_message = $response->get_error_message();
            
            // Provide more specific error messages for common connection issues
            $user_message = '';
            
            if (strpos($error_code, 'timeout') !== false || strpos($error_message, 'timeout') !== false) {
                $user_message = __('Archive.org connection timed out. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-pro');
            } elseif (strpos($error_message, 'resolve host') !== false || strpos($error_message, 'name resolution') !== false) {
                $user_message = __('Archive.org cannot be reached. Please check your internet connection and try again.', 'spun-web-archive-pro');
            } elseif (strpos($error_message, 'connect') !== false) {
                $user_message = __('Cannot connect to Archive.org. The site may be temporarily unavailable. Please try again later.', 'spun-web-archive-pro');
            } elseif (strpos($error_message, 'SSL') !== false || strpos($error_message, 'certificate') !== false) {
                $user_message = __('SSL/Certificate error connecting to Archive.org. Please check your server configuration.', 'spun-web-archive-pro');
            } else {
                $user_message = sprintf(__('Archive submission failed: %s', 'spun-web-archive-pro'), $error_message);
            }
            
            return array(
                'success' => false,
                'error' => $user_message,
                'error_type' => 'connection_error',
                'redirect_to_settings' => true
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        if ($response_code >= 200 && $response_code < 400) {
            // Extract archive URL from response headers
            $archive_url = '';
            if (isset($response_headers['content-location'])) {
                $archive_url = $response_headers['content-location'];
            } elseif (isset($response_headers['location'])) {
                $archive_url = $response_headers['location'];
            } else {
                // Construct likely archive URL
                $archive_url = 'https://web.archive.org/web/' . date('YmdHis') . '/' . $url;
            }
            
            return array(
                'success' => true,
                'archive_url' => $archive_url,
                'response_code' => $response_code,
                'method' => 'simple'
            );
        }
        
        return array(
            'success' => false,
            'error' => sprintf(__('Simple submission failed with code: %d', 'spun-web-archive-pro'), $response_code)
        );
    }
    
    /**
     * Create archive item using S3 API
     */
    private function create_archive_item($url, $options = array()) {
        // Generate unique identifier for the item
        $parsed_url = parse_url($url);
        $identifier = 'web-' . sanitize_title($parsed_url['host']) . '-' . date('YmdHis') . '-' . wp_generate_password(8, false);
        
        // Prepare metadata
        $metadata = array(
            'title' => isset($options['title']) ? $options['title'] : 'Web Archive: ' . $url,
            'description' => isset($options['description']) ? $options['description'] : 'Archived web page from ' . $url,
            'mediatype' => 'web',
            'collection' => 'opensource_media', // Default collection for web content
            'creator' => 'SpunWebArchivePro',
            'subject' => 'web archive',
            'originalurl' => $url,
            'date' => date('Y-m-d')
        );
        
        // Create the item endpoint
        $endpoint = 'https://s3.us.archive.org/' . $identifier;
        
        // Prepare the file content (simple HTML redirect)
        $file_content = sprintf(
            '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=%s"><title>Archived: %s</title></head><body><p>This page has been archived. <a href="%s">Click here to view the original</a>.</p></body></html>',
            esc_url($url),
            esc_html($url),
            esc_url($url)
        );
        
        // Create authorization header
        $date = gmdate('D, d M Y H:i:s T');
        $content_type = 'text/html';
        $content_md5 = base64_encode(md5($file_content, true));
        
        $string_to_sign = "PUT\n{$content_md5}\n{$content_type}\n{$date}\n";
        
        // Add metadata headers to signature
        $meta_headers = array();
        foreach ($metadata as $key => $value) {
            $header_key = 'x-archive-meta-' . $key;
            $meta_headers[$header_key] = $value;
            $string_to_sign .= $header_key . ':' . $value . "\n";
        }
        
        $string_to_sign .= '/' . $identifier . '/index.html';
        
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->api_secret, true));
        
        $headers = array_merge($meta_headers, array(
            'Date' => $date,
            'Content-Type' => $content_type,
            'Content-MD5' => $content_md5,
            'Authorization' => 'AWS ' . $this->api_key . ':' . $signature,
            'User-Agent' => 'SpunWebArchivePro/' . SWAP_VERSION
        ));
        
        $response = wp_remote_request($endpoint . '/index.html', array(
            'method' => 'PUT',
            'headers' => $headers,
            'body' => $file_content,
            'timeout' => 60,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            $archive_url = 'https://archive.org/details/' . $identifier;
            
            return array(
                'success' => true,
                'archive_url' => $archive_url,
                'identifier' => $identifier,
                'response_code' => $response_code,
                'method' => 's3'
            );
        } else {
            return array(
                'success' => false,
                'error' => sprintf(__('S3 submission failed with code: %d', 'spun-web-archive-pro'), $response_code),
                'response_code' => $response_code
            );
        }
    }
    
    /**
     * Check if URL is available in archive
     */
    public function check_availability($url) {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $request_url = add_query_arg(array(
            'url' => $url,
            'timestamp' => date('YmdHis')
        ), $this->availability_endpoint);
        
        $response = wp_remote_get($request_url, array(
            'timeout' => 30,
            'sslverify' => true,
            'headers' => array(
                'User-Agent' => 'SpunWebArchivePro/' . SWAP_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            // Log the error for debugging but return false for availability check
            error_log('Archive.org availability check failed: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200) {
            $data = json_decode($response_body, true);
            return $data;
        }
        
        return false;
    }
    
    /**
     * Store test result for callback tracking
     */
    private function store_test_result($test_id, $result, $details = array()) {
        if (empty($test_id)) {
            return;
        }
        
        $test_data = array(
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
            'timestamp' => current_time('timestamp'),
            'details' => $details
        );
        
        // Merge additional details
        $test_data = array_merge($test_data, $details);
        
        // Store for 1 hour
        set_transient('swap_api_test_' . $test_id, $test_data, HOUR_IN_SECONDS);
        
        // Also store in connection log
        $this->add_to_connection_log($test_id, $test_data);
    }
    
    /**
     * Log connection attempt
     */
    private function log_connection_attempt($test_id, $endpoint, $headers) {
        if (empty($test_id)) {
            return;
        }
        
        $log_entry = array(
            'test_id' => $test_id,
            'timestamp' => current_time('timestamp'),
            'endpoint' => $endpoint,
            'headers' => $this->sanitize_headers_for_log($headers),
            'status' => 'attempting'
        );
        
        $this->add_to_connection_log($test_id, $log_entry);
    }
    
    /**
     * Add entry to connection log
     */
    private function add_to_connection_log($test_id, $data) {
        $log_key = 'swap_connection_log_' . $test_id;
        $existing_log = get_transient($log_key) ?: array();
        
        $existing_log[] = array(
            'timestamp' => current_time('timestamp'),
            'data' => $data
        );
        
        // Keep only last 50 entries
        if (count($existing_log) > 50) {
            $existing_log = array_slice($existing_log, -50);
        }
        
        // Store for 1 hour
        set_transient($log_key, $existing_log, HOUR_IN_SECONDS);
    }
    
    /**
     * Sanitize headers for logging (remove sensitive data)
     */
    private function sanitize_headers_for_log($headers) {
        $sanitized = $headers;
        
        // Remove or mask sensitive headers
        if (isset($sanitized['Authorization'])) {
            $auth_parts = explode(':', $sanitized['Authorization']);
            if (count($auth_parts) >= 2) {
                $sanitized['Authorization'] = $auth_parts[0] . ':***MASKED***';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get callback URL for test result
     */
    public function get_test_callback_url($test_id) {
        if (class_exists('SWAP_API_Callback')) {
            return SWAP_API_Callback::get_callback_url('api_test_result', array('test_id' => $test_id));
        }
        
        return '';
    }
    
    /**
     * Get connection status callback URL
     */
    public function get_status_callback_url() {
        if (class_exists('SWAP_API_Callback')) {
            return SWAP_API_Callback::get_callback_url('connection_status');
        }
        
        return '';
    }
    
    /**
     * Get archive URL for a given URL
     */
    public function get_archive_url($url, $timestamp = null) {
        $availability = $this->check_availability($url);
        
        if ($availability && isset($availability['archived_snapshots']['closest']['url'])) {
            return $availability['archived_snapshots']['closest']['url'];
        }
        
        return false;
    }
    
    /**
     * Batch submit multiple URLs
     */
    public function batch_submit($urls, $options = array()) {
        $results = array();
        $delay = isset($options['delay']) ? intval($options['delay']) : 2;
        
        foreach ($urls as $url) {
            $result = $this->submit_url($url, $options);
            $results[] = array(
                'url' => $url,
                'result' => $result
            );
            
            // Add delay between requests to avoid rate limiting
            if ($delay > 0 && count($results) < count($urls)) {
                sleep($delay);
            }
        }
        
        return $results;
    }
    
    /**
     * Validate API credentials
     */
    public function validate_credentials($api_key, $api_secret) {
        $temp_api = new self($api_key, $api_secret);
        return $temp_api->test_connection();
    }
    
    /**
     * Get rate limit information
     */
    public function get_rate_limit_info() {
        // Archive.org doesn't provide specific rate limit endpoints
        // Return general guidelines
        return array(
            'requests_per_minute' => 30,
            'requests_per_hour' => 1000,
            'recommended_delay' => 2
        );
    }
    
    /**
     * Log API request for debugging
     */
    private function log_request($url, $response, $error = null) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'url' => $url,
            'response_code' => is_array($response) ? $response['response_code'] : 'N/A',
            'error' => $error
        );
        
        error_log('SWAP API Request: ' . json_encode($log_data));
    }
}