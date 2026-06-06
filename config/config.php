<?php
/**
 * Application Configuration
 */

// Define Base URL for absolute paths throughout the application
if (!defined('BASE_URL')) {
    define('BASE_URL', '/VendorBridge/'); 
}

// Database Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'vendorbridge_db');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
