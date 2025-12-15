<?php
/**
 * Database Optimizer
 * Tools for analyzing and optimizing database performance
 * 
 * @package SLPA\Database
 * @version 1.0.0
 */

class DatabaseOptimizer {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Analyze table and suggest optimizations
     */
    public function analyzeTable($tableName) {
        $analysis = [
            'table' => $tableName,
            'rows' => $this->getRowCount($tableName),
            'size' => $this->getTableSize($tableName),
            'indexes' => $this->analyzeIndexes($tableName),
            'columns' => $this->analyzeColumns($tableName),
            'fragmentation' => $this->checkFragmentation($tableName),
            'suggestions' => []
        ];
        
        // Generate suggestions
        $analysis['suggestions'] = $this->generateSuggestions($analysis);
        
        return $analysis;
    }
    
    /**
     * Get row count
     */
    private function getRowCount($tableName) {
        $result = $this->db->query("SELECT COUNT(*) as count FROM `$tableName`");
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
    
    /**
     * Get table size in MB
     */
    private function getTableSize($tableName) {
        $sql = "SELECT 
                    ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    ROUND(data_length / 1024 / 1024, 2) AS data_mb,
                    ROUND(index_length / 1024 / 1024, 2) AS index_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                AND table_name = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $tableName);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Analyze indexes
     */
    private function analyzeIndexes($tableName) {
        $result = $this->db->query("SHOW INDEX FROM `$tableName`");
        
        $indexes = [];
        $usage = $this->getIndexUsage($tableName);
        
        while ($row = $result->fetch_assoc()) {
            $indexName = $row['Key_name'];
            
            if (!isset($indexes[$indexName])) {
                $indexes[$indexName] = [
                    'name' => $indexName,
                    'type' => $row['Index_type'],
                    'unique' => $row['Non_unique'] == 0,
                    'columns' => [],
                    'cardinality' => 0,
                    'used' => isset($usage[$indexName]) ? $usage[$indexName] : 0
                ];
            }
            
            $indexes[$indexName]['columns'][] = $row['Column_name'];
            $indexes[$indexName]['cardinality'] += (int)$row['Cardinality'];
        }
        
        return array_values($indexes);
    }
    
    /**
     * Get index usage statistics
     */
    private function getIndexUsage($tableName) {
        $sql = "SELECT 
                    index_name,
                    COUNT(*) as usage_count
                FROM information_schema.STATISTICS
                WHERE table_schema = DATABASE()
                AND table_name = ?
                GROUP BY index_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $tableName);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $usage = [];
        
        while ($row = $result->fetch_assoc()) {
            $usage[$row['index_name']] = (int)$row['usage_count'];
        }
        
        return $usage;
    }
    
    /**
     * Analyze columns
     */
    private function analyzeColumns($tableName) {
        $result = $this->db->query("SHOW COLUMNS FROM `$tableName`");
        
        $columns = [];
        
        while ($row = $result->fetch_assoc()) {
            $columns[] = [
                'name' => $row['Field'],
                'type' => $row['Type'],
                'null' => $row['Null'] === 'YES',
                'key' => $row['Key'],
                'default' => $row['Default'],
                'extra' => $row['Extra']
            ];
        }
        
        return $columns;
    }
    
