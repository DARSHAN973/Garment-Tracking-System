<?php
/**
 * Migration Script: Add missing GSD element time columns
 * This script adds the missing time columns to match client requirements
 */

require_once 'utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "Starting migration: Add missing GSD element columns...\n";
    
    // Add missing columns to gsd_elements table
    $alterSql = "ALTER TABLE gsd_elements 
        ADD COLUMN cond_len_45_sec DECIMAL(8,3) DEFAULT 0 AFTER cond_len_30_sec,
        ADD COLUMN cond_len_80_sec DECIMAL(8,3) DEFAULT 0 AFTER cond_len_45_sec,
        ADD COLUMN short_time_sec DECIMAL(8,3) DEFAULT 0 AFTER cond_len_80_sec,
        ADD COLUMN long_time_sec DECIMAL(8,3) DEFAULT 0 AFTER short_time_sec";
    
    $db->query($alterSql);
    echo "✓ Added missing columns to gsd_elements table\n";
    
    // Update existing records with default values
    $updateSql = "UPDATE gsd_elements 
        SET 
            cond_len_45_sec = 0,
            cond_len_80_sec = 0,
            short_time_sec = 0,
            long_time_sec = 0
        WHERE 
            cond_len_45_sec IS NULL 
            OR cond_len_80_sec IS NULL 
            OR short_time_sec IS NULL 
            OR long_time_sec IS NULL";
    
    $db->query($updateSql);
    echo "✓ Updated existing records with default values\n";
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>