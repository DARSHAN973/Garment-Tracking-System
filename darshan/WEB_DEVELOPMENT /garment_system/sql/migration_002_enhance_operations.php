<?php
/**
 * Migration Script: Enhance operations table with client requirements
 * This script adds the missing operation fields to match client's Excel format
 */

require_once 'utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "Starting migration: Enhance operations table...\n";
    
    // Get current table structure
    $columns = $db->query('DESCRIBE operations');
    $existingColumns = array_column($columns, 'Field');
    
    echo "Current columns: " . implode(', ', $existingColumns) . "\n";
    
    // Define the columns we need to add based on client requirements
    $newColumns = [
        'seam_length_cm' => 'DECIMAL(8,2) DEFAULT NULL COMMENT "Standard seam length in centimeters"',
        'seam_type' => 'VARCHAR(50) DEFAULT NULL COMMENT "Type of seam (SS, Overlock, Hemming, etc.)"', 
        'folder_attachment' => 'VARCHAR(100) DEFAULT NULL COMMENT "Folder or attachment used"',
        'presser_foot' => 'VARCHAR(50) DEFAULT NULL COMMENT "Presser foot type/number"',
        'needle_type' => 'VARCHAR(50) DEFAULT NULL COMMENT "Needle type and size"',
        'operator_grade' => 'CHAR(1) DEFAULT NULL COMMENT "Operator skill grade (A, B, C)"',
        'quality_parameters' => 'TEXT DEFAULT NULL COMMENT "Quality specifications and requirements"',
        'sketch_image' => 'VARCHAR(255) DEFAULT NULL COMMENT "Path to operation sketch/image"',
        'video_url' => 'VARCHAR(255) DEFAULT NULL COMMENT "Link to operation video"',
        'machine_speed' => 'INT DEFAULT NULL COMMENT "Recommended machine speed (RPM)"',
        'stitch_per_inch' => 'DECIMAL(4,1) DEFAULT NULL COMMENT "Stitches per inch setting"',
        'operation_cost' => 'DECIMAL(10,4) DEFAULT NULL COMMENT "Cost per operation"'
    ];
    
    // Add missing columns one by one
    foreach ($newColumns as $columnName => $definition) {
        if (!in_array($columnName, $existingColumns)) {
            $alterSql = "ALTER TABLE operations ADD COLUMN {$columnName} {$definition}";
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
    
    // Update the description field to be more flexible if it's too small
    try {
        $db->query("ALTER TABLE operations MODIFY COLUMN description TEXT");
        echo "✓ Updated description field to TEXT\n";
    } catch (Exception $e) {
        echo "⚠ Failed to update description field: " . $e->getMessage() . "\n";
    }
    
    // Verify final structure
    $finalColumns = $db->query('DESCRIBE operations');
    echo "\nFinal table structure:\n";
    foreach ($finalColumns as $row) {
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
    
    echo "\nMigration completed successfully!\n";
    echo "Operations table now supports client's detailed operation tracking requirements.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>