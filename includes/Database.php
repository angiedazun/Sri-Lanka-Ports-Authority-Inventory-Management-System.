<?php
/**
 * Database Connection Class (Singleton Pattern)
 * Provides a single database connection instance throughout the application
 */

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset(DB_CHARSET);
            
            // Set SQL mode for better compatibility
            $this->connection->query("SET sql_mode = ''");
            
        } catch (Exception $e) {
            Logger::error("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Execute query with error handling
     */
    public function query($sql) {
        $result = $this->connection->query($sql);
        
        if (!$result) {
            Logger::error("Query error: " . $this->connection->error . " | SQL: " . $sql);
            throw new Exception("Database query failed");
        }
        
        return $result;
    }
    
    /**
     * Prepare statement
     */
    public function prepare($sql) {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            Logger::error("Prepare error: " . $this->connection->error . " | SQL: " . $sql);
            throw new Exception("Statement preparation failed");
        }
        
        return $stmt;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    /**
     * Escape string
     */
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    /**
     * Close connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
