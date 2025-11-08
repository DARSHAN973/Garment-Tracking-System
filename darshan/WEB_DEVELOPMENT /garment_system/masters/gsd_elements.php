<?php
$pageTitle = 'GSD Elements';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();
$message = '';
$messageType = 'success';

// Safe role resolution to prevent undefined array key warnings
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && true) {
        $code = sanitizeInput($_POST['code']);
        $category = sanitizeInput($_POST['category']);
        $description = sanitizeInput($_POST['description']);
        $stdTimeSec = floatval($_POST['std_time_sec']);
        $condLen5Sec = floatval($_POST['cond_len_5_sec']);
        $condLen15Sec = floatval($_POST['cond_len_15_sec']);
        $condLen30Sec = floatval($_POST['cond_len_30_sec']);
        $condLen45Sec = floatval($_POST['cond_len_45_sec']);
        $condLen80Sec = floatval($_POST['cond_len_80_sec']);
        $shortTimeSec = floatval($_POST['short_time_sec']);
        $longTimeSec = floatval($_POST['long_time_sec']);
        
        if (empty($code) || empty($description)) {
            $message = 'Code and Description are required.';
            $messageType = 'error';
        } elseif ($stdTimeSec < 0) {
            $message = 'Standard time cannot be negative.';
            $messageType = 'error';
        } else {
            // Check if code already exists
            $existing = $db->queryOne("SELECT element_id FROM gsd_elements WHERE code = ?", [$code]);
            if ($existing) {
                $message = 'GSD Element code already exists.';
                $messageType = 'error';
            } else {
                $result = $db->insert('gsd_elements', [
                    'code' => $code,
                    'category' => $category,
                    'description' => $description,
                    'std_time_sec' => $stdTimeSec,
                    'cond_len_5_sec' => $condLen5Sec,
                    'cond_len_15_sec' => $condLen15Sec,
                    'cond_len_30_sec' => $condLen30Sec,
                    'cond_len_45_sec' => $condLen45Sec,
                    'cond_len_80_sec' => $condLen80Sec,
                    'short_time_sec' => $shortTimeSec,
                    'long_time_sec' => $longTimeSec,
                    'is_active' => 1
                ]);
                
                if ($result) {
                    $message = 'GSD Element created successfully.';
                    logActivity('gsd_elements', $result, 'CREATE');
                } else {
                    $message = 'Error creating GSD element.';
                    $messageType = 'error';
                }
            }
        }
    }
    
    if ($action === 'update' && true) {
        $id = intval($_POST['id']);
        $category = sanitizeInput($_POST['category']);
        $description = sanitizeInput($_POST['description']);
        $stdTimeSec = floatval($_POST['std_time_sec']);
        $condLen5Sec = floatval($_POST['cond_len_5_sec']);
        $condLen15Sec = floatval($_POST['cond_len_15_sec']);
        $condLen30Sec = floatval($_POST['cond_len_30_sec']);
        $condLen45Sec = floatval($_POST['cond_len_45_sec']);
        $condLen80Sec = floatval($_POST['cond_len_80_sec']);
        $shortTimeSec = floatval($_POST['short_time_sec']);
        $longTimeSec = floatval($_POST['long_time_sec']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($description)) {
            $message = 'Description is required.';
            $messageType = 'error';
        } elseif ($stdTimeSec < 0) {
            $message = 'Standard time cannot be negative.';
            $messageType = 'error';
        } else {
            $oldData = $db->getById('gsd_elements', $id);
            $result = $db->update('gsd_elements', $id, [
                'category' => $category,
                'description' => $description,
                'std_time_sec' => $stdTimeSec,
                'cond_len_5_sec' => $condLen5Sec,
                'cond_len_15_sec' => $condLen15Sec,
                'cond_len_30_sec' => $condLen30Sec,
                'cond_len_45_sec' => $condLen45Sec,
                'cond_len_80_sec' => $condLen80Sec,
                'short_time_sec' => $shortTimeSec,
                'long_time_sec' => $longTimeSec,
                'is_active' => $isActive
            ]);
            
            if ($result) {
                $message = 'GSD Element updated successfully.';
                logActivity('gsd_elements', $id, 'UPDATE', $oldData);
            } else {
                $message = 'Error updating GSD element.';
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'delete' && true) {
        $id = intval($_POST['id']);
        
        // Check if GSD element is used in other tables
        $usageCheck = $db->queryOne("
            SELECT COUNT(*) as count FROM (
                SELECT element_id FROM method_elements WHERE element_id = ?
            ) as usage
        ", [$id]);
        
        if ($usageCheck && $usageCheck['count'] > 0) {
            $message = 'Cannot delete GSD element. It is being used in method analysis.';
            $messageType = 'error';
        } else {
            $oldData = $db->getById('gsd_elements', $id);
            $result = $db->hardDelete('gsd_elements', $id, 'element_id');
            
            if ($result) {
                $message = 'GSD Element permanently deleted successfully.';
                logActivity('gsd_elements', $id, 'DELETE', $oldData);
            } else {
                $message = 'Error deleting GSD element.';
                $messageType = 'error';
            }
        }
    }
}

// Search and pagination
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build search condition
$searchCondition = '';
$searchParams = [];
if (!empty($search)) {
    $searchCondition = "WHERE (code LIKE ? OR category LIKE ? OR description LIKE ?)";
    $searchParams = ["%$search%", "%$search%", "%$search%"];
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM gsd_elements $searchCondition";
$totalResult = $db->queryOne($countQuery, $searchParams);
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit);

// Get GSD elements with search and pagination
$query = "SELECT * FROM gsd_elements $searchCondition ORDER BY code ASC, element_id DESC LIMIT $limit OFFSET $offset";
$gsdElements = $db->query($query, $searchParams);

// Calculate display numbers for results summary
$startRecord = $totalRecords > 0 ? $offset + 1 : 0;
$endRecord = min($offset + $limit, $totalRecords);

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
                        <h1 class="text-3xl font-bold text-gray-900">GSD Elements</h1>
                        <p class="text-gray-600 mt-2">Manage motion elements for method analysis</p>
                    </div>
                    <?php if (true): ?>
                    <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add GSD Element
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

            <!-- Search and Results Summary -->
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Search GSD Elements</h2>
                        <?php if (!empty($search)): ?>
                        <a href="gsd_elements.php" class="text-sm text-gray-600 hover:text-gray-800">Clear Search</a>
                        <?php endif; ?>
                    </div>
                    
                    <form method="GET" class="flex gap-4">
                        <div class="flex-1">
                            <input type="text" 
                                   name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by element code, category, or description..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search
                        </button>
                    </form>
                    
                    <?php if ($totalRecords > 0): ?>
                    <div class="mt-4 text-sm text-gray-600">
                        Showing <?php echo $startRecord; ?>-<?php echo $endRecord; ?> of <?php echo $totalRecords; ?> results
                        <?php if (!empty($search)): ?>
                        for "<span class="font-medium"><?php echo htmlspecialchars($search); ?></span>"
                        <?php endif; ?>
                    </div>
                    <?php elseif (!empty($search)): ?>
                    <div class="mt-4 text-sm text-gray-600">
                        No results found for "<span class="font-medium"><?php echo htmlspecialchars($search); ?></span>"
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GSD Elements Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Motion Elements Library</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Standard Time (sec)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conditional Times</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <?php if (true): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($gsdElements as $element): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($element['code']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($element['category'] ?: 'General'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($element['description']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($element['std_time_sec'] ?? 0, 3); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        5cm: <?php echo number_format($element['cond_len_5_sec'] ?? 0, 3); ?>s<br>
                                        15cm: <?php echo number_format($element['cond_len_15_sec'] ?? 0, 3); ?>s<br>
                                        30cm: <?php echo number_format($element['cond_len_30_sec'] ?? 0, 3); ?>s<br>
                                        45cm: <?php echo number_format($element['cond_len_45_sec'] ?? 0, 3); ?>s<br>
                                        80cm: <?php echo number_format($element['cond_len_80_sec'] ?? 0, 3); ?>s<br>
                                        <small class="text-gray-600">
                                            Short: <?php echo number_format($element['short_time_sec'] ?? 0, 3); ?>s | 
                                            Long: <?php echo number_format($element['long_time_sec'] ?? 0, 3); ?>s
                                        </small>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $element['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $element['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <?php if (true): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($element)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <?php if (true): ?>
                                    <button onclick="confirmDelete(<?php echo $element['element_id']; ?>, '<?php echo htmlspecialchars($element['code'], ENT_QUOTES); ?>')" 
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

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
                        <?php if ($startPage > 2): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border <?php echo $i === $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $totalPages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Next</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Create Modal -->
<?php if (true): ?>
<div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create GSD Element</h3>
            <form method="POST" id="createForm">
                <input type="hidden" name="action" value="create">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code *</label>
                        <input type="text" name="code" required maxlength="20" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="G1A, M3, F1">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select category</option>
                            <option value="REACH">Reach</option>
                            <option value="GRASP">Grasp</option>
                            <option value="MOVE">Move</option>
                            <option value="POSITION">Position</option>
                            <option value="RELEASE">Release</option>
                            <option value="EYE_TRAVEL">Eye Travel</option>
                            <option value="EYE_FOCUS">Eye Focus</option>
                            <option value="BEND">Body Movement</option>
                            <option value="MACHINE">Machine</option>
                            <option value="FABRIC">Fabric Handling</option>
                            <option value="QC">Quality Control</option>
                            <option value="ALLOWANCE">Allowance</option>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                        <textarea name="description" required maxlength="255" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Detailed description of the motion element"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Standard Time (sec)</label>
                        <input type="number" name="std_time_sec" step="0.001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="2.500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">5cm Distance (sec)</label>
                        <input type="number" name="cond_len_5_sec" step="0.001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">15cm Distance (sec)</label>
                        <input type="number" name="cond_len_15_sec" step="0.001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">30cm Distance (sec)</label>
                        <input type="number" name="cond_len_30_sec" step="0.001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">45cm Distance (sec)</label>
                        <input type="number" name="cond_len_45_sec" step="0.001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">80cm Distance (sec)</label>
                        <input type="number" name="cond_len_80_sec" step="0.001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Short Time Category (sec)</label>
                        <input type="number" name="short_time_sec" step="0.001" min="0" value="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Long Time Category (sec)</label>
                        <input type="number" name="long_time_sec" step="0.001" min="0" value="0" 
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
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit GSD Element</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                        <input type="text" id="editCode" readonly 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" id="editCategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select category</option>
                            <option value="REACH">Reach</option>
                            <option value="GRASP">Grasp</option>
                            <option value="MOVE">Move</option>
                            <option value="POSITION">Position</option>
                            <option value="RELEASE">Release</option>
                            <option value="EYE_TRAVEL">Eye Travel</option>
                            <option value="EYE_FOCUS">Eye Focus</option>
                            <option value="BEND">Body Movement</option>
                            <option value="MACHINE">Machine</option>
                            <option value="FABRIC">Fabric Handling</option>
                            <option value="QC">Quality Control</option>
                            <option value="ALLOWANCE">Allowance</option>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                        <textarea name="description" id="editDescription" required maxlength="255" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Standard Time (sec)</label>
                        <input type="number" name="std_time_sec" id="editStdTime" step="0.001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">5cm Distance (sec)</label>
                        <input type="number" name="cond_len_5_sec" id="editCondLen5" step="0.001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">15cm Distance (sec)</label>
                        <input type="number" name="cond_len_15_sec" id="editCondLen15" step="0.001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">30cm Distance (sec)</label>
                        <input type="number" name="cond_len_30_sec" id="editCondLen30" step="0.001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">45cm Distance (sec)</label>
                        <input type="number" name="cond_len_45_sec" id="editCondLen45" step="0.001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">80cm Distance (sec)</label>
                        <input type="number" name="cond_len_80_sec" id="editCondLen80" step="0.001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Short Time (sec)</label>
                        <input type="number" name="short_time_sec" id="editShortTime" step="0.001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Long Time (sec)</label>
                        <input type="number" name="long_time_sec" id="editLongTime" step="0.001" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="col-span-2">
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
            <h3 class="text-lg font-medium text-gray-900 mt-4">Delete GSD Element</h3>
            <p class="mt-2 px-7 py-3 text-sm text-gray-500">
                Are you sure you want to delete GSD element "<span id="deleteElementName" class="font-medium"></span>"?
                This action cannot be undone.
            </p>
            <form id="deleteForm" method="POST" class="mt-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteElementId">
                
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

function openEditModal(element) {
    document.getElementById('editId').value = element.element_id;
    document.getElementById('editCode').value = element.code;
    document.getElementById('editCategory').value = element.category || '';
    document.getElementById('editDescription').value = element.description;
    document.getElementById('editStdTime').value = element.std_time_sec;
    document.getElementById('editCondLen5').value = element.cond_len_5_sec;
    document.getElementById('editCondLen15').value = element.cond_len_15_sec;
    document.getElementById('editCondLen30').value = element.cond_len_30_sec;
    document.getElementById('editCondLen45').value = element.cond_len_45_sec || 0;
    document.getElementById('editCondLen80').value = element.cond_len_80_sec || 0;
    document.getElementById('editShortTime').value = element.short_time_sec || 0;
    document.getElementById('editLongTime').value = element.long_time_sec || 0;
    document.getElementById('editActive').checked = element.is_active == 1;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(elementId, elementCode) {
    document.getElementById('deleteElementId').value = elementId;
    document.getElementById('deleteElementName').textContent = elementCode;
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