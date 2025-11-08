<?php
$pageTitle = 'Create Operation Breakdown';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';
require_once '../utils/Calculator.php';

requirePermission('ob', 'write');

$db = new DatabaseHelper();
$calculator = new Calculator();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $styleId = intval($_POST['style_id']);
        $obName = sanitizeInput($_POST['ob_name']);
        $description = sanitizeInput($_POST['description']);
        $operations = json_decode($_POST['operations'], true);
        
        if (empty($styleId) || empty($operations)) {
            $message = 'Style and operations are required.';
            $messageType = 'error';
        } else {
            try {
                $db->beginTransaction();
                
                // Calculate totals
                $totalSmv = array_sum(array_column($operations, 'smv'));
                $targetPerHour = $calculator->calculateTargetPerHour($totalSmv);
                
                // Insert OB record
                $obId = $db->insert('ob', [
                    'style_id' => $styleId,
                    'ob_name' => $obName,
                    'description' => $description,
                    'total_smv' => $totalSmv,
                    'target_per_hour' => $targetPerHour,
                    'status' => 'DRAFT'
                ]);
                
                if ($obId) {
                    // Insert operations
                    foreach ($operations as $index => $operation) {
                        $db->insert('ob_details', [
                            'ob_id' => $obId,
                            'operation_id' => $operation['operation_id'],
                            'machine_type_id' => $operation['machine_type_id'],
                            'sequence_no' => $index + 1,
                            'smv' => $operation['smv'],
                            'description' => $operation['description']
                        ]);
                    }
                    
                    $db->commit();
                    logActivity('ob', $obId, 'CREATE');
                    header('Location: ob_detail.php?id=' . $obId);
                    exit;
                } else {
                    throw new Exception('Failed to create OB');
                }
            } catch (Exception $e) {
                $db->rollback();
                $message = 'Error creating OB: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get data for dropdowns
$styles = $db->query("SELECT style_id, style_code, description FROM styles WHERE is_active = 1 ORDER BY style_code");
$operations = $db->query("SELECT operation_id, operation_name, category FROM operations WHERE is_active = 1 ORDER BY operation_name");
$machineTypes = $db->query("SELECT machine_type_id, machine_name FROM machine_types WHERE is_active = 1 ORDER BY machine_name");

// Group operations by category
$operationsByCategory = [];
foreach ($operations as $operation) {
    $category = $operation['category'] ?: 'Other';
    $operationsByCategory[$category][] = $operation;
}

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-6xl mx-auto">
            
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Create Operation Breakdown</h1>
                        <p class="text-gray-600 mt-2">Define garment production operations and calculate SMV</p>
                    </div>
                    <a href="ob_list.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        ← Back to List
                    </a>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'error' ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200'; ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 <?php echo $messageType === 'error' ? 'text-red-400' : 'text-green-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $messageType === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M5 13l4 4L19 7'; ?>"></path>
                    </svg>
                    <p class="<?php echo $messageType === 'error' ? 'text-red-700' : 'text-green-700'; ?>"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" id="obForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="operations" id="operationsData">
                
                <!-- Basic Information -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Style *</label>
                            <select name="style_id" id="styleSelect" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select a style</option>
                                <?php foreach ($styles as $style): ?>
                                <option value="<?php echo $style['style_id']; ?>">
                                    <?php echo htmlspecialchars($style['style_code']); ?>
                                    <?php if ($style['description']): ?>
                                     - <?php echo htmlspecialchars($style['description']); ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">OB Name</label>
                            <input type="text" name="ob_name" maxlength="100" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="e.g., Standard OB v1.0">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="2" maxlength="500"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Additional details about this operation breakdown"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Operations Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Operations Sequence</h3>
                        <button type="button" onclick="addOperation()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Operation
                        </button>
                    </div>
                    
                    <!-- Operations Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full" id="operationsTable">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">#</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Operation</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">SMV</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-20">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="operationsBody">
                                <!-- Operations will be added dynamically -->
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 font-medium">
                                    <td colspan="3" class="px-4 py-2 text-right">Total SMV:</td>
                                    <td class="px-4 py-2" id="totalSmv">0.000</td>
                                    <td class="px-4 py-2">Target/Hour: <span id="targetPerHour" class="text-blue-600">0</span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <div id="emptyState" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p>No operations added yet. Click "Add Operation" to start building your operation breakdown.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="ob_list.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" id="saveButton" disabled 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Create OB
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Operation Modal -->
<div id="addOperationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Operation</h3>
            <form id="addOperationForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Operation Category</label>
                    <select id="operationCategory" onchange="filterOperations()" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Categories</option>
                        <?php foreach (array_keys($operationsByCategory) as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Operation *</label>
                    <select id="operationSelect" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select operation</option>
                        <?php foreach ($operationsByCategory as $category => $categoryOps): ?>
                        <optgroup label="<?php echo htmlspecialchars($category); ?>">
                            <?php foreach ($categoryOps as $operation): ?>
                            <option value="<?php echo $operation['operation_id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($operation['operation_name']); ?>"
                                    data-category="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($operation['operation_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Machine Type *</label>
                    <select id="machineSelect" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select machine</option>
                        <?php foreach ($machineTypes as $machine): ?>
                        <option value="<?php echo $machine['machine_type_id']; ?>" 
                                data-name="<?php echo htmlspecialchars($machine['machine_name']); ?>">
                            <?php echo htmlspecialchars($machine['machine_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">SMV *</label>
                    <input type="number" id="smvInput" step="0.001" min="0" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0.000">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="descriptionInput" rows="2" maxlength="255"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Optional operation details"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAddOperationModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Add Operation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let operations = [];
let operationCounter = 0;

const operationsData = <?php echo json_encode($operationsByCategory); ?>;
const machinesData = <?php echo json_encode($machineTypes); ?>;

function addOperation() {
    document.getElementById('addOperationModal').classList.remove('hidden');
}

function closeAddOperationModal() {
    document.getElementById('addOperationModal').classList.add('hidden');
    document.getElementById('addOperationForm').reset();
}

function filterOperations() {
    const category = document.getElementById('operationCategory').value;
    const operationSelect = document.getElementById('operationSelect');
    
    // Clear current options except the first one
    operationSelect.innerHTML = '<option value="">Select operation</option>';
    
    if (category) {
        const categoryOps = operationsData[category] || [];
        categoryOps.forEach(op => {
            const option = document.createElement('option');
            option.value = op.operation_id;
            option.textContent = op.operation_name;
            option.setAttribute('data-name', op.operation_name);
            option.setAttribute('data-category', category);
            operationSelect.appendChild(option);
        });
    } else {
        // Show all operations grouped
        Object.entries(operationsData).forEach(([cat, ops]) => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = cat;
            ops.forEach(op => {
                const option = document.createElement('option');
                option.value = op.operation_id;
                option.textContent = op.operation_name;
                option.setAttribute('data-name', op.operation_name);
                option.setAttribute('data-category', cat);
                optgroup.appendChild(option);
            });
            operationSelect.appendChild(optgroup);
        });
    }
}

document.getElementById('addOperationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const operationId = document.getElementById('operationSelect').value;
    const machineId = document.getElementById('machineSelect').value;
    const smv = parseFloat(document.getElementById('smvInput').value);
    const description = document.getElementById('descriptionInput').value;
    
    if (!operationId || !machineId || !smv) {
        alert('Please fill all required fields');
        return;
    }
    
    // Get selected option details
    const operationOption = document.getElementById('operationSelect').selectedOptions[0];
    const machineOption = document.getElementById('machineSelect').selectedOptions[0];
    
    const operation = {
        id: ++operationCounter,
        operation_id: operationId,
        operation_name: operationOption.getAttribute('data-name'),
        machine_type_id: machineId,
        machine_name: machineOption.getAttribute('data-name'),
        smv: smv,
        description: description
    };
    
    operations.push(operation);
    renderOperations();
    closeAddOperationModal();
});

function renderOperations() {
    const tbody = document.getElementById('operationsBody');
    const emptyState = document.getElementById('emptyState');
    
    if (operations.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');
        updateTotals();
        return;
    }
    
    emptyState.classList.add('hidden');
    
    tbody.innerHTML = operations.map((op, index) => `
        <tr class="border-b border-gray-200">
            <td class="px-4 py-2 text-sm text-gray-500">${index + 1}</td>
            <td class="px-4 py-2">
                <div class="text-sm font-medium text-gray-900">${op.operation_name}</div>
            </td>
            <td class="px-4 py-2">
                <div class="text-sm text-gray-900">${op.machine_name}</div>
            </td>
            <td class="px-4 py-2">
                <input type="number" step="0.001" min="0" value="${op.smv}" 
                       onchange="updateOperationSmv(${op.id}, this.value)"
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
            </td>
            <td class="px-4 py-2">
                <input type="text" value="${op.description || ''}" maxlength="255"
                       onchange="updateOperationDescription(${op.id}, this.value)"
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500"
                       placeholder="Optional description">
            </td>
            <td class="px-4 py-2 text-center">
                <div class="flex justify-center space-x-1">
                    ${index > 0 ? `<button type="button" onclick="moveOperation(${op.id}, 'up')" class="text-blue-600 hover:text-blue-800" title="Move Up">↑</button>` : ''}
                    ${index < operations.length - 1 ? `<button type="button" onclick="moveOperation(${op.id}, 'down')" class="text-blue-600 hover:text-blue-800" title="Move Down">↓</button>` : ''}
                    <button type="button" onclick="removeOperation(${op.id})" class="text-red-600 hover:text-red-800 ml-2" title="Remove">×</button>
                </div>
            </td>
        </tr>
    `).join('');
    
    updateTotals();
}

function updateOperationSmv(id, smv) {
    const operation = operations.find(op => op.id === id);
    if (operation) {
        operation.smv = parseFloat(smv) || 0;
        updateTotals();
    }
}

function updateOperationDescription(id, description) {
    const operation = operations.find(op => op.id === id);
    if (operation) {
        operation.description = description;
    }
}

function removeOperation(id) {
    operations = operations.filter(op => op.id !== id);
    renderOperations();
}

function moveOperation(id, direction) {
    const index = operations.findIndex(op => op.id === id);
    if (direction === 'up' && index > 0) {
        [operations[index], operations[index - 1]] = [operations[index - 1], operations[index]];
    } else if (direction === 'down' && index < operations.length - 1) {
        [operations[index], operations[index + 1]] = [operations[index + 1], operations[index]];
    }
    renderOperations();
}

function updateTotals() {
    const totalSmv = operations.reduce((sum, op) => sum + op.smv, 0);
    const targetPerHour = totalSmv > 0 ? Math.round(60 / totalSmv) : 0;
    
    document.getElementById('totalSmv').textContent = totalSmv.toFixed(3);
    document.getElementById('targetPerHour').textContent = targetPerHour;
    
    // Update form data
    document.getElementById('operationsData').value = JSON.stringify(operations);
    
    // Enable/disable save button
    const saveButton = document.getElementById('saveButton');
    const styleSelected = document.getElementById('styleSelect').value;
    saveButton.disabled = !styleSelected || operations.length === 0;
}

document.getElementById('styleSelect').addEventListener('change', updateTotals);

// Close modal on outside click
document.addEventListener('click', function(event) {
    if (event.target.id === 'addOperationModal') {
        closeAddOperationModal();
    }
});

// Initialize empty state
renderOperations();
</script>

<?php include '../includes/footer.php'; ?>