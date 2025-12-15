<?php
/**
 * Unit Test for Security Class
 * 
 * @package SLPA\Tests\Unit
 * 
 * Note: Using SimpleTestCase for testing without PHPUnit dependency.
 * To use PHPUnit: composer require --dev phpunit/phpunit
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../SimpleTestCase.php';

class SecurityTest extends TestCase {
    private $security;
    
    protected function setUp(): void {
        $this->security = new Security();
    }
    
    public function testCSRFTokenGeneration() {
        $token = $this->security->generateCSRFToken();
        
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertGreaterThan(32, strlen($token));
    }
    
    public function testCSRFTokenValidation() {
        $token = $this->security->generateCSRFToken();
        $_SESSION['csrf_token'] = $token;
        
        $this->assertTrue($this->security->validateCSRFToken($token));
        $this->assertFalse($this->security->validateCSRFToken('invalid_token'));
    }
    
    public function testPasswordHashing() {
        $password = 'SecurePassword123!';
        $hash = $this->security->hashPassword($password);
        
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }
    
    public function testPasswordVerification() {
        $password = 'SecurePassword123!';
        $hash = $this->security->hashPassword($password);
        
        $this->assertTrue($this->security->verifyPassword($password, $hash));
        $this->assertFalse($this->security->verifyPassword('WrongPassword', $hash));
    }
    
    public function testXSSPrevention() {
        $input = '<script>alert("XSS")</script>';
        $cleaned = $this->security->sanitizeInput($input);
        
        $this->assertStringNotContainsString('<script>', $cleaned);
    }
    
    public function testSQLInjectionPrevention() {
        $input = "'; DROP TABLE users; --";
        $cleaned = $this->security->sanitizeInput($input);
        
        $this->assertNotEquals($input, $cleaned);
    }
    
    public function testRandomStringGeneration() {
        $string1 = $this->security->generateRandomString(32);
        $string2 = $this->security->generateRandomString(32);
        
        $this->assertEquals(32, strlen($string1));
        $this->assertNotEquals($string1, $string2);
    }
    
    public function testEncryption() {
        $data = 'Sensitive information';
        $key = 'encryption_key_123';
        
        $encrypted = $this->security->encrypt($data, $key);
        $this->assertNotEquals($data, $encrypted);
        
        $decrypted = $this->security->decrypt($encrypted, $key);
        $this->assertEquals($data, $decrypted);
    }
    
    protected function tearDown(): void {
        unset($_SESSION['csrf_token']);
    }
}
