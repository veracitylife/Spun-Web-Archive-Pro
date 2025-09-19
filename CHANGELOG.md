# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.5] - 2025-01-20

### Added
- **WordPress Compatibility Helper**: New `wordpress-compat.php` file for enhanced WordPress environment validation
- **WordPress Function Stubs**: Comprehensive `.wordpress-stubs.php` file for IDE and linter compatibility
- **Static Analysis Configuration**: Added `phpstan.neon` for proper WordPress plugin analysis
- **Developer Documentation**: New `DEVELOPER-README.md` with setup instructions and troubleshooting

### Enhanced
- **Linter Compatibility**: Resolved false positive warnings from static analysis tools
- **Development Environment**: Improved IDE support with WordPress function signatures
- **Code Quality**: Enhanced static analysis configuration for WordPress plugins
- **Documentation**: Comprehensive developer setup and troubleshooting guide

### Technical Details
- Added WordPress environment validation functions in compatibility helper
- Created comprehensive WordPress core function stubs for development tools
- Configured PHPStan for WordPress plugin development best practices
- Updated all version references from 0.3.4 to 0.3.5 across the entire codebase
- Enhanced development workflow with proper tooling configuration

## [0.3.4] - 2025-01-20

### Security
- **Enhanced SQL Query Safety**: Improved security in uninstall cleanup process with proper query escaping
- **Database Security Hardening**: Updated transient and user meta deletion queries to use prepared statements
- **Table Name Escaping**: Implemented proper table name escaping for DROP TABLE operations during uninstall

### Technical Details
- Modified `uninstall.php` to use `$wpdb->prepare()` for transient and user meta deletion queries
- Added proper escaping for database table names in cleanup operations
- Enhanced security comments and documentation for database operations
- Updated all version references from 0.3.3 to 0.3.4 across the entire codebase
- Improved code security standards compliance throughout the plugin

## [0.3.2] - 2025-01-20

### Changed
- **Removed Bulk Actions**: Eliminated WordPress bulk actions functionality from All Posts and All Pages screens
- **Individual Post Submission**: Replaced bulk actions with individual "Submit to Archive" links for each post/page
- **Streamlined UI**: Removed bulk submission settings and references from admin interface
- **Simplified Workflow**: Focus on individual post submission for better control and user experience

### Removed
- `SWAP_Bulk_Actions` class and all bulk action functionality
- `SWAP_Bulk_Submitter` class and bulk submission processing
- Bulk submission settings from admin page (batch size, delay settings)
- Bulk submission references from documentation and UI

### Added
- `SWAP_Post_Actions` class for individual post submission links
- Individual "Submit to Archive" row actions in post/page lists
- On-demand submission functionality similar to auto-submit feature

### Technical Details
- Updated plugin description to reflect individual submission focus
- Removed bulk action hooks and processing from WordPress admin
- Updated all version references to 0.3.2 across codebase
- Enhanced post list interface with individual submission controls

## [0.3.1] - 2025-01-19

### Enhanced
- **Comprehensive Error Handling**: Added robust connection error detection for Archive.org timeouts and unreachable sites
- **User-Friendly Error Messages**: Implemented clear, actionable error messages including "This site can't be reached" for DNS failures
- **Smart Error Recovery**: Added automatic error type detection with specific guidance for timeouts, connection refused, and SSL errors
- **Enhanced Visual Feedback**: Improved error display with color-coded status indicators and detailed error explanations in admin interface
- **Better Connection Diagnostics**: Enhanced API testing with specific error categorization and troubleshooting guidance
- **Improved JavaScript Error Handling**: Enhanced AJAX error handling with dynamic error messages and conditional warnings
- **CSS Error Styling**: Added comprehensive CSS styles for test results, error states, and visual feedback components

### Technical Details
- Updated `class-archive-api.php` with enhanced error detection for `wp_remote_get` calls
- Modified `class-credentials-page.php` with improved AJAX response handling and structured error responses
- Enhanced JavaScript error handling in credentials testing interface with specific timeout and connection error messages
- Added CSS styles in `admin.css` for improved visual feedback on error states and test results
- Implemented error type classification system with `error_type` and `redirect_to_settings` response parameters

## [0.3.0] - 2025-01-14

### Added
- **WordPress Native Bulk Actions**: Integrated bulk submission directly into WordPress All Posts and All Pages screens
- **SWAP_Bulk_Actions Class**: New dedicated class for handling WordPress bulk actions with proper hooks and processing
- **Seamless User Experience**: Submit multiple posts/pages to archive using familiar WordPress bulk action interface
- **Batch Processing Integration**: Bulk actions respect existing batch size and delay settings for optimal performance

### Enhanced
- **User Interface**: Replaced dedicated bulk submission tab with native WordPress bulk actions for better workflow integration
- **Code Organization**: Streamlined bulk submission functionality into dedicated bulk actions handler
- **Performance**: Optimized bulk processing using WordPress native bulk action system
- **Documentation**: Updated README.md to reflect new bulk actions functionality and usage instructions

