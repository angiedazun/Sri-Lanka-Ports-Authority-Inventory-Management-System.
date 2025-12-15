<?php
/**
 * Validator Class
 * Centralized input validation
 */

class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * Validate required field
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field] = $message ?? "{$field} is required";
        }
        return $this;
    }
    
    /**
     * Validate email
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = $message ?? "{$field} must be a valid email";
            }
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function min($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "{$field} must be at least {$length} characters";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function max($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "{$field} must not exceed {$length} characters";
        }
        return $this;
    }
    
    /**
     * Validate numeric
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "{$field} must be numeric";
        }
        return $this;
    }
    
    /**
     * Validate integer
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?? "{$field} must be an integer";
        }
        return $this;
    }
    
    /**
     * Validate date
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "{$field} must be a valid date";
            }
        }
        return $this;
    }
    
    /**
     * Validate in array
     */
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? "{$field} must be one of: " . implode(', ', $values);
        }
        return $this;
    }
    
    /**
     * Custom validation
     */
    public function custom($field, $callback, $message = null) {
        if (isset($this->data[$field])) {
            if (!$callback($this->data[$field])) {
                $this->errors[$field] = $message ?? "{$field} validation failed";
            }
        }
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !$this->passes();
    }
    
    /**
     * Get validation errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get first error
     */
    public function firstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Get validated data
     */
    public function validated() {
        return $this->data;
    }
}
