<?php
$pageTitle = 'Machine Types';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Check permissions
requirePermission('masters', 'read');

$db = new DatabaseHelper();

// Handle form submissions
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && hasPermission($_SESSION['role'], 'masters', 'write')) {
        $code = sanitizeInput($_POST['code']);
        $name = sanitizeInput($_POST['name']);
        
        if (empty($code) || empty($name)) {
            $message = 'Code and Name are required.';
            $messageType = 'error';
        } else {
            // Check if code already exists
            $existing = $db->queryOne("SELECT machine_type_id FROM machine_types WHERE code = ?", [$code]);
            if ($existing) {
                $message = 'Machine Type code already exists.';
                $messageType = 'error';
            } else {
                $result = $db->insert('machine_types', [
                    'code' => $code,
                    'name' => $name,
                    'is_active' => 1
                ]);
                
                if ($result) {
                    $message = 'Machine Type created successfully.';
                    logActivity('machine_types', $result, 'CREATE', null, ['code' => $code, 'name' => $name]);
                } else {
                    $message = 'Error creating machine type.';
                    $messageType = 'error';
                }
            }
        }
    }
    
    if ($action === 'update' && hasPermission($_SESSION['role'], 'masters', 'write')) {
        $id = intval($_POST['id']);
        $name = sanitizeInput($_POST['name']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $message = 'Name is required.';
            $messageType = 'error';
        } else {
            $oldData = $db->getById('machine_types', $id);
            $result = $db->update('machine_types', $id, [
                'name' => $name,
                'is_active' => $isActive
            ]);
            
            if ($result) {
                $message = 'Machine Type updated successfully.';
                logActivity('machine_types', $id, 'UPDATE', $oldData, ['name' => $name, 'is_active' => $isActive]);
            } else {
                $message = 'Error updating machine type.';
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'delete' && hasPermission($_SESSION['role'], 'masters', 'delete')) {
        $id = intval($_POST['id']);
        
        // Check if machine type is used in other tables
        $usageCheck = $db->queryOne("
            SELECT COUNT(*) as count FROM (
                SELECT machine_type_id FROM operations WHERE default_machine_type_id = ?
                UNION ALL
                SELECT machine_type_id FROM thread_factors WHERE machine_type_id = ?
                UNION ALL
                SELECT machine_type_id FROM line_configurations WHERE machine_type_id = ?
            ) as usage
        ", [$id, $id, $id]);
        
        if ($usageCheck && $usageCheck['count'] > 0) {
            $message = 'Cannot delete machine type. It is being used in operations, thread factors, or line configurations.';
            $messageType = 'error';
        } else {
            $oldData = $db->getById('machine_types', $id);
            $result = $db->delete('machine_types', $id);
            
            if ($result) {
                $message = 'Machine Type deleted successfully.';
                logActivity('machine_types', $id, 'DELETE', $oldData);
            } else {
                $message = 'Error deleting machine type.';
                $messageType = 'error';
            }
        }
    }
}

// Get all machine types
$machineTypes = $db->getAll('machine_types', []);

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
                        <h1 class="text-3xl font-bold text-gray-900">Machine Types</h1>
                        <p class="text-gray-600 mt-2">Manage sewing and finishing machine types</p>
                    </div>
                    <?php if (hasPermission($_SESSION['role'], 'masters', 'write')): ?>
                    <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Machine Type
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

            <!-- Machine Types Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">All Machine Types</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <?php if (hasPermission($_SESSION['role'], 'masters', 'write')): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($machineTypes as $machine): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($machine['code']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($machine['name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $machine['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $machine['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDateTime($machine['created_at']); ?>
                                </td>
                                <?php if (hasPermission($_SESSION['role'], 'masters', 'write')): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($machine)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <a href="../masters/thread_factors.php?machine_id=<?php echo $machine['machine_type_id']; ?>" 
                                       class="text-green-600 hover:text-green-900 mr-3">Thread Factors</a>
                                    <?php if (hasPermission($_SESSION['role'], 'masters', 'delete')): ?>
                                    <button onclick="confirmDelete(<?php echo $machine['machine_type_id']; ?>, '<?php echo htmlspecialchars($machine['name'], ENT_QUOTES); ?>')" 
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
<?php if (hasPermission($_SESSION['role'], 'masters', 'write')): ?>
<div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create Machine Type</h3>
            <form method="POST" id="createForm">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Code *</label>
                    <input type="text" name="code" required maxlength="20" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., SNLS, 3-TH O/L">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" required maxlength="100" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Machine type name">
                </div>
                
                <div class="flex justify-end space-x-3">
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
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Machine Type</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                    <input type="text" id="editCode" readonly 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" id="editName" required maxlength="100" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="editActive" class="mr-2">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
                
                <div class="flex justify-end space-x-3">
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
            <h3 class="text-lg font-medium text-gray-900 mt-4">Delete Machine Type</h3>
            <p class="mt-2 px-7 py-3 text-sm text-gray-500">
                Are you sure you want to delete "<span id="deleteMachineTypeName" class="font-medium"></span>"?
                This action cannot be undone.
            </p>
            <form id="deleteForm" method="POST" class="mt-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteMachineTypeId">
                
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

function openEditModal(machine) {
    document.getElementById('editId').value = machine.machine_type_id;
    document.getElementById('editCode').value = machine.code;
    document.getElementById('editName').value = machine.name;
    document.getElementById('editActive').checked = machine.is_active == 1;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(machineTypeId, machineTypeName) {
    document.getElementById('deleteMachineTypeId').value = machineTypeId;
    document.getElementById('deleteMachineTypeName').textContent = machineTypeName;
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