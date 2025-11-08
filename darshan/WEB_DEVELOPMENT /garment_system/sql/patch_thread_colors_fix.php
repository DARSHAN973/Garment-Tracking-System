<?php
/**
 * Patch Migration: Add missing thread_type_id to thread_colors table
 * This fixes a critical bug where thread_colors table was missing foreign key to thread_types
 */

require_once '../utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "🔧 PATCH MIGRATION: Adding missing thread_type_id column to thread_colors table\n\n";
    
    // 1. Check if column already exists
    $result = $db->query("SHOW COLUMNS FROM thread_colors LIKE 'thread_type_id'");
    
    if (count($result) == 0) {
        echo "1. Adding thread_type_id column to thread_colors table...\n";
        
        // Add the missing column after thread_color_id
        $db->query("
            ALTER TABLE thread_colors 
            ADD COLUMN thread_type_id INT NOT NULL DEFAULT 1 
            AFTER thread_color_id
        ");
        
        // Add foreign key constraint
        $db->query("
            ALTER TABLE thread_colors 
            ADD CONSTRAINT fk_thread_colors_type 
            FOREIGN KEY (thread_type_id) REFERENCES thread_types(thread_type_id) 
            ON DELETE CASCADE
        ");
        
        // Add index
        $db->query("
            ALTER TABLE thread_colors 
            ADD INDEX idx_thread_type (thread_type_id)
        ");
        
        echo "   ✅ Added thread_type_id column with foreign key constraint\n";
        
        // 2. Update existing thread colors with appropriate thread types
        echo "2. Updating existing thread colors with thread types...\n";
        
        // Get thread types
        $threadTypes = $db->query("SELECT thread_type_id, name, code FROM thread_types ORDER BY thread_type_id");
        
        if (!empty($threadTypes)) {
            // Update colors based on color names/codes to assign appropriate thread types
            $colorMappings = [
                'White' => 1,      // Usually Polyester Core Spun
                'Black' => 1,      // Usually Polyester Core Spun
                'Navy' => 1,       // Usually Polyester Core Spun
                'Gray' => 1,       // Usually Polyester Core Spun
                'Red' => 2,        // Usually 100% Polyester
                'Blue' => 2,       // Usually 100% Polyester
                'Green' => 2,      // Usually 100% Polyester
                'Yellow' => 2,     // Usually 100% Polyester
                'Orange' => 2,     // Usually 100% Polyester
                'Purple' => 2      // Usually 100% Polyester
            ];
            
            foreach ($colorMappings as $colorName => $threadTypeId) {
                $db->query("
                    UPDATE thread_colors 
                    SET thread_type_id = ? 
                    WHERE color_name LIKE ?
                ", [$threadTypeId, '%' . $colorName . '%']);
            }
            
            echo "   ✅ Updated thread colors with appropriate thread types\n";
        }
        
        echo "\n✅ PATCH MIGRATION COMPLETED SUCCESSFULLY!\n";
        echo "   - Added missing thread_type_id column\n";
        echo "   - Added foreign key constraint to thread_types\n";
        echo "   - Updated existing records with appropriate thread types\n\n";
        
        // Verify the fix
        $result = $db->query("DESCRIBE thread_colors");
        echo "Updated thread_colors table structure:\n";
        foreach ($result as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        
    } else {
        echo "✅ thread_type_id column already exists in thread_colors table.\n";
    }
    
} catch (Exception $e) {
    echo "❌ PATCH MIGRATION FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>