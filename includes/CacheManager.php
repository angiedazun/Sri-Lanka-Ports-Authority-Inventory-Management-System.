<?php
/**
 * Caching System
 * Multi-layer caching with Redis, Memcached, and file-based fallback
 * 
 * @package SLPA\Cache
 * @version 1.0.0
 */

class CacheManager {
    private static $instance = null;
    private $adapter;
    private $prefix;
    private $defaultTTL = 3600; // 1 hour
    
    private function __construct() {
        $this->prefix = 'slpa_';
        $this->initializeAdapter();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize cache adapter (Redis > Memcached > File)
     */
    private function initializeAdapter() {
        // Try Redis first
        if (extension_loaded('redis') && class_exists('Redis')) {
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                $this->adapter = new RedisCacheAdapter($redis);
                return;
            } catch (Exception $e) {
                // Fall through to next adapter
            }
        }
        
        // Try Memcached
        if (extension_loaded('memcached') && class_exists('Memcached')) {
            try {
                $memcached = new Memcached();
                $memcached->addServer('127.0.0.1', 11211);
                $this->adapter = new MemcachedCacheAdapter($memcached);
                return;
            } catch (Exception $e) {
                // Fall through to file cache
            }
        }
        
        // Fallback to file-based cache
        $this->adapter = new FileCacheAdapter();
    }
    
    /**
     * Get cached value
     */
    public function get($key, $default = null) {
        $fullKey = $this->prefix . $key;
        $value = $this->adapter->get($fullKey);
        
        if ($value === false || $value === null) {
            return $default;
        }
        
        return unserialize($value);
    }
    
    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = null) {
        $fullKey = $this->prefix . $key;
        $ttl = $ttl ?? $this->defaultTTL;
        
        return $this->adapter->set($fullKey, serialize($value), $ttl);
    }
    
    /**
     * Check if key exists
     */
    public function has($key) {
        $fullKey = $this->prefix . $key;
        return $this->adapter->has($fullKey);
    }
    
    /**
     * Delete cached value
     */
    public function delete($key) {
        $fullKey = $this->prefix . $key;
        return $this->adapter->delete($fullKey);
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        return $this->adapter->clear();
    }
    
    /**
     * Get or set with callback
     */
    public function remember($key, $ttl, callable $callback) {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Cache forever (1 year)
     */
    public function forever($key, $value) {
        return $this->set($key, $value, 31536000);
    }
    
    /**
     * Increment value
     */
    public function increment($key, $value = 1) {
        $fullKey = $this->prefix . $key;
        return $this->adapter->increment($fullKey, $value);
    }
    
    /**
     * Decrement value
     */
    public function decrement($key, $value = 1) {
        $fullKey = $this->prefix . $key;
        return $this->adapter->decrement($fullKey, $value);
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        return $this->adapter->getStats();
    }
}

/**
 * Redis Cache Adapter
 */
class RedisCacheAdapter {
    private $redis;
    
    public function __construct(Redis $redis) {
        $this->redis = $redis;
    }
    
    public function get($key) {
        return $this->redis->get($key);
    }
    
    public function set($key, $value, $ttl) {
        return $this->redis->setex($key, $ttl, $value);
    }
    
    public function has($key) {
        return $this->redis->exists($key);
    }
    
    public function delete($key) {
        return $this->redis->del($key);
    }
    
    public function clear() {
        return $this->redis->flushDB();
    }
    
    public function increment($key, $value) {
        return $this->redis->incrBy($key, $value);
    }
    
    public function decrement($key, $value) {
        return $this->redis->decrBy($key, $value);
    }
    
    public function getStats() {
        return $this->redis->info();
    }
}

/**
 * Memcached Cache Adapter
 */
class MemcachedCacheAdapter {
    private $memcached;
    
    public function __construct(Memcached $memcached) {
        $this->memcached = $memcached;
    }
    
    public function get($key) {
        return $this->memcached->get($key);
    }
    
    public function set($key, $value, $ttl) {
        return $this->memcached->set($key, $value, $ttl);
    }
    
    public function has($key) {
        $this->memcached->get($key);
        return $this->memcached->getResultCode() === Memcached::RES_SUCCESS;
    }
    
    public function delete($key) {
        return $this->memcached->delete($key);
    }
    
    public function clear() {
        return $this->memcached->flush();
    }
    
    public function increment($key, $value) {
        return $this->memcached->increment($key, $value);
    }
    
    public function decrement($key, $value) {
        return $this->memcached->decrement($key, $value);
    }
    
    public function getStats() {
        return $this->memcached->getStats();
    }
}

/**
 * File Cache Adapter
 */
class FileCacheAdapter {
    private $cachePath;
    
    public function __construct() {
        $this->cachePath = BASE_PATH . '/cache';
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public function get($key) {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return false;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Check expiration
        if ($data['expires'] < time()) {
            $this->delete($key);
            return false;
        }
        
        return $data['value'];
    }
    
    public function set($key, $value, $ttl) {
        $file = $this->getFilePath($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public function has($key) {
        return $this->get($key) !== false;
    }
    
    public function delete($key) {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    public function clear() {
        $files = glob($this->cachePath . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    public function increment($key, $value) {
        $current = $this->get($key);
        $new = ($current === false ? 0 : (int)unserialize($current)) + $value;
        $this->set($key, serialize($new), 3600);
        return $new;
    }
    
    public function decrement($key, $value) {
        return $this->increment($key, -$value);
    }
    
    public function getStats() {
        $files = glob($this->cachePath . '/*.cache');
        return [
            'items' => count($files),
            'size' => array_sum(array_map('filesize', $files))
        ];
    }
    
    private function getFilePath($key) {
        return $this->cachePath . '/' . md5($key) . '.cache';
    }
}

/**
 * Query Cache Helper
 */
class QueryCache {
    private $cache;
    private $db;
    
    public function __construct() {
        $this->cache = CacheManager::getInstance();
        $this->db = Database::getInstance();
    }
    
    /**
     * Cache query results
     */
    public function query($sql, $params = [], $ttl = 3600) {
        $key = 'query_' . md5($sql . serialize($params));
        
        return $this->cache->remember($key, $ttl, function() use ($sql, $params) {
            $conn = $this->db->getConnection();
            
            if (empty($params)) {
                $result = $conn->query($sql);
            } else {
                $stmt = $conn->prepare($sql);
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            return $data;
        });
    }
    
    /**
     * Invalidate query cache by pattern
     */
    public function invalidate($pattern) {
        // This would require cache adapter support for pattern matching
        // For now, clear all cache
        $this->cache->clear();
    }
}
