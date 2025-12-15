<?php
require_once 'includes/db.php';

echo "Database connection: OK\n";
echo "Tables:\n";

$tables = $conn->query("SHOW TABLES");
while($row = $tables->fetch_array()) {
    echo "- " . $row[0] . "\n";
}

// Check users table
$users = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $users->fetch_assoc()['count'];
echo "\nUsers: $user_count\n";

// Check toner tables
$toner = $conn->query("SELECT COUNT(*) as count FROM toner_master");
$toner_count = $toner->fetch_assoc()['count'];
echo "Toner items: $toner_count\n";

// Check paper tables
$papers = $conn->query("SELECT COUNT(*) as count FROM papers_master");
$papers_count = $papers->fetch_assoc()['count'];
echo "Paper items: $papers_count\n";

// Check ribbon tables
$ribbons = $conn->query("SELECT COUNT(*) as count FROM ribbons_master");
$ribbons_count = $ribbons->fetch_assoc()['count'];
echo "Ribbon items: $ribbons_count\n";

echo "\nAll checks passed!\n";
?>
