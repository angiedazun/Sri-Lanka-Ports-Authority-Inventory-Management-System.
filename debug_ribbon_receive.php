<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

echo "<h2>Ribbon Receiving Debug</h2>";

// Check ribbons_master
echo "<h3>1. Ribbons Master Table</h3>";
$result = $conn->query("SELECT * FROM ribbons_master");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Found " . $result->num_rows . " ribbons in master table</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Model</th><th>JCT Stock</th><th>UCT Stock</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['ribbon_id'] . "</td>";
        echo "<td>" . $row['ribbon_model'] . "</td>";
        echo "<td>" . $row['jct_stock'] . "</td>";
        echo "<td>" . $row['uct_stock'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ No ribbons found in master table!</p>";
    echo "<p><strong>This is the problem!</strong> You need to add ribbons to the master table first.</p>";
    
    // Create sample ribbon
    echo "<h3>Creating Sample Ribbon...</h3>";
    $sql = "INSERT INTO ribbons_master (ribbon_model, compatible_printers, reorder_level, jct_stock, uct_stock, purchase_date) 
            VALUES ('Sample Ribbon Model 1', 'HP LaserJet', 10, 0, 0, CURDATE())";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Sample ribbon created successfully!</p>";
        echo "<p>Ribbon ID: " . $conn->insert_id . "</p>";
        echo "<p>Now try receiving again.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
}

// Check receiving table structure
echo "<h3>2. Receiving Table Structure</h3>";
$result = $conn->query("DESCRIBE ribbons_receiving");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test form data
echo "<h3>3. Test Form Submission</h3>";
echo "<p>This would simulate a form POST:</p>";
echo "<pre>";
echo "ribbon_id: 1\n";
echo "ribbon_model: 'Sample Ribbon Model 1'\n";
echo "lot: '2025/LOT 1'\n";
echo "jct_quantity: 10\n";
echo "uct_quantity: 5\n";
echo "unit_price: 150.00\n";
echo "receive_date: " . date('Y-m-d') . "\n";
echo "</pre>";

echo "<h3>4. Next Steps</h3>";
echo "<ul>";
echo "<li><a href='pages/ribbons_master.php'>Go to Ribbons Master</a> - Add ribbons here first</li>";
echo "<li><a href='pages/ribbons_receiving.php'>Go to Ribbons Receiving</a> - Then receive them</li>";
echo "</ul>";
?>
