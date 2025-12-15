<?php
/**
 * Security Class
 * Handles CSRF protection, input validation, and security utilities
 */

class Security {
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        
        // Check token expiration
        if (time() - ($_SESSION[CSRF_TOKEN_NAME . '_time'] ?? 0) > CSRF_TOKEN_LIFETIME) {
            unset($_SESSION[CSRF_TOKEN_NAME]);
            unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
            return false;
        }
        
        // Compare tokens
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Get CSRF token HTML input field
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return [
                'valid' => false,
                'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
            ];
        }
        
        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Password must contain at least one uppercase letter'
            ];
        }
        
        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Password must contain at least one lowercase letter'
            ];
        }
        
        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Password must contain at least one number'
            ];
        }
        
        return ['valid' => true, 'message' => 'Password is strong'];
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check login attempts (Rate limiting)
     */
    public static function checkLoginAttempts($username) {
        $cacheKey = 'login_attempts_' . $username;
        
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'count' => 0,
                'time' => time()
            ];
        }
        
        $attempts = $_SESSION[$cacheKey];
        
        // Reset if lockout time passed
        if (time() - $attempts['time'] > LOGIN_LOCKOUT_TIME) {
            $_SESSION[$cacheKey] = [
                'count' => 0,
                'time' => time()
            ];
            return true;
        }
        
        // Check if locked out
        if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
            $remainingTime = LOGIN_LOCKOUT_TIME - (time() - $attempts['time']);
            Logger::warning("Login locked out for user: {$username}");
            return false;
        }
        
        return true;
    }
    
    /**
     * Record failed login attempt
     */
    public static function recordFailedLogin($username) {
        $cacheKey = 'login_attempts_' . $username;
        
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'count' => 0,
                'time' => time()
            ];
        }
        
        $_SESSION[$cacheKey]['count']++;
        $_SESSION[$cacheKey]['time'] = time();
        
        Logger::warning("Failed login attempt for user: {$username}");
    }
    
    /**
     * Reset login attempts
     */
    public static function resetLoginAttempts($username) {
        $cacheKey = 'login_attempts_' . $username;
        unset($_SESSION[$cacheKey]);
    }
    
    /**
     * Prevent XSS
     */
    public static function escape($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error'];
        }
        
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['valid' => false, 'message' => 'File size exceeds maximum allowed size'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_FILE_TYPES)) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }
        
        return ['valid' => true, 'message' => 'File is valid'];
    }
}
