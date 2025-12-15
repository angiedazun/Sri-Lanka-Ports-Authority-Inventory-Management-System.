<?php
/**
 * Export API Endpoint
 * Handles data export requests
 */

require_once '../includes/db.php';

// Check authentication
if (!Auth::check()) {
    Response::json(['success' => false, 'error' => 'Unauthorized'], 401);
}

try {
    $action = $_GET['action'] ?? 'export';
    $format = $_GET['format'] ?? 'excel';
    $table = $_GET['table'] ?? '';
    
    if (empty($table)) {
        Response::json(['success' => false, 'error' => 'Table parameter required'], 400);
    }
    
    // Validate table
    $allowedTables = ['papers_master', 'ribbons_master', 'toner_master', 'users'];
    if (!in_array($table, $allowedTables)) {
        Response::json(['success' => false, 'error' => 'Invalid table'], 400);
    }
    
    $exportManager = new ExportManager();
    
    // Build query
    $query = "SELECT * FROM $table";
    $conditions = [];
    
    // Apply filters if provided
    if (isset($_GET['filters']) && is_array($_GET['filters'])) {
        foreach ($_GET['filters'] as $column => $value) {
            $conditions[] = "$column = '" . Sanitizer::sql($value, $conn) . "'";
        }
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $query .= " ORDER BY id DESC";
    
    // Export options
    $options = [
        'title' => ucwords(str_replace('_', ' ', $table)),
        'footer' => 'SLPA Inventory Management System'
    ];
    
    // Export based on format
    $filename = $table . '_' . date('Y-m-d_His');
    $exportManager->exportQuery($query, $format, $filename, $options);
    
} catch (Exception $e) {
    $logger = new Logger();
    $logger->error("Export failed: " . $e->getMessage());
    Response::json(['success' => false, 'error' => $e->getMessage()], 500);
}
