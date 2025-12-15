<?php
require_once '../includes/db.php';
require_login();

// Get filter parameters
$from_date = isset($_GET['from_date']) && !empty($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) && !empty($_GET['to_date']) ? $_GET['to_date'] : null;
$filter_month = isset($_GET['filter_month']) && !empty($_GET['filter_month']) ? $_GET['filter_month'] : null;
$filter_year = isset($_GET['filter_year']) && !empty($_GET['filter_year']) ? $_GET['filter_year'] : null;
$include_zero_stock = isset($_GET['include_zero_stock']) ? (bool)$_GET['include_zero_stock'] : false;
$include_summary = isset($_GET['include_summary']) ? (bool)$_GET['include_summary'] : true;

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = "";

if ($from_date) {
    $where_conditions[] = "purchase_date >= ?";
    $params[] = $from_date;
    $types .= "s";
}

if ($to_date) {
    $where_conditions[] = "purchase_date <= ?";
    $params[] = $to_date;
    $types .= "s";
}

if ($filter_month) {
    $where_conditions[] = "MONTH(purchase_date) = ?";
    $params[] = $filter_month;
    $types .= "i";
}

if ($filter_year) {
    $where_conditions[] = "YEAR(purchase_date) = ?";
    $params[] = $filter_year;
    $types .= "i";
}

