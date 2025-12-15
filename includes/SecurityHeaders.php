<?php
/**
 * Security Headers Middleware
 * Sets secure HTTP headers
 */

class SecurityHeaders {
    
    /**
     * Apply all security headers
     */
    public static function apply() {
        if (!SECURE_HEADERS) {
            return;
        }
        
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (adjust as needed)
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://cdnjs.cloudflare.com",
            "connect-src 'self'",
            "frame-ancestors 'self'"
        ];
        header('Content-Security-Policy: ' . implode('; ', $csp));
        
        // Strict Transport Security (HSTS) - Only enable on HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Permissions Policy (formerly Feature Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // Remove server signature
        header_remove('X-Powered-By');
    }
}

// Auto-apply security headers
SecurityHeaders::apply();
