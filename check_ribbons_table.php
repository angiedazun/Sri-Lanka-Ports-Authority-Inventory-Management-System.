<?php
require_once 'includes/db.php';

echo "Ribbons Master Table Structure:\n";
echo "================================\n\n";

$result = $conn->query('DESCRIBE ribbons_master');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n\nSample Data:\n";
echo "=============\n\n";

$result = $conn->query('SELECT * FROM ribbons_master LIMIT 5');
if ($result) {
    while($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