    /**
     * Check table fragmentation
     */
    private function checkFragmentation($tableName) {
        $sql = "SELECT 
                    data_free,
                    ROUND(data_free / (data_length + index_length) * 100, 2) as fragmentation_percent
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                AND table_name = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $tableName);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Generate optimization suggestions
     */
    private function generateSuggestions($analysis) {
        $suggestions = [];
        
        // Check fragmentation
        if ($analysis['fragmentation']['fragmentation_percent'] > 10) {
            $suggestions[] = [
                'type' => 'fragmentation',
                'priority' => 'high',
                'message' => "Table is {$analysis['fragmentation']['fragmentation_percent']}% fragmented. Run OPTIMIZE TABLE.",
                'action' => "OPTIMIZE TABLE `{$analysis['table']}`"
            ];
        }
        
        // Check for missing indexes
        $hasIndex = false;
        foreach ($analysis['indexes'] as $index) {
            if ($index['name'] !== 'PRIMARY') {
                $hasIndex = true;
                break;
            }
        }
        
        if (!$hasIndex && $analysis['rows'] > 1000) {
            $suggestions[] = [
                'type' => 'index',
                'priority' => 'medium',
                'message' => 'Table has no secondary indexes and contains significant data. Consider adding indexes on frequently queried columns.',
                'action' => null
            ];
        }
        
        // Check for unused indexes
        foreach ($analysis['indexes'] as $index) {
            if ($index['name'] !== 'PRIMARY' && $index['used'] == 0) {
                $suggestions[] = [
                    'type' => 'index',
                    'priority' => 'low',
                    'message' => "Index '{$index['name']}' appears unused and could be removed to improve write performance.",
                    'action' => "DROP INDEX `{$index['name']}` ON `{$analysis['table']}`"
                ];
            }
        }
        
        // Check column types
        foreach ($analysis['columns'] as $column) {
            // Check for VARCHAR with very small max length
            if (preg_match('/varchar\((\d+)\)/i', $column['type'], $matches)) {
                $length = (int)$matches[1];
                if ($length < 10) {
                    $suggestions[] = [
                        'type' => 'column',
                        'priority' => 'low',
                        'message' => "Column '{$column['name']}' uses VARCHAR($length). Consider using CHAR($length) for fixed-length data.",
                        'action' => null
                    ];
                }
            }
            
            // Check for TEXT fields that could be VARCHAR
            if (stripos($column['type'], 'text') !== false && $analysis['rows'] < 10000) {
                $suggestions[] = [
                    'type' => 'column',
                    'priority' => 'low',
                    'message' => "Column '{$column['name']}' uses TEXT type. If data length is predictable and under 255 chars, consider VARCHAR.",
                    'action' => null
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Optimize table
     */
    public function optimizeTable($tableName) {
        $this->logger->info("Optimizing table: $tableName");
        
        $result = $this->db->query("OPTIMIZE TABLE `$tableName`");
        
        return [
            'table' => $tableName,
            'success' => $result !== false,
            'message' => 'Table optimized successfully'
        ];
    }
    
    /**
     * Analyze query performance
     */
    public function explainQuery($sql) {
        $result = $this->db->query("EXPLAIN $sql");
        
        $explain = [];
        while ($row = $result->fetch_assoc()) {
            $explain[] = $row;
        }
        
        $analysis = [
            'query' => $sql,
            'explain' => $explain,
            'suggestions' => $this->analyzeExplain($explain)
        ];
        
        return $analysis;
    }
    
    /**
     * Analyze EXPLAIN output
     */
    private function analyzeExplain($explain) {
        $suggestions = [];
        
        foreach ($explain as $row) {
            // Check for full table scans
            if ($row['type'] === 'ALL') {
                $suggestions[] = [
                    'type' => 'performance',
                    'priority' => 'high',
                    'message' => "Full table scan detected on table '{$row['table']}'. Consider adding an index.",
                    'details' => $row
                ];
            }
            
            // Check for filesort
            if (stripos($row['Extra'], 'Using filesort') !== false) {
                $suggestions[] = [
                    'type' => 'performance',
                    'priority' => 'medium',
                    'message' => "Filesort operation detected. Consider adding an index on ORDER BY columns.",
                    'details' => $row
                ];
            }
            
            // Check for temporary table
            if (stripos($row['Extra'], 'Using temporary') !== false) {
                $suggestions[] = [
                    'type' => 'performance',
                    'priority' => 'medium',
                    'message' => "Temporary table created. Consider optimizing GROUP BY or DISTINCT operations.",
                    'details' => $row
                ];
            }
            
            // Check rows examined
            if (isset($row['rows']) && $row['rows'] > 10000) {
                $suggestions[] = [
                    'type' => 'performance',
                    'priority' => 'medium',
                    'message' => "Large number of rows examined ({$row['rows']}). Consider adding more selective WHERE conditions or indexes.",
                    'details' => $row
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Get slow queries
     */
    public function getSlowQueries($limit = 10) {
        // Enable slow query log analysis
        $sql = "SELECT 
                    sql_text,
                    ROUND(timer_wait/1000000000000, 2) as duration_ms,
                    lock_time,
                    rows_examined,
                    rows_sent
                FROM performance_schema.events_statements_history
                WHERE timer_wait > 1000000000000
                ORDER BY timer_wait DESC
                LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $queries = [];
            
            while ($row = $result->fetch_assoc()) {
                $queries[] = $row;
            }
            
            return $queries;
            
        } catch (Exception $e) {
            $this->logger->warning("Could not fetch slow queries: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate database health report
     */
    public function healthReport() {
        $tables = $this->getAllTables();
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $this->getDatabaseName(),
            'total_tables' => count($tables),
            'total_size_mb' => 0,
            'tables' => [],
            'issues' => [],
            'suggestions' => []
        ];
        
        foreach ($tables as $table) {
            $analysis = $this->analyzeTable($table);
            
            $report['total_size_mb'] += $analysis['size']['size_mb'];
            $report['tables'][] = [
                'name' => $table,
                'rows' => $analysis['rows'],
                'size_mb' => $analysis['size']['size_mb'],
                'indexes' => count($analysis['indexes']),
                'fragmentation' => $analysis['fragmentation']['fragmentation_percent']
            ];
            
            // Collect issues
            if ($analysis['fragmentation']['fragmentation_percent'] > 10) {
                $report['issues'][] = "Table '$table' is highly fragmented";
            }
            
            // Collect suggestions
            foreach ($analysis['suggestions'] as $suggestion) {
                if ($suggestion['priority'] === 'high') {
                    $report['suggestions'][] = $suggestion;
                }
            }
        }
        
        return $report;
    }
    
    /**
     * Get all tables
     */
    private function getAllTables() {
        $result = $this->db->query("SHOW TABLES");
        
        $tables = [];
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        return $tables;
    }
    
    /**
     * Get database name
     */
    private function getDatabaseName() {
        $result = $this->db->query("SELECT DATABASE() as db");
        $row = $result->fetch_assoc();
        return $row['db'];
    }
    
    /**
     * Optimize all tables
     */
    public function optimizeAllTables() {
        $tables = $this->getAllTables();
        $results = [];
        
        foreach ($tables as $table) {
            $results[] = $this->optimizeTable($table);
        }
        
        return [
            'optimized' => count($results),
            'results' => $results
        ];
    }
    
    /**
     * Create recommended indexes
     */
    public function createRecommendedIndexes($tableName) {
        $analysis = $this->analyzeTable($tableName);
        $created = [];
        
        // Example: Create indexes on foreign key columns without indexes
        foreach ($analysis['columns'] as $column) {
            $columnName = $column['name'];
            
            // Check if column ends with _id (common foreign key pattern)
            if (preg_match('/_id$/', $columnName) && empty($column['key'])) {
                $indexName = "idx_$columnName";
                
                try {
                    $this->db->query("ALTER TABLE `$tableName` ADD INDEX `$indexName` (`$columnName`)");
                    $created[] = $indexName;
                    $this->logger->info("Created index: $tableName.$indexName");
                } catch (Exception $e) {
                    $this->logger->error("Failed to create index: " . $e->getMessage());
                }
            }
        }
        
        return [
            'table' => $tableName,
            'indexes_created' => count($created),
            'indexes' => $created
        ];
    }
}
