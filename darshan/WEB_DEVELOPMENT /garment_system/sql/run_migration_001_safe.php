<?php
/**
 * Migration Script: Safely add missing GSD element columns
 * This script adds missing columns without dropping the table (safer approach)
 */

require_once 'utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "Starting migration: Safely add missing GSD element columns...\n";
    
    // Get current table structure
    $columns = $db->query('DESCRIBE gsd_elements');
    $existingColumns = array_column($columns, 'Field');
    
    echo "Current columns: " . implode(', ', $existingColumns) . "\n";
    
    // Define the columns we need to add
    $newColumns = [
        'std_time_sec' => 'DECIMAL(8,3) DEFAULT 0',
        'cond_len_5_sec' => 'DECIMAL(8,3) DEFAULT 0', 
        'cond_len_15_sec' => 'DECIMAL(8,3) DEFAULT 0',
        'cond_len_30_sec' => 'DECIMAL(8,3) DEFAULT 0',
        'cond_len_45_sec' => 'DECIMAL(8,3) DEFAULT 0',
        'cond_len_80_sec' => 'DECIMAL(8,3) DEFAULT 0',
        'short_time_sec' => 'DECIMAL(8,3) DEFAULT 0',
        'long_time_sec' => 'DECIMAL(8,3) DEFAULT 0'
    ];
    
    // Add missing columns one by one
    foreach ($newColumns as $columnName => $definition) {
        if (!in_array($columnName, $existingColumns)) {
            $alterSql = "ALTER TABLE gsd_elements ADD COLUMN {$columnName} {$definition}";
            try {
                $db->query($alterSql);
                echo "✓ Added column: {$columnName}\n";
            } catch (Exception $e) {
                echo "⚠ Failed to add {$columnName}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "- Column {$columnName} already exists\n";
        }
    }
    
    // Convert standard_time to std_time_sec if both exist
    if (in_array('standard_time', $existingColumns) && in_array('std_time_sec', $existingColumns)) {
        $updateSql = "UPDATE gsd_elements SET std_time_sec = COALESCE(standard_time * 1000, 0) WHERE std_time_sec = 0";
        $db->query($updateSql);
        echo "✓ Converted standard_time to seconds in std_time_sec\n";
    }
    
    // Verify final structure
    $finalColumns = $db->query('DESCRIBE gsd_elements');
    echo "\nFinal table structure:\n";
    foreach ($finalColumns as $row) {
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>