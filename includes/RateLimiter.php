<?php
/**
 * Rate Limiter Class
 * Prevents brute force attacks and API abuse
 */

class RateLimiter {
    
    /**
     * Check if request is allowed
     */
    public static function check($identifier, $maxAttempts = 5, $decayMinutes = 15) {
        $key = self::getKey($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'reset_time' => time() + ($decayMinutes * 60)
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window passed
        if (time() > $data['reset_time']) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'reset_time' => time() + ($decayMinutes * 60)
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            $remainingTime = $data['reset_time'] - time();
            Logger::warning("Rate limit exceeded", [
                'identifier' => $identifier,
                'attempts' => $data['attempts'],
                'remaining_time' => $remainingTime
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Record an attempt
     */
    public static function hit($identifier, $decayMinutes = 15) {
        $key = self::getKey($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'reset_time' => time() + ($decayMinutes * 60)
            ];
        }
        
        $_SESSION[$key]['attempts']++;
    }
    
    /**
     * Get remaining attempts
     */
    public static function remaining($identifier, $maxAttempts = 5) {
        $key = self::getKey($identifier);
        
        if (!isset($_SESSION[$key])) {
            return $maxAttempts;
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window passed
        if (time() > $data['reset_time']) {
            return $maxAttempts;
        }
        
        return max(0, $maxAttempts - $data['attempts']);
    }
    
    /**
     * Get time until reset
     */
    public static function availableIn($identifier) {
        $key = self::getKey($identifier);
        
        if (!isset($_SESSION[$key])) {
            return 0;
        }
        
        $data = $_SESSION[$key];
        return max(0, $data['reset_time'] - time());
    }
    
    /**
     * Clear rate limit for identifier
     */
    public static function clear($identifier) {
        $key = self::getKey($identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * Generate rate limit key
     */
    private static function getKey($identifier) {
        return 'rate_limit_' . md5($identifier);
    }
    
    /**
     * Throttle requests (for API endpoints)
     */
    public static function throttle($identifier, $maxAttempts = 60, $decayMinutes = 1) {
        if (!self::check($identifier, $maxAttempts, $decayMinutes)) {
            $availableIn = self::availableIn($identifier);
            
            http_response_code(429);
            header('Retry-After: ' . $availableIn);
            
            Response::error(
                'Too many requests. Please try again later.',
                429,
                ['retry_after' => $availableIn . ' seconds']
            );
        }
        
        self::hit($identifier, $decayMinutes);
    }
}
