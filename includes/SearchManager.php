<?php
/**
 * Search Manager
 * Advanced search and filtering system for inventory items
 * 
 * Features:
 * - Multi-table search
 * - Fuzzy matching
 * - Advanced filters
 * - Sorting and pagination
 * - Search history
 * - Search suggestions
 */

class SearchManager {
    private $db;
    private $logger;
    
    // Searchable tables and their columns
    private $searchableConfig = [
        'papers_master' => [
            'columns' => ['name', 'size', 'type', 'description'],
            'display_name' => 'Papers',
            'view_link' => 'pages/papers_master.php'
        ],
        'ribbons_master' => [
            'columns' => ['name', 'type', 'model', 'description'],
            'display_name' => 'Ribbons',
            'view_link' => 'pages/ribbons_master.php'
        ],
        'toner_master' => [
            'columns' => ['name', 'type', 'model', 'description'],
            'display_name' => 'Toner',
            'view_link' => 'pages/toner_master.php'
        ],
        'users' => [
            'columns' => ['username', 'email', 'full_name', 'role'],
            'display_name' => 'Users',
            'view_link' => 'pages/users.php'
        ]
    ];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Perform global search across all tables
     */
    public function globalSearch($query, $options = []) {
        try {
            $results = [];
            $tables = $options['tables'] ?? array_keys($this->searchableConfig);
            $limit = $options['limit'] ?? 50;
            $fuzzy = $options['fuzzy'] ?? true;
            
            foreach ($tables as $table) {
                if (!isset($this->searchableConfig[$table])) {
                    continue;
                }
                
                $tableResults = $this->searchTable($table, $query, [
                    'limit' => $limit,
                    'fuzzy' => $fuzzy
                ]);
                
                if (!empty($tableResults)) {
                    $results[$table] = [
                        'display_name' => $this->searchableConfig[$table]['display_name'],
                        'view_link' => $this->searchableConfig[$table]['view_link'],
                        'count' => count($tableResults),
                        'results' => $tableResults
                    ];
                }
            }
            
            $this->saveSearchHistory($query, array_sum(array_column($results, 'count')));
            
            return $results;
            
        } catch (Exception $e) {
            $this->logger->error("Global search failed", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Search within specific table
     */
    public function searchTable($table, $query, $options = []) {
        try {
            if (!isset($this->searchableConfig[$table])) {
                throw new Exception("Table $table is not searchable");
            }
            
            $config = $this->searchableConfig[$table];
            $limit = $options['limit'] ?? 50;
            $offset = $options['offset'] ?? 0;
            $fuzzy = $options['fuzzy'] ?? true;
            $filters = $options['filters'] ?? [];
            $orderBy = $options['order_by'] ?? 'id';
            $orderDir = strtoupper($options['order_dir'] ?? 'DESC');
            
            // Build WHERE clause for search
            $whereConditions = [];
            $params = [];
            $types = '';
            
            // Search conditions
            if (!empty($query)) {
                $searchConditions = [];
                
                foreach ($config['columns'] as $column) {
                    if ($fuzzy) {
                        $searchConditions[] = "$column LIKE ?";
                        $params[] = "%$query%";
                        $types .= 's';
                    } else {
                        $searchConditions[] = "$column = ?";
                        $params[] = $query;
                        $types .= 's';
                    }
                }
                
                $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';
            }
            
            // Additional filters
            foreach ($filters as $column => $value) {
                if (in_array($column, $config['columns']) || $column === 'id') {
                    if (is_array($value)) {
                        $placeholders = implode(',', array_fill(0, count($value), '?'));
                        $whereConditions[] = "$column IN ($placeholders)";
                        foreach ($value as $v) {
                            $params[] = $v;
                            $types .= is_int($v) ? 'i' : 's';
                        }
                    } else {
                        $whereConditions[] = "$column = ?";
                        $params[] = $value;
                        $types .= is_int($value) ? 'i' : 's';
                    }
                }
            }
            
            // Build query
            $sql = "SELECT * FROM $table";
            
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(' AND ', $whereConditions);
            }
            
            $sql .= " ORDER BY $orderBy $orderDir LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            // Execute query
            $stmt = $this->db->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            
            $result = $stmt->get_result();
            $results = [];
            
            while ($row = $result->fetch_assoc()) {
                // Calculate relevance score
                $row['_relevance'] = $this->calculateRelevance($row, $query, $config['columns']);
                $row['_table'] = $table;
                $results[] = $row;
            }
            
            // Sort by relevance if searching
            if (!empty($query)) {
                usort($results, function($a, $b) {
                    return $b['_relevance'] <=> $a['_relevance'];
                });
            }
            
            return $results;
            
        } catch (Exception $e) {
            $this->logger->error("Table search failed", [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Calculate relevance score for search result
     */
    private function calculateRelevance($row, $query, $columns) {
        if (empty($query)) {
            return 0;
        }
        
        $score = 0;
        $query = strtolower($query);
        
        foreach ($columns as $column) {
            if (!isset($row[$column])) {
                continue;
            }
            
            $value = strtolower($row[$column]);
            
            // Exact match
            if ($value === $query) {
                $score += 100;
            }
            // Starts with query
            elseif (strpos($value, $query) === 0) {
                $score += 50;
            }
            // Contains query
            elseif (strpos($value, $query) !== false) {
                $score += 25;
            }
            // Word match
            elseif (in_array($query, explode(' ', $value))) {
                $score += 40;
            }
        }
        
        return $score;
    }
    
    /**
     * Get search suggestions
     */
    public function getSuggestions($query, $limit = 10) {
        try {
            $suggestions = [];
            
            foreach ($this->searchableConfig as $table => $config) {
                $column = $config['columns'][0]; // Use primary column
                
                $stmt = $this->db->prepare(
                    "SELECT DISTINCT $column as suggestion 
                     FROM $table 
                     WHERE $column LIKE ? 
                     LIMIT ?"
                );
                
                $searchQuery = "$query%";
                $stmt->bind_param('si', $searchQuery, $limit);
                $stmt->execute();
                
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $suggestions[] = [
                        'text' => $row['suggestion'],
                        'table' => $table,
                        'category' => $config['display_name']
                    ];
                }
            }
            
            return array_slice($suggestions, 0, $limit);
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get suggestions", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Advanced filter builder
     */
    public function buildAdvancedFilter($table, $filters) {
        try {
            $results = $this->searchTable($table, '', [
                'filters' => $filters,
                'limit' => 1000
            ]);
            
            return [
                'success' => true,
                'count' => count($results),
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Advanced filter failed", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Save search to history
     */
    private function saveSearchHistory($query, $resultCount) {
        try {
            // Create search_history table if not exists
            $this->db->query(
                "CREATE TABLE IF NOT EXISTS search_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT DEFAULT NULL,
                    query VARCHAR(500) NOT NULL,
                    result_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_query (query),
                    INDEX idx_user (user_id),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            
            $userId = Auth::check() ? Auth::id() : null;
            
            $stmt = $this->db->prepare(
                "INSERT INTO search_history (user_id, query, result_count) VALUES (?, ?, ?)"
            );
            $stmt->bind_param('isi', $userId, $query, $resultCount);
            $stmt->execute();
            
        } catch (Exception $e) {
            // Silent fail - search history is not critical
            $this->logger->debug("Failed to save search history", ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get popular searches
     */
    public function getPopularSearches($limit = 10) {
        try {
            $result = $this->db->query(
                "SELECT query, COUNT(*) as count, AVG(result_count) as avg_results
                 FROM search_history
                 WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY query
                 ORDER BY count DESC
                 LIMIT $limit"
            );
            
            $searches = [];
            while ($row = $result->fetch_assoc()) {
                $searches[] = $row;
            }
            
            return $searches;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get popular searches", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get user's recent searches
     */
    public function getRecentSearches($userId, $limit = 10) {
        try {
            $stmt = $this->db->prepare(
                "SELECT DISTINCT query, MAX(created_at) as last_search
                 FROM search_history
                 WHERE user_id = ?
                 GROUP BY query
                 ORDER BY last_search DESC
                 LIMIT ?"
            );
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $searches = [];
            
            while ($row = $result->fetch_assoc()) {
                $searches[] = $row;
            }
            
            return $searches;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get recent searches", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Clear search history
     */
    public function clearSearchHistory($userId = null, $olderThan = null) {
        try {
            if ($userId !== null) {
                if ($olderThan !== null) {
                    $stmt = $this->db->prepare(
                        "DELETE FROM search_history WHERE user_id = ? AND created_at < ?"
                    );
                    $stmt->bind_param('is', $userId, $olderThan);
                } else {
                    $stmt = $this->db->prepare("DELETE FROM search_history WHERE user_id = ?");
                    $stmt->bind_param('i', $userId);
                }
            } else {
                if ($olderThan !== null) {
                    $stmt = $this->db->prepare("DELETE FROM search_history WHERE created_at < ?");
                    $stmt->bind_param('s', $olderThan);
                } else {
                    $this->db->query("TRUNCATE TABLE search_history");
                    return true;
                }
            }
            
            $stmt->execute();
            return $stmt->affected_rows;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to clear search history", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
