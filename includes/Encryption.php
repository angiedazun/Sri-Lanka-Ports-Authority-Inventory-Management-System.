<?php
/**
 * Encryption Class
 * Handles data encryption and decryption
 */

class Encryption {
    private static $method = 'AES-256-CBC';
    
    /**
     * Get encryption key from environment or generate
     */
    private static function getKey() {
        $key = $_ENV['ENCRYPTION_KEY'] ?? null;
        
        if (!$key) {
            // Generate key if not exists (store this in .env)
            $key = base64_encode(random_bytes(32));
            Logger::warning('Encryption key not set in .env, using temporary key');
        }
        
        return base64_decode($key);
    }
    
    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return $data;
        }
        
        try {
            $key = self::getKey();
            $iv = random_bytes(openssl_cipher_iv_length(self::$method));
            
            $encrypted = openssl_encrypt($data, self::$method, $key, 0, $iv);
            
            if ($encrypted === false) {
                throw new Exception('Encryption failed');
            }
            
            // Combine IV and encrypted data
            return base64_encode($iv . $encrypted);
            
        } catch (Exception $e) {
            Logger::error('Encryption error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt($data) {
        if (empty($data)) {
            return $data;
        }
        
        try {
            $key = self::getKey();
            $data = base64_decode($data);
            
            $ivLength = openssl_cipher_iv_length(self::$method);
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            
            $decrypted = openssl_decrypt($encrypted, self::$method, $key, 0, $iv);
            
            if ($decrypted === false) {
                throw new Exception('Decryption failed');
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            Logger::error('Decryption error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Hash data (one-way)
     */
    public static function hash($data) {
        return hash('sha256', $data);
    }
    
    /**
     * Generate secure random string
     */
    public static function randomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Generate UUID v4
     */
    public static function uuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
