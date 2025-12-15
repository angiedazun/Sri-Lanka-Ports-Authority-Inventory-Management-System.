<?php
/**
 * Load Balancer
 * Distributes load across multiple database servers
 * 
 * @package SLPA\LoadBalancer
 * @version 1.0.0
 */

class LoadBalancer {
    private static $instance = null;
    private $connections = [];
    private $config = [];
    private $strategy = 'round_robin';
    private $currentIndex = 0;
    
    private function __construct() {
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load configuration
     */
    private function loadConfig() {
        $this->config = [
            'master' => [
                'host' => DB_HOST,
                'user' => DB_USER,
                'pass' => DB_PASS,
                'name' => DB_NAME,
                'weight' => 10
            ],
            'slaves' => [
                // Add slave servers here when available
                // ['host' => '192.168.1.101', 'user' => 'slave_user', 'pass' => 'pass', 'name' => DB_NAME, 'weight' => 5],
                // ['host' => '192.168.1.102', 'user' => 'slave_user', 'pass' => 'pass', 'name' => DB_NAME, 'weight' => 5],
            ]
        ];
    }
    
    /**
     * Get connection for read operations
     */
    public function getReadConnection() {
        // If no slaves, use master
        if (empty($this->config['slaves'])) {
            return $this->getMasterConnection();
        }
        
        // Select slave based on strategy
        switch ($this->strategy) {
            case 'round_robin':
                return $this->getRoundRobinConnection();
            case 'weighted':
                return $this->getWeightedConnection();
            case 'least_connections':
                return $this->getLeastConnectionsConnection();
            case 'random':
                return $this->getRandomConnection();
            default:
                return $this->getMasterConnection();
        }
    }
    
    /**
     * Get connection for write operations (always master)
     */
    public function getWriteConnection() {
        return $this->getMasterConnection();
    }
    
    /**
     * Get master connection
     */
    private function getMasterConnection() {
        if (!isset($this->connections['master'])) {
            $config = $this->config['master'];
            $this->connections['master'] = $this->createConnection($config);
        }
        
        return $this->connections['master'];
    }
    
    /**
     * Round robin load balancing
     */
    private function getRoundRobinConnection() {
        $slaves = $this->config['slaves'];
        
        if (empty($slaves)) {
            return $this->getMasterConnection();
        }
        
        $index = $this->currentIndex % count($slaves);
        $this->currentIndex++;
        
        $key = 'slave_' . $index;
        
        if (!isset($this->connections[$key])) {
            $this->connections[$key] = $this->createConnection($slaves[$index]);
        }
        
        return $this->connections[$key];
    }
    
    /**
     * Weighted load balancing
     */
    private function getWeightedConnection() {
        $slaves = $this->config['slaves'];
        
        if (empty($slaves)) {
            return $this->getMasterConnection();
        }
        
        // Calculate total weight
        $totalWeight = array_sum(array_column($slaves, 'weight'));
        $random = mt_rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($slaves as $index => $slave) {
            $currentWeight += $slave['weight'];
            
            if ($random <= $currentWeight) {
                $key = 'slave_' . $index;
                
                if (!isset($this->connections[$key])) {
                    $this->connections[$key] = $this->createConnection($slave);
                }
                
                return $this->connections[$key];
            }
        }
        
        return $this->getMasterConnection();
    }
    
    /**
     * Least connections load balancing
     */
    private function getLeastConnectionsConnection() {
        // For simplicity, use round robin
        // In production, track active connections per server
        return $this->getRoundRobinConnection();
    }
    
    /**
     * Random load balancing
     */
    private function getRandomConnection() {
        $slaves = $this->config['slaves'];
        
        if (empty($slaves)) {
            return $this->getMasterConnection();
        }
        
        $index = array_rand($slaves);
        $key = 'slave_' . $index;
        
        if (!isset($this->connections[$key])) {
            $this->connections[$key] = $this->createConnection($slaves[$index]);
        }
        
        return $this->connections[$key];
    }
    
    /**
     * Create database connection
     */
    private function createConnection($config) {
        $conn = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name']
        );
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset('utf8mb4');
        
        return $conn;
    }
    
    /**
     * Set load balancing strategy
     */
    public function setStrategy($strategy) {
        $this->strategy = $strategy;
    }
    
    /**
     * Get connection statistics
     */
    public function getStats() {
        return [
            'total_connections' => count($this->connections),
            'strategy' => $this->strategy,
            'slaves_count' => count($this->config['slaves'])
        ];
    }
    
    /**
     * Close all connections
     */
    public function closeAll() {
        foreach ($this->connections as $conn) {
            if ($conn instanceof mysqli) {
                $conn->close();
            }
        }
        
        $this->connections = [];
    }
}

/**
 * Connection Pool
 * Maintains a pool of reusable database connections
 */
class ConnectionPool {
    private static $instance = null;
    private $pool = [];
    private $maxConnections = 10;
    private $activeConnections = 0;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get connection from pool
     */
    public function getConnection() {
        // Reuse idle connection if available
        if (!empty($this->pool)) {
            $conn = array_pop($this->pool);
            
            // Check if connection is still alive
            if ($conn->ping()) {
                $this->activeConnections++;
                return $conn;
            }
        }
        
        // Create new connection if under limit
        if ($this->activeConnections < $this->maxConnections) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset('utf8mb4');
            $this->activeConnections++;
            return $conn;
        }
        
        // Wait for available connection
        return $this->waitForConnection();
    }
    
    /**
     * Release connection back to pool
     */
    public function releaseConnection($conn) {
        /** @phpstan-ignore-next-line */
        if ($conn instanceof mysqli && @$conn->ping()) {
            $this->pool[] = $conn;
            $this->activeConnections--;
        }
    }
    
    /**
     * Wait for available connection
     */
    private function waitForConnection($timeout = 5) {
        $start = time();
        
        while (time() - $start < $timeout) {
            if (!empty($this->pool)) {
                return array_pop($this->pool);
            }
            
            usleep(100000); // 100ms
        }
        
        throw new Exception("Connection pool timeout");
    }
    
    /**
     * Get pool statistics
     */
    public function getStats() {
        return [
            'idle_connections' => count($this->pool),
            'active_connections' => $this->activeConnections,
            'max_connections' => $this->maxConnections
        ];
    }
    
    /**
     * Close all connections
     */
    public function closeAll() {
        foreach ($this->pool as $conn) {
            if ($conn instanceof mysqli) {
                $conn->close();
            }
        }
        
        $this->pool = [];
        $this->activeConnections = 0;
    }
}

/**
 * Query Queue
 * Queues database queries for batch processing
 */
class QueryQueue {
    private static $instance = null;
    private $queue = [];
    private $batchSize = 100;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Add query to queue
     */
    public function add($sql, $params = []) {
        $this->queue[] = [
            'sql' => $sql,
            'params' => $params,
            'added_at' => microtime(true)
        ];
        
        // Auto-flush if batch size reached
        if (count($this->queue) >= $this->batchSize) {
            $this->flush();
        }
    }
    
    /**
     * Flush queue (execute all queries)
     */
    public function flush() {
        if (empty($this->queue)) {
            return;
        }
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $conn->begin_transaction();
        
        try {
            foreach ($this->queue as $item) {
                if (empty($item['params'])) {
                    $conn->query($item['sql']);
                } else {
                    $stmt = $conn->prepare($item['sql']);
                    $types = str_repeat('s', count($item['params']));
                    $stmt->bind_param($types, ...$item['params']);
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            $this->queue = [];
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
    /**
     * Get queue size
     */
    public function size() {
        return count($this->queue);
    }
}
