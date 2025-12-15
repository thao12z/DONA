<?php
/**
 * Database Configuration
 * Update these values with your cPanel MySQL credentials
 *
 * CRITICAL SECURITY WARNING:
 * - NEVER use empty password in production!
 * - Set DB_PASS to your secure cPanel database password
 * - Ensure DB_USER matches your cPanel database username
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'dona_sales');
define('DB_USER', 'root');
define('DB_PASS', ''); // WARNING: Change this in production!
define('DB_CHARSET', 'utf8mb4');

/**
 * Database connection options
 */
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
