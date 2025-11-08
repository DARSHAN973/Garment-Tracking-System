<?php
$pageTitle = 'Enhanced Thread Consumption Report';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$db = new DatabaseHelper();
$message = '';
$messageType = 'success';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_tcr') {
        $style_id = intval($_POST['style_id']);
        $tcr_name = trim($_POST['tcr_name']);
        $description = trim($_POST['description'] ?? '');
        $created_by = $_SESSION['user_id'];
        
        if (!empty($tcr_name) && $style_id > 0) {
            try {
                $tcrId = $db->insert('tcr', [
                    'style_id' => $style_id,
                    'tcr_name' => $tcr_name,
                    'description' => $description,
                    'status' => 'Draft',
                    'version' => 1,
                    'created_by' => $created_by,
                    'updated_by' => $created_by
                ]);
                
                $_SESSION['success_message'] = "TCR '{$tcr_name}' created successfully! Now add thread consumption details.";
                header("Location: tcr_detail.php?tcr_id={$tcrId}");
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error creating TCR: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Please provide valid TCR name and select a style.";
        }
    }
    
    elseif ($action === 'add_tcr_detail') {
        $tcr_id = intval($_POST['tcr_id']);
        $operation_id = intval($_POST['operation_id']);
        $thread_type_id = intval($_POST['thread_type_id']);
        $thread_color_id = intval($_POST['thread_color_id']);
        $seam_length_cm = floatval($_POST['seam_length_cm']);
        $stitch_per_inch = floatval($_POST['stitch_per_inch']);
        $wastage_percentage = floatval($_POST['wastage_percentage']);
        $sequence_order = intval($_POST['sequence_order']);
        $notes = trim($_POST['notes'] ?? '');
        
        if ($tcr_id > 0 && $operation_id > 0) {
            try {
                // Get thread type factor
                $threadType = $db->getById('thread_types', $thread_type_id, 'thread_type_id');
                $threadFactor = $threadType['base_consumption_factor'] ?? 1.0000;
                
                // Get thread color cost
                $threadColor = $db->getById('thread_colors', $thread_color_id, 'thread_color_id');
                $costPerMeter = $threadColor['cost_per_meter'] ?? 0.0000;
                
                // Calculate consumption
                // Formula: (Seam Length * SPI / 2.54) * Thread Factor * (1 + Wastage%) / 100
                $baseConsumption = ($seam_length_cm * $stitch_per_inch / 2.54) / 100; // Convert to meters
                $calculatedConsumption = $baseConsumption * $threadFactor * (1 + $wastage_percentage / 100);
                $finalConsumption = $calculatedConsumption;
                $costPerUnit = $finalConsumption * $costPerMeter;
                
                $detailId = $db->insert('tcr_details', [
                    'tcr_id' => $tcr_id,
                    'operation_id' => $operation_id,
                    'thread_type_id' => $thread_type_id,
                    'thread_color_id' => $thread_color_id,
                    'base_consumption' => $baseConsumption,
                    'seam_length_cm' => $seam_length_cm,
                    'stitch_per_inch' => $stitch_per_inch,
                    'thread_factor' => $threadFactor,
                    'wastage_percentage' => $wastage_percentage,
                    'calculated_consumption' => $calculatedConsumption,
                    'final_consumption' => $finalConsumption,
                    'cost_per_unit' => $costPerUnit,
                    'notes' => $notes,
                    'sequence_order' => $sequence_order
                ]);
                
                // Apply consumption factors if selected
                if (!empty($_POST['consumption_factors'])) {
                    foreach ($_POST['consumption_factors'] as $factorId) {
                        $factor = $db->getById('consumption_factors', $factorId, 'factor_id');
                        if ($factor) {
                            $db->insert('consumption_factor_assignments', [
                                'tcr_detail_id' => $detailId,
                                'factor_id' => $factorId,
                                'applied_multiplier' => $factor['multiplier']
                            ]);
                        }
                    }
                    
                    // Recalculate with factors
                    $totalMultiplier = 1.0;
                    $factors = $db->query("
                        SELECT cf.multiplier 
                        FROM consumption_factor_assignments cfa 
                        JOIN consumption_factors cf ON cfa.factor_id = cf.factor_id 
                        WHERE cfa.tcr_detail_id = ?
                    ", [$detailId]);
                    
                    foreach ($factors as $f) {
                        $totalMultiplier *= $f['multiplier'];
                    }
                    
                    $adjustedConsumption = $calculatedConsumption * $totalMultiplier;
                    $adjustedCost = $adjustedConsumption * $costPerMeter;
                    
                    $db->update('tcr_details', $detailId, [
                        'final_consumption' => $adjustedConsumption,
                        'cost_per_unit' => $adjustedCost
                    ], 'tcr_detail_id');
                }
                
                $_SESSION['success_message'] = "Thread consumption detail added successfully!";
                header("Location: tcr_detail.php?tcr_id={$tcr_id}");
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error adding detail: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Please provide valid TCR and operation information.";
        }
    }
}

// Get styles for dropdown
$styles = $db->getAll('styles', ['is_active' => 1], 'name ASC');

// Get operations for dropdown
$operations = $db->getAll('operations', ['is_active' => 1], 'name ASC');

// Get thread types
$threadTypes = $db->getAll('thread_types', ['is_active' => 1], 'name ASC');

// Get thread colors
$threadColors = $db->getAll('thread_colors', ['is_active' => 1], 'color_name ASC');

// Get consumption factors by type
$consumptionFactors = $db->query("SELECT * FROM consumption_factors WHERE is_active = 1 ORDER BY factor_type, factor_name");
$factorsByType = [];
foreach ($consumptionFactors as $factor) {
    $factorsByType[$factor['factor_type']][] = $factor;
}

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    <div class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Enhanced Thread Consumption Report</h1>
                <p class="mt-2 text-sm text-gray-600">Create detailed thread consumption reports with factor-based calculations</p>
            </div>
            <a href="tcr_list.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to TCR List
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
    <?php endif; ?>

    <!-- TCR Creation Form -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Create New TCR</h3>
            
            <form method="POST" id="tcrCreateForm">
                <input type="hidden" name="action" value="create_tcr">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Style *</label>
                        <select name="style_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Style</option>
                            <?php foreach ($styles as $style): ?>
                            <option value="<?php echo $style['style_id']; ?>"><?php echo htmlspecialchars($style['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">TCR Name *</label>
                        <input type="text" name="tcr_name" required maxlength="100" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., T-Shirt Basic TCR v1.0">
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" maxlength="500"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Describe the TCR purpose and specifications"></textarea>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="tcr_list.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create TCR
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Information Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-blue-900">Enhanced Features</h3>
                    <p class="text-blue-700 text-sm mt-1">Factor-based calculations, color tracking, and cost analysis</p>
                </div>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calculator text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-green-900">Automatic Calculations</h3>
                    <p class="text-green-700 text-sm mt-1">Seam length, stitch density, and wastage factor integration</p>
                </div>
            </div>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-palette text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-purple-900">Thread Colors & Types</h3>
                    <p class="text-purple-700 text-sm mt-1">Comprehensive thread library with cost tracking</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Master Data Summary -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Available Master Data</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Thread Types (<?php echo count($threadTypes); ?>)</h4>
                    <div class="space-y-1">
                        <?php foreach (array_slice($threadTypes, 0, 5) as $type): ?>
                        <div class="text-sm text-gray-600">
                            <?php echo htmlspecialchars($type['name']); ?>
                            <span class="text-xs text-gray-400">(<?php echo $type['code']; ?>)</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($threadTypes) > 5): ?>
                        <div class="text-xs text-gray-500">... and <?php echo count($threadTypes) - 5; ?> more</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Thread Colors (<?php echo count($threadColors); ?>)</h4>
                    <div class="space-y-1">
                        <?php foreach (array_slice($threadColors, 0, 5) as $color): ?>
                        <div class="text-sm text-gray-600 flex items-center">
                            <div class="w-3 h-3 rounded-full mr-2" style="background-color: <?php echo $color['hex_color']; ?>"></div>
                            <?php echo htmlspecialchars($color['color_name']); ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($threadColors) > 5): ?>
                        <div class="text-xs text-gray-500">... and <?php echo count($threadColors) - 5; ?> more</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Consumption Factors (<?php echo count($consumptionFactors); ?>)</h4>
                    <div class="space-y-1">
                        <?php 
                        $displayFactors = array_slice($consumptionFactors, 0, 5);
                        foreach ($displayFactors as $factor): 
                        ?>
                        <div class="text-sm text-gray-600">
                            <?php echo htmlspecialchars($factor['factor_name']); ?>
                            <span class="text-xs text-gray-400">(×<?php echo $factor['multiplier']; ?>)</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($consumptionFactors) > 5): ?>
                        <div class="text-xs text-gray-500">... and <?php echo count($consumptionFactors) - 5; ?> more</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

<script>
// Form validation and enhancement
document.getElementById('tcrCreateForm').addEventListener('submit', function(e) {
    const styleName = document.querySelector('select[name="style_id"] option:checked').textContent;
    const tcrName = document.querySelector('input[name="tcr_name"]').value;
    
    if (!styleName || styleName === 'Select Style') {
        e.preventDefault();
        alert('Please select a style');
        return;
    }
    
    if (!tcrName.trim()) {
        e.preventDefault();
        alert('Please enter TCR name');
        return;
    }
});
</script>

<?php include '../includes/footer.php'; ?>