<?php
$pageTitle = 'Thread Factors';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();
$message = '';
$messageType = 'success';

// Get machine types for dropdown
$machineTypes = $db->getAll('machine_types', ['is_active' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && true) {
        $machineTypeId = intval($_POST['machine_type_id']);
        $factorPerCm = floatval($_POST['factor_per_cm']);
        $needleCount = intval($_POST['needle_count']);
        $looperCount = intval($_POST['looper_count']);
        $pctNeedle = floatval($_POST['pct_needle']);
        $pctBobbin = floatval($_POST['pct_bobbin']);
        $pctLooper = floatval($_POST['pct_looper']);
        $backtackCm = floatval($_POST['backtack_cm']);
        $endWasteCm = floatval($_POST['end_waste_cm']);
        
        // Validate inputs
        if ($machineTypeId <= 0 || $factorPerCm <= 0) {
            $message = 'Machine Type and Factor Per Cm are required.';
            $messageType = 'error';
        } elseif (($pctNeedle + $pctBobbin + $pctLooper) > 1) {
            $message = 'Thread percentage splits cannot exceed 100%.';
            $messageType = 'error';
        } else {
            // Check if thread factor already exists for this machine type
            $existing = $db->queryOne("SELECT thread_factor_id FROM thread_factors WHERE machine_type_id = ? AND is_active = 1", [$machineTypeId]);
            if ($existing) {
                $message = 'Thread factor already exists for this machine type.';
                $messageType = 'error';
            } else {
                $result = $db->insert('thread_factors', [
                    'machine_type_id' => $machineTypeId,
                    'factor_per_cm' => $factorPerCm,
                    'needle_count' => $needleCount,
                    'looper_count' => $looperCount,
                    'pct_needle' => $pctNeedle,
                    'pct_bobbin' => $pctBobbin,
                    'pct_looper' => $pctLooper,
                    'backtack_cm' => $backtackCm,
                    'end_waste_cm' => $endWasteCm,
                    'is_active' => 1
                ]);
                
                if ($result) {
                    $message = 'Thread factors created successfully.';
                    logActivity('thread_factors', $result, 'CREATE');
                } else {
                    $message = 'Error creating thread factors.';
                    $messageType = 'error';
                }
            }
        }
    }
    
    if ($action === 'update' && true) {
        $id = intval($_POST['id']);
        $factorPerCm = floatval($_POST['factor_per_cm']);
        $needleCount = intval($_POST['needle_count']);
        $looperCount = intval($_POST['looper_count']);
        $pctNeedle = floatval($_POST['pct_needle']);
        $pctBobbin = floatval($_POST['pct_bobbin']);
        $pctLooper = floatval($_POST['pct_looper']);
        $backtackCm = floatval($_POST['backtack_cm']);
        $endWasteCm = floatval($_POST['end_waste_cm']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($factorPerCm <= 0) {
            $message = 'Factor Per Cm must be greater than 0.';
            $messageType = 'error';
        } elseif (($pctNeedle + $pctBobbin + $pctLooper) > 1) {
            $message = 'Thread percentage splits cannot exceed 100%.';
            $messageType = 'error';
        } else {
            $oldData = $db->getById('thread_factors', $id);
            $result = $db->update('thread_factors', $id, [
                'factor_per_cm' => $factorPerCm,
                'needle_count' => $needleCount,
                'looper_count' => $looperCount,
                'pct_needle' => $pctNeedle,
                'pct_bobbin' => $pctBobbin,
                'pct_looper' => $pctLooper,
                'backtack_cm' => $backtackCm,
                'end_waste_cm' => $endWasteCm,
                'is_active' => $isActive
            ]);
            
            if ($result) {
                $message = 'Thread factors updated successfully.';
                logActivity('thread_factors', $id, 'UPDATE', $oldData);
            } else {
                $message = 'Error updating thread factors.';
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'delete' && true) {
        $id = intval($_POST['id']);
        
        // Check if thread factor is used in other tables
        $usageCheck = $db->queryOne("
            SELECT COUNT(*) as count FROM (
                SELECT thread_factor_id FROM tcr_details WHERE thread_factor_id = ?
                UNION ALL
                SELECT thread_factor_id FROM consumption_factor_assignments WHERE thread_factor_id = ?
            ) as usage
        ", [$id, $id]);
        
        if ($usageCheck && $usageCheck['count'] > 0) {
            $message = 'Cannot delete thread factor. It is being used in TCR records or consumption assignments.';
            $messageType = 'error';
        } else {
            $oldData = $db->getById('thread_factors', $id);
            $result = $db->delete('thread_factors', $id);
            
            if ($result) {
                $message = 'Thread factor deleted successfully.';
                logActivity('thread_factors', $id, 'DELETE', $oldData);
            } else {
                $message = 'Error deleting thread factor.';
                $messageType = 'error';
            }
        }
    }
}

// Get all thread factors with machine names
$threadFactors = $db->query("
    SELECT tf.*, mt.name as machine_name, mt.code as machine_code
    FROM thread_factors tf 
    JOIN machine_types mt ON tf.machine_type_id = mt.machine_type_id 
    ORDER BY mt.name ASC
");

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
                        <h1 class="text-3xl font-bold text-gray-900">Thread Factors</h1>
                        <p class="text-gray-600 mt-2">Manage thread consumption factors by machine type</p>
                    </div>
                    <?php if (true): ?>
                    <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Thread Factor
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'error' ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200'; ?> alert-auto-hide">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 <?php echo $messageType === 'error' ? 'text-red-400' : 'text-green-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $messageType === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M5 13l4 4L19 7'; ?>"></path>
                    </svg>
                    <p class="<?php echo $messageType === 'error' ? 'text-red-700' : 'text-green-700'; ?>"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Thread Factors Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Thread Consumption Factors</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Machine Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Factor/Cm</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Counts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% Splits</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allowances</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <?php if (true): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($threadFactors as $factor): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($factor['machine_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($factor['machine_code']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($factor['factor_per_cm'], 4); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        N: <?php echo $factor['needle_count']; ?> | 
                                        L: <?php echo $factor['looper_count']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        N: <?php echo number_format($factor['pct_needle'] * 100, 1); ?>% |
                                        B: <?php echo number_format($factor['pct_bobbin'] * 100, 1); ?>% |
                                        L: <?php echo number_format($factor['pct_looper'] * 100, 1); ?>%
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        BT: <?php echo number_format($factor['backtack_cm'], 1); ?>cm |
                                        EW: <?php echo number_format($factor['end_waste_cm'], 1); ?>cm
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $factor['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $factor['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <?php if (true): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($factor)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <?php if (true): ?>
                                    <button onclick="confirmDelete(<?php echo $factor['factor_id']; ?>, 'Factor for <?php echo htmlspecialchars($factor['machine_name'], ENT_QUOTES); ?>')" 
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Create Modal -->
<?php if (true): ?>
<div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create Thread Factor</h3>
            <form method="POST" id="createForm">
                <input type="hidden" name="action" value="create">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine Type *</label>
                        <select name="machine_type_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select machine type</option>
                            <?php foreach ($machineTypes as $machine): ?>
                            <option value="<?php echo $machine['machine_type_id']; ?>"><?php echo htmlspecialchars($machine['name'] . ' (' . $machine['code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Factor Per Cm *</label>
                        <input type="number" name="factor_per_cm" step="0.0001" min="0" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="2.2000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Needle Count</label>
                        <input type="number" name="needle_count" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Looper Count</label>
                        <input type="number" name="looper_count" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">% Needle</label>
                        <input type="number" name="pct_needle" step="0.0001" min="0" max="1" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.5000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">% Bobbin</label>
                        <input type="number" name="pct_bobbin" step="0.0001" min="0" max="1" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.5000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">% Looper</label>
                        <input type="number" name="pct_looper" step="0.0001" min="0" max="1" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0.0000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Backtack (cm)</label>
                        <input type="number" name="backtack_cm" step="0.1" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Waste (cm)</label>
                        <input type="number" name="end_waste_cm" step="0.1" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Thread Factor</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine Type</label>
                        <input type="text" id="editMachineName" readonly 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Factor Per Cm *</label>
                        <input type="number" name="factor_per_cm" id="editFactorPerCm" step="0.0001" min="0" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Needle Count</label>
                        <input type="number" name="needle_count" id="editNeedleCount" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Looper Count</label>
                        <input type="number" name="looper_count" id="editLooperCount" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">% Needle</label>
                        <input type="number" name="pct_needle" id="editPctNeedle" step="0.0001" min="0" max="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">% Bobbin</label>
                        <input type="number" name="pct_bobbin" id="editPctBobbin" step="0.0001" min="0" max="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">% Looper</label>
                        <input type="number" name="pct_looper" id="editPctLooper" step="0.0001" min="0" max="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Backtack (cm)</label>
                        <input type="number" name="backtack_cm" id="editBacktackCm" step="0.1" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Waste (cm)</label>
                        <input type="number" name="end_waste_cm" id="editEndWasteCm" step="0.1" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="editActive" class="mr-2">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Delete Thread Factor</h3>
            <p class="mt-2 px-7 py-3 text-sm text-gray-500">
                Are you sure you want to delete "<span id="deleteFactorName" class="font-medium"></span>"?
                This action cannot be undone.
            </p>
            <form id="deleteForm" method="POST" class="mt-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteFactorId">
                
                <div class="flex justify-center space-x-3">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createForm').reset();
}

function openEditModal(factor) {
    document.getElementById('editId').value = factor.thread_factor_id;
    document.getElementById('editMachineName').value = factor.machine_name + ' (' + factor.machine_code + ')';
    document.getElementById('editFactorPerCm').value = factor.factor_per_cm;
    document.getElementById('editNeedleCount').value = factor.needle_count;
    document.getElementById('editLooperCount').value = factor.looper_count;
    document.getElementById('editPctNeedle').value = factor.pct_needle;
    document.getElementById('editPctBobbin').value = factor.pct_bobbin;
    document.getElementById('editPctLooper').value = factor.pct_looper;
    document.getElementById('editBacktackCm').value = factor.backtack_cm;
    document.getElementById('editEndWasteCm').value = factor.end_waste_cm;
    document.getElementById('editActive').checked = factor.is_active == 1;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(factorId, factorName) {
    document.getElementById('deleteFactorId').value = factorId;
    document.getElementById('deleteFactorName').textContent = factorName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modals on outside click
document.addEventListener('click', function(event) {
    if (event.target.id === 'createModal') closeCreateModal();
    if (event.target.id === 'editModal') closeEditModal();
    if (event.target.id === 'deleteModal') closeDeleteModal();
});
</script>

<?php include '../includes/footer.php'; ?>