<?php
/**
 * Simplified Session Management - Admin Only Authentication
 */

// Include configuration BEFORE starting session
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/database.php';

// Start session if not already started (configuration is now loaded)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['username']);
}

/**
 * Simplified permission check - always true for logged in users
 */
if (!function_exists('hasPermission')) {
    function hasPermission($userRole = null, $module = null, $action = null) {
        return isLoggedIn(); // Single user has full access to everything
    }
}

/**
 * Check if session is valid (not expired)
 */
function isSessionValid() {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    // Check if session has expired
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn() || !isSessionValid()) {
        // Destroy session
        session_destroy();
        
        // Redirect to login
        $redirect_path = dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../';
        header('Location: ' . $redirect_path . 'auth/login.php');
        exit();
    }
}

/**
 * Simplified permission check - admin user has all permissions
 */
if (!function_exists('hasPermissionSimplified')) {
    function hasPermissionSimplified($module, $action) {
        return isLoggedIn(); // All logged in users (admin) have full access
    }
}

/**
 * Require permission for a specific module/action.
 * Simplified for single user system - just check if logged in
 */
function requirePermission($module = null, $action = null) {
    if (!isLoggedIn()) {
        $redirect_path = dirname($_SERVER['PHP_SELF']) == '/' ? '' : '../';
        header('Location: ' . $redirect_path . 'auth/login.php');
        exit();
    }
}/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? $_SESSION['username']
    ];
}

/**
 * Sanitize input for security
 */
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Log user activity for audit
 */
function logActivity($table_name, $record_id, $action, $old_values = null, $new_values = null) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("
            INSERT INTO activity_log (user_id, table_name, record_id, action, old_data, new_data, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $table_name,
            $record_id,
            $action,
            $old_values ? json_encode($old_values) : null,
            $new_values ? json_encode($new_values) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Auto-check authentication for protected pages (exclude login/logout pages)
$current_page = basename($_SERVER['PHP_SELF']);
$auth_pages = ['login.php', 'logout.php', 'authenticate.php'];

if (!in_array($current_page, $auth_pages)) {
    requireLogin();
}
?>
