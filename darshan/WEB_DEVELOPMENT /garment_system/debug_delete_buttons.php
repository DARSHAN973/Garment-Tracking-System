<?php
session_start();

// Show current session info for debugging
echo "<h2>🔍 DELETE BUTTON DEBUGGING</h2>";
echo "<hr>";

echo "<h3>1. SESSION INFO:</h3>";
if (isset($_SESSION)) {
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
    echo "Permissions: ";
    if (isset($_SESSION['permissions'])) {
        print_r($_SESSION['permissions']);
    } else {
        echo "Not set";
    }
} else {
    echo "No session found";
}

echo "<hr>";

echo "<h3>2. PERMISSION CHECK SIMULATION:</h3>";

// Simulate permission function if it exists
if (function_exists('hasPermission')) {
    $canDelete = hasPermission($_SESSION['role'] ?? 'guest', 'masters', 'delete');
    echo "Can Delete Masters: " . ($canDelete ? '✅ YES' : '❌ NO') . "<br>";
} else {
    echo "hasPermission function not loaded<br>";
    
    // Try to load it
    if (file_exists('../auth/session_check.php')) {
        require_once '../auth/session_check.php';
        if (function_exists('hasPermission')) {
            $canDelete = hasPermission($_SESSION['role'] ?? 'guest', 'masters', 'delete');
            echo "Can Delete Masters (after loading): " . ($canDelete ? '✅ YES' : '❌ NO') . "<br>";
        }
    }
}

echo "<hr>";

echo "<h3>3. RECOMMENDATIONS:</h3>";
echo "If delete buttons are not showing:<br>";
echo "1. ✅ Check you are logged in as admin/manager<br>";
echo "2. ✅ Verify your role has 'delete' permission<br>";
echo "3. ✅ Clear browser cache and refresh page<br>";
echo "4. ✅ Check browser console for JavaScript errors<br>";

echo "<hr>";
echo "<h3>4. DELETE BUTTON LOCATIONS:</h3>";
echo "• <strong>Operations:</strong> Right side of each row, red 'Delete' button<br>";
echo "• <strong>Machine Types:</strong> After 'Thread Factors' link<br>";
echo "• <strong>Styles:</strong> Bottom right of each card<br>";
echo "• <strong>GSD Elements:</strong> Right side of each row<br>";
echo "• <strong>Thread Factors:</strong> Right side of each row<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 20px; }
hr { margin: 20px 0; }
pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
</style>