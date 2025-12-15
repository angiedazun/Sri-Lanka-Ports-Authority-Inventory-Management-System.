<?php
/**
 * Advanced Search Engine
 * Professional search with filters, saved searches, and full-text capabilities
 * 
 * @package SLPA\Search
 * @version 1.0.0
 */

class AdvancedSearch {
    private $db;
    private $logger;
    private $searchTables = [];
    private $searchFields = [];
    private $filters = [];
    private $sortBy;
    private $sortOrder = 'ASC';
    private $page = 1;
    private $perPage = 50;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Add searchable table
     */
    public function addTable($table, $fields, $displayName = null) {
        $this->searchTables[$table] = [
            'fields' => $fields,
            'display_name' => $displayName ?: $table
        ];
        return $this;
    }
    
    /**
     * Add filter
     */
    public function addFilter($field, $operator, $value) {
        $this->filters[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }
    
    /**
     * Set sorting
     */
    public function sortBy($field, $order = 'ASC') {
        $this->sortBy = $field;
        $this->sortOrder = strtoupper($order);
        return $this;
    }
    
    /**
     * Set pagination
     */
    public function paginate($page, $perPage = 50) {
        $this->page = max(1, (int)$page);
        $this->perPage = (int)$perPage;
        return $this;
    }
    
    /**
     * Perform search
     */
    public function search($query) {
        $results = [];
        
        foreach ($this->searchTables as $table => $config) {
            $tableResults = $this->searchTable($table, $config, $query);
            $results = array_merge($results, $tableResults);
        }
        
        // Apply sorting
        if ($this->sortBy) {
            usort($results, function($a, $b) {
                $aVal = $a[$this->sortBy] ?? '';
                $bVal = $b[$this->sortBy] ?? '';
                
                if ($this->sortOrder === 'DESC') {
                    return $bVal <=> $aVal;
                }
                return $aVal <=> $bVal;
            });
        }
        
        // Calculate pagination
        $total = count($results);
        $offset = ($this->page - 1) * $this->perPage;
        $results = array_slice($results, $offset, $this->perPage);
        
        return [
            'results' => $results,
            'total' => $total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => ceil($total / $this->perPage)
        ];
    }
    
    /**
     * Search specific table
     */
    private function searchTable($table, $config, $query) {
        $conn = $this->db->getConnection();
        
        // Build search conditions
        $searchConditions = [];
        $params = [];
        $types = '';
        
        foreach ($config['fields'] as $field) {
            $searchConditions[] = "`$field` LIKE ?";
            $params[] = "%$query%";
            $types .= 's';
        }
        
        $sql = "SELECT *, '$table' as _source_table, '{$config['display_name']}' as _source_name 
                FROM `$table` 
                WHERE (" . implode(' OR ', $searchConditions) . ")";
        
        // Apply filters
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                $sql .= " AND `{$filter['field']}` {$filter['operator']} ?";
                $params[] = $filter['value'];
                $types .= is_numeric($filter['value']) ? 'i' : 's';
            }
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        
        return $results;
    }
    
    /**
     * Full-text search
     */
    public function fullTextSearch($query, $table, $fields) {
        $conn = $this->db->getConnection();
        
        $fieldList = implode(',', array_map(function($f) {
            return "`$f`";
        }, $fields));
        
        $sql = "SELECT *, MATCH($fieldList) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM `$table`
                WHERE MATCH($fieldList) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $limit = $this->perPage;
        $stmt->bind_param('ssi', $query, $query, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $results = [];
        
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        
        return $results;
    }
    
    /**
     * Save search
     */
    public function saveSearch($name, $description = '') {
        $conn = $this->db->getConnection();
        
        $searchData = [
            'tables' => $this->searchTables,
            'filters' => $this->filters,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder
        ];
        
        $sql = "INSERT INTO saved_searches 
                (name, description, search_data, user_id, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $searchDataJson = json_encode($searchData);
        $userId = $_SESSION['user_id'] ?? 0;
        
        $stmt->bind_param('sssi', $name, $description, $searchDataJson, $userId);
        $stmt->execute();
        
        return $conn->insert_id;
    }
    
    /**
     * Load saved search
     */
    public static function loadSavedSearch($searchId) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT search_data FROM saved_searches WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $searchId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            throw new Exception("Saved search not found: $searchId");
        }
        
        $searchData = json_decode($row['search_data'], true);
        
        $search = new self();
        $search->searchTables = $searchData['tables'] ?? [];
        $search->filters = $searchData['filters'] ?? [];
        $search->sortBy = $searchData['sort_by'] ?? null;
        $search->sortOrder = $searchData['sort_order'] ?? 'ASC';
        
        return $search;
    }
    
