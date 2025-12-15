<?php
/**
 * Configuration Management
 * Central configuration file for the application
 */

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'slpasystem');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'SLPA Inventory Management System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/slpasystem');

// Path Configuration
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/includes');
define('PUBLIC_PATH', BASE_PATH . '/assets');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('LOG_PATH', BASE_PATH . '/logs');
define('BACKUP_PATH', BASE_PATH . '/backups');

// Session Configuration
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_NAME', 'SLPA_SESSION');
define('SESSION_SECURE', false); // Set true for HTTPS
define('SESSION_HTTPONLY', true);

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('VERIFY_IP_ADDRESS', false); // Set true to verify IP (may cause issues with dynamic IPs)
define('SECURE_HEADERS', true); // Enable security headers
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? base64_encode(random_bytes(32)));

// Pagination Configuration
define('RECORDS_PER_PAGE', 50);
define('MAX_RECORDS_PER_PAGE', 200);

// File Upload Configuration
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'xlsx', 'csv']);

// Email Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM', $_ENV['SMTP_FROM'] ?? 'noreply@slpa.lk');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'SLPA System');

// Logging Configuration
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'error'); // debug, info, warning, error
define('LOG_FILE', LOG_PATH . '/app.log');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Cache Configuration
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600);

// Report Configuration
define('REPORT_TEMP_PATH', BASE_PATH . '/temp');
define('REPORT_MAX_RECORDS', 50000);

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Colombo');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . '/php_errors.log');
}

// Create necessary directories
$directories = [LOG_PATH, UPLOAD_PATH, BACKUP_PATH, REPORT_TEMP_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
