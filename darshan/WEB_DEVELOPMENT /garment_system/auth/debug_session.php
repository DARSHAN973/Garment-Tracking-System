<?php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/app_config.php';

header('Content-Type: text/plain; charset=utf-8');

// Start session if not already
if (session_status() === PHP_SESSION_NONE) session_start();

echo "== SESSION DUMP ==\n";
print_r($_SESSION);

echo "\n== getCurrentUser() ==\n";
print_r(getCurrentUser());

echo "\n== current role helper (if exists) ==\n";
if (function_exists('currentRole')) {
    var_dump(currentRole());
} else {
    echo "currentRole() not defined\n";
}

echo "\n== Permission checks ==\n";
$role = $_SESSION['role'] ?? null;
var_dump(['role_from_session' => $role]);
var_dump(['hasPermission_masters_read' => hasPermission($role, 'masters', 'read')]);
var_dump(['hasPermission_masters_write' => hasPermission($role, 'masters', 'write')]);

echo "\n== End ==\n";
?>