<?php
/**
 * Simple Test Runner (PHPUnit Alternative)
 * 
 * This is a lightweight test runner that can be used without installing PHPUnit.
 * It provides basic assertion methods and test execution.
 * 
 * To install PHPUnit properly, run:
 * composer require --dev phpunit/phpunit
 * 
 * Then run tests with:
 * ./vendor/bin/phpunit tests
 */

class SimpleTestCase {
    protected $testsPassed = 0;
    protected $testsFailed = 0;
    protected $failures = [];
    
    public function assertTrue($condition, $message = '') {
        if ($condition === true) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: 'Assertion failed: expected true, got false';
        }
    }
    
    public function assertFalse($condition, $message = '') {
        if ($condition === false) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: 'Assertion failed: expected false, got true';
        }
    }
    
    public function assertEquals($expected, $actual, $message = '') {
        if ($expected == $actual) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: "Assertion failed: expected '$expected', got '$actual'";
        }
    }
    
    public function assertNotEquals($expected, $actual, $message = '') {
        if ($expected != $actual) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: "Assertion failed: expected not equals to '$expected'";
        }
    }
    
    public function assertSame($expected, $actual, $message = '') {
        if ($expected === $actual) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: "Assertion failed: expected identical to '$expected'";
        }
    }
    
    public function assertInstanceOf($class, $object, $message = '') {
        if ($object instanceof $class) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: "Assertion failed: object is not instance of '$class'";
        }
    }
    
    public function assertNotFalse($value, $message = '') {
        if ($value !== false) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: 'Assertion failed: value is false';
        }
    }
    
    public function assertStringContainsString($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) !== false) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: "Assertion failed: string does not contain '$needle'";
        }
    }
    
    public function assertStringNotContainsString($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) === false) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: "Assertion failed: string contains '$needle'";
        }
    }
    
    public function assertArrayHasKey($key, $array, $message = '') {
        if (array_key_exists($key, $array)) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: "Assertion failed: array does not have key '$key'";
        }
    }
    
    public function assertNotEmpty($value, $message = '') {
        if (!empty($value)) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: 'Assertion failed: value is empty';
        }
    }
    
    public function assertIsString($value, $message = '') {
        if (is_string($value)) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: 'Assertion failed: value is not a string';
        }
    }
    
    public function assertGreaterThan($expected, $actual, $message = '') {
        if ($actual > $expected) {
            $this->testsPassed++;
        } else {
            $this->testsFailed++;
            $this->failures[] = $message ?: "Assertion failed: $actual is not greater than $expected";
        }
    }
    
    protected function setUp(): void {
        // Override in child classes
    }
    
    protected function tearDown(): void {
        // Override in child classes
    }
    
    public function runTests() {
        $methods = get_class_methods($this);
        $testMethods = array_filter($methods, function($method) {
            return strpos($method, 'test') === 0;
        });
        
        echo "\nRunning tests in " . get_class($this) . "...\n";
        echo str_repeat('-', 50) . "\n";
        
        foreach ($testMethods as $method) {
            echo "Running $method... ";
            
            $this->setUp();
            
            try {
                $this->$method();
                echo "✓\n";
            } catch (Exception $e) {
                echo "✗\n";
                $this->testsFailed++;
                $this->failures[] = "$method: " . $e->getMessage();
            }
            
            $this->tearDown();
        }
        
        echo str_repeat('-', 50) . "\n";
        echo "Tests passed: {$this->testsPassed}\n";
        echo "Tests failed: {$this->testsFailed}\n";
        
        if (!empty($this->failures)) {
            echo "\nFailures:\n";
            foreach ($this->failures as $failure) {
                echo "  - $failure\n";
            }
        }
        
        echo "\n";
        
        return $this->testsFailed === 0;
    }
}

// Alias for compatibility
class TestCase extends SimpleTestCase {}
