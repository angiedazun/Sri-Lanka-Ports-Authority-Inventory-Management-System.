<?php
/**
 * Custom Exception Classes
 * Provides specific exception types for better error handling
 * 
 * @package SLPA\Exceptions
 * @version 1.0.0
 */

/**
 * Base SLPA Exception
 */
class SLPAException extends Exception {
    protected $context = [];
    
    public function __construct($message = "", $code = 0, ?Exception $previous = null, array $context = []) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    public function getContext() {
        return $this->context;
    }
}

/**
 * Database Exception
 */
class DatabaseException extends SLPAException {
    protected $query;
    
    public function setQuery($query) {
        $this->query = $query;
        return $this;
    }
    
    public function getQuery() {
        return $this->query;
    }
}

/**
 * Validation Exception
 */
class ValidationException extends SLPAException {
    protected $errors = [];
    
    public function setErrors(array $errors) {
        $this->errors = $errors;
        return $this;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
}

/**
 * Authentication Exception
 */
class AuthenticationException extends SLPAException {
    const CODE_INVALID_CREDENTIALS = 1001;
    const CODE_ACCOUNT_LOCKED = 1002;
    const CODE_SESSION_EXPIRED = 1003;
    const CODE_INSUFFICIENT_PERMISSIONS = 1004;
}

/**
 * Authorization Exception
 */
class AuthorizationException extends SLPAException {
    protected $requiredRole;
    protected $userRole;
    
    public function setRequiredRole($role) {
        $this->requiredRole = $role;
        return $this;
    }
    
    public function setUserRole($role) {
        $this->userRole = $role;
        return $this;
    }
    
    public function getRequiredRole() {
        return $this->requiredRole;
    }
    
    public function getUserRole() {
        return $this->userRole;
    }
}

/**
 * File Exception
 */
class FileException extends SLPAException {
    const CODE_FILE_NOT_FOUND = 2001;
    const CODE_FILE_NOT_READABLE = 2002;
    const CODE_FILE_NOT_WRITABLE = 2003;
    const CODE_INVALID_FILE_TYPE = 2004;
    const CODE_FILE_TOO_LARGE = 2005;
}

/**
 * Configuration Exception
 */
class ConfigurationException extends SLPAException {
    protected $configKey;
    
    public function setConfigKey($key) {
        $this->configKey = $key;
        return $this;
    }
    
    public function getConfigKey() {
        return $this->configKey;
    }
}

/**
 * API Exception
 */
class ApiException extends SLPAException {
    protected $statusCode = 500;
    protected $headers = [];
    
    public function setStatusCode($code) {
        $this->statusCode = $code;
        return $this;
    }
    
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    public function setHeaders(array $headers) {
        $this->headers = $headers;
        return $this;
    }
    
    public function getHeaders() {
        return $this->headers;
    }
}

/**
 * Business Logic Exception
 */
class BusinessException extends SLPAException {
    // For domain-specific business rule violations
}

/**
 * External Service Exception
 */
class ExternalServiceException extends SLPAException {
    protected $serviceName;
    protected $response;
    
    public function setServiceName($name) {
        $this->serviceName = $name;
        return $this;
    }
    
    public function getServiceName() {
        return $this->serviceName;
    }
    
    public function setResponse($response) {
        $this->response = $response;
        return $this;
    }
    
    public function getResponse() {
        return $this->response;
    }
}

/**
 * Rate Limit Exception
 */
class RateLimitException extends SLPAException {
    protected $retryAfter;
    
    public function setRetryAfter($seconds) {
        $this->retryAfter = $seconds;
        return $this;
    }
    
    public function getRetryAfter() {
        return $this->retryAfter;
    }
}