    /**
     * Get user's saved searches
     */
    public static function getUserSavedSearches($userId) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT id, name, description, created_at, last_used 
                FROM saved_searches 
                WHERE user_id = ? 
                ORDER BY last_used DESC, created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $searches = [];
        
        while ($row = $result->fetch_assoc()) {
            $searches[] = $row;
        }
        
        return $searches;
    }
    
    /**
     * Log search
     */
    public function logSearch($query, $resultsCount, $userId) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO search_history 
                (user_id, query, results_count, search_data, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $searchDataJson = json_encode([
            'tables' => array_keys($this->searchTables),
            'filters' => $this->filters
        ]);
        
        $stmt->bind_param('isis', $userId, $query, $resultsCount, $searchDataJson);
        $stmt->execute();
    }
    
    /**
     * Get search history
     */
    public static function getSearchHistory($userId, $limit = 20) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT query, results_count, created_at 
                FROM search_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
    
    /**
     * Get popular searches
     */
    public static function getPopularSearches($limit = 10) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT query, COUNT(*) as search_count 
                FROM search_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY query 
                ORDER BY search_count DESC 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $popular = [];
        
        while ($row = $result->fetch_assoc()) {
            $popular[] = $row;
        }
        
        return $popular;
    }
}

/**
 * Faceted Search
 */
class FacetedSearch {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get facets for search results
     */
    public function getFacets($table, $facetFields, $baseQuery = '') {
        $conn = $this->db->getConnection();
        $facets = [];
        
        foreach ($facetFields as $field) {
            $sql = "SELECT `$field` as value, COUNT(*) as count 
                    FROM `$table`";
            
            if ($baseQuery) {
                $sql .= " WHERE $baseQuery";
            }
            
            $sql .= " GROUP BY `$field` 
                     ORDER BY count DESC 
                     LIMIT 20";
            
            $result = $conn->query($sql);
            $values = [];
            
            while ($row = $result->fetch_assoc()) {
                if ($row['value']) {
                    $values[] = $row;
                }
            }
            
            $facets[$field] = $values;
        }
        
        return $facets;
    }
    
    /**
     * Apply facet filters
     */
    public function applyFacets($baseQuery, $facetFilters) {
        if (empty($facetFilters)) {
            return $baseQuery;
        }
        
        $conditions = [];
        foreach ($facetFilters as $field => $values) {
            if (is_array($values)) {
                $placeholders = implode(',', array_fill(0, count($values), '?'));
                $conditions[] = "`$field` IN ($placeholders)";
            } else {
                $conditions[] = "`$field` = ?";
            }
        }
        
        if ($baseQuery) {
            return $baseQuery . ' AND ' . implode(' AND ', $conditions);
        }
        
        return implode(' AND ', $conditions);
    }
}

/**
 * Search Suggestions
 */
class SearchSuggestions {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get suggestions based on query
     */
    public function getSuggestions($query, $table, $field, $limit = 10) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT DISTINCT `$field` 
                FROM `$table` 
                WHERE `$field` LIKE ? 
                ORDER BY `$field` 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $searchTerm = $query . '%';
        $stmt->bind_param('si', $searchTerm, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $suggestions = [];
        
        while ($row = $result->fetch_row()) {
            $suggestions[] = $row[0];
        }
        
        return $suggestions;
    }
    
    /**
     * Get related searches
     */
    public function getRelatedSearches($query, $limit = 5) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT DISTINCT query 
                FROM search_history 
                WHERE query LIKE ? AND query != ?
                GROUP BY query 
                ORDER BY COUNT(*) DESC 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $searchTerm = '%' . $query . '%';
        $stmt->bind_param('ssi', $searchTerm, $query, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $related = [];
        
        while ($row = $result->fetch_row()) {
            $related[] = $row[0];
        }
        
        return $related;
    }
}
