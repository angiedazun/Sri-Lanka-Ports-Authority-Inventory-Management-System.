<?php
// Load autoloader and configuration (includes config.php with all constants)
require_once __DIR__ . '/autoload.php';

// Get database connection using Singleton pattern
$db = Database::getInstance();
$conn = $db->getConnection();

// Legacy compatibility - keeping variable name $conn for existing code
// Gradually migrate to using Database::getInstance()->getConnection()

// Note: Ribbon tables already exist in the database with proper structure
// No need to create them again

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to require login
function require_login() {
    if (!is_logged_in()) {
        header("Location: ../auth/login.php");
        exit();
    }
}
?>