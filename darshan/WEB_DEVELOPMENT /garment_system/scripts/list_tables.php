<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = getDbConnection();
    $stmt = $db->query("SHOW TABLES");
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
    if (empty($rows)) {
        echo "No tables found\n";
    } else {
        foreach ($rows as $r) echo $r[0] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>