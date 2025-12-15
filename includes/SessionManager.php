<?php
/**
 * Session Manager
 * Advanced session handling with database/Redis storage
 * 
 * @package SLPA\Session
 * @version 1.0.0
 */

class SessionManager {
    private static $instance = null;
    private $handler;
    private $config = [];
    
    private function __construct() {
        $this->config = [
            'driver' => 'database', // database, redis, file
            'lifetime' => 7200, // 2 hours
            'cookie_name' => 'SLPA_SESSION',
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'gc_probability' => 1,
            'gc_divisor' => 100
        ];
        
        $this->initializeHandler();
        $this->startSession();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize session handler
     */
    private function initializeHandler() {
        switch ($this->config['driver']) {
            case 'redis':
                if (extension_loaded('redis')) {
                    $this->handler = new RedisSessionHandler();
                    break;
                }
                // Fallback to database
            case 'database':
                $this->handler = new DatabaseSessionHandler();
                break;
            default:
                $this->handler = null; // Use PHP default file handler
        }
        
        if ($this->handler) {
            session_set_save_handler($this->handler, true);
        }
    }
    
    /**
     * Start session
     */
    private function startSession() {
        // Configure session
        ini_set('session.gc_probability', $this->config['gc_probability']);
        ini_set('session.gc_divisor', $this->config['gc_divisor']);
        ini_set('session.gc_maxlifetime', $this->config['lifetime']);
        
        session_name($this->config['cookie_name']);
        
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['cookie_path'],
            'domain' => $this->config['cookie_domain'],
            'secure' => $this->config['cookie_secure'],
            'httponly' => $this->config['cookie_httponly'],
            'samesite' => 'Lax'
        ]);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Get session value
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set session value
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Check if key exists
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session value
     */
    public function remove($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Clear all session data
     */
    public function clear() {
        $_SESSION = [];
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerate($deleteOld = true) {
        session_regenerate_id($deleteOld);
    }
    
    /**
     * Destroy session
     */
    public function destroy() {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    /**
     * Get session ID
     */
    public function getId() {
        return session_id();
    }
    
    /**
     * Flash message (store for next request)
     */
    public function flash($key, $value) {
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Get flash message
     */
    public function getFlash($key, $default = null) {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
    
    /**
     * Keep flash messages for next request
     */
    public function reflash() {
        if (isset($_SESSION['_flash'])) {
            foreach ($_SESSION['_flash'] as $key => $value) {
                $this->flash($key, $value);
            }
        }
    }
    
    /**
     * Get all flash messages
     */
    public function getAllFlash() {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }
}

/**
 * Database Session Handler
 * Stores sessions in database
 */
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $db;
    private $table = 'sessions';
    private $lifetime = 7200;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->createTable();
    }
    
    /**
     * Create sessions table
     */
    private function createTable() {
        $this->db->query("CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            user_id INT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            payload TEXT NOT NULL,
            last_activity INT NOT NULL,
            INDEX idx_user (user_id),
            INDEX idx_activity (last_activity)
        )");
    }
    
    /**
     * Open session
     */
    #[\ReturnTypeWillChange]
    public function open($path, $name) {
        return true;
    }
    
    /**
     * Close session
     */
    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }
    
    /**
     * Read session data
     */
    #[\ReturnTypeWillChange]
    public function read($id) {
        $stmt = $this->db->prepare(
            "SELECT payload FROM sessions WHERE id = ? AND last_activity >= ?"
        );
        
        $expiration = time() - $this->lifetime;
        $stmt->bind_param('si', $id, $expiration);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['payload'];
        }
        
        return '';
    }
    
    /**
     * Write session data
     */
    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $lastActivity = time();
        
        $stmt = $this->db->prepare(
            "INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) 
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
             user_id = VALUES(user_id),
             ip_address = VALUES(ip_address),
             user_agent = VALUES(user_agent),
             payload = VALUES(payload),
             last_activity = VALUES(last_activity)"
        );
        
        $stmt->bind_param('sisssi', $id, $userId, $ipAddress, $userAgent, $data, $lastActivity);
        
        return $stmt->execute();
    }
    
