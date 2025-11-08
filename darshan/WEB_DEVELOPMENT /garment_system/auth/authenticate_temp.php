<?php
/**
 * Simple File-Based Authentication (Temporary Solution)
 */
require_once '../config/app_config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$login_id = sanitizeInput($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']);

// Basic validation
if (empty($login_id) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter both email/username and password.';
    header('Location: login.php');
    exit();
}

// Hardcoded admin credentials (temporary solution)
$valid_users = [
    'admin' => [
        'password' => 'admin123',
        'user_id' => 1,
        'username' => 'admin',
        'email' => 'admin@garment.com',
        'full_name' => 'Administrator'
    ]
];

// Check credentials
if (isset($valid_users[$login_id]) && $valid_users[$login_id]['password'] === $password) {
    $user = $valid_users[$login_id];
    
    // Login successful - create session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['last_activity'] = time();
    
    // Set remember me cookie if requested
    if ($remember_me) {
        setcookie('remember_login', $user['username'], time() + (86400 * 30), '/');
    }
    
    // Redirect to dashboard
    header('Location: ../dashboard.php');
    exit();
    
} else {
    $_SESSION['login_error'] = 'Invalid credentials. Please try again.';
    header('Location: login.php');
    exit();
}
?>