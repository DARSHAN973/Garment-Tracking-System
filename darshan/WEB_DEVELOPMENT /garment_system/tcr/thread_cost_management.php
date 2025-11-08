<?php
$pageTitle = 'Thread Cost Management';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_thread_cost') {
        $thread_color_id = intval($_POST['thread_color_id']);
        $new_cost = floatval($_POST['new_cost']);
        $supplier = trim($_POST['supplier'] ?? '');
        $effective_date = $_POST['effective_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($thread_color_id > 0 && $new_cost >= 0) {
            try {
                $db->beginTransaction();
                
                // Get current cost for comparison
                $currentThread = $db->getById('thread_colors', $thread_color_id, 'thread_color_id');
                
                // Insert into cost history
                $db->insert('thread_cost_history', [
                    'thread_color_id' => $thread_color_id,
                    'old_cost_per_meter' => $currentThread['cost_per_meter'] ?? 0,
                    'new_cost_per_meter' => $new_cost,
                    'supplier' => $supplier,
                    'effective_date' => $effective_date,
                    'notes' => $notes,
                    'changed_by' => $_SESSION['user_id']
                ]);
                
                // Update thread color with new cost
                $db->update('thread_colors', [
                    'cost_per_meter' => $new_cost,
                    'last_cost_update' => date('Y-m-d H:i:s')
                ], ['thread_color_id' => $thread_color_id]);
                
                $db->commit();
                $_SESSION['success_message'] = "Thread cost updated successfully!";
                header("Location: thread_cost_management.php");
                exit;
            } catch (Exception $e) {
                $db->rollback();
                $_SESSION['error_message'] = "Error updating cost: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'bulk_update') {
        $cost_updates = $_POST['cost_updates'] ?? [];
        $supplier = trim($_POST['bulk_supplier'] ?? '');
        $effective_date = $_POST['bulk_effective_date'] ?? date('Y-m-d');
        $notes = trim($_POST['bulk_notes'] ?? '');
        
        if (!empty($cost_updates)) {
            try {
                $db->beginTransaction();
                $updated_count = 0;
                
                foreach ($cost_updates as $thread_color_id => $new_cost) {
                    $thread_color_id = intval($thread_color_id);
                    $new_cost = floatval($new_cost);
                    
                    if ($thread_color_id > 0 && $new_cost >= 0) {
                        $currentThread = $db->getById('thread_colors', $thread_color_id, 'thread_color_id');
                        
                        if ($currentThread && $currentThread['cost_per_meter'] != $new_cost) {
                            // Insert into cost history
                            $db->insert('thread_cost_history', [
                                'thread_color_id' => $thread_color_id,
                                'old_cost_per_meter' => $currentThread['cost_per_meter'],
                                'new_cost_per_meter' => $new_cost,
                                'supplier' => $supplier,
                                'effective_date' => $effective_date,
                                'notes' => $notes,
                                'changed_by' => $_SESSION['user_id']
                            ]);
                            
                            // Update thread color
                            $db->update('thread_colors', [
                                'cost_per_meter' => $new_cost,
                                'last_cost_update' => date('Y-m-d H:i:s')
                            ], ['thread_color_id' => $thread_color_id]);
                            
                            $updated_count++;
                        }
                    }
                }
                
                $db->commit();
                $_SESSION['success_message'] = "Bulk update completed! Updated {$updated_count} thread colors.";
                header("Location: thread_cost_management.php");
                exit;
            } catch (Exception $e) {
                $db->rollback();
                $_SESSION['error_message'] = "Error in bulk update: " . $e->getMessage();
            }
        }
    }
}

