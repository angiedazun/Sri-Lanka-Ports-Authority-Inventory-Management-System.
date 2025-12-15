<?php
/**
 * Input Sanitizer Class
 * Advanced input sanitization and XSS prevention
 */

class Sanitizer {
    
    /**
     * Sanitize string input
     */
    public static function string($input) {
        if (is_array($input)) {
            return array_map([self::class, 'string'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        
        return $input;
    }
    
    /**
     * Sanitize for HTML output (XSS prevention)
     */
    public static function html($input) {
        if (is_array($input)) {
            return array_map([self::class, 'html'], $input);
        }
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize for JavaScript output
     */
    public static function js($input) {
        if (is_array($input)) {
            return array_map([self::class, 'js'], $input);
        }
        
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * Sanitize email
     */
    public static function email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize URL
     */
    public static function url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitize integer
     */
    public static function int($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     */
    public static function float($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize filename
     */
    public static function filename($filename) {
        // Remove any path separators
        $filename = basename($filename);
        
        // Remove special characters except dots, hyphens, and underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent directory traversal
        $filename = str_replace(['..', './'], '', $filename);
        
        return $filename;
    }
    
    /**
     * Sanitize SQL input (use with prepared statements)
     */
    public static function sql($input, $connection) {
        if (is_array($input)) {
            return array_map(function($item) use ($connection) {
                return self::sql($item, $connection);
            }, $input);
        }
        
        return $connection->real_escape_string($input);
    }
    
    /**
     * Clean HTML (allow specific tags)
     */
    public static function cleanHtml($input, $allowedTags = '<p><br><strong><em><u>') {
        return strip_tags($input, $allowedTags);
    }
    
    /**
     * Remove all HTML tags
     */
    public static function stripTags($input) {
        return strip_tags($input);
    }
    
    /**
     * Sanitize array keys
     */
    public static function arrayKeys($array) {
        $sanitized = [];
        foreach ($array as $key => $value) {
            $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $sanitized[$sanitizedKey] = $value;
        }
        return $sanitized;
    }
    
    /**
     * Deep sanitize array
     */
    public static function array($array, $type = 'string') {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            $key = self::string($key);
            
            if (is_array($value)) {
                $sanitized[$key] = self::array($value, $type);
            } else {
                switch ($type) {
                    case 'html':
                        $sanitized[$key] = self::html($value);
                        break;
                    case 'int':
                        $sanitized[$key] = self::int($value);
                        break;
                    case 'float':
                        $sanitized[$key] = self::float($value);
                        break;
                    case 'email':
                        $sanitized[$key] = self::email($value);
                        break;
                    default:
                        $sanitized[$key] = self::string($value);
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Prevent SQL injection in LIKE queries
     */
    public static function likeSafe($input, $connection) {
        $input = self::sql($input, $connection);
        $input = str_replace(['%', '_'], ['\%', '\_'], $input);
        return $input;
    }
    
    /**
     * Sanitize phone number
     */
    public static function phone($phone) {
        return preg_replace('/[^0-9+\-\(\)\s]/', '', $phone);
    }
    
    /**
     * Sanitize and validate date
     */
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        
        if ($d && $d->format($format) === $date) {
            return $date;
        }
        
        return null;
    }
}
