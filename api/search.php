<?php
/**
 * Search API Endpoint
 * Handles search requests
 */

require_once '../includes/db.php';

// Check authentication
if (!Auth::check()) {
    Response::json(['success' => false, 'error' => 'Unauthorized'], 401);
}

try {
    $action = $_GET['action'] ?? 'search';
    $searchManager = new SearchManager();
    
    switch ($action) {
        case 'search':
        case 'global':
            $query = $_GET['q'] ?? $_GET['query'] ?? '';
            
            if (empty($query)) {
                Response::json(['success' => false, 'error' => 'Query parameter required'], 400);
            }
            
            $options = [
                'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 50,
                'fuzzy' => !isset($_GET['exact']) || $_GET['exact'] !== 'true'
            ];
            
            if (isset($_GET['tables'])) {
                $options['tables'] = explode(',', $_GET['tables']);
            }
            
            $results = $searchManager->globalSearch($query, $options);
            
            Response::json([
                'success' => true,
                'query' => $query,
                'results' => $results
            ]);
            break;
            
        case 'table':
            $table = $_GET['table'] ?? '';
            $query = $_GET['q'] ?? $_GET['query'] ?? '';
            
            if (empty($table)) {
                Response::json(['success' => false, 'error' => 'Table parameter required'], 400);
            }
            
            $options = [
                'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 50,
                'offset' => isset($_GET['offset']) ? (int)$_GET['offset'] : 0,
                'fuzzy' => !isset($_GET['exact']) || $_GET['exact'] !== 'true',
                'order_by' => $_GET['order_by'] ?? 'id',
                'order_dir' => $_GET['order_dir'] ?? 'DESC'
            ];
            
            if (isset($_GET['filters']) && is_array($_GET['filters'])) {
                $options['filters'] = $_GET['filters'];
            }
            
            $results = $searchManager->searchTable($table, $query, $options);
            
            Response::json([
                'success' => true,
                'table' => $table,
                'query' => $query,
                'results' => $results,
                'count' => count($results)
            ]);
            break;
            
        case 'suggestions':
            $query = $_GET['q'] ?? $_GET['query'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            $suggestions = $searchManager->getSuggestions($query, $limit);
            
            Response::json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
            break;
            
        case 'recent':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $searches = $searchManager->getRecentSearches(Auth::id(), $limit);
            
            Response::json([
                'success' => true,
                'searches' => $searches
            ]);
            break;
            
        case 'popular':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $searches = $searchManager->getPopularSearches($limit);
            
            Response::json([
                'success' => true,
                'searches' => $searches
            ]);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    $logger = new Logger();
    $logger->error("Search API failed: " . $e->getMessage());
    Response::json(['success' => false, 'error' => $e->getMessage()], 500);
}
