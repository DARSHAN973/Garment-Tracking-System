<?php
require_once '../config/app_config.php';
require_once '../config/database.php';
session_start();

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        $db = getDbConnection();
        
        // Log logout activity
        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, table_name, record_id, action, created_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            'users',
            $_SESSION['user_id'],
            'LOGOUT',
            getCurrentUTC()
        ]);
        
        // Remove session record
        $session_id = session_id();
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_id = ?");
        $stmt->execute([$session_id]);
        
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Clear remember me cookie
setcookie('remember_login', '', time() - 3600, '/');

// Destroy session
session_unset();
session_destroy();

// Start new session for flash message
session_start();
$_SESSION['logout_message'] = 'You have been successfully logged out.';

// Redirect to login
header('Location: login.php');
exit();
?>