// Get all thread colors with latest cost information
$threadColors = $db->query("
    SELECT 
        tc.*,
        tt.name as thread_type_name,
        tt.code as thread_type_code,
        (SELECT COUNT(*) FROM thread_cost_history tch WHERE tch.thread_color_id = tc.thread_color_id) as cost_history_count,
        (SELECT tch.effective_date FROM thread_cost_history tch WHERE tch.thread_color_id = tc.thread_color_id ORDER BY tch.created_at DESC LIMIT 1) as last_cost_change
    FROM thread_colors tc
    JOIN thread_types tt ON tc.thread_type_id = tt.thread_type_id
    WHERE tc.is_active = 1
    ORDER BY tt.name, tc.color_name
");

// Get suppliers list for quick access
$suppliers = $db->query("
    SELECT DISTINCT supplier 
    FROM thread_cost_history 
    WHERE supplier IS NOT NULL AND supplier != '' 
    ORDER BY supplier
");

// Get cost history for display
$costHistory = $db->query("
    SELECT 
        tch.*,
        tc.color_name,
        tc.color_code,
        tt.name as thread_type_name,
        u.username as changed_by_name
    FROM thread_cost_history tch
    JOIN thread_colors tc ON tch.thread_color_id = tc.thread_color_id
    JOIN thread_types tt ON tc.thread_type_id = tt.thread_type_id
    LEFT JOIN users u ON tch.changed_by = u.user_id
    ORDER BY tch.created_at DESC
    LIMIT 20
");

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Thread Cost Management</h1>
                <p class="mt-2 text-sm text-gray-600">Manage thread costs and track price history</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="showBulkUpdateModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-upload mr-2"></i>Bulk Update
                </button>
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

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-palette text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Thread Colors</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($threadColors); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-line text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Cost/Meter</dt>
                            <dd class="text-lg font-medium text-gray-900">
                                $<?php echo count($threadColors) > 0 ? number_format(array_sum(array_column($threadColors, 'cost_per_meter')) / count($threadColors), 4) : '0.0000'; ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-history text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Cost Updates</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($costHistory); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-building text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Suppliers</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($suppliers); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thread Colors and Costs -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Thread Colors and Current Costs</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thread Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Cost/Meter</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">History</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($threadColors as $color): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($color['thread_type_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($color['thread_type_code']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-4 w-4 rounded-full border" style="background-color: <?php echo htmlspecialchars($color['hex_color']); ?>"></div>
                                    <div class="ml-2">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($color['color_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($color['color_code']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">$<?php echo number_format($color['cost_per_meter'], 4); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if ($color['last_cost_change']): ?>
                                    <?php echo date('M j, Y', strtotime($color['last_cost_change'])); ?>
                                <?php else: ?>
                                    <span class="text-gray-400">Never updated</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo $color['cost_history_count']; ?> updates
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="updateCost(<?php echo $color['thread_color_id']; ?>, '<?php echo htmlspecialchars($color['color_name']); ?>', <?php echo $color['cost_per_meter']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <button onclick="viewHistory(<?php echo $color['thread_color_id']; ?>, '<?php echo htmlspecialchars($color['color_name']); ?>')" 
                                        class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-history"></i> History
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Cost History -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Cost Changes</h3>
            
            <?php if (empty($costHistory)): ?>
            <div class="text-center py-8">
                <i class="fas fa-history text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No cost history available.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thread Color</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Change</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Changed By</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($costHistory as $history): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M j, Y g:i A', strtotime($history['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($history['color_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($history['thread_type_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    From: $<?php echo number_format($history['old_cost_per_meter'], 4); ?>
                                </div>
                                <div class="text-sm font-semibold <?php echo $history['new_cost_per_meter'] > $history['old_cost_per_meter'] ? 'text-red-600' : 'text-green-600'; ?>">
                                    To: $<?php echo number_format($history['new_cost_per_meter'], 4); ?>
                                    <?php
                                    $change = $history['new_cost_per_meter'] - $history['old_cost_per_meter'];
                                    if ($change != 0):
                                        $percentage = ($change / $history['old_cost_per_meter']) * 100;
                                    ?>
                                    (<?php echo $change > 0 ? '+' : ''; ?><?php echo number_format($percentage, 1); ?>%)
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($history['supplier'] ?: 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($history['changed_by_name'] ?: 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                <?php echo htmlspecialchars($history['notes'] ?: ''); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Cost Modal -->
<div id="updateCostModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-lg font-bold text-gray-900">Update Thread Cost</h3>
                <button onclick="closeUpdateCostModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="updateCostForm" method="POST">
                <input type="hidden" name="action" value="update_thread_cost">
                <input type="hidden" id="update_thread_color_id" name="thread_color_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Thread Color</label>
                        <div id="update_thread_color_display" class="mt-1 text-sm text-gray-900 bg-gray-100 p-2 rounded"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Cost</label>
                        <div id="update_current_cost" class="mt-1 text-sm text-gray-900 bg-gray-100 p-2 rounded"></div>
                    </div>
                    
                    <div>
                        <label for="new_cost" class="block text-sm font-medium text-gray-700">New Cost per Meter *</label>
                        <input type="number" id="new_cost" name="new_cost" step="0.0001" min="0" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="supplier" class="block text-sm font-medium text-gray-700">Supplier</label>
                        <input type="text" id="supplier" name="supplier" list="suppliers"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter supplier name">
                        <datalist id="suppliers">
                            <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo htmlspecialchars($supplier['supplier']); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div>
                        <label for="effective_date" class="block text-sm font-medium text-gray-700">Effective Date</label>
                        <input type="date" id="effective_date" name="effective_date" value="<?php echo date('Y-m-d'); ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Reason for cost change..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6 border-t mt-6">
                    <button type="button" onclick="closeUpdateCostModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        Update Cost
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Update Modal -->
<div id="bulkUpdateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-lg font-bold text-gray-900">Bulk Update Thread Costs</h3>
                <button onclick="closeBulkUpdateModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="bulkUpdateForm" method="POST">
                <input type="hidden" name="action" value="bulk_update">
                
                <!-- Bulk Settings -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <label for="bulk_supplier" class="block text-sm font-medium text-gray-700">Supplier</label>
                        <input type="text" id="bulk_supplier" name="bulk_supplier" list="suppliers"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="bulk_effective_date" class="block text-sm font-medium text-gray-700">Effective Date</label>
                        <input type="date" id="bulk_effective_date" name="bulk_effective_date" value="<?php echo date('Y-m-d'); ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="bulk_percentage" class="block text-sm font-medium text-gray-700">Apply % Change</label>
                        <div class="flex">
                            <input type="number" id="bulk_percentage" step="0.1" placeholder="e.g., 5 or -10"
                                   class="mt-1 block w-full border-gray-300 rounded-l-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <button type="button" onclick="applyPercentageChange()" 
                                    class="mt-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-r-md border border-l-0 border-green-600">
                                Apply
                            </button>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="bulk_notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="bulk_notes" name="bulk_notes" rows="2"
                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 mb-4"
                              placeholder="Reason for bulk cost changes..."></textarea>
                </div>
                
                <!-- Thread Colors Table -->
                <div class="max-h-96 overflow-y-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select_all" onchange="toggleAllCosts()" class="h-4 w-4 text-blue-600">
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thread Color</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Cost</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Cost</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($threadColors as $color): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="cost-checkbox h-4 w-4 text-blue-600" 
                                           data-color-id="<?php echo $color['thread_color_id']; ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-4 w-4 rounded-full border" style="background-color: <?php echo htmlspecialchars($color['hex_color']); ?>"></div>
                                        <div class="ml-2">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($color['color_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($color['thread_type_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="current-cost">$<?php echo number_format($color['cost_per_meter'], 4); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="number" name="cost_updates[<?php echo $color['thread_color_id']; ?>]" 
                                           step="0.0001" min="0" value="<?php echo $color['cost_per_meter']; ?>"
                                           class="new-cost-input w-24 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6 border-t mt-6">
                    <button type="button" onclick="closeBulkUpdateModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        Update Selected Costs
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateCost(threadColorId, colorName, currentCost) {
    document.getElementById('update_thread_color_id').value = threadColorId;
    document.getElementById('update_thread_color_display').textContent = colorName;
    document.getElementById('update_current_cost').textContent = '$' + currentCost.toFixed(4);
    document.getElementById('new_cost').value = currentCost.toFixed(4);
    document.getElementById('updateCostModal').classList.remove('hidden');
}

function closeUpdateCostModal() {
    document.getElementById('updateCostModal').classList.add('hidden');
    document.getElementById('updateCostForm').reset();
}

function openBulkUpdateModal() {
    document.getElementById('bulkUpdateModal').classList.remove('hidden');
}

function closeBulkUpdateModal() {
    document.getElementById('bulkUpdateModal').classList.add('hidden');
    document.getElementById('bulkUpdateForm').reset();
    // Reset all checkboxes
    document.querySelectorAll('.cost-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('select_all').checked = false;
}

function toggleAllCosts() {
    const selectAll = document.getElementById('select_all');
    const checkboxes = document.querySelectorAll('.cost-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function applyPercentageChange() {
    const percentage = parseFloat(document.getElementById('bulk_percentage').value);
    if (isNaN(percentage)) return;
    
    const checkedBoxes = document.querySelectorAll('.cost-checkbox:checked');
    checkedBoxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const currentCostText = row.querySelector('.current-cost').textContent.replace('$', '');
        const currentCost = parseFloat(currentCostText);
        const newCost = currentCost * (1 + percentage / 100);
        const newCostInput = row.querySelector('.new-cost-input');
        newCostInput.value = newCost.toFixed(4);
    });
}

function viewHistory(threadColorId, colorName) {
    alert('Thread cost history feature will be implemented in next iteration');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const updateModal = document.getElementById('updateCostModal');
    const bulkModal = document.getElementById('bulkUpdateModal');
    
    if (event.target === updateModal) {
        closeUpdateCostModal();
    }
    if (event.target === bulkModal) {
        closeBulkUpdateModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>