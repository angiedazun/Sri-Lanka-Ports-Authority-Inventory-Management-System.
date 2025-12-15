<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$suggestions = [];

if (!empty($query)) {
    // Search from ribbons_issuing table for ribbons with matching code
    // Group by code to get unique codes with their details
    $stmt = $conn->prepare("
        SELECT ribbon_id, ribbon_model, code, lot, stock
        FROM ribbons_issuing 
        WHERE code LIKE ? OR ribbon_model LIKE ? 
        GROUP BY code, ribbon_model, lot
        ORDER BY issue_date DESC
        LIMIT 10
    ");
    $searchTerm = "%{$query}%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'id' => $row['ribbon_id'],
            'model' => $row['ribbon_model'],
            'code' => $row['code'],
            'lot' => $row['lot'] ?? '',
            'supplier_name' => '' // Not available in ribbons_issuing
        ];
    }
    $stmt->close();
}

echo json_encode($suggestions);
?>
