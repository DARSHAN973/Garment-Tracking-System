<?php
/**
 * Single User Authentication System
 * Simplified login for single admin user
 */

require_once '../config/app_config.php';
require_once '../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$username = sanitizeInput($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter both username and password.';
    header('Location: login.php');
    exit();
}

try {
    $db = getDbConnection();
    
    // Get the single admin user
    $stmt = $db->prepare("SELECT user_id, username, email, password_hash, role FROM users WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['login_error'] = 'No active user found. Please contact administrator.';
        header('Location: login.php');
        exit();
    }
    
    // Check username and password
    if ($username !== $user['username'] || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_error'] = 'Invalid credentials. Please try again.';
        header('Location: login.php');
        exit();
    }
    
    // Login successful - create session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = ROLE_USER; // Single role for all users
    $_SESSION['last_activity'] = time();
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = ? WHERE user_id = ?");
    $stmt->execute([date('Y-m-d H:i:s'), $user['user_id']]);
    
    // Redirect to dashboard
    header('Location: ../dashboard.php');
    exit();
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['login_error'] = 'System error. Please try again later.';
    header('Location: login.php');
    exit();
}
?>