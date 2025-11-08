<?php
$pageTitle = 'TCR Details - Enhanced Thread Consumption';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();
$tcr_id = intval($_GET['tcr_id'] ?? 0);

if ($tcr_id <= 0) {
    header('Location: tcr_list.php');
    exit;
}

// Get TCR data
$tcr = $db->query("
    SELECT t.*, s.name as style_name, u1.username as created_by_name, u2.username as approved_by_name
    FROM tcr t
    JOIN styles s ON t.style_id = s.style_id
    LEFT JOIN users u1 ON t.created_by = u1.user_id
    LEFT JOIN users u2 ON t.approved_by = u2.user_id
    WHERE t.tcr_id = ?
", [$tcr_id]);

if (empty($tcr)) {
    header('Location: tcr_list.php');
    exit;
}
$tcr = $tcr[0];

// Handle form submissions for adding details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && true) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_detail') {
        $operation_id = intval($_POST['operation_id']);
        $thread_type_id = intval($_POST['thread_type_id']);
        $thread_color_id = intval($_POST['thread_color_id']);
        $seam_length_cm = floatval($_POST['seam_length_cm']);
        $stitch_per_inch = floatval($_POST['stitch_per_inch']);
        $wastage_percentage = floatval($_POST['wastage_percentage']);
        $sequence_order = intval($_POST['sequence_order']);
        $notes = trim($_POST['notes'] ?? '');
        $consumption_factors = $_POST['consumption_factors'] ?? [];
        
        if ($operation_id > 0 && $thread_type_id > 0 && $thread_color_id > 0) {
            try {
                // Get thread type factor
                $threadType = $db->getById('thread_types', $thread_type_id, 'thread_type_id');
                $threadFactor = $threadType['base_consumption_factor'] ?? 1.0000;
                
                // Get thread color cost
                $threadColor = $db->getById('thread_colors', $thread_color_id, 'thread_color_id');
                $costPerMeter = $threadColor['cost_per_meter'] ?? 0.0000;
                
                // Calculate consumption
                $baseConsumption = ($seam_length_cm * $stitch_per_inch / 2.54) / 100; // Convert to meters
                $calculatedConsumption = $baseConsumption * $threadFactor * (1 + $wastage_percentage / 100);
                
                // Apply consumption factors
                $totalFactorMultiplier = 1.0;
                foreach ($consumption_factors as $factorId) {
                    $factor = $db->getById('consumption_factors', $factorId, 'factor_id');
                    if ($factor) {
                        $totalFactorMultiplier *= $factor['multiplier'];
                    }
                }
                
                $finalConsumption = $calculatedConsumption * $totalFactorMultiplier;
                $costPerUnit = $finalConsumption * $costPerMeter;
                
                $db->beginTransaction();
                
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
                
                // Insert factor assignments
                foreach ($consumption_factors as $factorId) {
                    $factor = $db->getById('consumption_factors', $factorId, 'factor_id');
                    if ($factor) {
                        $db->insert('consumption_factor_assignments', [
                            'tcr_detail_id' => $detailId,
                            'factor_id' => $factorId,
                            'applied_multiplier' => $factor['multiplier']
                        ]);
                    }
                }
                
                $db->commit();
                $_SESSION['success_message'] = "Thread consumption detail added successfully!";
                header("Location: tcr_enhanced_detail.php?tcr_id={$tcr_id}");
                exit;
            } catch (Exception $e) {
                $db->rollback();
                $_SESSION['error_message'] = "Error adding detail: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'delete_detail') {
        $detail_id = intval($_POST['detail_id']);
        if ($detail_id > 0) {
            try {
                $db->beginTransaction();
                $db->query("DELETE FROM consumption_factor_assignments WHERE tcr_detail_id = ?", [$detail_id]);
                $db->query("DELETE FROM tcr_details WHERE tcr_detail_id = ?", [$detail_id]);
                $db->commit();
                
                $_SESSION['success_message'] = "Thread consumption detail deleted successfully!";
                header("Location: tcr_enhanced_detail.php?tcr_id={$tcr_id}");
                exit;
            } catch (Exception $e) {
                $db->rollback();
                $_SESSION['error_message'] = "Error deleting detail: " . $e->getMessage();
            }
        }
    }
}

// Get TCR details with all related data
$tcrDetails = $db->query("
    SELECT 
        td.*,
        o.name as operation_name,
        o.code as operation_code,
        tt.name as thread_type_name,
        tt.code as thread_type_code,
        tc.color_name,
        tc.color_code,
        tc.hex_color,
        tc.cost_per_meter
    FROM tcr_details td
    JOIN operations o ON td.operation_id = o.operation_id
    JOIN thread_types tt ON td.thread_type_id = tt.thread_type_id
    JOIN thread_colors tc ON td.thread_color_id = tc.thread_color_id
    WHERE td.tcr_id = ? AND td.is_active = 1
    ORDER BY td.sequence_order, td.tcr_detail_id
", [$tcr_id]);

// Get consumption factors for each detail
foreach ($tcrDetails as &$detail) {
    $factors = $db->query("
        SELECT cf.factor_name, cf.factor_type, cfa.applied_multiplier
        FROM consumption_factor_assignments cfa
        JOIN consumption_factors cf ON cfa.factor_id = cf.factor_id
        WHERE cfa.tcr_detail_id = ?
    ", [$detail['tcr_detail_id']]);
    $detail['factors'] = $factors;
}

// Calculate totals
$totalConsumption = array_sum(array_column($tcrDetails, 'final_consumption'));
$totalCost = array_sum(array_column($tcrDetails, 'cost_per_unit'));

// Get dropdown data
$operations = $db->getAll('operations', ['is_active' => 1], 'name ASC');
$threadTypes = $db->getAll('thread_types', ['is_active' => 1], 'name ASC');
$threadColors = $db->getAll('thread_colors', ['is_active' => 1], 'color_name ASC');
$consumptionFactors = $db->query("SELECT * FROM consumption_factors WHERE is_active = 1 ORDER BY factor_type, factor_name");

// Group factors by type
$factorsByType = [];
foreach ($consumptionFactors as $factor) {
    $factorsByType[$factor['factor_type']][] = $factor;
}

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">TCR Details: <?php echo htmlspecialchars($tcr['tcr_name']); ?></h1>
                <p class="mt-2 text-sm text-gray-600">Style: <?php echo htmlspecialchars($tcr['style_name']); ?> | Status: 
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $tcr['status'] === 'Approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                        <?php echo $tcr['status']; ?>
                    </span>
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="tcr_list.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
                <?php if (true): ?>
                <button onclick="openAddDetailModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-plus mr-2"></i>Add Thread Detail
                </button>
                <?php endif; ?>
            </div>
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-list text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Operations</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($tcrDetails); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-weight text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Consumption</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo number_format($totalConsumption, 4); ?>m</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-dollar-sign text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Cost</dt>
                            <dd class="text-lg font-medium text-gray-900">$<?php echo number_format($totalCost, 4); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calculator text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Cost/Meter</dt>
                            <dd class="text-lg font-medium text-gray-900">
                                $<?php echo $totalConsumption > 0 ? number_format($totalCost / $totalConsumption, 4) : '0.0000'; ?>
                            </dd>
                </div>
            </div>
        </div>
    </div>

    <!-- TCR Details Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Thread Consumption Details</h3>
            
            <?php if (empty($tcrDetails)): ?>
            <div class="text-center py-8">
                <i class="fas fa-plus-circle text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No thread consumption details added yet.</p>
                <?php if (true): ?>
                <button onclick="openAddDetailModal()" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                    Add First Detail
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seq</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operation</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thread Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seam Details</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Factors</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consumption</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                            <?php if (true): ?>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($tcrDetails as $detail): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $detail['sequence_order']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($detail['operation_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($detail['operation_code']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($detail['thread_type_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($detail['thread_type_code']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-4 w-4 rounded-full border" style="background-color: <?php echo htmlspecialchars($detail['hex_color']); ?>"></div>
                                    <div class="ml-2">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($detail['color_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($detail['color_code']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>Length: <?php echo number_format($detail['seam_length_cm'], 1); ?>cm</div>
                                <div>SPI: <?php echo number_format($detail['stitch_per_inch'], 1); ?></div>
                                <div>Wastage: <?php echo number_format($detail['wastage_percentage'], 1); ?>%</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($detail['factors'])): ?>
                                    <div class="text-xs">
                                        <?php foreach ($detail['factors'] as $factor): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mb-1 mr-1">
                                                <?php echo htmlspecialchars($factor['factor_name']); ?> (<?php echo number_format($factor['applied_multiplier'], 3); ?>x)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">No factors</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>Base: <?php echo number_format($detail['base_consumption'], 4); ?>m</div>
                                <div class="font-semibold text-blue-600">Final: <?php echo number_format($detail['final_consumption'], 4); ?>m</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">$<?php echo number_format($detail['cost_per_unit'], 4); ?></div>
                                <div class="text-xs text-gray-500">@$<?php echo number_format($detail['cost_per_meter'], 4); ?>/m</div>
                            </td>
                            <?php if (true): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editDetail(<?php echo $detail['tcr_detail_id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteDetail(<?php echo $detail['tcr_detail_id']; ?>)" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php if (!empty($detail['notes'])): ?>
                        <tr class="bg-gray-50">
                            <td colspan="<?php echo true ? 9 : 8; ?>" class="px-6 py-2">
                                <div class="text-sm text-gray-600">
                                    <strong>Notes:</strong> <?php echo htmlspecialchars($detail['notes']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <td colspan="<?php echo true ? 6 : 5; ?>" class="px-6 py-3 text-sm font-medium text-gray-900 text-right">
                                Totals:
                            </td>
                            <td class="px-6 py-3 text-sm font-bold text-gray-900">
                                <?php echo number_format($totalConsumption, 4); ?>m
                            </td>
                            <td class="px-6 py-3 text-sm font-bold text-gray-900">
                                $<?php echo number_format($totalCost, 4); ?>
                            </td>
                            <?php if (true): ?>
                            <td></td>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Detail Modal -->
<div id="addDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-lg font-bold text-gray-900">Add Thread Consumption Detail</h3>
                <button onclick="closeAddDetailModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addDetailForm" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add_detail">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div>
                            <label for="operation_id" class="block text-sm font-medium text-gray-700">Operation *</label>
                            <select id="operation_id" name="operation_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Operation</option>
                                <?php foreach ($operations as $operation): ?>
                                <option value="<?php echo $operation['operation_id']; ?>">
                                    <?php echo htmlspecialchars($operation['name']); ?> (<?php echo htmlspecialchars($operation['code']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="thread_type_id" class="block text-sm font-medium text-gray-700">Thread Type *</label>
                            <select id="thread_type_id" name="thread_type_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Thread Type</option>
                                <?php foreach ($threadTypes as $threadType): ?>
                                <option value="<?php echo $threadType['thread_type_id']; ?>" data-factor="<?php echo $threadType['base_consumption_factor']; ?>">
                                    <?php echo htmlspecialchars($threadType['name']); ?> (Factor: <?php echo number_format($threadType['base_consumption_factor'], 4); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="thread_color_id" class="block text-sm font-medium text-gray-700">Thread Color *</label>
                            <select id="thread_color_id" name="thread_color_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Thread Color</option>
                                <?php foreach ($threadColors as $threadColor): ?>
                                <option value="<?php echo $threadColor['thread_color_id']; ?>" 
                                        data-cost="<?php echo $threadColor['cost_per_meter']; ?>"
                                        data-hex="<?php echo htmlspecialchars($threadColor['hex_color']); ?>">
                                    <?php echo htmlspecialchars($threadColor['color_name']); ?> (<?php echo htmlspecialchars($threadColor['color_code']); ?>) - $<?php echo number_format($threadColor['cost_per_meter'], 4); ?>/m
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="sequence_order" class="block text-sm font-medium text-gray-700">Sequence Order</label>
                            <input type="number" id="sequence_order" name="sequence_order" min="1" max="999" 
                                   value="<?php echo count($tcrDetails) + 1; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div>
                            <label for="seam_length_cm" class="block text-sm font-medium text-gray-700">Seam Length (cm) *</label>
                            <input type="number" id="seam_length_cm" name="seam_length_cm" step="0.1" min="0" required
                                   onchange="calculateConsumption()"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="stitch_per_inch" class="block text-sm font-medium text-gray-700">Stitches Per Inch *</label>
                            <input type="number" id="stitch_per_inch" name="stitch_per_inch" step="0.1" min="0" required
                                   onchange="calculateConsumption()"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="wastage_percentage" class="block text-sm font-medium text-gray-700">Wastage Percentage</label>
                            <input type="number" id="wastage_percentage" name="wastage_percentage" step="0.1" min="0" max="100" value="5"
                                   onchange="calculateConsumption()"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Consumption Preview</label>
                            <div class="bg-gray-100 p-3 rounded-lg">
                                <div class="text-sm text-gray-600">
                                    <div>Base Consumption: <span id="preview_base_consumption">0.0000m</span></div>
                                    <div>After Factors: <span id="preview_final_consumption">0.0000m</span></div>
                                    <div>Estimated Cost: <span id="preview_cost">$0.0000</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consumption Factors Section -->
                <div class="border-t pt-6">
                    <h4 class="text-md font-semibold text-gray-900 mb-4">Additional Consumption Factors</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($factorsByType as $type => $factors): ?>
                        <div class="border rounded-lg p-3">
                            <h5 class="font-medium text-sm text-gray-700 mb-2"><?php echo ucwords(str_replace('_', ' ', $type)); ?></h5>
                            <?php foreach ($factors as $factor): ?>
                            <div class="flex items-center mb-2">
                                <input type="checkbox" id="factor_<?php echo $factor['factor_id']; ?>" 
                                       name="consumption_factors[]" value="<?php echo $factor['factor_id']; ?>"
                                       data-multiplier="<?php echo $factor['multiplier']; ?>"
                                       onchange="calculateConsumption()"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="factor_<?php echo $factor['factor_id']; ?>" class="ml-2 text-sm text-gray-700">
                                    <?php echo htmlspecialchars($factor['factor_name']); ?> 
                                    <span class="text-gray-500">(<?php echo number_format($factor['multiplier'], 3); ?>x)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Notes Section -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="notes" name="notes" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Optional notes about this thread consumption detail..."></textarea>
                </div>

                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <button type="button" onclick="closeAddDetailModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        Add Detail
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Delete Thread Detail</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete this thread consumption detail? This action cannot be undone.
                </p>
            </div>
            <form id="deleteDetailForm" method="POST">
                <input type="hidden" name="action" value="delete_detail">
                <input type="hidden" id="delete_detail_id" name="detail_id">
                <div class="items-center px-4 py-3">
                    <button type="button" onclick="closeDeleteDetailModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-24 mr-2 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddDetailModal() {
    document.getElementById('addDetailModal').classList.remove('hidden');
}

function closeAddDetailModal() {
    document.getElementById('addDetailModal').classList.add('hidden');
    document.getElementById('addDetailForm').reset();
    resetCalculation();
}

function deleteDetail(detailId) {
    document.getElementById('delete_detail_id').value = detailId;
    document.getElementById('deleteDetailModal').classList.remove('hidden');
}

function closeDeleteDetailModal() {
    document.getElementById('deleteDetailModal').classList.add('hidden');
}

function editDetail(detailId) {
    // TODO: Implement edit functionality
    alert('Edit functionality will be implemented in next iteration');
}

function calculateConsumption() {
    const seamLength = parseFloat(document.getElementById('seam_length_cm').value) || 0;
    const stitchPerInch = parseFloat(document.getElementById('stitch_per_inch').value) || 0;
    const wastagePercent = parseFloat(document.getElementById('wastage_percentage').value) || 0;
    
    // Get thread type factor
    const threadTypeSelect = document.getElementById('thread_type_id');
    const selectedThreadOption = threadTypeSelect.options[threadTypeSelect.selectedIndex];
    const threadFactor = parseFloat(selectedThreadOption?.getAttribute('data-factor')) || 1.0;
    
    // Get thread cost
    const threadColorSelect = document.getElementById('thread_color_id');
    const selectedColorOption = threadColorSelect.options[threadColorSelect.selectedIndex];
    const costPerMeter = parseFloat(selectedColorOption?.getAttribute('data-cost')) || 0;
    
    // Calculate base consumption (meters)
    let baseConsumption = 0;
    if (seamLength > 0 && stitchPerInch > 0) {
        baseConsumption = (seamLength * stitchPerInch / 2.54) / 100; // Convert to meters
    }
    
    // Apply thread factor and wastage
    let calculatedConsumption = baseConsumption * threadFactor * (1 + wastagePercent / 100);
    
    // Apply additional factors
    let totalFactorMultiplier = 1.0;
    const factorCheckboxes = document.querySelectorAll('input[name="consumption_factors[]"]:checked');
    factorCheckboxes.forEach(checkbox => {
        const multiplier = parseFloat(checkbox.getAttribute('data-multiplier')) || 1.0;
        totalFactorMultiplier *= multiplier;
    });
    
    const finalConsumption = calculatedConsumption * totalFactorMultiplier;
    const estimatedCost = finalConsumption * costPerMeter;
    
    // Update preview
    document.getElementById('preview_base_consumption').textContent = baseConsumption.toFixed(4) + 'm';
    document.getElementById('preview_final_consumption').textContent = finalConsumption.toFixed(4) + 'm';
    document.getElementById('preview_cost').textContent = '$' + estimatedCost.toFixed(4);
}

function resetCalculation() {
    document.getElementById('preview_base_consumption').textContent = '0.0000m';
    document.getElementById('preview_final_consumption').textContent = '0.0000m';
    document.getElementById('preview_cost').textContent = '$0.0000';
}

// Add event listeners for thread type and color changes
document.getElementById('thread_type_id').addEventListener('change', calculateConsumption);
document.getElementById('thread_color_id').addEventListener('change', calculateConsumption);

// Close modals when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addDetailModal');
    const deleteModal = document.getElementById('deleteDetailModal');
    
    if (event.target === addModal) {
        closeAddDetailModal();
    }
    if (event.target === deleteModal) {
        closeDeleteDetailModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>