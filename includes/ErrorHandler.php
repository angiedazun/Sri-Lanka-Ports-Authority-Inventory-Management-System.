<?php
/**
 * Global Error Handler
 * Centralized error and exception handling system
 * 
 * @package SLPA\ErrorHandling
 * @version 1.0.0
 */

class ErrorHandler {
    private static $instance = null;
    private $logger;
    private $environment;
    private $displayErrors;
    
    public function __construct() {
        $this->logger = new Logger();
        $this->environment = defined('APP_ENV') ? APP_ENV : 'production';
        $this->displayErrors = defined('APP_DEBUG') ? APP_DEBUG : false;
        
        $this->register();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register error and exception handlers
     */
    private function register() {
        // Set error reporting based on environment
        if ($this->environment === 'development') {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }
        
        // Register handlers
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        // Don't handle suppressed errors (@)
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorType = $this->getErrorType($errno);
        
        $this->logger->error("PHP Error: $errorType", [
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'type' => $errorType
        ]);
        
        // Convert to ErrorException for consistent handling
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception) {
        $this->logException($exception);
        
        // Check if it's an AJAX request
        if ($this->isAjaxRequest()) {
            $this->renderAjaxError($exception);
        } else {
            $this->renderHtmlError($exception);
        }
        
        exit(1);
    }
    
    /**
     * Handle fatal errors on shutdown
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorType = $this->getErrorType($error['type']);
            $this->logger->error("Fatal Error ($errorType): {$error['message']} in {$error['file']} on line {$error['line']}");
            
            if ($this->isAjaxRequest()) {
                $this->renderAjaxError(new Exception($error['message']));
            } else {
                $this->renderHtmlError(new Exception($error['message']));
            }
        }
    }
    
    /**
     * Log exception details
     */
    private function logException($exception) {
        $context = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        // Add context from custom exceptions
        if ($exception instanceof SLPAException) {
            $context['custom_context'] = $exception->getContext();
        }
        
        if ($exception instanceof DatabaseException) {
            $context['query'] = $exception->getQuery();
        }
        
        if ($exception instanceof ValidationException) {
            $context['validation_errors'] = $exception->getErrors();
        }
        
        // Log based on exception type
        if ($exception instanceof AuthenticationException || 
            $exception instanceof AuthorizationException) {
            $this->logger->warning("Security Exception", $context);
        } else {
            $this->logger->error(get_class($exception), $context);
        }
    }
    
    /**
     * Render error for AJAX requests
     */
    private function renderAjaxError($exception) {
        header('Content-Type: application/json');
        http_response_code($this->getHttpStatusCode($exception));
        
        $response = [
            'success' => false,
            'error' => [
                'message' => $this->getUserFriendlyMessage($exception),
                'type' => get_class($exception)
            ]
        ];
        
        // Add details in development
        if ($this->displayErrors) {
            $response['error']['details'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
    /**
     * Render error for HTML requests
     */
    private function renderHtmlError($exception) {
        http_response_code($this->getHttpStatusCode($exception));
        
        $message = $this->getUserFriendlyMessage($exception);
        $details = '';
        
        if ($this->displayErrors) {
            $details = $this->renderDebugInfo($exception);
        }
        
        include __DIR__ . '/../pages/error.php';
    }
    
    /**
     * Render debug information
     */
    private function renderDebugInfo($exception) {
        $html = '<div class="debug-info">';
        $html .= '<h3>Debug Information</h3>';
        $html .= '<p><strong>Exception:</strong> ' . get_class($exception) . '</p>';
        $html .= '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        $html .= '<p><strong>File:</strong> ' . $exception->getFile() . ':' . $exception->getLine() . '</p>';
        
        $html .= '<h4>Stack Trace:</h4>';
        $html .= '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
        
        if ($exception instanceof SLPAException && !empty($exception->getContext())) {
            $html .= '<h4>Context:</h4>';
            $html .= '<pre>' . htmlspecialchars(print_r($exception->getContext(), true)) . '</pre>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get user-friendly error message
     */
    private function getUserFriendlyMessage($exception) {
        if ($exception instanceof ValidationException) {
            return 'Validation failed. Please check your input.';
        }
        
        if ($exception instanceof AuthenticationException) {
            switch ($exception->getCode()) {
                case AuthenticationException::CODE_INVALID_CREDENTIALS:
                    return 'Invalid username or password.';
                case AuthenticationException::CODE_ACCOUNT_LOCKED:
                    return 'Your account has been locked. Please contact support.';
                case AuthenticationException::CODE_SESSION_EXPIRED:
                    return 'Your session has expired. Please log in again.';
                case AuthenticationException::CODE_INSUFFICIENT_PERMISSIONS:
                    return 'You do not have permission to access this resource.';
            }
        }
        
        if ($exception instanceof AuthorizationException) {
            return 'Access denied. You do not have permission to perform this action.';
        }
        
        if ($exception instanceof DatabaseException) {
            return 'A database error occurred. Please try again later.';
        }
        
        if ($exception instanceof FileException) {
            switch ($exception->getCode()) {
                case FileException::CODE_FILE_NOT_FOUND:
                    return 'The requested file was not found.';
                case FileException::CODE_FILE_NOT_READABLE:
                    return 'The file cannot be read.';
                case FileException::CODE_FILE_NOT_WRITABLE:
                    return 'The file cannot be written to.';
                case FileException::CODE_INVALID_FILE_TYPE:
                    return 'Invalid file type.';
                case FileException::CODE_FILE_TOO_LARGE:
                    return 'The file is too large.';
            }
        }
        
        if ($exception instanceof RateLimitException) {
            $retryAfter = $exception->getRetryAfter();
            return "Rate limit exceeded. Please try again in $retryAfter seconds.";
        }
        
        if ($exception instanceof ExternalServiceException) {
            return 'An external service is currently unavailable. Please try again later.';
        }
        
        // Default message
        return $this->displayErrors ? $exception->getMessage() : 'An unexpected error occurred. Please try again later.';
    }
    
    /**
     * Get HTTP status code for exception
     */
    private function getHttpStatusCode($exception) {
        if ($exception instanceof ApiException) {
            return $exception->getStatusCode();
        }
        
        if ($exception instanceof AuthenticationException) {
            return 401;
        }
        
        if ($exception instanceof AuthorizationException) {
            return 403;
        }
        
        if ($exception instanceof FileException && $exception->getCode() === FileException::CODE_FILE_NOT_FOUND) {
            return 404;
        }
        
        if ($exception instanceof ValidationException) {
            return 422;
        }
        
        if ($exception instanceof RateLimitException) {
            return 429;
        }
        
        return 500;
    }
    
    /**
     * Get error type name
     */
    private function getErrorType($errno) {
        $errorTypes = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];
        
        return $errorTypes[$errno] ?? 'UNKNOWN';
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

// Initialize error handler
ErrorHandler::getInstance();
