<?php
require_once '../includes/db.php';

// Logout user using Auth class
Auth::logout();

// Log the logout
Logger::info('User logged out via logout page');

// Redirect to login page with message
$_SESSION['message'] = 'You have been successfully logged out.';
$_SESSION['message_type'] = 'success';

Response::redirect('login.php');
?>