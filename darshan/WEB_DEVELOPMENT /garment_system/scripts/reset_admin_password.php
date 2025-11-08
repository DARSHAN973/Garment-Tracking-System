<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDbConnection();
    $newPassword = 'admin123';
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);

    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $stmt->execute([$hash, 'admin']);

    if ($stmt->rowCount() > 0) {
        echo "SUCCESS: admin password updated to 'admin123'\n";
    } else {
        echo "WARNING: No user 'admin' updated. Check if user exists.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>