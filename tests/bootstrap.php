<?php
/**
 * PHPUnit Bootstrap File
 * Initializes testing environment
 * 
 * @package SLPA\Tests
 * @version 1.0.0
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define test mode
define('TEST_MODE', true);

// Include autoloader
require_once __DIR__ . '/../includes/autoload.php';

// Load test configuration
$testConfigFile = __DIR__ . '/config/test_config.php';
if (file_exists($testConfigFile)) {
    require_once $testConfigFile;
}

// Initialize test database
class TestDatabase {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            $config = [
                'host' => getenv('TEST_DB_HOST') ?: 'localhost',
                'user' => getenv('TEST_DB_USER') ?: 'root',
                'pass' => getenv('TEST_DB_PASS') ?: '',
                'name' => getenv('TEST_DB_NAME') ?: 'slpa_test'
            ];
            
            self::$connection = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['name']
            );
            
            if (self::$connection->connect_error) {
                throw new DatabaseException("Test database connection failed: " . self::$connection->connect_error);
            }
        }
        
        return self::$connection;
    }
    
    public static function resetDatabase() {
        $db = self::getConnection();
        
        // Drop and recreate test tables
        $tables = ['users', 'papers_master', 'ribbons_master', 'toner_master'];
        
        foreach ($tables as $table) {
            $db->query("TRUNCATE TABLE $table");
        }
    }
    
    public static function disconnect() {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}

// Test helper functions
function createTestUser($username = 'testuser', $role = 'user') {
    $db = TestDatabase::getConnection();
    $password = password_hash('password123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('sss', $username, $password, $role);
    $stmt->execute();
    
    return $db->insert_id;
}

function deleteTestUser($userId) {
    $db = TestDatabase::getConnection();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

function createTestPaper($name = 'Test Paper', $quantity = 100) {
    $db = TestDatabase::getConnection();
    
    $stmt = $db->prepare("INSERT INTO papers_master (name, quantity, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param('si', $name, $quantity);
    $stmt->execute();
    
    return $db->insert_id;
}

echo "Test environment initialized\n";