    /**
     * Destroy session
     */
    #[\ReturnTypeWillChange]
    public function destroy($id) {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param('s', $id);
        return $stmt->execute();
    }
    
    /**
     * Garbage collection
     */
    #[\ReturnTypeWillChange]
    public function gc($max_lifetime) {
        $expiration = time() - $max_lifetime;
        
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE last_activity < ?");
        $stmt->bind_param('i', $expiration);
        
        return $stmt->execute();
    }
}

/**
 * Redis Session Handler
 * Stores sessions in Redis
 */
class RedisSessionHandler implements SessionHandlerInterface {
    private $redis;
    private $prefix = 'session:';
    private $lifetime = 7200;
    
    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }
    
    /**
     * Open session
     */
    #[\ReturnTypeWillChange]
    public function open($path, $name) {
        return true;
    }
    
    /**
     * Close session
     */
    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }
    
    /**
     * Read session data
     */
    #[\ReturnTypeWillChange]
    public function read($id) {
        $data = $this->redis->get($this->prefix . $id);
        return $data !== false ? $data : '';
    }
    
    /**
     * Write session data
     */
    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        return $this->redis->setex(
            $this->prefix . $id,
            $this->lifetime,
            $data
        );
    }
    
    /**
     * Destroy session
     */
    #[\ReturnTypeWillChange]
    public function destroy($id) {
        return $this->redis->del($this->prefix . $id) > 0;
    }
    
    /**
     * Garbage collection (Redis handles this automatically)
     */
    #[\ReturnTypeWillChange]
    public function gc($max_lifetime) {
        return true;
    }
}

/**
 * Session Activity Tracker
 * Tracks user session activity
 */
class SessionActivityTracker {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->createTable();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create activity table
     */
    private function createTable() {
        $this->db->query("CREATE TABLE IF NOT EXISTS session_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(128) NOT NULL,
            user_id INT NULL,
            action VARCHAR(255) NOT NULL,
            url VARCHAR(500) NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_session (session_id),
            INDEX idx_user (user_id),
            INDEX idx_created (created_at)
        )");
    }
    
    /**
     * Log activity
     */
    public function log($action, $url = null) {
        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $url = $url ?? $_SERVER['REQUEST_URI'] ?? null;
        
        $stmt = $this->db->prepare(
            "INSERT INTO session_activity (session_id, user_id, action, url, ip_address) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sisss', $sessionId, $userId, $action, $url, $ipAddress);
        $stmt->execute();
    }
    
    /**
     * Get user activity
     */
    public function getUserActivity($userId, $limit = 50) {
        $stmt = $this->db->prepare(
            "SELECT * FROM session_activity 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?"
        );
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $activity = [];
        
        while ($row = $result->fetch_assoc()) {
            $activity[] = $row;
        }
        
        return $activity;
    }
    
    /**
     * Get active sessions
     */
    public function getActiveSessions() {
        $result = $this->db->query(
            "SELECT 
                s.id,
                s.user_id,
                s.ip_address,
                s.user_agent,
                FROM_UNIXTIME(s.last_activity) as last_activity,
                u.username
             FROM sessions s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.last_activity >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE))
             ORDER BY s.last_activity DESC"
        );
        
        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        return $sessions;
    }
    
    /**
     * Get session count by user
     */
    public function getUserSessionCount($userId) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count 
             FROM sessions 
             WHERE user_id = ? 
             AND last_activity >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE))"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['count'];
    }
    
    /**
     * Terminate user sessions
     */
    public function terminateUserSessions($userId, $exceptCurrentSession = true) {
        $currentSession = session_id();
        
        if ($exceptCurrentSession) {
            $stmt = $this->db->prepare(
                "DELETE FROM sessions WHERE user_id = ? AND id != ?"
            );
            $stmt->bind_param('is', $userId, $currentSession);
        } else {
            $stmt = $this->db->prepare(
                "DELETE FROM sessions WHERE user_id = ?"
            );
            $stmt->bind_param('i', $userId);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Clean old activity logs
     */
    public function cleanOldActivity($days = 30) {
        $stmt = $this->db->prepare(
            "DELETE FROM session_activity 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->bind_param('i', $days);
        
        return $stmt->execute();
    }
}
