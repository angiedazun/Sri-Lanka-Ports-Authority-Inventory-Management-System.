<?php
/**
 * Autoloader
 * Automatically loads classes when they are used
 */

spl_autoload_register(function ($class) {
    // Define base directory for class files
    $baseDir = __DIR__ . '/';
    
    // Convert class name to file path
    $file = $baseDir . $class . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load all core classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Sanitizer.php';
require_once __DIR__ . '/Encryption.php';
require_once __DIR__ . '/AuditTrail.php';
require_once __DIR__ . '/RateLimiter.php';

// Load new functionality classes
require_once __DIR__ . '/ExportManager.php';
require_once __DIR__ . '/NotificationManager.php';
require_once __DIR__ . '/BackupManager.php';
require_once __DIR__ . '/SearchManager.php';
require_once __DIR__ . '/AnalyticsManager.php';
require_once __DIR__ . '/BulkOperations.php';

// Load code quality classes
require_once __DIR__ . '/CodeAnalyzer.php';
require_once __DIR__ . '/CodeFormatter.php';
require_once __DIR__ . '/CodeValidator.php';
require_once __DIR__ . '/Container.php';
require_once __DIR__ . '/Exceptions.php';
require_once __DIR__ . '/ErrorHandler.php';

// Load stub files for optional libraries (prevents IDE warnings)
if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    require_once __DIR__ . '/stubs/phpspreadsheet.php';
}
if (!class_exists('TCPDF')) {
    require_once __DIR__ . '/stubs/tcpdf.php';
}
if (!extension_loaded('redis')) {
    require_once __DIR__ . '/stubs/redis.php';
}
if (!extension_loaded('memcached')) {
    require_once __DIR__ . '/stubs/memcached.php';
}
