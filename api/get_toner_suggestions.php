<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$suggestions = [];

if (!empty($query)) {
    // Search from toner_issuing table for codes that have been issued
    // Get the most recent issue record for each code with its stock type and LOT
    $stmt = $conn->prepare("
        SELECT ti.toner_id, ti.toner_model, ti.code, ti.color, ti.stock as stock_type, ti.lot
        FROM toner_issuing ti 
        WHERE ti.code LIKE ? OR ti.toner_model LIKE ? 
        ORDER BY ti.issue_date DESC
        LIMIT 10
    ");
    $searchTerm = "%{$query}%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'id' => $row['toner_id'],
            'model' => $row['toner_model'],
            'code' => $row['code'],
            'color' => $row['color'],
            'stock_location' => $row['stock_type'], // Use actual stock type from issuing record
            'lot' => $row['lot'] ?? '' // Include LOT number
        ];
    }
    $stmt->close();
}

echo json_encode($suggestions);
?>