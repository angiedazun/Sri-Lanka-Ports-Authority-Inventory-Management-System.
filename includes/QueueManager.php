<?php
/**
 * Queue Manager
 * Handles background job processing with retry logic
 * 
 * @package SLPA\Queue
 * @version 1.0.0
 */

class QueueManager {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->createTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create queue tables
     */
    private function createTables() {
        // Jobs table
        $this->db->query("CREATE TABLE IF NOT EXISTS queue_jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL DEFAULT 'default',
            payload TEXT NOT NULL,
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            priority INT DEFAULT 0,
            reserved_at DATETIME NULL,
            available_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_queue_status (queue, reserved_at, available_at),
            INDEX idx_available (available_at),
            INDEX idx_priority (priority DESC)
        )");
        
        // Failed jobs table
        $this->db->query("CREATE TABLE IF NOT EXISTS queue_failed_jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL,
            payload TEXT NOT NULL,
            exception TEXT,
            failed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    /**
     * Push job to queue
     */
    public function push($job, $queue = 'default', $priority = 0) {
        $payload = $this->serializeJob($job);
        
        $stmt = $this->db->prepare(
            "INSERT INTO queue_jobs (queue, payload, priority, available_at) 
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param('ssi', $queue, $payload, $priority);
        $stmt->execute();
        
        return $this->db->insert_id;
    }
    
    /**
     * Push delayed job
     */
    public function later($job, $delay, $queue = 'default', $priority = 0) {
        $payload = $this->serializeJob($job);
        $availableAt = date('Y-m-d H:i:s', time() + $delay);
        
        $stmt = $this->db->prepare(
            "INSERT INTO queue_jobs (queue, payload, priority, available_at) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssis', $queue, $payload, $priority, $availableAt);
        $stmt->execute();
        
        return $this->db->insert_id;
    }
    
    /**
     * Pop next job from queue
     */
    public function pop($queue = 'default') {
        // Lock and get next available job
        $this->db->begin_transaction();
        
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM queue_jobs 
                 WHERE queue = ? 
                 AND reserved_at IS NULL 
                 AND available_at <= NOW() 
                 ORDER BY priority DESC, id ASC 
                 LIMIT 1 
                 FOR UPDATE"
            );
            $stmt->bind_param('s', $queue);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($job = $result->fetch_assoc()) {
                // Reserve job
                $stmt = $this->db->prepare(
                    "UPDATE queue_jobs 
                     SET reserved_at = NOW(), attempts = attempts + 1 
                     WHERE id = ?"
                );
                $stmt->bind_param('i', $job['id']);
                $stmt->execute();
                
                $this->db->commit();
                
                return new QueueJob($job, $this);
            }
            
            $this->db->commit();
            return null;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Delete job
     */
    public function delete($jobId) {
        $stmt = $this->db->prepare("DELETE FROM queue_jobs WHERE id = ?");
        $stmt->bind_param('i', $jobId);
        $stmt->execute();
    }
    
    /**
     * Release job back to queue
     */
    public function release($jobId, $delay = 0) {
        $availableAt = date('Y-m-d H:i:s', time() + $delay);
        
        $stmt = $this->db->prepare(
            "UPDATE queue_jobs 
             SET reserved_at = NULL, available_at = ? 
             WHERE id = ?"
        );
        $stmt->bind_param('si', $availableAt, $jobId);
        $stmt->execute();
    }
    
    /**
     * Mark job as failed
     */
    public function fail($jobId, $exception) {
        $this->db->begin_transaction();
        
        try {
            // Get job details
            $stmt = $this->db->prepare("SELECT * FROM queue_jobs WHERE id = ?");
            $stmt->bind_param('i', $jobId);
            $stmt->execute();
            $job = $stmt->get_result()->fetch_assoc();
            
            // Move to failed jobs
            $stmt = $this->db->prepare(
                "INSERT INTO queue_failed_jobs (queue, payload, exception) 
                 VALUES (?, ?, ?)"
            );
            $stmt->bind_param('sss', $job['queue'], $job['payload'], $exception);
            $stmt->execute();
            
            // Delete from jobs
            $this->delete($jobId);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Serialize job
     */
    private function serializeJob($job) {
        return json_encode($job);
    }
    
    /**
     * Get queue statistics
     */
    public function getStats($queue = null) {
        $stats = [];
        
        if ($queue) {
            // Stats for specific queue
            $stmt = $this->db->prepare(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN reserved_at IS NULL THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as processing
                 FROM queue_jobs 
                 WHERE queue = ?"
            );
            $stmt->bind_param('s', $queue);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();
        } else {
            // Stats for all queues
            $result = $this->db->query(
                "SELECT 
                    queue,
                    COUNT(*) as total,
                    SUM(CASE WHEN reserved_at IS NULL THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as processing
                 FROM queue_jobs 
                 GROUP BY queue"
            );
            
            while ($row = $result->fetch_assoc()) {
                $stats[$row['queue']] = $row;
            }
        }
        
        // Failed jobs count
        $result = $this->db->query("SELECT COUNT(*) as failed FROM queue_failed_jobs");
        $stats['failed'] = $result->fetch_assoc()['failed'];
        
        return $stats;
    }
    
    /**
     * Clear failed jobs
     */
    public function clearFailedJobs() {
        $this->db->query("DELETE FROM queue_failed_jobs");
    }
    
    /**
     * Retry failed job
     */
    public function retryFailedJob($failedJobId) {
        $this->db->begin_transaction();
        
        try {
            // Get failed job
            $stmt = $this->db->prepare("SELECT * FROM queue_failed_jobs WHERE id = ?");
            $stmt->bind_param('i', $failedJobId);
            $stmt->execute();
            $failed = $stmt->get_result()->fetch_assoc();
            
            // Push back to queue
            $stmt = $this->db->prepare(
                "INSERT INTO queue_jobs (queue, payload, available_at) 
                 VALUES (?, ?, NOW())"
            );
            $stmt->bind_param('ss', $failed['queue'], $failed['payload']);
            $stmt->execute();
            
            // Delete from failed jobs
            $stmt = $this->db->prepare("DELETE FROM queue_failed_jobs WHERE id = ?");
            $stmt->bind_param('i', $failedJobId);
            $stmt->execute();
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}

/**
 * Queue Job
 * Represents a single job in the queue
 */
class QueueJob {
    private $data;
    private $manager;
    
    public function __construct($data, $manager) {
        $this->data = $data;
        $this->manager = $manager;
    }
    
    /**
     * Get job ID
     */
    public function getId() {
        return $this->data['id'];
    }
    
    /**
     * Get job payload
     */
    public function getPayload() {
        return json_decode($this->data['payload'], true);
    }
    
    /**
     * Get attempts count
     */
    public function getAttempts() {
        return $this->data['attempts'];
    }
    
    /**
     * Get max attempts
     */
    public function getMaxAttempts() {
        return $this->data['max_attempts'];
    }
    
    /**
     * Check if job should be retried
     */
    public function shouldRetry() {
        return $this->getAttempts() < $this->getMaxAttempts();
    }
    
    /**
     * Delete job
     */
    public function delete() {
        $this->manager->delete($this->getId());
    }
    
    /**
     * Release job back to queue
     */
    public function release($delay = 0) {
        $this->manager->release($this->getId(), $delay);
    }
    
    /**
     * Mark job as failed
     */
    public function fail($exception) {
        $this->manager->fail($this->getId(), $exception);
    }
}

/**
 * Queue Worker
 * Processes jobs from the queue
 */
class QueueWorker {
    private $manager;
    private $queue;
    private $sleep = 3; // seconds
    private $maxRuntime = 3600; // 1 hour
    private $running = false;
    
    public function __construct($queue = 'default') {
        $this->manager = QueueManager::getInstance();
        $this->queue = $queue;
    }
    
    /**
     * Start processing jobs
     */
    public function work() {
        $this->running = true;
        $startTime = time();
        
        while ($this->running) {
            // Check runtime limit
            if (time() - $startTime >= $this->maxRuntime) {
                break;
            }
            
            // Get next job
            $job = $this->manager->pop($this->queue);
            
            if ($job) {
                $this->processJob($job);
            } else {
                // No jobs, sleep
                sleep($this->sleep);
            }
        }
    }
    
    /**
     * Process single job
     */
    private function processJob($job) {
        try {
            $payload = $job->getPayload();
            
            // Execute job handler
            if (isset($payload['class']) && isset($payload['method'])) {
                $class = $payload['class'];
                $method = $payload['method'];
                $args = $payload['args'] ?? [];
                
                if (class_exists($class)) {
                    $instance = new $class();
                    
                    if (method_exists($instance, $method)) {
                        call_user_func_array([$instance, $method], $args);
                    }
                }
            }
            
            // Job succeeded, delete it
            $job->delete();
        } catch (Exception $e) {
            // Job failed
            if ($job->shouldRetry()) {
                // Release with exponential backoff
                $delay = pow(2, $job->getAttempts()) * 60; // 2^n minutes
                $job->release($delay);
            } else {
                // Max attempts reached, mark as failed
                $job->fail($e->getMessage());
            }
        }
    }
    
    /**
     * Stop worker
     */
    public function stop() {
        $this->running = false;
    }
}

/**
 * Job Dispatcher
 * Helper for dispatching jobs
 */
class JobDispatcher {
    /**
     * Dispatch job immediately
     */
    public static function dispatch($class, $method, $args = [], $queue = 'default', $priority = 0) {
        $manager = QueueManager::getInstance();
        
        return $manager->push([
            'class' => $class,
            'method' => $method,
            'args' => $args
        ], $queue, $priority);
    }
    
    /**
     * Dispatch delayed job
     */
    public static function dispatchLater($class, $method, $args, $delay, $queue = 'default', $priority = 0) {
        $manager = QueueManager::getInstance();
        
        return $manager->later([
            'class' => $class,
            'method' => $method,
            'args' => $args
        ], $delay, $queue, $priority);
    }
}
