<?php
/**
 * Application Configuration
 */

// Check PHP version compatibility (must be first)
require_once __DIR__ . '/php-version-check.php';

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Environment (development/production)
// IMPORTANT: Set to 'production' for live deployment to disable error display
define('ENVIRONMENT', 'production');

// Base URLs
define('BASE_URL', 'https://donapaper.vn');
define('ADMIN_URL', 'https://quanly.donapaper.vn');
define('USER_URL', 'https://kinhdoanh.donapaper.vn');
define('API_URL', BASE_URL . '/api');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('ID_CARD_PATH', UPLOAD_PATH . '/id_cards');

// Session settings
define('SESSION_LIFETIME', 1800); // 30 minutes
define('SESSION_NAME', 'DONA_SESSION');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Spam protection
define('SPAM_WINDOW_SECONDS', 300); // 5 minutes
define('SPAM_MAX_ACTIONS', 20);
define('SPAM_BAN_DURATION', 600); // 10 minutes
define('SPAM_PERMANENT_BAN_THRESHOLD', 3); // 3 violations in 3 days

// File upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

// Include database config
require_once __DIR__ . '/database.php';