if (!$include_zero_stock) {
    $where_conditions[] = "(jct_stock > 0 OR uct_stock > 0)";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get filtered toner data
$sql = "SELECT * FROM toner_master $where_clause ORDER BY toner_model";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$toners = [];
while ($row = $result->fetch_assoc()) {
    $toners[] = $row;
}

// Calculate statistics for filtered data
$total_toners = count($toners);
$total_jct_stock = array_sum(array_column($toners, 'jct_stock'));
$total_uct_stock = array_sum(array_column($toners, 'uct_stock'));
$total_stock = $total_jct_stock + $total_uct_stock;
$low_stock = count(array_filter($toners, function($t) { 
    $total_stock = $t['jct_stock'] + $t['uct_stock'];
    return $total_stock <= $t['reorder_level'] && $total_stock > 0; 
}));
$out_of_stock = count(array_filter($toners, function($t) { 
    return ($t['jct_stock'] + $t['uct_stock']) == 0; 
}));

// Format filter description
$filter_description = [];
if ($from_date) $filter_description[] = "From: " . date('M j, Y', strtotime($from_date));
if ($to_date) $filter_description[] = "To: " . date('M j, Y', strtotime($to_date));
if ($filter_month) $filter_description[] = "Month: " . date('F', mktime(0, 0, 0, $filter_month, 1));
if ($filter_year) $filter_description[] = "Year: " . $filter_year;

$filter_text = !empty($filter_description) ? implode(', ', $filter_description) : 'All Records';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toner Master Report - SLPA System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }
        
        .print-header {
            text-align: center;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .print-header h1 {
            color: #667eea;
            margin: 0;
            font-size: 28px;
        }
        
        .print-header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .no-print {
            margin-bottom: 20px;
            padding: 15px;
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 4px;
            text-align: center;
        }
        
        .summary-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .summary-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        
        .summary-item h3 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #667eea;
        }
        
        .summary-item p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        
        .toner-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }
        
        .toner-table th,
        .toner-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        
        .toner-table th {
            background-color: #667eea;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        
        .toner-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .stock-good { color: #28a745; font-weight: bold; }
        .stock-low { color: #ffc107; font-weight: bold; }
        .stock-out { color: #dc3545; font-weight: bold; }
        
        .color-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .color-black { background: #212529; color: white; }
        .color-cyan { background: #17a2b8; color: white; }
        .color-magenta { background: #e83e8c; color: white; }
        .color-yellow { background: #ffc107; color: #212529; }
        .color-tri-color { background: linear-gradient(45deg, #17a2b8, #e83e8c, #ffc107); color: white; }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #e0e0e0;
            padding-top: 15px;
        }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .print-header { page-break-after: avoid; }
            .summary-section { page-break-after: avoid; }
            .toner-table { page-break-inside: avoid; }
            .toner-table thead { display: table-header-group; }
        }
        
        @page {
            margin: 1cm;
            size: A4;
        }
    </style>
    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Close window after printing (for popups)
        window.addEventListener('afterprint', function() {
            if (window.opener) {
                window.close();
            }
        });
    </script>
</head>
<body>
    <div class="no-print">
        <p><strong>Print Instructions:</strong> Use Ctrl+P (Windows) or Cmd+P (Mac) to print this report. Choose "Save as PDF" to save as PDF file.</p>
    </div>
    
    <div class="print-header">
        <h1>SLPA Toner Master Report</h1>
        <p><strong>Generated on:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
        <p><strong>Filter Applied:</strong> <?php echo $filter_text; ?></p>
        <?php if (!$include_zero_stock): ?>
        <p><em>Note: Toners with zero stock are excluded from this report</em></p>
        <?php endif; ?>
    </div>

    <?php if ($include_summary): ?>
    <div class="summary-section">
        <h2 style="margin: 0 0 15px 0; color: #667eea;">Summary Statistics</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3><?php echo $total_toners; ?></h3>
                <p>Total Toners</p>
            </div>
            <div class="summary-item">
                <h3><?php echo $total_jct_stock; ?></h3>
                <p>JCT Stock</p>
            </div>
            <div class="summary-item">
                <h3><?php echo $total_uct_stock; ?></h3>
                <p>UCT Stock</p>
            </div>
            <div class="summary-item">
                <h3><?php echo $low_stock; ?></h3>
                <p>Low Stock Items</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-section">
        <h2 style="color: #667eea; margin-bottom: 15px;">Toner Inventory Details</h2>
        
        <?php if (empty($toners)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <p>No toner records found matching the selected criteria.</p>
            </div>
        <?php else: ?>
            <table class="toner-table">
                <thead>
                    <tr>
                        <th>Toner Model</th>
                        <th>Compatible Printers</th>
                        <th>Color</th>
                        <th>JCT Stock</th>
                        <th>UCT Stock</th>
                        <th>Total Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($toners as $toner): 
                        $total_stock = $toner['jct_stock'] + $toner['uct_stock'];
                        $status_class = '';
                        $status_text = '';
                        
                        if ($total_stock == 0) {
                            $status_class = 'stock-out';
                            $status_text = 'Out of Stock';
                        } elseif ($total_stock <= $toner['reorder_level']) {
                            $status_class = 'stock-low';
                            $status_text = 'Low Stock';
                        } else {
                            $status_class = 'stock-good';
                            $status_text = 'In Stock';
                        }
                        
                        $color_class = 'color-' . strtolower(str_replace('-', '-', $toner['color']));
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($toner['toner_model']); ?></strong></td>
                        <td style="max-width: 200px; word-wrap: break-word;">
                            <?php echo htmlspecialchars($toner['compatible_printers']); ?>
                        </td>
                        <td>
                            <span class="color-badge <?php echo $color_class; ?>">
                                <?php echo htmlspecialchars($toner['color']); ?>
                            </span>
                        </td>
                        <td style="text-align: center;"><?php echo $toner['jct_stock']; ?></td>
                        <td style="text-align: center;"><?php echo $toner['uct_stock']; ?></td>
                        <td style="text-align: center;"><strong><?php echo $total_stock; ?></strong></td>
                        <td style="text-align: center;"><?php echo $toner['reorder_level']; ?></td>
                        <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                        <td style="text-align: center;"><?php echo date('M j, Y', strtotime($toner['purchase_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p><strong>SLPA Toner Management System</strong></p>
        <p>Report generated on <?php echo date('F j, Y \a\t g:i A'); ?> | Total Records: <?php echo $total_toners; ?></p>
    </div>
</body>
</html>

<?php
if (isset($stmt)) $stmt->close();
$conn->close();
?>