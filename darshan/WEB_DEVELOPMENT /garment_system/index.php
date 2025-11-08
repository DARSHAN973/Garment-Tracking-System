<?php
/**
 * Main application entry point
 * Redirects to login if not authenticated, otherwise to dashboard
 */

session_start();

// Include configuration
require_once 'config/app_config.php';

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.php');
} else {
    // User not logged in, redirect to login
    header('Location: auth/login.php');
}

exit();
?>