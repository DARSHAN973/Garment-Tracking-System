<?php
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

$pageTitle = 'Capacity Analysis & Line Balancing';
$userRole = $_SESSION['role'] ?? 'viewer';
$db = new DatabaseHelper();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasPermission($userRole, 'capacity', 'write')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_scenario') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $target_production = intval($_POST['target_production']);
        $working_hours_per_day = floatval($_POST['working_hours_per_day']);
        $working_days_per_week = intval($_POST['working_days_per_week']);
        $efficiency_factor = floatval($_POST['efficiency_factor']);
        $created_by = $_SESSION['user_id'];
        
        if (!empty($name) && $target_production > 0) {
            try {
                $scenarioId = $db->insert('capacity_scenarios', [
                    'name' => $name,
                    'description' => $description,
                    'target_production' => $target_production,
                    'working_hours_per_day' => $working_hours_per_day,
                    'working_days_per_week' => $working_days_per_week,
                    'efficiency_factor' => $efficiency_factor,
                    'created_by' => $created_by
                ]);
                
                $_SESSION['success_message'] = "Capacity scenario '{$name}' created successfully!";
                header('Location: capacity_analysis.php');
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error creating scenario: " . $e->getMessage();
            }
        } else {
            $_SESSION['error_message'] = "Please provide valid scenario name and target production.";
        }
    }
    
    elseif ($action === 'update_scenario') {
        $scenario_id = intval($_POST['scenario_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $target_production = intval($_POST['target_production']);
        $working_hours_per_day = floatval($_POST['working_hours_per_day']);
        $working_days_per_week = intval($_POST['working_days_per_week']);
        $efficiency_factor = floatval($_POST['efficiency_factor']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($scenario_id > 0 && !empty($name)) {
            try {
                $db->update('capacity_scenarios', $scenario_id, [
                    'name' => $name,
                    'description' => $description,
                    'target_production' => $target_production,
                    'working_hours_per_day' => $working_hours_per_day,
                    'working_days_per_week' => $working_days_per_week,
                    'efficiency_factor' => $efficiency_factor,
                    'is_active' => $is_active
                ], 'scenario_id');
                
                $_SESSION['success_message'] = "Scenario updated successfully!";
                header('Location: capacity_analysis.php');
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error updating scenario: " . $e->getMessage();
            }
        }
    }
}

// Get all scenarios with user names
$scenarios = $db->query("
    SELECT cs.*, u.username as created_by_name 
    FROM capacity_scenarios cs 
    LEFT JOIN users u ON cs.created_by = u.user_id 
    ORDER BY cs.created_at DESC
");

// Get operations for dropdown
$operations = $db->getAll('operations', ['is_active' => 1], 'name ASC');

// Get machine types for dropdown  
$machineTypes = $db->getAll('machine_types', ['is_active' => 1], 'name ASC');

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Capacity Analysis & Line Balancing</h1>
                <p class="mt-2 text-sm text-gray-600">Design and optimize production line configurations for maximum efficiency</p>
            </div>
            
            <?php if (hasPermission($userRole, 'capacity', 'write')): ?>
            <button onclick="openCreateScenarioModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <i class="fas fa-plus mr-2"></i>New Scenario
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Scenarios</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($scenarios); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-industry text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Scenarios</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count(array_filter($scenarios, fn($s) => $s['is_active'])); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-cogs text-orange-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Available Operations</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($operations); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-tools text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Machine Types</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo count($machineTypes); ?></dd>
                        </dl>
                    </div>
                </div>
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

    <!-- Scenarios Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md mb-8">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Capacity Scenarios</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scenario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Production</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Working Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Efficiency</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($scenarios as $scenario): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($scenario['name']); ?></div>
                                <?php if (!empty($scenario['description'])): ?>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($scenario['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo number_format($scenario['target_production']); ?> units/day</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $scenario['working_hours_per_day']; ?>h × <?php echo $scenario['working_days_per_week']; ?> days</div>
                                <div class="text-sm text-gray-500"><?php echo $scenario['working_hours_per_day'] * $scenario['working_days_per_week']; ?>h/week</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo number_format($scenario['efficiency_factor'] * 100, 1); ?>%</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($scenario['created_by_name'] ?? 'Unknown'); ?>
                                <div class="text-xs text-gray-400"><?php echo date('M j, Y', strtotime($scenario['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $scenario['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $scenario['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">
                                <a href="line_config.php?scenario_id=<?php echo $scenario['scenario_id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900">Configure</a>
                                <?php if (hasPermission($userRole, 'capacity', 'write')): ?>
                                <button onclick="openEditScenarioModal(<?php echo htmlspecialchars(json_encode($scenario)); ?>)" 
                                        class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Scenario Modal -->
<?php if (hasPermission($userRole, 'capacity', 'write')): ?>
<div id="createScenarioModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create Capacity Scenario</h3>
            <form method="POST" id="createScenarioForm">
                <input type="hidden" name="action" value="create_scenario">
                
                <div class="grid grid-cols-1 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Scenario Name *</label>
                        <input type="text" name="name" required maxlength="100" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., T-Shirt Line A - Summer Collection">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" maxlength="500"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Describe the scenario objectives and requirements"></textarea>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Production/Day *</label>
                        <input type="number" name="target_production" required min="1" max="10000" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Working Hours/Day</label>
                        <input type="number" name="working_hours_per_day" step="0.5" min="1" max="24" value="8" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Working Days/Week</label>
                        <select name="working_days_per_week" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="5">5 Days (Mon-Fri)</option>
                            <option value="6">6 Days (Mon-Sat)</option>
                            <option value="7">7 Days (Full Week)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Line Efficiency Factor</label>
                        <input type="number" name="efficiency_factor" step="0.01" min="0.5" max="1" value="0.85" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">0.85 = 85% efficiency (recommended)</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateScenarioModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Create Scenario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Scenario Modal -->
<div id="editScenarioModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Capacity Scenario</h3>
            <form method="POST" id="editScenarioForm">
                <input type="hidden" name="action" value="update_scenario">
                <input type="hidden" name="scenario_id" id="editScenarioId">
                
                <div class="grid grid-cols-1 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Scenario Name *</label>
                        <input type="text" name="name" id="editScenarioName" required maxlength="100" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="editScenarioDescription" rows="3" maxlength="500"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Production/Day *</label>
                        <input type="number" name="target_production" id="editTargetProduction" required min="1" max="10000" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Working Hours/Day</label>
                        <input type="number" name="working_hours_per_day" id="editWorkingHours" step="0.5" min="1" max="24" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Working Days/Week</label>
                        <select name="working_days_per_week" id="editWorkingDays" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="5">5 Days (Mon-Fri)</option>
                            <option value="6">6 Days (Mon-Sat)</option>
                            <option value="7">7 Days (Full Week)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Line Efficiency Factor</label>
                        <input type="number" name="efficiency_factor" id="editEfficiencyFactor" step="0.01" min="0.5" max="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="editScenarioActive" class="mr-2">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditScenarioModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update Scenario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function openCreateScenarioModal() {
    document.getElementById('createScenarioModal').classList.remove('hidden');
}

function closeCreateScenarioModal() {
    document.getElementById('createScenarioModal').classList.add('hidden');
    document.getElementById('createScenarioForm').reset();
}

function openEditScenarioModal(scenario) {
    document.getElementById('editScenarioId').value = scenario.scenario_id;
    document.getElementById('editScenarioName').value = scenario.name;
    document.getElementById('editScenarioDescription').value = scenario.description || '';
    document.getElementById('editTargetProduction').value = scenario.target_production;
    document.getElementById('editWorkingHours').value = scenario.working_hours_per_day;
    document.getElementById('editWorkingDays').value = scenario.working_days_per_week;
    document.getElementById('editEfficiencyFactor').value = scenario.efficiency_factor;
    document.getElementById('editScenarioActive').checked = scenario.is_active == 1;
    document.getElementById('editScenarioModal').classList.remove('hidden');
}

function closeEditScenarioModal() {
    document.getElementById('editScenarioModal').classList.add('hidden');
}

// Close modals on outside click
document.addEventListener('click', function(event) {
    if (event.target.id === 'createScenarioModal') closeCreateScenarioModal();
    if (event.target.id === 'editScenarioModal') closeEditScenarioModal();
});
</script>

<?php include '../includes/footer.php'; ?>