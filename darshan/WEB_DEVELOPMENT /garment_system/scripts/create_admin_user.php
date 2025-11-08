<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app_config.php';

try {
    $db = getDbConnection();
    // Check if admin exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->execute(['admin', 'admin@garment.com']);
    $user = $stmt->fetch();
    if ($user) {
        echo "Admin user already exists (ID: " . $user['user_id'] . ")\n";
        exit;
    }

    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@garment.com', $hash, ROLE_ADMIN, 1, date('Y-m-d H:i:s')]);

    echo "Admin user created with username 'admin' and password 'admin123'\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>