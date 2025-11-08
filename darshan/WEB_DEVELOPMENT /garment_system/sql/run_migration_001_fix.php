<?php
/**
 * Migration Script: Fix and enhance GSD elements table structure
 * This script aligns the table with schema.sql and adds missing time columns
 */

require_once 'utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "Starting migration: Fix and enhance GSD elements table...\n";
    
    // First, let's standardize the gsd_elements table structure to match schema.sql
    echo "1. Updating table structure to match schema.sql...\n";
    
    // Drop the table and recreate it with the correct structure
    $dropSql = "DROP TABLE IF EXISTS gsd_elements_backup";
    $db->query($dropSql);
    
    // Create backup
    $backupSql = "CREATE TABLE gsd_elements_backup AS SELECT * FROM gsd_elements";
    $db->query($backupSql);
    echo "✓ Created backup of existing data\n";
    
    // Drop and recreate table with correct structure
    $dropOriginalSql = "DROP TABLE IF EXISTS gsd_elements";
    $db->query($dropOriginalSql);
    
    // Create table with enhanced structure including all client-required fields
    $createSql = "CREATE TABLE gsd_elements (
        element_id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE,
        category VARCHAR(50),
        description VARCHAR(255),
        std_time_sec DECIMAL(8,3) NOT NULL DEFAULT 0,
        cond_len_5_sec DECIMAL(8,3) DEFAULT 0,
        cond_len_15_sec DECIMAL(8,3) DEFAULT 0,
        cond_len_30_sec DECIMAL(8,3) DEFAULT 0,
        cond_len_45_sec DECIMAL(8,3) DEFAULT 0,
        cond_len_80_sec DECIMAL(8,3) DEFAULT 0,
        short_time_sec DECIMAL(8,3) DEFAULT 0,
        long_time_sec DECIMAL(8,3) DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        INDEX idx_code (code),
        INDEX idx_category (category),
        INDEX idx_is_active (is_active)
    )";
    
    $db->query($createSql);
    echo "✓ Recreated gsd_elements table with enhanced structure\n";
    
    // Migrate data from backup, mapping old fields to new structure
    $migrateSql = "INSERT INTO gsd_elements (
        code, 
        category, 
        description, 
        std_time_sec,
        is_active,
        created_at,
        created_by
    ) 
    SELECT 
        code,
        category,
        description,
        COALESCE(standard_time * 1000, 0) as std_time_sec, -- Convert from minutes to seconds if needed
        is_active,
        created_at,
        created_by
    FROM gsd_elements_backup";
    
    $db->query($migrateSql);
    echo "✓ Migrated existing data to new structure\n";
    
    // Verify the migration
    $countOriginal = $db->queryOne("SELECT COUNT(*) as count FROM gsd_elements_backup");
    $countNew = $db->queryOne("SELECT COUNT(*) as count FROM gsd_elements");
    
    echo "Data verification: Original={$countOriginal['count']}, Migrated={$countNew['count']}\n";
    
    if ($countOriginal['count'] == $countNew['count']) {
        echo "✓ Data migration verified successfully\n";
        
        // Clean up backup table
        $db->query("DROP TABLE gsd_elements_backup");
        echo "✓ Cleaned up backup table\n";
    } else {
        echo "⚠ Warning: Data count mismatch! Keeping backup table for investigation.\n";
    }
    
    echo "Migration completed successfully!\n";
    echo "GSD Elements table now includes: std_time_sec, cond_len_5_sec, cond_len_15_sec, cond_len_30_sec, cond_len_45_sec, cond_len_80_sec, short_time_sec, long_time_sec\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "Backup table 'gsd_elements_backup' preserved for recovery.\n";
    exit(1);
}
?>