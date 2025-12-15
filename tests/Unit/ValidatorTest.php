<?php
/**
 * Unit Test for Validator Class
 * 
 * @package SLPA\Tests\Unit
 * 
 * Note: Using SimpleTestCase for testing without PHPUnit dependency.
 * To use PHPUnit: composer require --dev phpunit/phpunit
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../SimpleTestCase.php';

class ValidatorTest extends TestCase {
    private $validator;
    
    protected function setUp(): void {
        $this->validator = new Validator(Database::getInstance());
    }
    
    public function testRequiredValidation() {
        $rules = ['name' => 'required'];
        $data = ['name' => 'John'];
        
        $this->assertTrue($this->validator->validate($data, $rules));
    }
    
    public function testRequiredValidationFails() {
        $rules = ['name' => 'required'];
        $data = ['name' => ''];
        
        $this->assertFalse($this->validator->validate($data, $rules));
        $this->assertArrayHasKey('name', $this->validator->getErrors());
    }
    
    public function testEmailValidation() {
        $rules = ['email' => 'email'];
        
        $this->assertTrue($this->validator->validate(['email' => 'test@example.com'], $rules));
        $this->assertFalse($this->validator->validate(['email' => 'invalid-email'], $rules));
    }
    
    public function testNumericValidation() {
        $rules = ['age' => 'numeric'];
        
        $this->assertTrue($this->validator->validate(['age' => 25], $rules));
        $this->assertTrue($this->validator->validate(['age' => '25'], $rules));
        $this->assertFalse($this->validator->validate(['age' => 'abc'], $rules));
    }
    
    public function testMinValidation() {
        $rules = ['username' => 'min:5'];
        
        $this->assertTrue($this->validator->validate(['username' => 'john123'], $rules));
        $this->assertFalse($this->validator->validate(['username' => 'john'], $rules));
    }
    
    public function testMaxValidation() {
        $rules = ['username' => 'max:10'];
        
        $this->assertTrue($this->validator->validate(['username' => 'john'], $rules));
        $this->assertFalse($this->validator->validate(['username' => 'verylongusername'], $rules));
    }
    
    public function testBetweenValidation() {
        $rules = ['age' => 'between:18,65'];
        
        $this->assertTrue($this->validator->validate(['age' => 25], $rules));
        $this->assertFalse($this->validator->validate(['age' => 15], $rules));
        $this->assertFalse($this->validator->validate(['age' => 70], $rules));
    }
    
    public function testMultipleRules() {
        $rules = [
            'username' => 'required|min:5|max:20',
            'email' => 'required|email',
            'age' => 'numeric|between:18,100'
        ];
        
        $data = [
            'username' => 'john123',
            'email' => 'john@example.com',
            'age' => 25
        ];
        
        $this->assertTrue($this->validator->validate($data, $rules));
    }
    
    public function testCustomErrorMessages() {
        $rules = ['name' => 'required'];
        $messages = ['name.required' => 'Name is mandatory'];
        
        $this->validator->setMessages($messages);
        $this->validator->validate(['name' => ''], $rules);
        
        $errors = $this->validator->getErrors();
        $this->assertEquals('Name is mandatory', $errors['name'][0]);
    }
}
