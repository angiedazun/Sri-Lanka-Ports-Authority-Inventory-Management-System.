<?php
/**
 * Unit Test for Database Class
 * 
 * @package SLPA\Tests\Unit
 * 
 * Note: Using SimpleTestCase for testing without PHPUnit dependency.
 * To use PHPUnit: composer require --dev phpunit/phpunit
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../SimpleTestCase.php';

class DatabaseTest extends TestCase {
    private $db;
    
    protected function setUp(): void {
        $this->db = Database::getInstance();
    }
    
    public function testSingletonInstance() {
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();
        
        $this->assertSame($db1, $db2, 'Database should be a singleton');
    }
    
    public function testConnection() {
        $conn = $this->db->getConnection();
        
        $this->assertInstanceOf('mysqli', $conn);
        $this->assertTrue($conn->ping(), 'Database connection should be active');
    }
    
    public function testQuery() {
        $result = $this->db->query("SELECT 1 as test");
        
        $this->assertNotFalse($result, 'Query should succeed');
        $this->assertEquals(1, $result->num_rows);
    }
    
    public function testPreparedStatement() {
        $stmt = $this->db->prepare("SELECT ? as test");
        $test = 'hello';
        $stmt->bind_param('s', $test);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $this->assertEquals('hello', $row['test']);
    }
    
    public function testEscapeString() {
        $input = "O'Reilly";
        $escaped = $this->db->escape($input);
        
        $this->assertStringContainsString("\'", $escaped);
    }
    
    public function testTransaction() {
        $this->db->beginTransaction();
        
        // Insert test data
        $this->db->query("INSERT INTO users (username, password) VALUES ('test_tx', 'pass')");
        
        $this->db->rollback();
        
        // Check that data was rolled back
        $result = $this->db->query("SELECT * FROM users WHERE username = 'test_tx'");
        $this->assertEquals(0, $result->num_rows);
    }
    
    protected function tearDown(): void {
        // Clean up test data
        $this->db->query("DELETE FROM users WHERE username LIKE 'test_%'");
    }
}
