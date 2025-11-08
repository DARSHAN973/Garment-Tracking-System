<?php
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

$pageTitle = 'Line Configuration & Analysis';
$userRole = $_SESSION['role'] ?? 'viewer';
$db = new DatabaseHelper();

// Get scenario ID from URL
$scenario_id = intval($_GET['scenario_id'] ?? 0);
if ($scenario_id <= 0) {
    $_SESSION['error_message'] = "Invalid scenario ID.";
    header('Location: capacity_analysis.php');
    exit;
}

// Get scenario details
$scenario = $db->getById('capacity_scenarios', $scenario_id, 'scenario_id');
if (!$scenario) {
    $_SESSION['error_message'] = "Scenario not found.";
    header('Location: capacity_analysis.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && true) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_line_config') {
        $line_name = trim($_POST['line_name']);
        $total_operators = intval($_POST['total_operators']);
        $helper_operators = intval($_POST['helper_operators']);
        $line_efficiency = floatval($_POST['line_efficiency']);
        
        if (!empty($line_name) && $total_operators > 0) {
            try {
                $lineConfigId = $db->insert('line_configurations', [
                    'scenario_id' => $scenario_id,
                    'line_name' => $line_name,
                    'total_operators' => $total_operators,
                    'helper_operators' => $helper_operators,
                    'line_efficiency' => $line_efficiency
                ]);
                
                $_SESSION['success_message'] = "Line configuration created successfully!";
                header('Location: line_config.php?scenario_id=' . $scenario_id);
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error creating line configuration: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'assign_operations') {
        $line_config_id = intval($_POST['line_config_id']);
        $operations = $_POST['operations'] ?? [];
        
        if ($line_config_id > 0 && !empty($operations)) {
            try {
                $db->beginTransaction();
                
                // Clear existing assignments
                $db->query("DELETE FROM operation_assignments WHERE line_config_id = ?", [$line_config_id]);
                
                // Add new assignments
                foreach ($operations as $index => $op) {
                    $operation_id = intval($op['operation_id']);
                    $station_number = intval($op['station_number']);
                    $assigned_operators = intval($op['assigned_operators']);
                    $operator_grade = $op['operator_grade'];
                    $allocated_smv = floatval($op['allocated_smv']);
                    $sequence_order = $index + 1;
                    
                    if ($operation_id > 0) {
                        $db->insert('operation_assignments', [
                            'line_config_id' => $line_config_id,
                            'operation_id' => $operation_id,
                            'station_number' => $station_number,
                            'assigned_operators' => $assigned_operators,
                            'operator_grade' => $operator_grade,
                            'allocated_smv' => $allocated_smv,
                            'sequence_order' => $sequence_order
                        ]);
                    }
                }
                
                $db->commit();
                $_SESSION['success_message'] = "Operations assigned successfully!";
                header('Location: line_config.php?scenario_id=' . $scenario_id);
                exit;
            } catch (Exception $e) {
                $db->rollback();
                $_SESSION['error_message'] = "Error assigning operations: " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'calculate_capacity') {
        $line_config_id = intval($_POST['line_config_id']);
        
        if ($line_config_id > 0) {
            try {
                // Get line configuration
                $lineConfig = $db->getById('line_configurations', $line_config_id, 'line_config_id');
                
                // Get operation assignments with SMV
                $assignments = $db->query("
                    SELECT oa.*, o.name as operation_name, o.standard_smv 
                    FROM operation_assignments oa 
                    JOIN operations o ON oa.operation_id = o.operation_id 
                    WHERE oa.line_config_id = ? 
                    ORDER BY oa.sequence_order
                ", [$line_config_id]);
                
                if (!empty($assignments)) {
                    // Calculate capacity metrics
                    $total_smv = array_sum(array_column($assignments, 'allocated_smv'));
                    $total_operators = $lineConfig['total_operators'];
                    $working_minutes = $scenario['working_hours_per_day'] * 60;
                    $efficiency = $lineConfig['line_efficiency'];
                    
                    // Find bottleneck (highest cycle time)
                    $bottleneck_time = 0;
                    $bottleneck_station = null;
                    $station_times = [];
                    
                    foreach ($assignments as $assignment) {
                        $station = $assignment['station_number'];
                        if (!isset($station_times[$station])) {
                            $station_times[$station] = 0;
                        }
                        $cycle_time = $assignment['allocated_smv'] / $assignment['assigned_operators'];
                        $station_times[$station] += $cycle_time;
                        
                        if ($station_times[$station] > $bottleneck_time) {
                            $bottleneck_time = $station_times[$station];
                            $bottleneck_station = $station;
                        }
                    }
                    
                    // Calculate outputs
                    $theoretical_output = floor($working_minutes / $bottleneck_time);
                    $actual_output = floor($theoretical_output * $efficiency);
                    
                    // Calculate balance efficiency
                    $sum_station_times = array_sum($station_times);
                    $balance_efficiency = ($sum_station_times > 0) ? ($bottleneck_time * count($station_times)) / $sum_station_times : 0;
                    
                    // Calculate smoothness index
                    $smoothness_index = sqrt(array_sum(array_map(function($time) use ($bottleneck_time) {
                        return pow($time - $bottleneck_time, 2);
                    }, $station_times)) / count($station_times));
                    
                    // Update line configuration
                    $db->update('line_configurations', $line_config_id, [
                        'bottle_neck_time' => $bottleneck_time,
                        'cycle_time' => $total_smv / $total_operators,
                        'theoretical_output' => $theoretical_output,
                        'actual_output' => $actual_output
                    ], 'line_config_id');
                    
                    // Save calculation results
                    $db->insert('capacity_calculations', [
                        'scenario_id' => $scenario_id,
                        'total_smv' => $total_smv,
                        'total_operators' => $total_operators,
                        'theoretical_output' => $theoretical_output,
                        'actual_output' => $actual_output,
                        'line_efficiency' => $efficiency,
                        'bottleneck_station' => $bottleneck_station,
                        'bottleneck_time' => $bottleneck_time,
                        'balance_efficiency' => $balance_efficiency,
                        'smoothness_index' => $smoothness_index
                    ]);
                    
                    $_SESSION['success_message'] = "Capacity calculation completed successfully!";
                } else {
                    $_SESSION['error_message'] = "No operations assigned to calculate capacity.";
                }
                
                header('Location: line_config.php?scenario_id=' . $scenario_id);
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error calculating capacity: " . $e->getMessage();
            }
        }
    }
}

// Get line configurations for this scenario
$lineConfigs = $db->query("
    SELECT * FROM line_configurations 
    WHERE scenario_id = ? 
    ORDER BY created_at DESC
", [$scenario_id]);

// Get operations for dropdown
$operations = $db->query("
    SELECT o.*, mt.name as machine_name 
    FROM operations o 
    LEFT JOIN machine_types mt ON o.default_machine_type_id = mt.machine_type_id 
    WHERE o.is_active = 1 
    ORDER BY o.name
");

// Get latest calculations
$latestCalculations = $db->query("
    SELECT * FROM capacity_calculations 
    WHERE scenario_id = ? 
    ORDER BY calculated_at DESC 
    LIMIT 10
", [$scenario_id]);

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
                    <a href="capacity_analysis.php" class="hover:text-gray-700">Capacity Analysis</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900">Line Configuration</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($scenario['name']); ?></h1>
                <p class="mt-2 text-sm text-gray-600">Configure production lines and assign operations for capacity analysis</p>
            </div>
            
            <?php if (true): ?>
            <button onclick="openCreateLineConfigModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-plus mr-2"></i>New Line Configuration
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scenario Info Card -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-100 rounded-lg p-6 mb-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <div class="text-sm font-medium text-gray-600">Target Production</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo number_format($scenario['target_production']); ?></div>
                <div class="text-sm text-gray-600">units/day</div>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-600">Working Schedule</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo $scenario['working_hours_per_day']; ?>h</div>
                <div class="text-sm text-gray-600"><?php echo $scenario['working_days_per_week']; ?> days/week</div>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-600">Efficiency Factor</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo number_format($scenario['efficiency_factor'] * 100, 1); ?>%</div>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-600">Line Configurations</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo count($lineConfigs); ?></div>
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

    <!-- Line Configurations -->
    <div class="space-y-8">
        <?php foreach ($lineConfigs as $lineConfig): ?>
        <?php
        // Get assignments for this line
        $assignments = $db->query("
            SELECT oa.*, o.name as operation_name, o.standard_smv, mt.name as machine_name
            FROM operation_assignments oa 
            JOIN operations o ON oa.operation_id = o.operation_id 
            LEFT JOIN machine_types mt ON o.default_machine_type_id = mt.machine_type_id
            WHERE oa.line_config_id = ? 
            ORDER BY oa.sequence_order
        ", [$lineConfig['line_config_id']]);
        ?>
        
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($lineConfig['line_name']); ?></h3>
                        <p class="text-sm text-gray-500">
                            <?php echo $lineConfig['total_operators']; ?> operators 
                            (<?php echo $lineConfig['helper_operators']; ?> helpers) • 
                            <?php echo number_format($lineConfig['line_efficiency'] * 100, 1); ?>% efficiency
                        </p>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <?php if (true): ?>
                        <button onclick="openAssignOperationsModal(<?php echo $lineConfig['line_config_id']; ?>)" 
                                class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                            Assign Operations
                        </button>
                        
                        <?php if (!empty($assignments)): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="calculate_capacity">
                            <input type="hidden" name="line_config_id" value="<?php echo $lineConfig['line_config_id']; ?>">
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Calculate Capacity
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4">
                <?php if (!empty($assignments)): ?>
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Operation Assignments</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Seq</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Operation</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Station</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SMV</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Operators</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($assignments as $assignment): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo $assignment['sequence_order']; ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($assignment['operation_name']); ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-900">Station <?php echo $assignment['station_number']; ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo number_format($assignment['allocated_smv'], 4); ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo $assignment['assigned_operators']; ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-900">Grade <?php echo $assignment['operator_grade']; ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-500"><?php echo htmlspecialchars($assignment['machine_name'] ?? '—'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Capacity Results -->
                <?php if ($lineConfig['theoretical_output']): ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 bg-gray-50 rounded-lg p-4">
                    <div>
                        <div class="text-sm font-medium text-gray-600">Theoretical Output</div>
                        <div class="text-xl font-bold text-gray-900"><?php echo number_format($lineConfig['theoretical_output']); ?></div>
                        <div class="text-sm text-gray-500">units/day</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-600">Actual Output</div>
                        <div class="text-xl font-bold text-green-600"><?php echo number_format($lineConfig['actual_output']); ?></div>
                        <div class="text-sm text-gray-500">units/day</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-600">Bottleneck Time</div>
                        <div class="text-xl font-bold text-red-600"><?php echo number_format($lineConfig['bottle_neck_time'], 2); ?></div>
                        <div class="text-sm text-gray-500">minutes</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-600">Cycle Time</div>
                        <div class="text-xl font-bold text-gray-900"><?php echo number_format($lineConfig['cycle_time'], 2); ?></div>
                        <div class="text-sm text-gray-500">minutes</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-calculator text-4xl mb-4"></i>
                    <p>No capacity calculations available. Assign operations and calculate to see results.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($lineConfigs)): ?>
        <div class="text-center py-12">
            <i class="fas fa-industry text-6xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Line Configurations</h3>
            <p class="text-gray-500 mb-6">Create your first line configuration to start capacity analysis.</p>
            <?php if (true): ?>
            <button onclick="openCreateLineConfigModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                Create Line Configuration
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Line Configuration Modal -->
<?php if (true): ?>
<div id="createLineConfigModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create Line Configuration</h3>
            <form method="POST" id="createLineConfigForm">
                <input type="hidden" name="action" value="create_line_config">
                
                <div class="grid grid-cols-1 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Line Name *</label>
                        <input type="text" name="line_name" required maxlength="50" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., Production Line A">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Total Operators *</label>
                        <input type="number" name="total_operators" required min="1" max="100" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="20">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Helper Operators</label>
                        <input type="number" name="helper_operators" min="0" max="50" value="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="2">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Line Efficiency</label>
                        <input type="number" name="line_efficiency" step="0.01" min="0.5" max="1" value="0.85" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">0.85 = 85% line efficiency (recommended)</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateLineConfigModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Operations Modal -->
<div id="assignOperationsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-5 mx-auto p-5 border w-[900px] shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Operations to Line</h3>
            <form method="POST" id="assignOperationsForm">
                <input type="hidden" name="action" value="assign_operations">
                <input type="hidden" name="line_config_id" id="assignLineConfigId">
                
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-3">
                        <label class="block text-sm font-medium text-gray-700">Operation Assignments</label>
                        <button type="button" onclick="addOperationRow()" 
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i>Add Operation
                        </button>
                    </div>
                    
                    <div id="operationsContainer">
                        <!-- Operation rows will be added dynamically -->
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignOperationsModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Save Assignments
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let operationRowIndex = 0;

function openCreateLineConfigModal() {
    document.getElementById('createLineConfigModal').classList.remove('hidden');
}

function closeCreateLineConfigModal() {
    document.getElementById('createLineConfigModal').classList.add('hidden');
    document.getElementById('createLineConfigForm').reset();
}

function openAssignOperationsModal(lineConfigId) {
    document.getElementById('assignLineConfigId').value = lineConfigId;
    document.getElementById('operationsContainer').innerHTML = '';
    operationRowIndex = 0;
    addOperationRow();
    document.getElementById('assignOperationsModal').classList.remove('hidden');
}

function closeAssignOperationsModal() {
    document.getElementById('assignOperationsModal').classList.add('hidden');
}

function addOperationRow() {
    const container = document.getElementById('operationsContainer');
    const rowHTML = `
        <div class="operation-row grid grid-cols-7 gap-3 mb-3 items-center border rounded-lg p-3 bg-gray-50">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Operation</label>
                <select name="operations[${operationRowIndex}][operation_id]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                    <option value="">Select Operation</option>
                    <?php foreach ($operations as $op): ?>
                    <option value="<?php echo $op['operation_id']; ?>" data-smv="<?php echo $op['standard_smv']; ?>">
                        <?php echo htmlspecialchars($op['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Station #</label>
                <input type="number" name="operations[${operationRowIndex}][station_number]" min="1" max="50" value="${operationRowIndex + 1}" 
                       class="w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Operators</label>
                <input type="number" name="operations[${operationRowIndex}][assigned_operators]" min="1" max="10" value="1" 
                       class="w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Grade</label>
                <select name="operations[${operationRowIndex}][operator_grade]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                    <option value="A">Grade A</option>
                    <option value="B" selected>Grade B</option>
                    <option value="C">Grade C</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">SMV</label>
                <input type="number" name="operations[${operationRowIndex}][allocated_smv]" step="0.0001" min="0" 
                       class="smv-input w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">&nbsp;</label>
                <button type="button" onclick="removeOperationRow(this)" 
                        class="text-red-600 hover:text-red-800 text-sm px-2 py-1">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', rowHTML);
    
    // Add event listener to auto-fill SMV when operation is selected
    const lastRow = container.lastElementChild;
    const operationSelect = lastRow.querySelector('select[name*="[operation_id]"]');
    const smvInput = lastRow.querySelector('.smv-input');
    
    operationSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const smv = selectedOption.getAttribute('data-smv');
        if (smv) {
            smvInput.value = smv;
        }
    });
    
    operationRowIndex++;
}

function removeOperationRow(button) {
    const row = button.closest('.operation-row');
    row.remove();
}

// Close modals on outside click
document.addEventListener('click', function(event) {
    if (event.target.id === 'createLineConfigModal') closeCreateLineConfigModal();
    if (event.target.id === 'assignOperationsModal') closeAssignOperationsModal();
});
</script>

<?php include '../includes/footer.php'; ?>