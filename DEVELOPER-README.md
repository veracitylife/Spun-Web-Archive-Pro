# Developer README - Spun Web Archive Pro

## Static Analysis and Linter Warnings

### Understanding "Undefined Function" Warnings

If you're seeing linter warnings about "undefined functions" for WordPress core functions like `wp_verify_nonce`, `get_option`, `esc_html`, etc., these are **false positives**. Here's why:

1. **WordPress Context**: These functions are part of WordPress core and are available when the plugin runs within WordPress
2. **Static Analysis Limitation**: Linters analyze PHP files in isolation without loading WordPress
3. **No Actual Errors**: The plugin works correctly in WordPress environment

### Solutions Implemented

#### 1. WordPress Compatibility Helper (`includes/wordpress-compat.php`)
- Provides runtime validation of WordPress environment
- Includes safe wrapper functions for WordPress API calls
- Logs warnings if WordPress environment is not properly loaded

#### 2. WordPress Function Stubs (`.wordpress-stubs.php`)
- Provides function signatures for static analysis tools
- Helps IDEs understand WordPress API
- **Not included in plugin execution** - development only

#### 3. PHPStan Configuration (`phpstan.neon`)
- Configures static analysis to use WordPress stubs
- Ignores common WordPress-related false positives
- Provides appropriate analysis level for WordPress plugins

### Configuring Your Development Environment

#### For PHPStan Users
```bash
# Install PHPStan
composer require --dev phpstan/phpstan

# Run analysis with provided configuration
phpstan analyse --configuration=phpstan.neon
```

#### For VS Code Users
Add to your `settings.json`:
```json
{
    "php.validate.executablePath": "path/to/php",
    "php.suggest.basic": false,
    "intelephense.stubs": [
        "wordpress"
    ]
}
```

#### For PhpStorm Users
1. Install WordPress plugin
2. Enable WordPress support in project settings
3. Point to WordPress installation or use stubs

### Verification Commands

```bash
# Check syntax of all PHP files
php -l spun-web-archive-pro.php
Get-ChildItem -Path "includes\*.php" | ForEach-Object { php -l $_.FullName }

# Run with WordPress stubs (if PHPStan installed)
phpstan analyse
```

### Plugin Architecture

#### Security Measures
- ✅ All files have `ABSPATH` checks
- ✅ Nonce verification for all forms
- ✅ Capability checks for admin functions
- ✅ Input sanitization and output escaping
- ✅ SQL injection prevention with prepared statements

#### Code Quality
- ✅ No syntax errors
- ✅ WordPress coding standards
- ✅ Proper error handling
- ✅ Optimized database queries
- ✅ Clean separation of concerns

### Common Linter Warnings Explained

| Warning | Explanation | Status |
|---------|-------------|---------|
| `wp_verify_nonce` undefined | WordPress security function | ✅ False positive |
| `get_option` undefined | WordPress options API | ✅ False positive |
| `esc_html` undefined | WordPress sanitization | ✅ False positive |
| `current_user_can` undefined | WordPress capabilities | ✅ False positive |
| `add_action` undefined | WordPress hooks system | ✅ False positive |

### Best Practices

1. **Always test in WordPress environment** - Static analysis can't replace real testing
2. **Use WordPress stubs for development** - Improves IDE experience
3. **Configure your linter properly** - Use provided configuration files
4. **Focus on real issues** - Don't spend time on WordPress API false positives

### Support

For questions about development setup or linter configuration:
- Email: support@spunwebtechnology.com
- Documentation: See `includes/class-documentation-page.php`

---

**Note**: This plugin is professionally developed and follows WordPress security and coding standards. The linter warnings about WordPress functions are expected and normal for WordPress plugins analyzed outside the WordPress environment.