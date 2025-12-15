<?php
require_once 'includes/db.php';

// Check if user is logged in, if not redirect to login
if (!is_logged_in()) {
    header("Location: auth/login.php");
    exit();
}

// If logged in, redirect to dashboard
header("Location: pages/dashboard.php");
exit();
?>