### Removed
- **Legacy Bulk Submission Interface**: Removed dedicated bulk submission tab from admin settings page
- **Old AJAX Handlers**: Cleaned up legacy `ajax_submit_single` method and related bulk submission AJAX functionality
- **Redundant UI Elements**: Simplified admin interface by removing duplicate bulk submission controls

### Technical Details
- Added `SWAP_Bulk_Actions` class with proper WordPress hooks for `edit-post` and `edit-page` screens
- Implemented `handle_bulk_action` method for processing bulk archive submissions
- Integrated with existing `SWAP_Bulk_Submitter` class for consistent submission handling
- Updated all version references from 0.2.9 to 0.3.0 across the codebase
- Maintained backward compatibility with existing bulk submission settings and configuration

## [0.2.9] - 2025-01-14

### Added
- **Centralized Credentials Management**: New dedicated credentials page with secure storage and management
- **Secure Credentials Storage**: Encrypted storage of API credentials using WordPress options
- **Real-time API Testing**: Instant connection testing with visual pass/fail feedback on credentials page
- **Credentials Status Indicators**: Visual indicators showing credential configuration status across the plugin
- **Dedicated Admin Menu**: New "Spun Web Archive Pro" main menu with submenus for credentials, settings, and documentation
- **Centralized Access**: All plugin features automatically use centralized credentials without duplication

### Enhanced
- **Admin Menu Structure**: Converted from options submenu to main menu with organized submenus
- **Credentials Integration**: Updated all classes to use centralized credentials with backward compatibility
- **User Interface**: Improved admin interface with credential status and management links
- **Security**: Enhanced credential handling with proper sanitization and validation
- **Code Organization**: Streamlined credential management across all plugin components

### Technical Details
- Added `SWAP_Credentials_Page` class for centralized credential management
- Enhanced `SWAP_Archive_API` class to use centralized credentials with fallback support
- Updated admin page to show credential status instead of inline credential forms
- Removed duplicate API testing functionality in favor of centralized testing
- Added proper menu structure with `add_menu_page` and organized submenus
- Enhanced JavaScript and AJAX handling for new menu structure
- Updated all version references to 0.2.9 across the codebase

### Removed
- **Duplicate Credential Forms**: Removed redundant API credential inputs from main settings
- **Old API Testing**: Removed duplicate API testing functionality from main admin page
- **Legacy AJAX Handlers**: Cleaned up old API testing AJAX handlers

## [0.2.8] - 2025-01-14

### Added
- **API Test Callbacks**: Enhanced API testing with detailed callback functionality
- **Test Result Storage**: Comprehensive test result tracking with transient storage
- **Response Time Monitoring**: Real-time response time tracking for API connections
- **Detailed Test Information**: Enhanced test results showing endpoint, status codes, headers, and error details
- **Callback URL Generation**: Dynamic callback URLs for test result retrieval
- **Test ID System**: Unique test identification for tracking and callback correlation
- **Enhanced Admin Interface**: Improved API test section with callback options and detailed results display

### Enhanced
- Updated Archive API class with callback support methods
- Enhanced JavaScript admin interface for callback result display
- Improved AJAX handlers to support callback functionality
- Better error handling and logging for API test operations
- Enhanced user interface with detailed test information display

### Technical Details
- Added `SWAP_API_Callback` class for handling test callbacks and result storage
- Enhanced `test_connection` method with callback parameters and result storage
- Added helper methods: `store_test_result`, `log_connection_attempt`, `sanitize_headers_for_log`
- Updated JavaScript to handle callback results and display detailed test information
- Added callback URL generation and test result retrieval endpoints
- Enhanced admin page with callback options and results display sections

## [0.2.7] - 2025-01-14

### Added
- **Submission Method Selection**: Radio button interface to choose between Simple Submission (no API required) and API Submission (advanced)
- **Non-API Submission Method**: Direct submission to Wayback Machine without requiring Archive.org API credentials
- **Comprehensive Method Explanation**: Detailed comparison section explaining differences between API and non-API methods with pros/cons
- **CSV Export Functionality**: Download complete submission history as CSV file with local URLs and archive.org links
- **Enhanced Form Validation**: Real-time validation with visual error indicators and user-friendly messaging
- **Improved User Experience**: Better visual feedback, clearer instructions, and streamlined workflow

### Enhanced
- Updated submission history to properly display archive.org links instead of local links
- Improved admin interface with better organization and visual hierarchy
- Enhanced error handling and user feedback throughout the plugin
- Better documentation and help text for all submission methods
- Strengthened security with proper nonce verification for CSV exports

### Technical Details
- Added `submit_to_wayback_simple` method for non-API submissions
- Modified `submit_url` method to handle submission method selection
- Enhanced admin page with conditional API credentials section and explanatory content
- Added JavaScript for radio button toggling, form validation, and dynamic UI updates
- Implemented CSV export handler with proper security checks and data formatting
- Added comprehensive method comparison interface with styled grid layout

