<?php
require_once 'utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "🔍 PHASE 1: MASTER DATA VERIFICATION\n";
    echo "==========================================\n\n";
    
    // 1. Machine Types Check
    echo "1. MACHINE TYPES:\n";
    $machineTypes = $db->getAll('machine_types', ['is_active' => 1]);
    echo "Count: " . count($machineTypes) . "\n";
    if ($machineTypes) {
        foreach ($machineTypes as $type) {
            echo "   - " . $type['name'] . " (ID: " . $type['machine_type_id'] . ")\n";
        }
    } else {
        echo "   ❌ No active machine types found!\n";
    }
    echo "\n";
    
    // 2. Thread Factors Check
    echo "2. THREAD FACTORS:\n";
    $threadFactors = $db->getAll('thread_factors', ['is_active' => 1]);
    echo "Count: " . count($threadFactors) . "\n";
    if ($threadFactors) {
        foreach ($threadFactors as $factor) {
            echo "   - Machine ID: " . $factor['machine_type_id'] . 
                 ", Factor: " . $factor['factor_per_cm'] . 
                 ", Needles: " . $factor['needle_count'] . "\n";
        }
    } else {
        echo "   ❌ No thread factors found! This will affect TCR calculations.\n";
    }
    echo "\n";
    
    // 3. Operations Check
    echo "3. OPERATIONS:\n";
    $operations = $db->getAll('operations', ['is_active' => 1]);
    echo "Count: " . count($operations) . "\n";
    if ($operations) {
        foreach ($operations as $op) {
            echo "   - " . $op['name'] . " (SMV: " . 
                 ($op['standard_smv'] ?? 'N/A') . ")\n";
        }
    } else {
        echo "   ❌ No active operations found!\n";
    }
    echo "\n";
    
    // 4. GSD Elements Check
    echo "4. GSD ELEMENTS:\n";
    $gsdElements = $db->getAll('gsd_elements', ['is_active' => 1]);
    echo "Count: " . count($gsdElements) . "\n";
    if ($gsdElements) {
        foreach ($gsdElements as $element) {
            echo "   - [" . $element['code'] . "] " . $element['description'] . 
                 " (Time: " . $element['standard_time'] . ")\n";
        }
    } else {
        echo "   ❌ No active GSD elements found!\n";
    }
    echo "\n";
    
    // 5. Styles Check
    echo "5. STYLES:\n";
    $styles = $db->getAll('styles', ['is_active' => 1]);
    echo "Count: " . count($styles) . "\n";
    if ($styles) {
        foreach ($styles as $style) {
            echo "   - [" . $style['style_code'] . "] " . $style['description'] . 
                 " (Product: " . $style['product'] . ")\n";
        }
    } else {
        echo "   ❌ No active styles found!\n";
    }
    echo "\n";
    
    // 6. Thread Types and Colors Check (TCR Dependencies)
    echo "6. THREAD TYPES & COLORS:\n";
    $threadTypes = $db->getAll('thread_types', ['is_active' => 1]);
    echo "Thread Types Count: " . count($threadTypes) . "\n";
    if ($threadTypes) {
        foreach ($threadTypes as $type) {
            echo "   - " . $type['name'] . " (ID: " . $type['thread_type_id'] . ")\n";
        }
    }
    
    $threadColors = $db->getAll('thread_colors', ['is_active' => 1]);
    echo "Thread Colors Count: " . count($threadColors) . "\n";
    if ($threadColors) {
        foreach ($threadColors as $color) {
            echo "   - " . $color['color_name'] . " (Type ID: " . $color['thread_type_id'] . ")\n";
        }
    }
    echo "\n";
    
    // 7. Critical Dependencies Analysis
    echo "📋 MASTER DATA REQUIREMENTS ANALYSIS:\n";
    echo "====================================\n";
    $criticalIssues = [];
    $warnings = [];
    
    if (count($machineTypes) === 0) {
        $criticalIssues[] = "No machine types - Cannot create thread factors or operations";
    }
    
    if (count($threadFactors) === 0) {
        $warnings[] = "No thread factors - TCR calculations will use default values";
    }
    
    if (count($operations) === 0) {
        $criticalIssues[] = "No operations - Cannot create operation breakdowns";
    }
    
    if (count($gsdElements) === 0) {
        $warnings[] = "No GSD elements - Limited detailed operation methods";
    }
    
    if (count($styles) === 0) {
        $criticalIssues[] = "No styles - Cannot create production plans";
    }
    
    if (count($threadTypes) === 0) {
        $criticalIssues[] = "No thread types - TCR system will not function";
    }
    
    // Summary
    if (empty($criticalIssues) && empty($warnings)) {
        echo "✅ All master data categories are properly populated\n";
        echo "   System ready for full workflow testing\n";
    } else {
        if (!empty($criticalIssues)) {
            echo "❌ Critical Issues (Must Fix):\n";
            foreach ($criticalIssues as $issue) {
                echo "   - " . $issue . "\n";
            }
        }
        
        if (!empty($warnings)) {
            echo "\n⚠️  Warnings (Recommended to Fix):\n";
            foreach ($warnings as $warning) {
                echo "   - " . $warning . "\n";
            }
        }
        
        echo "\n🎯 RECOMMENDATION: ";
        if (!empty($criticalIssues)) {
            echo "Fix critical issues before proceeding with workflow testing\n";
        } else {
            echo "Continue with workflow testing (warnings can be addressed later)\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error during Phase 1 testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>