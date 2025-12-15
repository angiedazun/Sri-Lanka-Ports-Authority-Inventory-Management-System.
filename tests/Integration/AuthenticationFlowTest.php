<?php
/**
 * Integration Test for Authentication Flow
 * 
 * @package SLPA\Tests\Integration
 * 
 * Note: Using SimpleTestCase for testing without PHPUnit dependency.
 * To use PHPUnit: composer require --dev phpunit/phpunit
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../SimpleTestCase.php';

class AuthenticationFlowTest extends TestCase {
    private $db;
    private $testUserId;
    
    protected function setUp(): void {
        TestDatabase::resetDatabase();
        $this->testUserId = createTestUser('auth_test_user', 'admin');
    }
    
    public function testCompleteLoginFlow() {
        // Step 1: Access login page
        $_SESSION = [];
        $this->assertFalse(isset($_SESSION['user_id']));
        
        // Step 2: Authenticate user
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT user_id, username, full_name, role, email FROM users WHERE username = 'auth_test_user'";
        $result = $conn->query($sql);
        $user = $result->fetch_assoc();
        
        // Step 3: Login using Auth::login()
        Auth::login(
            $user['user_id'],
            $user['username'],
            $user['full_name'],
            $user['role'],
            $user['email']
        );
        
        $this->assertTrue(isset($_SESSION['user_id']));
        $this->assertEquals($user['user_id'], $_SESSION['user_id']);
        $this->assertEquals('auth_test_user', $_SESSION['username']);
    }
    
    public function testFailedLoginAttempt() {
        $_SESSION = [];
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Try to authenticate with wrong password
        $sql = "SELECT user_id, password FROM users WHERE username = 'auth_test_user'";
        $result = $conn->query($sql);
        $user = $result->fetch_assoc();
        
        // Verify wrong password fails
        $wrongPassword = 'wrong_password';
        $verified = Security::verifyPassword($wrongPassword, $user['password']);
        
        $this->assertFalse($verified);
        $this->assertFalse(isset($_SESSION['user_id']));
    }
    
    public function testLogoutFlow() {
        // Login first
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT user_id, username, full_name, role, email FROM users WHERE username = 'auth_test_user'";
        $result = $conn->query($sql);
        $user = $result->fetch_assoc();
        
        Auth::login(
            $user['user_id'],
            $user['username'],
            $user['full_name'],
            $user['role'],
            $user['email']
        );
        
        $this->assertTrue(isset($_SESSION['user_id']));
        
        // Logout
        Auth::logout();
        
        $this->assertFalse(isset($_SESSION['user_id']));
    }
    
    public function testSessionPersistence() {
        // Login
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT user_id, username, full_name, role, email FROM users WHERE username = 'auth_test_user'";
        $result = $conn->query($sql);
        $user = $result->fetch_assoc();
        
        Auth::login(
            $user['user_id'],
            $user['username'],
            $user['full_name'],
            $user['role'],
            $user['email']
        );
        
        // Simulate page reload
        $sessionId = session_id();
        session_write_close();
        session_id($sessionId);
        session_start();
        
        // Check session persists
        $this->assertTrue(isset($_SESSION['user_id']));
        $this->assertEquals($this->testUserId, $_SESSION['user_id']);
    }
    
    protected function tearDown(): void {
        deleteTestUser($this->testUserId);
        $_SESSION = [];
        $_POST = [];
    }
}
