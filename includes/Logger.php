<?php
/**
 * Logger Class
 * Handles application logging with different severity levels
 */

class Logger {
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    
    private static $levels = [
        'debug' => self::DEBUG,
        'info' => self::INFO,
        'warning' => self::WARNING,
        'error' => self::ERROR
    ];
    
    /**
     * Log debug message
     */
    public static function debug($message, $context = []) {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Main logging method
     */
    private static function log($level, $message, $context = []) {
        // Check if logging is enabled for this level
        if (!self::shouldLog($level)) {
            return;
        }
        
        // Format log entry
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $userInfo = isset($_SESSION['username']) ? ' | User: ' . $_SESSION['username'] : '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        
        $logEntry = sprintf(
            "[%s] [%s] [IP: %s]%s %s%s\n",
            $timestamp,
            $level,
            $ipAddress,
            $userInfo,
            $message,
            $contextStr
        );
        
        // Write to log file
        self::writeToFile($logEntry);
        
        // If error level, also log to PHP error log
        if ($level === self::ERROR) {
            error_log($message);
        }
    }
    
    /**
     * Check if we should log this level
     */
    private static function shouldLog($level) {
        $configLevel = strtolower(LOG_LEVEL);
        $levels = ['debug', 'info', 'warning', 'error'];
        
        $currentIndex = array_search($configLevel, $levels);
        $messageIndex = array_search(strtolower($level), $levels);
        
        return $messageIndex >= $currentIndex;
    }
    
    /**
     * Write log entry to file
     */
    private static function writeToFile($logEntry) {
        $logFile = LOG_FILE;
        
        // Check file size and rotate if necessary
        if (file_exists($logFile) && filesize($logFile) > LOG_MAX_SIZE) {
            self::rotateLog($logFile);
        }
        
        // Append to log file
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Rotate log file when it gets too large
     */
    private static function rotateLog($logFile) {
        $timestamp = date('Y-m-d_His');
        $archiveFile = str_replace('.log', "_{$timestamp}.log", $logFile);
        
        @rename($logFile, $archiveFile);
        
        // Keep only last 5 archived logs
        $logDir = dirname($logFile);
        $logFiles = glob($logDir . '/*.log');
        
        if (count($logFiles) > 6) { // 1 current + 5 archives
            usort($logFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Delete oldest logs
            for ($i = 0; $i < count($logFiles) - 6; $i++) {
                @unlink($logFiles[$i]);
            }
        }
    }
    
    /**
     * Log database query
     */
    public static function query($sql, $params = []) {
        if (APP_DEBUG) {
            self::debug("SQL Query: " . $sql, ['params' => $params]);
        }
    }
    
    /**
     * Log user activity
     */
    public static function activity($action, $details = []) {
        $user = $_SESSION['username'] ?? 'Guest';
        self::info("User Activity: {$action}", array_merge(['user' => $user], $details));
    }
}
