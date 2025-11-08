<?php
require_once '../config/app_config.php';
require_once '../config/database.php';
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

try {
    $db = getDbConnection();
    
    // Check for user by email or username
    $stmt = $db->prepare("
        SELECT user_id, username, email, password_hash, role, is_active 
        FROM users 
        WHERE (email = ? OR username = ?) AND is_active = 1
    ");
    $stmt->execute([$login_id, $login_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['login_error'] = 'Invalid credentials. Please try again.';
        header('Location: login.php');
        exit();
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        $_SESSION['login_error'] = 'Invalid credentials. Please try again.';
        header('Location: login.php');
        exit();
    }
    
    // Login successful - create session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = ? WHERE user_id = ?");
    $stmt->execute([getCurrentUTC(), $user['user_id']]);
    
    // Create session record for security
    $session_id = session_id();
    $stmt = $db->prepare("
        INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, expires_at) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        ip_address = VALUES(ip_address), 
        user_agent = VALUES(user_agent), 
        expires_at = VALUES(expires_at)
    ");
    $stmt->execute([
        $session_id,
        $user['user_id'],
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        date('Y-m-d H:i:s', time() + SESSION_TIMEOUT)
    ]);
    
    // Set remember me cookie if requested
    if ($remember_me) {
        setcookie('remember_login', $user['username'], time() + (86400 * 30), '/'); // 30 days
    }
    
    // Log successful login
    logActivity('users', $user['user_id'], 'LOGIN');
    
    // Redirect to dashboard
    header('Location: ../dashboard.php');
    exit();
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['login_error'] = 'System error. Please try again later.';
    header('Location: login.php');
    exit();
}

/**
 * Log user activity for audit
 */
function logActivity($table_name, $record_id, $action) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, table_name, record_id, action, created_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $table_name,
            $record_id,
            $action,
            getCurrentUTC()
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>