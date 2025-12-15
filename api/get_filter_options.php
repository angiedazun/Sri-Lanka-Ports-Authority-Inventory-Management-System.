<?php
require_once '../includes/db.php';
require_login();

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

$results = [];

try {
    switch ($type) {
        case 'suppliers':
            // Get unique suppliers from all tables
            $suppliers = [];
            
            // From papers_receiving
            $result = $conn->query("SELECT DISTINCT supplier_name FROM papers_receiving WHERE supplier_name IS NOT NULL AND supplier_name != '' ORDER BY supplier_name");
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = $row['supplier_name'];
            }
            
            // From toner_receiving
            $result = $conn->query("SELECT DISTINCT supplier_name FROM toner_receiving WHERE supplier_name IS NOT NULL AND supplier_name != '' ORDER BY supplier_name");
            while ($row = $result->fetch_assoc()) {
                if (!in_array($row['supplier_name'], $suppliers)) {
                    $suppliers[] = $row['supplier_name'];
                }
            }
            
            // From ribbons_receiving
            $result = $conn->query("SELECT DISTINCT supplier_name FROM ribbons_receiving WHERE supplier_name IS NOT NULL AND supplier_name != '' ORDER BY supplier_name");
            while ($row = $result->fetch_assoc()) {
                if (!in_array($row['supplier_name'], $suppliers)) {
                    $suppliers[] = $row['supplier_name'];
                }
            }
            
            sort($suppliers);
            $results = $suppliers;
            break;
            
        case 'items':
            // Get unique items from all tables
            $items = [];
            
            // Paper types
            $result = $conn->query("SELECT DISTINCT paper_type FROM papers_master WHERE paper_type IS NOT NULL AND paper_type != '' ORDER BY paper_type");
            while ($row = $result->fetch_assoc()) {
                $items[] = $row['paper_type'];
            }
            
            // Toner models
            $result = $conn->query("SELECT DISTINCT toner_model FROM toner_master WHERE toner_model IS NOT NULL AND toner_model != '' ORDER BY toner_model");
            while ($row = $result->fetch_assoc()) {
                $items[] = $row['toner_model'];
            }
            
            // Ribbon models
            $result = $conn->query("SELECT DISTINCT ribbon_model FROM ribbons_master WHERE ribbon_model IS NOT NULL AND ribbon_model != '' ORDER BY ribbon_model");
            while ($row = $result->fetch_assoc()) {
                $items[] = $row['ribbon_model'];
            }
            
            sort($items);
            $results = array_unique($items);
            break;
            
        case 'lots':
            // Get unique LOT numbers from all tables
            $lots = [];
            
            // From papers_receiving
            $result = $conn->query("SELECT DISTINCT lot FROM papers_receiving WHERE lot IS NOT NULL AND lot != '' AND lot != '0' ORDER BY lot DESC LIMIT 100");
            while ($row = $result->fetch_assoc()) {
                $lots[] = $row['lot'];
            }
            
            // From toner_receiving
            $result = $conn->query("SELECT DISTINCT lot FROM toner_receiving WHERE lot IS NOT NULL AND lot != '' AND lot != '0' ORDER BY lot DESC LIMIT 100");
            while ($row = $result->fetch_assoc()) {
                if (!in_array($row['lot'], $lots)) {
                    $lots[] = $row['lot'];
                }
            }
            
            // From ribbons_receiving
            $result = $conn->query("SELECT DISTINCT lot FROM ribbons_receiving WHERE lot IS NOT NULL AND lot != '' AND lot != '0' ORDER BY lot DESC LIMIT 100");
            while ($row = $result->fetch_assoc()) {
                if (!in_array($row['lot'], $lots)) {
                    $lots[] = $row['lot'];
                }
            }
            
            rsort($lots);
            $results = $lots;
            break;
            
        default:
            $results = [];
    }
    
} catch (Exception $e) {
    error_log('Error in get_filter_options.php: ' . $e->getMessage());
    $results = [];
}

echo json_encode($results);
