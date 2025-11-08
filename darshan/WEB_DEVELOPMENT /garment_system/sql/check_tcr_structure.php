<?php
require_once '../utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "=== Current TCR Table Structure ===\n\n";
    
    $tables = ['tcr', 'tcr_details'];
    
    foreach ($tables as $table) {
        echo "Table: {$table}\n";
        try {
            $columns = $db->query("DESCRIBE {$table}");
            foreach ($columns as $column) {
                echo "  {$column['Field']} - {$column['Type']}\n";
            }
        } catch (Exception $e) {
            echo "  Table does not exist or error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    // Check for related tables
    echo "=== Related Tables ===\n\n";
    $relatedTables = ['thread_types', 'thread_colors', 'thread_consumption_factors'];
    
    foreach ($relatedTables as $table) {
        echo "Table: {$table}\n";
        try {
            $columns = $db->query("DESCRIBE {$table}");
            foreach ($columns as $column) {
                echo "  {$column['Field']} - {$column['Type']}\n";
            }
        } catch (Exception $e) {
            echo "  Table does not exist: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>