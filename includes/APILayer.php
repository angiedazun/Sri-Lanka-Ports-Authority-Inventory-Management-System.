<?php
/**
 * RESTful API Layer
 * Comprehensive API with authentication, rate limiting, and documentation
 * 
 * @package SLPA\API
 * @version 1.0.0
 */

class APIRouter {
    private $routes = [];
    private $middleware = [];
    private $basePrefix = '';
    
    /**
     * Add route
     */
    public function addRoute($method, $path, $handler, $middleware = []) {
        $fullPath = $this->basePrefix . $path;
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'pattern' => $this->pathToPattern($fullPath),
            'handler' => $handler,
            'middleware' => array_merge($this->middleware, $middleware)
        ];
        
        return $this;
    }
    
    /**
     * Set route prefix
     */
    public function prefix($prefix) {
        $router = clone $this;
        $router->basePrefix = $this->basePrefix . $prefix;
        return $router;
    }
    
    /**
     * Add middleware
     */
    public function middleware($middleware) {
        $router = clone $this;
        $router->middleware = array_merge($this->middleware, (array)$middleware);
        return $router;
    }
    
    /**
     * Convert path to regex pattern
     */
    private function pathToPattern($path) {
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Dispatch request
     */
    public function dispatch($method, $path) {
        $method = strtoupper($method);
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Execute middleware
                foreach ($route['middleware'] as $middleware) {
                    $result = call_user_func($middleware);
                    if ($result !== true) {
                        return $result; // Middleware failed
                    }
                }
                
                // Execute handler
                return call_user_func($route['handler'], $params);
            }
        }
        
        return new APIResponse(['error' => 'Route not found'], 404);
    }
    
    /**
     * Get all routes
     */
    public function getRoutes() {
        return $this->routes;
    }
}

/**
 * API Response
 */
class APIResponse {
    private $data;
    private $statusCode;
    private $headers;
    
    public function __construct($data, $statusCode = 200, $headers = []) {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    /**
     * Send response
     */
    public function send() {
        http_response_code($this->statusCode);
        
        header('Content-Type: application/json');
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        
        echo json_encode($this->data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Success response
     */
    public static function success($data, $message = null) {
        $response = ['success' => true, 'data' => $data];
        if ($message) $response['message'] = $message;
        return new self($response);
    }
    
    /**
     * Error response
     */
    public static function error($message, $code = 400, $errors = null) {
        $response = ['success' => false, 'message' => $message];
        if ($errors) $response['errors'] = $errors;
        return new self($response, $code);
    }
}

/**
 * API Authentication
 */
class APIAuth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate API key
     */
    public function generateAPIKey($userId) {
        $apiKey = bin2hex(random_bytes(32));
        $hashedKey = password_hash($apiKey, PASSWORD_DEFAULT);
        
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO api_keys (user_id, api_key, created_at, expires_at) 
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR))";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $userId, $hashedKey);
        $stmt->execute();
        
        return $apiKey;
    }
    
    /**
     * Validate API key
     */
    public function validateAPIKey($apiKey) {
        $conn = $this->db->getConnection();
        $sql = "SELECT k.id, k.user_id, k.api_key, u.username, u.role 
                FROM api_keys k
                JOIN users u ON k.user_id = u.id
                WHERE k.is_active = 1 AND k.expires_at > NOW()";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if (password_verify($apiKey, $row['api_key'])) {
                // Update last used
                $updateSql = "UPDATE api_keys SET last_used_at = NOW() WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param('i', $row['id']);
                $updateStmt->execute();
                
                return [
                    'user_id' => $row['user_id'],
                    'username' => $row['username'],
                    'role' => $row['role']
                ];
            }
        }
        
        return false;
    }
    
    /**
     * Revoke API key
     */
    public function revokeAPIKey($apiKey) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE api_keys SET is_active = 0 WHERE api_key = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $apiKey);
        $stmt->execute();
    }
}

/**
 * Rate Limiter
 */
