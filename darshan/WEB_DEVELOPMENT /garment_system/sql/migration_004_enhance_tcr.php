<?php
require_once '../utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "Starting TCR system enhancement migration...\n\n";
    
    // 1. Create thread_types table
    echo "1. Creating thread_types table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS thread_types (
            thread_type_id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            code VARCHAR(20),
            description TEXT,
            base_consumption_factor DECIMAL(6,4) NOT NULL DEFAULT 1.0000,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_active (is_active),
            UNIQUE KEY unique_code (code)
        ) ENGINE=InnoDB
    ");
    
    // 2. Create thread_colors table
    echo "2. Creating thread_colors table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS thread_colors (
            thread_color_id INT PRIMARY KEY AUTO_INCREMENT,
            color_name VARCHAR(50) NOT NULL,
            color_code VARCHAR(20),
            hex_color VARCHAR(7),
            pantone_code VARCHAR(20),
            cost_per_meter DECIMAL(8,4) DEFAULT 0.0000,
            supplier VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_color_name (color_name),
            INDEX idx_active (is_active),
            UNIQUE KEY unique_color_code (color_code)
        ) ENGINE=InnoDB
    ");
    
    // 3. Create consumption_factors table
    echo "3. Creating consumption_factors table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS consumption_factors (
            factor_id INT PRIMARY KEY AUTO_INCREMENT,
            factor_name VARCHAR(50) NOT NULL,
            factor_type ENUM('seam_type', 'fabric_weight', 'stitch_density', 'operation_type', 'custom') NOT NULL,
            multiplier DECIMAL(6,4) NOT NULL DEFAULT 1.0000,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_factor_type (factor_type),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB
    ");
    
    // 4. Create enhanced tcr_details table
    echo "4. Creating tcr_details table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS tcr_details (
            tcr_detail_id INT PRIMARY KEY AUTO_INCREMENT,
            tcr_id INT NOT NULL,
            operation_id INT NOT NULL,
            thread_type_id INT NOT NULL,
            thread_color_id INT NOT NULL,
            base_consumption DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
            seam_length_cm DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            stitch_per_inch DECIMAL(5,2) NOT NULL DEFAULT 12.00,
            thread_factor DECIMAL(6,4) NOT NULL DEFAULT 1.0000,
            wastage_percentage DECIMAL(5,2) NOT NULL DEFAULT 5.00,
            calculated_consumption DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
            final_consumption DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
            cost_per_unit DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
            notes TEXT,
            sequence_order INT NOT NULL DEFAULT 1,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tcr_id) REFERENCES tcr(tcr_id) ON DELETE CASCADE,
            FOREIGN KEY (operation_id) REFERENCES operations(operation_id) ON DELETE CASCADE,
            FOREIGN KEY (thread_type_id) REFERENCES thread_types(thread_type_id) ON DELETE CASCADE,
            FOREIGN KEY (thread_color_id) REFERENCES thread_colors(thread_color_id) ON DELETE CASCADE,
            INDEX idx_tcr (tcr_id),
            INDEX idx_operation (operation_id),
            INDEX idx_sequence (sequence_order),
            UNIQUE KEY unique_tcr_operation (tcr_id, operation_id, thread_type_id)
        ) ENGINE=InnoDB
    ");
    
    // 5. Create consumption_factor_assignments table
    echo "5. Creating consumption_factor_assignments table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS consumption_factor_assignments (
            assignment_id INT PRIMARY KEY AUTO_INCREMENT,
            tcr_detail_id INT NOT NULL,
            factor_id INT NOT NULL,
            applied_multiplier DECIMAL(6,4) NOT NULL DEFAULT 1.0000,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tcr_detail_id) REFERENCES tcr_details(tcr_detail_id) ON DELETE CASCADE,
            FOREIGN KEY (factor_id) REFERENCES consumption_factors(factor_id) ON DELETE CASCADE,
            INDEX idx_tcr_detail (tcr_detail_id),
            INDEX idx_factor (factor_id),
            UNIQUE KEY unique_detail_factor (tcr_detail_id, factor_id)
        ) ENGINE=InnoDB
    ");
    
    // 6. Create thread_cost_history table for price tracking
    echo "6. Creating thread_cost_history table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS thread_cost_history (
            cost_history_id INT PRIMARY KEY AUTO_INCREMENT,
            thread_type_id INT NOT NULL,
            thread_color_id INT NOT NULL,
            cost_per_meter DECIMAL(8,4) NOT NULL,
            effective_date DATE NOT NULL,
            supplier VARCHAR(100),
            notes TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (thread_type_id) REFERENCES thread_types(thread_type_id) ON DELETE CASCADE,
            FOREIGN KEY (thread_color_id) REFERENCES thread_colors(thread_color_id) ON DELETE CASCADE,
            INDEX idx_thread_type (thread_type_id),
            INDEX idx_thread_color (thread_color_id),
            INDEX idx_effective_date (effective_date)
        ) ENGINE=InnoDB
    ");
    
    echo "\n✅ TCR system enhancement completed successfully!\n";
    echo "Created tables:\n";
    echo "- thread_types: Master data for thread types (Polyester, Cotton, etc.)\n";
    echo "- thread_colors: Master data for thread colors with cost tracking\n";
    echo "- consumption_factors: Configurable factors affecting thread consumption\n";
    echo "- tcr_details: Enhanced thread consumption details with factor-based calculations\n";
    echo "- consumption_factor_assignments: Links consumption factors to TCR details\n";
    echo "- thread_cost_history: Historical cost tracking for pricing analysis\n\n";
    
    // Insert sample data
    echo "=== Inserting Sample Master Data ===\n\n";
    
    // Sample thread types
    echo "Inserting thread types...\n";
    $threadTypes = [
        ['Polyester Core Spun', 'PCS', 'High strength polyester thread with cotton wrap', 1.0000],
        ['100% Polyester', 'PE', 'Standard polyester thread for general use', 0.9500],
        ['Cotton Thread', 'CT', '100% cotton thread for natural fabrics', 1.1000],
        ['Nylon Thread', 'NY', 'High strength nylon for heavy duty applications', 0.8500],
        ['Elastic Thread', 'EL', 'Elastic thread for stretch operations', 1.2000],
        ['Metallic Thread', 'MT', 'Decorative metallic thread', 0.7500]
    ];
    
    foreach ($threadTypes as $thread) {
        try {
            $db->insert('thread_types', [
                'name' => $thread[0],
                'code' => $thread[1], 
                'description' => $thread[2],
                'base_consumption_factor' => $thread[3]
            ]);
        } catch (Exception $e) {
            // Ignore duplicate entries
        }
    }
    
    // Sample thread colors
    echo "Inserting thread colors...\n";
    $threadColors = [
        ['White', 'WHT', '#FFFFFF', 'Bright White', 0.0250],
        ['Black', 'BLK', '#000000', 'Jet Black', 0.0250],
        ['Navy Blue', 'NVY', '#000080', 'Navy 19-4052', 0.0280],
        ['Red', 'RED', '#FF0000', 'True Red 19-1664', 0.0300],
        ['Royal Blue', 'RBL', '#4169E1', 'Royal 19-3955', 0.0280],
        ['Yellow', 'YEL', '#FFFF00', 'Lemon Chrome 13-0859', 0.0320],
        ['Green', 'GRN', '#008000', 'Classic Green 17-6153', 0.0300],
        ['Gray', 'GRY', '#808080', 'Cool Gray 11', 0.0260],
        ['Brown', 'BRN', '#8B4513', 'Cognac 18-1142', 0.0290],
        ['Pink', 'PNK', '#FFC0CB', 'Rose Quartz 13-1520', 0.0310]
    ];
    
    foreach ($threadColors as $color) {
        try {
            $db->insert('thread_colors', [
                'color_name' => $color[0],
                'color_code' => $color[1],
                'hex_color' => $color[2],
                'pantone_code' => $color[3],
                'cost_per_meter' => $color[4]
            ]);
        } catch (Exception $e) {
            // Ignore duplicate entries
        }
    }
    
    // Sample consumption factors
    echo "Inserting consumption factors...\n";
    $factors = [
        ['Single Stitch Seam', 'seam_type', 1.0000, 'Basic single needle seam'],
        ['Double Stitch Seam', 'seam_type', 1.8000, 'Double needle parallel seam'],
        ['Overlock 3-Thread', 'seam_type', 2.2000, '3-thread overlock seam'],
        ['Overlock 4-Thread', 'seam_type', 2.8000, '4-thread overlock seam'],
        ['Flatlock Seam', 'seam_type', 1.6000, 'Flatlock seam construction'],
        ['Light Weight Fabric', 'fabric_weight', 0.9000, 'Fabrics under 150 GSM'],
        ['Medium Weight Fabric', 'fabric_weight', 1.0000, 'Fabrics 150-300 GSM'],
        ['Heavy Weight Fabric', 'fabric_weight', 1.2000, 'Fabrics over 300 GSM'],
        ['High Stitch Density', 'stitch_density', 1.3000, 'Over 14 SPI'],
        ['Low Stitch Density', 'stitch_density', 0.8000, 'Under 8 SPI'],
        ['Hemming Operation', 'operation_type', 1.1000, 'Hemming operations'],
        ['Topstitch Operation', 'operation_type', 1.0500, 'Decorative topstitching'],
        ['Bartack Operation', 'operation_type', 0.3000, 'Bartack reinforcement']
    ];
    
    foreach ($factors as $factor) {
        try {
            $db->insert('consumption_factors', [
                'factor_name' => $factor[0],
                'factor_type' => $factor[1],
                'multiplier' => $factor[2],
                'description' => $factor[3]
            ]);
        } catch (Exception $e) {
            // Ignore duplicate entries
        }
    }
    
    echo "\n✅ Sample master data inserted successfully!\n";
    
    // Show table structures
    echo "\n=== Enhanced TCR Table Structures ===\n\n";
    
    $tables = [
        'thread_types',
        'thread_colors', 
        'consumption_factors',
        'tcr_details',
        'consumption_factor_assignments',
        'thread_cost_history'
    ];
    
    foreach ($tables as $table) {
        echo "Table: {$table}\n";
        $columns = $db->query("DESCRIBE {$table}");
        foreach ($columns as $column) {
            echo "  {$column['Field']} - {$column['Type']}\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>