<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT user_id, username, email, role, is_active FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($users)) {
        echo "No users found\n";
    } else {
        foreach ($users as $u) {
            echo htmlspecialchars($u['user_id']) . ' | ' . htmlspecialchars($u['username']) . ' | ' . htmlspecialchars($u['email']) . ' | ' . htmlspecialchars($u['role']) . ' | ' . htmlspecialchars($u['is_active']) . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>