class RateLimiter {
    private $db;
    private $limits = [
        'default' => ['requests' => 100, 'period' => 3600], // 100 requests per hour
        'authenticated' => ['requests' => 1000, 'period' => 3600]
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check rate limit
     */
    public function checkLimit($identifier, $tier = 'default') {
        $limit = $this->limits[$tier];
        $conn = $this->db->getConnection();
        
        $sql = "SELECT COUNT(*) as count FROM api_requests 
                WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $identifier, $limit['period']);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] >= $limit['requests']) {
            return false; // Rate limit exceeded
        }
        
        // Log request
        $this->logRequest($identifier);
        
        return true;
    }
    
    /**
     * Log API request
     */
    private function logRequest($identifier) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO api_requests (identifier, endpoint, method, created_at) 
                VALUES (?, ?, ?, NOW())";
        
        $endpoint = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $identifier, $endpoint, $method);
        $stmt->execute();
    }
    
    /**
     * Get remaining requests
     */
    public function getRemainingRequests($identifier, $tier = 'default') {
        $limit = $this->limits[$tier];
        $conn = $this->db->getConnection();
        
        $sql = "SELECT COUNT(*) as count FROM api_requests 
                WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $identifier, $limit['period']);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return max(0, $limit['requests'] - $row['count']);
    }
}

/**
 * Webhook Manager
 */
class WebhookManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register webhook
     */
    public function register($userId, $url, $events, $secret = null) {
        $conn = $this->db->getConnection();
        
        if (!$secret) {
            $secret = bin2hex(random_bytes(32));
        }
        
        $eventsJson = json_encode($events);
        
        $sql = "INSERT INTO webhooks (user_id, url, events, secret, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isss', $userId, $url, $eventsJson, $secret);
        $stmt->execute();
        
        return [
            'id' => $conn->insert_id,
            'secret' => $secret
        ];
    }
    
    /**
     * Trigger webhook
     */
    public function trigger($event, $data) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT id, url, secret FROM webhooks 
                WHERE is_active = 1 AND JSON_CONTAINS(events, ?)";
        
        $eventJson = json_encode($event);
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $eventJson);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        while ($webhook = $result->fetch_assoc()) {
            $this->sendWebhook($webhook['id'], $webhook['url'], $webhook['secret'], $event, $data);
        }
    }
    
    /**
     * Send webhook
     */
    private function sendWebhook($webhookId, $url, $secret, $event, $data) {
        $payload = json_encode([
            'event' => $event,
            'data' => $data,
            'timestamp' => time()
        ]);
        
        $signature = hash_hmac('sha256', $payload, $secret);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if ($ch) {
            /** @phpstan-ignore-next-line */
            curl_close($ch);
        }
        
        // Log webhook delivery
        $this->logDelivery($webhookId, $statusCode, $error);
    }
    
    /**
     * Log webhook delivery
     */
    private function logDelivery($webhookId, $statusCode, $error) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO webhook_logs (webhook_id, status_code, error, created_at) 
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $webhookId, $statusCode, $error);
        $stmt->execute();
    }
}

/**
 * API Documentation Generator
 */
class APIDocumentation {
    private $router;
    
    public function __construct(APIRouter $router) {
        $this->router = $router;
    }
    
    /**
     * Generate OpenAPI specification
     */
    public function generateOpenAPI() {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'SLPA Inventory API',
                'version' => '1.0.0',
                'description' => 'RESTful API for SLPA Inventory Management System'
            ],
            'servers' => [
                ['url' => (defined('BASE_URL') ? constant('BASE_URL') : 'http://localhost') . '/api/v1']
            ],
            'paths' => $this->generatePaths(),
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key'
                    ]
                ]
            ]
        ];
        
        return $spec;
    }
    
    /**
     * Generate paths documentation
     */
    private function generatePaths() {
        $paths = [];
        
        foreach ($this->router->getRoutes() as $route) {
            $path = preg_replace('/\{([^}]+)\}/', '{$1}', $route['path']);
            $method = strtolower($route['method']);
            
            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }
            
            $paths[$path][$method] = [
                'summary' => 'API endpoint',
                'responses' => [
                    '200' => ['description' => 'Successful response'],
                    '400' => ['description' => 'Bad request'],
                    '401' => ['description' => 'Unauthorized'],
                    '404' => ['description' => 'Not found']
                ]
            ];
        }
        
        return $paths;
    }
    
    /**
     * Generate HTML documentation
     */
    public function generateHTML() {
        $spec = $this->generateOpenAPI();
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>API Documentation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@4/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@4/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            spec: ' . json_encode($spec) . ',
            dom_id: "#swagger-ui"
        });
    </script>
</body>
</html>';
        
        return $html;
    }
}
