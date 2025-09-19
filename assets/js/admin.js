/**
 * Spun Web Archive Pro Admin JavaScript
 */

(function($) {
    'use strict';
    
    var SWAP = {
        isSubmitting: false,
        
        init: function() {
            console.log('SWAP Admin JS: Initializing...');
            this.bindEvents();
            this.initTabs();
        },
        
        bindEvents: function() {
            // API test button
            $('#test-api').on('click', this.testApiConnection);
            
            // Submission method radio buttons
            $('input[name="swap_api_settings[submission_method]"]').on('change', this.toggleApiCredentials);
            
            // Form submission validation
            $('form').on('submit', this.validateFormSubmission);
            
            // Single post submission
            window.swapSubmitSingle = this.submitSinglePost;
            
            // Initialize submission method toggle on page load
            this.toggleApiCredentials();
        },
        
        initTabs: function() {
            // Tab functionality for admin page
            $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                // Remove active class from all tabs
                $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
                // Add active class to clicked tab
                $(this).addClass('nav-tab-active');
                
                // Hide all tab content
                $('.swap-tab-content').hide();
                // Show target tab content
                $(target).show();
            });
            
            // Show first tab by default
            $('.swap-tab-content').hide();
            $('#api-settings').show();
        },
        
        // API testing is now handled by the centralized credentials page
        
        toggleApiCredentials: function() {
            var submissionMethod = $('input[name="swap_api_settings[submission_method]"]:checked').val();
            var $apiSection = $('#api-credentials-section');
            var $errorDiv = $('#api-validation-error');
            
            if (submissionMethod === 'api') {
                $apiSection.show();
            } else {
                $apiSection.hide();
                $errorDiv.hide(); // Hide error when switching to simple mode
            }
        },
        
        validateFormSubmission: function(e) {
            var submissionMethod = $('input[name="swap_api_settings[submission_method]"]:checked').val();
            var $errorDiv = $('#api-validation-error');
            
            // Only validate if API method is selected
            if (submissionMethod === 'api') {
                var apiKey = $('#api_key').val().trim();
                var apiSecret = $('#api_secret').val().trim();
                
                if (!apiKey || !apiSecret) {
                    e.preventDefault(); // Prevent form submission
                    $errorDiv.show();
                    
                    // Scroll to the error message
                    $('html, body').animate({
                        scrollTop: $errorDiv.offset().top - 100
                    }, 500);
                    
                    return false;
                }
            }
            
            // Hide error if validation passes
            $errorDiv.hide();
            return true;
        },
        

        

        

        

        

        

        

        

        

        

        

        

        

        

        
        submitSinglePost: function(postId) {
            if (SWAP.isSubmitting) {
                return;
            }
            
            if (!confirm('Submit this post to the Internet Archive?')) {
                return;
            }
            
            SWAP.isSubmitting = true;
            
            $.ajax({
                url: swap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'swap_submit_single',
                    nonce: swap_ajax.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Post successfully submitted to the archive!');
                        location.reload();
                    } else {
                        alert('Submission failed: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                },
                complete: function() {
                    SWAP.isSubmitting = false;
                }
            });
        },
        
        displayCallbackResults: function(data) {
            var $callbackResults = $('#callback-results');
            var $testDetails = $('#test-details');
            var $callbackUrls = $('#callback-urls');
            
            // Show the callback results section
            $callbackResults.show();
            
            // Display test details
            var detailsHtml = '<h4>Test Details</h4>';
            detailsHtml += '<p><strong>Test ID:</strong> ' + data.test_id + '</p>';
            
            if (data.response_time) {
                detailsHtml += '<p><strong>Response Time:</strong> ' + data.response_time + 'ms</p>';
            }
            
            if (data.endpoint) {
                detailsHtml += '<p><strong>Endpoint:</strong> ' + data.endpoint + '</p>';
            }
            
            if (data.status_code) {
                detailsHtml += '<p><strong>Status Code:</strong> ' + data.status_code + '</p>';
            }
            
            $testDetails.html(detailsHtml);
            
            // Display callback URLs if available
            if (data.callback_url || data.status_url) {
                var urlsHtml = '<h4>Callback URLs</h4>';
                
                if (data.callback_url) {
                    urlsHtml += '<p><strong>Callback URL:</strong> <a href="' + data.callback_url + '" target="_blank">' + data.callback_url + '</a></p>';
                }
                
                if (data.status_url) {
                    urlsHtml += '<p><strong>Status URL:</strong> <a href="' + data.status_url + '" target="_blank">' + data.status_url + '</a></p>';
                }
                
                $callbackUrls.html(urlsHtml);
            } else {
                $callbackUrls.empty();
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        SWAP.init();
    });
    
})(jQuery);