<?php
require_once '../utils/Database.php';

try {
    $db = new DatabaseHelper();
    
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
            $db->query("INSERT IGNORE INTO thread_types (name, code, description, base_consumption_factor) VALUES (?, ?, ?, ?)", $thread);
        } catch (Exception $e) {
            echo "  Error inserting thread type: " . $e->getMessage() . "\n";
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
            $db->query("INSERT IGNORE INTO thread_colors (color_name, color_code, hex_color, pantone_code, cost_per_meter) VALUES (?, ?, ?, ?, ?)", $color);
        } catch (Exception $e) {
            echo "  Error inserting thread color: " . $e->getMessage() . "\n";
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
            $db->query("INSERT IGNORE INTO consumption_factors (factor_name, factor_type, multiplier, description) VALUES (?, ?, ?, ?)", $factor);
        } catch (Exception $e) {
            echo "  Error inserting factor: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Sample master data inserted successfully!\n";
    
    // Verify data insertion
    $threadTypeCount = $db->queryOne("SELECT COUNT(*) as count FROM thread_types");
    $threadColorCount = $db->queryOne("SELECT COUNT(*) as count FROM thread_colors");
    $factorCount = $db->queryOne("SELECT COUNT(*) as count FROM consumption_factors");
    
    echo "Verification:\n";
    echo "- Thread Types: " . ($threadTypeCount['count'] ?? 0) . " records\n";
    echo "- Thread Colors: " . ($threadColorCount['count'] ?? 0) . " records\n";
    echo "- Consumption Factors: " . ($factorCount['count'] ?? 0) . " records\n";
    
} catch (Exception $e) {
    echo "❌ Sample data insertion failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>