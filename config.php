<?php
/**
 * TTT ZOOM Application Configuration
 * Version: 2.0
 * Last Updated: July 26, 2025
 * 
 * This file contains all application-wide constants and configuration settings
 */

// Prevent direct access
if (!defined('TTT_ZOOM_APP')) {
    define('TTT_ZOOM_APP', true);
}

// Application Information
define('APP_NAME', 'TTT ZOOM Attendance Management System');
define('APP_VERSION', '2.0.0');
define('APP_AUTHOR', 'NifTyCoon');
define('APP_DESCRIPTION', 'Professional Zoom attendance tracking and management system');

// Environment Configuration
define('ENVIRONMENT', 'production'); // 'development' or 'production'
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Application Paths
define('APP_ROOT', __DIR__);

// Dynamic URL detection for both local and live environments
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);
    
    // For local XAMPP environment
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return '/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM';
    }
    
    // For live server - adjust this based on your actual server path
    return str_replace('\\', '/', $basePath);
}

define('APP_URL', getBaseUrl());
define('ADMIN_PATH', APP_ROOT . '/admin');
define('COMMON_PATH', APP_ROOT . '/common');
define('LOGS_PATH', APP_ROOT . '/logs');
define('DATABASE_PATH', APP_ROOT . '/database');

// Database Configuration Constants
// Environment-specific database configuration
if (ENVIRONMENT === 'development') {
    // Local development settings (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'ttt_zoom_system');
} else {
    // Production settings - Update these for your live server
    define('DB_HOST', $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost');
    define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? $_SERVER['DB_USERNAME'] ?? 'root');
    define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? '');
    define('DB_NAME', $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'ttt_zoom_system');
}
define('DB_CHARSET', 'utf8mb4');

// File Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['xlsx', 'xls', 'csv']);

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// Zoom API Configuration
define('ZOOM_API_BASE_URL', 'https://api.zoom.us/v2');
define('ZOOM_JWT_EXPIRATION', 3600); // 1 hour
define('ZOOM_RATE_LIMIT_DELAY', 100); // milliseconds

// Logging Configuration
define('LOG_LEVEL', DEBUG_MODE ? 'DEBUG' : 'INFO');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('LOG_ROTATION_COUNT', 5);

// Email Configuration (if needed for notifications)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('FROM_EMAIL', 'noreply@ttt-zoom.local');
define('FROM_NAME', 'TTT ZOOM System');

// Application Features
define('FEATURE_BULK_REGISTRATION', true);
define('FEATURE_EXCEL_EXPORT', true);
define('FEATURE_WEBHOOK_LOGGING', true);
define('FEATURE_ATTENDANCE_ANALYTICS', true);

// Time Zone Configuration
define('DEFAULT_TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(DEFAULT_TIMEZONE);

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Set error log location
ini_set('error_log', LOGS_PATH . '/php_errors.log');

/**
 * Application Utility Functions
 */

/**
 * Get application configuration value
 */
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Check if application is in debug mode
 */
function isDebugMode() {
    return DEBUG_MODE;
}

/**
 * Get application version
 */
function getAppVersion() {
    return APP_VERSION;
}

/**
 * Get full application URL
 */
function getAppUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host . APP_URL . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Redirect to a page within the application
 */
function redirectTo($path, $queryParams = []) {
    $url = getAppUrl($path);
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Log application events
 */
function logEvent($message, $level = 'INFO', $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
    
    $logFile = LOGS_PATH . '/application.log';
    
    // Ensure logs directory exists
    if (!file_exists(LOGS_PATH)) {
        mkdir(LOGS_PATH, 0755, true);
    }
    
    // Rotate log if too large
    if (file_exists($logFile) && filesize($logFile) > LOG_MAX_SIZE) {
        for ($i = LOG_ROTATION_COUNT - 1; $i > 0; $i--) {
            $oldFile = $logFile . '.' . $i;
            $newFile = $logFile . '.' . ($i + 1);
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }
        rename($logFile, $logFile . '.1');
    }
    
    error_log($logEntry, 3, $logFile);
}

/**
 * Format file size for display
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Check if string is a valid email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if current user has admin privileges
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if current user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Format timestamp for display
 */
function formatDateTime($timestamp, $format = 'Y-m-d H:i:s') {
    if (empty($timestamp)) return '-';
    
    $dt = is_numeric($timestamp) ? 
        DateTime::createFromFormat('U', $timestamp) : 
        DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);
    
    if (!$dt) return $timestamp; // Return original if parsing fails
    
    return $dt->format($format);
}

/**
 * Get user-friendly error message
 */
function getFriendlyError($error) {
    $errorMessages = [
        'file_too_large' => 'File size exceeds the maximum allowed limit of ' . formatFileSize(MAX_FILE_SIZE),
        'invalid_file_type' => 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_FILE_TYPES),
        'database_error' => 'Database connection error. Please try again later.',
        'session_expired' => 'Your session has expired. Please log in again.',
        'access_denied' => 'You do not have permission to access this resource.',
        'validation_error' => 'Please check your input and try again.',
        'zoom_api_error' => 'Zoom API error. Please check your configuration.',
    ];
    
    return $errorMessages[$error] ?? 'An unexpected error occurred. Please try again.';
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log application start
if (isDebugMode()) {
    logEvent('Application configuration loaded', 'DEBUG', [
        'version' => APP_VERSION,
        'environment' => ENVIRONMENT,
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit')
    ]);
}

?>