## [0.2.6] - 2025-01-14

### Fixed
- Enhanced API test button functionality with comprehensive debugging capabilities
- Improved JavaScript error handling and console logging for better troubleshooting
- Better AJAX error reporting and user feedback mechanisms
- Enhanced PHP error logging for API connection testing and validation

### Enhanced
- Added detailed debugging output for API test functionality
- Improved error messages and user feedback throughout the plugin
- Better handling of missing JavaScript objects and AJAX request failures
- Enhanced console logging for easier debugging and development

### Technical Details
- Added comprehensive console logging to JavaScript admin functions
- Enhanced AJAX error handling with detailed error reporting
- Improved PHP error logging in AJAX handlers for better debugging
- Added fallback handling for missing JavaScript objects and variables

## [0.2.5] - 2024-12-30

### Added
- Comprehensive documentation page integrated into admin dashboard
- Direct access to plugin documentation via Documentation tab in admin interface
- Complete user guide covering installation, configuration, daily usage, and troubleshooting
- Dedicated documentation class for better code organization

### Enhanced
- Improved admin interface with dedicated documentation section
- Better user onboarding experience with integrated help system
- Enhanced navigation with documentation tab in admin panel

### Technical Details
- Added `SWAP_Documentation_Page` class for documentation management
- Integrated documentation display into existing admin page structure
- Updated admin navigation to include documentation access

## [0.2.4] - 2024-12-302025-01-14

### Fixed
- **Critical API Test Function Fix**: Resolved nonce mismatch preventing API connection testing
- **Archive API Initialization**: Fixed missing Archive API instance in AJAX handlers
- **AJAX Nonce Consistency**: Updated all AJAX handlers to use consistent nonce verification
- **Enhanced Error Logging**: Added comprehensive debugging and error logging for troubleshooting
- **JavaScript Console Logging**: Added detailed AJAX response logging for better debugging

### Changed
- Improved AJAX error handling with detailed status and error information
- Enhanced debugging capabilities for API connection testing
- Better error feedback and logging throughout the plugin

### Technical Details
- Fixed nonce creation to use 'swap_ajax_nonce' consistently across JavaScript and PHP
- Added Archive API initialization check in AJAX handlers to prevent undefined property errors
- Updated both 'ajax_test_api' and 'ajax_get_posts' handlers for consistent nonce verification
- Added server-side error logging and client-side console logging for comprehensive debugging

## [0.2.3] - 2025-01-14

### Added
- Enhanced API test connection with proper Archive.org S3 API integration
- Visual feedback for API test results with green "pass" and red "failed" indicators
- Dual submission method: Wayback Machine Save API and S3 API fallback
- Proper AWS S3 signature authentication for Archive.org
- Comprehensive error handling and status reporting

### Fixed
- API test connection now properly validates credentials against Archive.org
- Corrected S3 API implementation following Archive.org documentation
- Improved URL submission process with better success detection
- Enhanced error messages for better user feedback

### Changed
- Updated documentation link to new end-user documentation URL
- Improved API connection testing with real-time status display
- Enhanced submission workflow with multiple fallback methods
- Better integration with Archive.org's S3 API endpoints

### Security
- Implemented proper AWS S3 signature authentication
- Enhanced API credential validation and error handling
- Improved secure communication with Archive.org services

## [0.2.2] - 2025-01-14

### Added
- Complete uninstall functionality that removes all plugin data
- Proper cleanup of database tables, options, post meta, and transients on plugin deletion
- Enhanced security checks in uninstall process
- Comprehensive data removal including user meta and cached data

### Fixed
- Removed duplicate `ajax_get_posts` function to eliminate conflicts
- Enhanced PHP 8.1 compatibility and maintained backward compatibility
- Improved code organization and eliminated redundant functions
- Better error handling and security validation

### Changed
- Maintained PHP 8.1 compatibility for broader server support
- Improved plugin architecture with cleaner code structure
- Enhanced compatibility checks for WordPress and PHP versions

### Security
- Strengthened uninstall security with multiple validation checks
- Improved AJAX request handling and nonce verification
- Enhanced user permission validation throughout the plugin

## [0.2.1] - 2025-01-13

### Added
- Initial release of Spun Web Archive Pro
- Bulk submission functionality for archiving multiple posts
- Auto submission feature for new content
- Advanced submission tracking and status monitoring
- Professional admin interface with enhanced UI
- API integration with Internet Archive (Wayback Machine)

### Features
- WordPress 5.0+ compatibility
- PHP 8.1+ compatibility
- Secure AJAX handling
- Database optimization
- User-friendly admin dashboard
- Comprehensive submission management

## [Unreleased]

### Planned
- Additional archive service integrations
- Enhanced reporting and analytics
- Scheduled submission improvements
- Advanced filtering options