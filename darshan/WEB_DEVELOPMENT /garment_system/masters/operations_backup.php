<?php
$pageTitle = 'Operations';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();
$message = '';
$messageType = 'success';

// Safe current role resolution to avoid undefined index warnings
$userRole = null;
try {
    $current = function_exists('getCurrentUser') ? getCurrentUser() : null;
    if (!empty($current) && isset($current['role'])) {
        $userRole = $current['role'];
    } elseif (isset($_SESSION) && isset($_SESSION['role'])) {
        $userRole = $_SESSION['role'];
    }
} catch (Throwable $e) {
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Get machine types for dropdown
$machineTypes = $db->getAll('machine_types', ['is_active' => 1]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && true) {
        $code = sanitizeInput($_POST['code']);
        $name = sanitizeInput($_POST['name']);
        $category = sanitizeInput($_POST['category']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $defaultMachineTypeId = !empty($_POST['default_machine_type_id']) ? intval($_POST['default_machine_type_id']) : null;
        $standardSmv = floatval($_POST['standard_smv'] ?? 0);
        $seamLengthCm = !empty($_POST['seam_length_cm']) ? floatval($_POST['seam_length_cm']) : null;
        $seamType = sanitizeInput($_POST['seam_type'] ?? '');
        $folderAttachment = sanitizeInput($_POST['folder_attachment'] ?? '');
        $presserFoot = sanitizeInput($_POST['presser_foot'] ?? '');
        $needleType = sanitizeInput($_POST['needle_type'] ?? '');
        $operatorGrade = sanitizeInput($_POST['operator_grade'] ?? '');
        $qualityParameters = sanitizeInput($_POST['quality_parameters'] ?? '');
        $machineSpeed = !empty($_POST['machine_speed']) ? intval($_POST['machine_speed']) : null;
        $stitchPerInch = !empty($_POST['stitch_per_inch']) ? floatval($_POST['stitch_per_inch']) : null;
        $operationCost = !empty($_POST['operation_cost']) ? floatval($_POST['operation_cost']) : null;
        
        if (empty($name)) {
            $message = 'Operation name is required.';
            $messageType = 'error';
        } else {
            // Check if code already exists (if provided)
            if (!empty($code)) {
                $existing = $db->queryOne("SELECT operation_id FROM operations WHERE code = ?", [$code]);
                if ($existing) {
                    $message = 'Operation code already exists.';
                    $messageType = 'error';
                } else {
                    $result = $db->insert('operations', [
                        'code' => $code,
                        'name' => $name,
                        'category' => $category,
                        'description' => $description,
                        'default_machine_type_id' => $defaultMachineTypeId,
                        'standard_smv' => $standardSmv,
                        'seam_length_cm' => $seamLengthCm,
                        'seam_type' => $seamType,
                        'folder_attachment' => $folderAttachment,
                        'presser_foot' => $presserFoot,
                        'needle_type' => $needleType,
                        'operator_grade' => $operatorGrade,
                        'quality_parameters' => $qualityParameters,
                        'machine_speed' => $machineSpeed,
                        'stitch_per_inch' => $stitchPerInch,
                        'operation_cost' => $operationCost,
                        'is_active' => 1
                    ]);
                    
                    if ($result) {
                        $message = 'Operation created successfully.';
                        logActivity('operations', $result, 'CREATE');
                    } else {
                        $message = 'Error creating operation.';
                        $messageType = 'error';
                    }
                }
            } else {
                $result = $db->insert('operations', [
                    'name' => $name,
                    'category' => $category,
                    'description' => $description,
                    'default_machine_type_id' => $defaultMachineTypeId,
                    'standard_smv' => $standardSmv,
                    'seam_length_cm' => $seamLengthCm,
                    'seam_type' => $seamType,
                    'folder_attachment' => $folderAttachment,
                    'presser_foot' => $presserFoot,
                    'needle_type' => $needleType,
                    'operator_grade' => $operatorGrade,
                    'quality_parameters' => $qualityParameters,
                    'machine_speed' => $machineSpeed,
                    'stitch_per_inch' => $stitchPerInch,
                    'operation_cost' => $operationCost,
                    'is_active' => 1
                ]);
                
                if ($result) {
                    $message = 'Operation created successfully.';
                    logActivity('operations', $result, 'CREATE');
                } else {
                    $message = 'Error creating operation.';
                    $messageType = 'error';
                }
            }
        }
    }
    
    if ($action === 'update' && true) {
        $id = intval($_POST['id']);
        $name = sanitizeInput($_POST['name']);
        $category = sanitizeInput($_POST['category']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $defaultMachineTypeId = !empty($_POST['default_machine_type_id']) ? intval($_POST['default_machine_type_id']) : null;
        $standardSmv = floatval($_POST['standard_smv'] ?? 0);
        $seamLengthCm = !empty($_POST['seam_length_cm']) ? floatval($_POST['seam_length_cm']) : null;
        $seamType = sanitizeInput($_POST['seam_type'] ?? '');
        $folderAttachment = sanitizeInput($_POST['folder_attachment'] ?? '');
        $presserFoot = sanitizeInput($_POST['presser_foot'] ?? '');
        $needleType = sanitizeInput($_POST['needle_type'] ?? '');
        $operatorGrade = sanitizeInput($_POST['operator_grade'] ?? '');
        $qualityParameters = sanitizeInput($_POST['quality_parameters'] ?? '');
        $machineSpeed = !empty($_POST['machine_speed']) ? intval($_POST['machine_speed']) : null;
        $stitchPerInch = !empty($_POST['stitch_per_inch']) ? floatval($_POST['stitch_per_inch']) : null;
        $operationCost = !empty($_POST['operation_cost']) ? floatval($_POST['operation_cost']) : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $message = 'Operation name is required.';
            $messageType = 'error';
        } else {
            $oldData = $db->getById('operations', $id);
            $result = $db->update('operations', $id, [
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'default_machine_type_id' => $defaultMachineTypeId,
                'standard_smv' => $standardSmv,
                'seam_length_cm' => $seamLengthCm,
                'seam_type' => $seamType,
                'folder_attachment' => $folderAttachment,
                'presser_foot' => $presserFoot,
                'needle_type' => $needleType,
                'operator_grade' => $operatorGrade,
                'quality_parameters' => $qualityParameters,
                'machine_speed' => $machineSpeed,
                'stitch_per_inch' => $stitchPerInch,
                'operation_cost' => $operationCost,
                'is_active' => $isActive
            ]);
            
            if ($result) {
                $message = 'Operation updated successfully.';
                logActivity('operations', $id, 'UPDATE', $oldData);
            } else {
                $message = 'Error updating operation.';
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'delete' && true) {
        $id = intval($_POST['id']);
        
        // Check if operation is used in other tables
        $usageCheck = $db->queryOne("
            SELECT COUNT(*) as count FROM (
                SELECT operation_id FROM ob_items WHERE operation_id = ?
                UNION ALL
                SELECT operation_id FROM tcr_items WHERE operation_id = ?
                UNION ALL
                SELECT operation_id FROM method_elements WHERE operation_id = ?
            ) as usage
        ", [$id, $id, $id]);
        
        if ($usageCheck && $usageCheck['count'] > 0) {
            $message = 'Cannot delete operation. It is being used in operation breakdowns, TCR records, or method analysis.';
            $messageType = 'error';
        } else {
            $oldData = $db->getById('operations', $id);
            $result = $db->delete('operations', $id);
            
            if ($result) {
                $message = 'Operation deleted successfully.';
                logActivity('operations', $id, 'DELETE', $oldData);
            } else {
                $message = 'Error deleting operation.';
                $messageType = 'error';
            }
        }
    }
}

// Get all operations with machine type names
$operations = $db->query("
    SELECT o.*, m.name as machine_name 
    FROM operations o 
    LEFT JOIN machine_types m ON o.default_machine_type_id = m.machine_type_id 
    ORDER BY o.category ASC, o.name ASC
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
                        <h1 class="text-3xl font-bold text-gray-900">Operations</h1>
                        <p class="text-gray-600 mt-2">Manage sewing and assembly operations catalog</p>
                    </div>
                    <?php if (true): ?>
                    <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Operation
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

            <!-- Operations Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">All Operations</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMV</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seam Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Machine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <?php if (true): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($operations as $operation): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($operation['code'] ?: '—'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($operation['name']); ?></div>
                                    <?php if (!empty($operation['description'])): ?>
                                    <div class="text-xs text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($operation['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($operation['category'] ?: 'General'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($operation['standard_smv'], 4); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($operation['seam_type'] ?: '—'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($operation['machine_name'] ?: '—'); ?>
                                    <?php if (!empty($operation['machine_speed'])): ?>
                                    <div class="text-xs text-gray-400"><?php echo number_format($operation['machine_speed']); ?> RPM</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($operation['operation_cost'])): ?>
                                    <div class="text-sm text-gray-900">$<?php echo number_format($operation['operation_cost'], 4); ?></div>
                                    <?php else: ?>
                                    <div class="text-sm text-gray-400">—</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $operation['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $operation['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <?php if (true): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($operation)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <?php if (true): ?>
                                    <button onclick="confirmDelete(<?php echo $operation['operation_id']; ?>, '<?php echo htmlspecialchars($operation['name'], ENT_QUOTES); ?>')" 
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
    <div class="relative top-10 mx-auto p-5 border w-[800px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create Operation</h3>
            <form method="POST" id="createForm">
                <input type="hidden" name="action" value="create">
                
                <!-- Basic Information -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                        <input type="text" name="code" maxlength="20" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Optional operation code">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" name="name" required maxlength="100" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Operation name">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select category</option>
                            <option value="JOINING">Joining</option>
                            <option value="HEMMING">Hemming</option>
                            <option value="TOPSTITCH">Topstitch</option>
                            <option value="FINISHING">Finishing</option>
                            <option value="REINFORCEMENT">Reinforcement</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Standard SMV</label>
                        <input type="number" name="standard_smv" step="0.0001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Standard minutes value">
                    </div>
                </div>
                
                <!-- Technical Specifications -->
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seam Length (cm)</label>
                        <input type="number" name="seam_length_cm" step="0.1" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seam Type</label>
                        <select name="seam_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select type</option>
                            <option value="SS">Single Stitch</option>
                            <option value="OVERLOCK">Overlock</option>
                            <option value="HEMMING">Hemming</option>
                            <option value="PINSEAM">Pinseam</option>
                            <option value="FLATLOCK">Flatlock</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Operator Grade</label>
                        <select name="operator_grade" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select grade</option>
                            <option value="A">Grade A (Expert)</option>
                            <option value="B">Grade B (Skilled)</option>
                            <option value="C">Grade C (Helper)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Machine & Equipment -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine Type</label>
                        <select name="default_machine_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select machine type</option>
                            <?php foreach ($machineTypes as $machine): ?>
                            <option value="<?php echo $machine['machine_type_id']; ?>"><?php echo htmlspecialchars($machine['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine Speed (RPM)</label>
                        <input type="number" name="machine_speed" min="100" max="10000" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Presser Foot</label>
                        <input type="text" name="presser_foot" maxlength="50" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., P351, Normal">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Needle Type</label>
                        <input type="text" name="needle_type" maxlength="50" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="e.g., DPX11, DCX11">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stitch Per Inch</label>
                        <input type="number" name="stitch_per_inch" step="0.1" min="1" max="30" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Folder/Attachment</label>
                    <input type="text" name="folder_attachment" maxlength="100" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., Magnet Guide, Overlock Guide, F503">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="2" maxlength="500"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Operation description"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quality Parameters</label>
                        <textarea name="quality_parameters" rows="2" maxlength="500"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Quality specifications and requirements"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Operation Cost</label>
                        <input type="number" name="operation_cost" step="0.0001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Cost per operation">
                    </div>
                </div>
                
                <!-- Media Fields -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sketch Image URL</label>
                        <input type="url" name="sketch_image" maxlength="255" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="https://...">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Video URL</label>
                        <input type="url" name="video_url" maxlength="255" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="https://...">
                    </div>
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
    <div class="relative top-10 mx-auto p-5 border w-[800px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Operation</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="operation_id" id="editOperationId">
                
                <!-- Basic Information -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                        <input type="text" name="code" id="editCode" maxlength="20" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" name="name" id="editName" required maxlength="100" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" id="editCategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select category</option>
                            <option value="JOINING">Joining</option>
                            <option value="HEMMING">Hemming</option>
                            <option value="TOPSTITCH">Topstitch</option>
                            <option value="FINISHING">Finishing</option>
                            <option value="REINFORCEMENT">Reinforcement</option>
                            <option value="PRESSING">Pressing</option>
                            <option value="QC">Quality Control</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Standard SMV</label>
                        <input type="number" name="standard_smv" id="editStandardSmv" step="0.0001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <!-- Technical Specifications -->
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seam Length (cm)</label>
                        <input type="number" name="seam_length_cm" id="editSeamLength" step="0.1" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seam Type</label>
                        <select name="seam_type" id="editSeamType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select type</option>
                            <option value="SS">Single Stitch</option>
                            <option value="OVERLOCK">Overlock</option>
                            <option value="HEMMING">Hemming</option>
                            <option value="PINSEAM">Pinseam</option>
                            <option value="FLATLOCK">Flatlock</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Operator Grade</label>
                        <select name="operator_grade" id="editOperatorGrade" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select grade</option>
                            <option value="A">Grade A (Expert)</option>
                            <option value="B">Grade B (Skilled)</option>
                            <option value="C">Grade C (Helper)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Machine & Equipment -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine Type</label>
                        <select name="default_machine_type_id" id="editMachineType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select machine type</option>
                            <?php foreach ($machineTypes as $machine): ?>
                            <option value="<?php echo $machine['machine_type_id']; ?>"><?php echo htmlspecialchars($machine['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine Speed (RPM)</label>
                        <input type="number" name="machine_speed" id="editMachineSpeed" min="100" max="10000" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Presser Foot</label>
                        <input type="text" name="presser_foot" id="editPresserFoot" maxlength="50" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Needle Type</label>
                        <input type="text" name="needle_type" id="editNeedleType" maxlength="50" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stitch Per Inch</label>
                        <input type="number" name="stitch_per_inch" id="editStitchPerInch" step="0.1" min="1" max="30" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Folder/Attachment</label>
                    <input type="text" name="folder_attachment" id="editFolderAttachment" maxlength="100" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="editDescription" rows="2" maxlength="500"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quality Parameters</label>
                        <textarea name="quality_parameters" id="editQualityParameters" rows="2" maxlength="500"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Operation Cost</label>
                        <input type="number" name="operation_cost" id="editOperationCost" step="0.0001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <!-- Media Fields -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sketch Image URL</label>
                        <input type="url" name="sketch_image" id="editSketchImage" maxlength="255" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Video URL</label>
                        <input type="url" name="video_url" id="editVideoUrl" maxlength="255" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
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
            <h3 class="text-lg font-medium text-gray-900 mt-4">Delete Operation</h3>
            <p class="mt-2 px-7 py-3 text-sm text-gray-500">
                Are you sure you want to delete "<span id="deleteOperationName" class="font-medium"></span>"?
                This action cannot be undone.
            </p>
            <form id="deleteForm" method="POST" class="mt-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteOperationId">
                
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

function openEditModal(operation) {
    document.getElementById('editOperationId').value = operation.operation_id;
    document.getElementById('editCode').value = operation.code || '';
    document.getElementById('editName').value = operation.name;
    document.getElementById('editCategory').value = operation.category || '';
    document.getElementById('editStandardSmv').value = operation.standard_smv || '0';
    document.getElementById('editMachineType').value = operation.default_machine_type_id || '';
    
    // Technical specifications
    document.getElementById('editSeamLength').value = operation.seam_length_cm || '';
    document.getElementById('editSeamType').value = operation.seam_type || '';
    document.getElementById('editOperatorGrade').value = operation.operator_grade || '';
    
    // Machine & Equipment
    document.getElementById('editMachineSpeed').value = operation.machine_speed || '';
    document.getElementById('editPresserFoot').value = operation.presser_foot || '';
    document.getElementById('editNeedleType').value = operation.needle_type || '';
    document.getElementById('editStitchPerInch').value = operation.stitch_per_inch || '';
    document.getElementById('editFolderAttachment').value = operation.folder_attachment || '';
    
    // Content fields
    document.getElementById('editDescription').value = operation.description || '';
    document.getElementById('editQualityParameters').value = operation.quality_parameters || '';
    document.getElementById('editOperationCost').value = operation.operation_cost || '';
    
    // Media fields
    document.getElementById('editSketchImage').value = operation.sketch_image || '';
    document.getElementById('editVideoUrl').value = operation.video_url || '';
    
    document.getElementById('editActive').checked = operation.is_active == 1;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(operationId, operationName) {
    document.getElementById('deleteOperationId').value = operationId;
    document.getElementById('deleteOperationName').textContent = operationName;
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