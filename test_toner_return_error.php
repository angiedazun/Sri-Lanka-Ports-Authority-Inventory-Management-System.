<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Toner Return Debug</h2>";

require_once 'includes/db.php';

// Check table structure
$result = $conn->query("DESCRIBE toner_return");
echo "<h3>Table Structure:</h3>";
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test query
$testQuery = "SELECT * FROM toner_return LIMIT 1";
$testResult = $conn->query($testQuery);
if ($testResult) {
    echo "<p>✓ Can read from toner_return table</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

echo "<p><a href='pages/toner_return.php'>Try Toner Return Page</a></p>";
?>
