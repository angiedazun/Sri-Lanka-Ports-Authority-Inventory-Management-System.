<?php
/**
 * Authentication Middleware
 * Handles user authentication and session management
 */

class Auth {
    
    /**
     * Check if user is logged in
     */
    public static function check() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role' => $_SESSION['role'] ?? 'user',
            'email' => $_SESSION['email'] ?? ''
        ];
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        return self::check() && ($_SESSION['role'] ?? '') === $role;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public static function hasAnyRole($roles) {
        if (!self::check()) {
            return false;
        }
        
        $userRole = $_SESSION['role'] ?? '';
        return in_array($userRole, (array)$roles);
    }
    
    /**
     * Require authentication
     */
    public static function requireLogin() {
        if (!self::check()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            $loginUrl = defined('BASE_URL') ? BASE_URL . '/auth/login.php' : '../auth/login.php';
            Response::redirect($loginUrl);
        }
        
        // Check session timeout
        if (self::isSessionExpired()) {
            self::logout();
            $_SESSION['message'] = 'Your session has expired. Please login again.';
            $_SESSION['message_type'] = 'warning';
            $loginUrl = defined('BASE_URL') ? BASE_URL . '/auth/login.php' : '../auth/login.php';
            Response::redirect($loginUrl);
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($role) {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            Logger::warning('Unauthorized access attempt', [
                'user' => self::id(),
                'required_role' => $role,
                'user_role' => $_SESSION['role'] ?? 'none'
            ]);
            
            Response::forbidden('Access denied. Insufficient permissions.');
        }
    }
    
    /**
     * Require any of the specified roles
     */
    public static function requireAnyRole($roles) {
        self::requireLogin();
        
        if (!self::hasAnyRole($roles)) {
            Logger::warning('Unauthorized access attempt', [
                'user' => self::id(),
                'required_roles' => $roles,
                'user_role' => $_SESSION['role'] ?? 'none'
            ]);
            
            Response::forbidden('Access denied. Insufficient permissions.');
        }
    }
    
    /**
     * Login user
     */
    public static function login($userId, $username, $fullName, $role, $email) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['role'] = $role;
        $_SESSION['email'] = $email;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // Generate session token for additional security
        $_SESSION['session_token'] = Security::generateToken(32);
        
        Logger::info('User logged in successfully', [
            'user_id' => $userId,
            'username' => $username
        ]);
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (self::check()) {
            Logger::info('User logged out', [
                'user_id' => self::id(),
                'username' => $_SESSION['username'] ?? 'unknown'
            ]);
        }
        
        // Clear all session data
        $_SESSION = [];
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        // Start new session for messages
        session_start();
    }
    
    /**
     * Check if session has expired
     */
    private static function isSessionExpired() {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        $elapsed = time() - $_SESSION['last_activity'];
        return $elapsed > SESSION_LIFETIME;
    }
    
    /**
     * Verify session integrity
     */
    public static function verifySessionIntegrity() {
        if (!self::check()) {
            return false;
        }
        
        // Check if IP address changed (optional - may cause issues with dynamic IPs)
        if (VERIFY_IP_ADDRESS && isset($_SESSION['ip_address'])) {
            if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                Logger::warning('Session IP address mismatch', [
                    'user_id' => self::id(),
                    'original_ip' => $_SESSION['ip_address'],
                    'current_ip' => $_SERVER['REMOTE_ADDR']
                ]);
                
                self::logout();
                return false;
            }
        }
        
        // Check if user agent changed
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                Logger::warning('Session user agent mismatch', [
                    'user_id' => self::id()
                ]);
                
                self::logout();
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get intended URL after login
     */
    public static function getIntendedUrl() {
        $url = $_SESSION['intended_url'] ?? '/slpasystem/pages/dashboard.php';
        unset($_SESSION['intended_url']);
        return $url;
    }
}
