<?php
$pageTitle = 'Styles';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();
$message = '';
$messageType = 'success';

// Resolve user role safely to avoid undefined index warnings
$userRole = null;
try {
    $current = getCurrentUser();
    if (!empty($current) && isset($current['role'])) {
        $userRole = $current['role'];
    } elseif (isset($_SESSION) && isset($_SESSION['role'])) {
        $userRole = $_SESSION['role'];
    }
} catch (Throwable $e) {
    // fallback to null - permissions will be evaluated as false
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && true) {
        $styleCode = sanitizeInput($_POST['style_code']);
        $description = sanitizeInput($_POST['description']);
        $product = sanitizeInput($_POST['product']);
        $fabric = sanitizeInput($_POST['fabric']);
        $spi = !empty($_POST['spi']) ? floatval($_POST['spi']) : null;
        $stitchLength = !empty($_POST['stitch_length']) ? floatval($_POST['stitch_length']) : null;
        
        if (empty($styleCode)) {
            $message = 'Style Code is required.';
            $messageType = 'error';
        } else {
            // Check if style code already exists
            $existing = $db->queryOne("SELECT style_id FROM styles WHERE style_code = ?", [$styleCode]);
            if ($existing) {
                $message = 'Style Code already exists.';
                $messageType = 'error';
            } else {
                $result = $db->insert('styles', [
                    'style_code' => $styleCode,
                    'description' => $description,
                    'product' => $product,
                    'fabric' => $fabric,
                    'spi' => $spi,
                    'stitch_length' => $stitchLength,
                    'is_active' => 1
                ]);
                
                if ($result) {
                    $message = 'Style created successfully.';
                    logActivity('styles', $result, 'CREATE');
                } else {
                    $message = 'Error creating style.';
                    $messageType = 'error';
                }
            }
        }
    }
    
    if ($action === 'update' && true) {
        $id = intval($_POST['id']);
        $description = sanitizeInput($_POST['description']);
        $product = sanitizeInput($_POST['product']);
        $fabric = sanitizeInput($_POST['fabric']);
        $spi = !empty($_POST['spi']) ? floatval($_POST['spi']) : null;
        $stitchLength = !empty($_POST['stitch_length']) ? floatval($_POST['stitch_length']) : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $oldData = $db->getById('styles', $id);
        $result = $db->update('styles', $id, [
            'description' => $description,
            'product' => $product,
            'fabric' => $fabric,
            'spi' => $spi,
            'stitch_length' => $stitchLength,
            'is_active' => $isActive
        ]);
        
        if ($result) {
            $message = 'Style updated successfully.';
            logActivity('styles', $id, 'UPDATE', $oldData);
        } else {
            $message = 'Error updating style.';
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete' && true) {
        $id = intval($_POST['id']);
        
        // Get style info for logging before deletion
        $oldData = $db->getById('styles', $id);
        if (!$oldData) {
            $message = 'Style not found.';
            $messageType = 'error';
        } else {
            // Check dependent records and delete them in transaction
            try {
                $db->beginTransaction();
                
                // Count dependent records for user feedback
                $obCount = $db->queryOne("SELECT COUNT(*) as count FROM ob WHERE style_id = ?", [$id]);
                $tcrCount = $db->queryOne("SELECT COUNT(*) as count FROM tcr WHERE style_id = ?", [$id]);
                
                $obRecords = $obCount['count'] ?? 0;
                $tcrRecords = $tcrCount['count'] ?? 0;
                
                // Delete dependent records first (cascade)
                if ($obRecords > 0) {
                    // Delete OB items first, then OB records
                    $db->query("DELETE FROM ob_items WHERE ob_id IN (SELECT ob_id FROM ob WHERE style_id = ?)", [$id]);
                    $db->query("DELETE FROM ob WHERE style_id = ?", [$id]);
                }
                
                if ($tcrRecords > 0) {
                    // Delete TCR items first, then TCR records
                    $db->query("DELETE FROM tcr_items WHERE tcr_id IN (SELECT tcr_id FROM tcr WHERE style_id = ?)", [$id]);
                    $db->query("DELETE FROM tcr WHERE style_id = ?", [$id]);
                }
                
                // Now delete the style itself
                $result = $db->hardDelete('styles', $id);
                
                if ($result) {
                    $db->commit();
                    
                    // Create detailed success message
                    $deletedItems = [];
                    if ($obRecords > 0) $deletedItems[] = "{$obRecords} OB record(s)";
                    if ($tcrRecords > 0) $deletedItems[] = "{$tcrRecords} TCR record(s)";
                    
                    $dependentMsg = !empty($deletedItems) ? ' and ' . implode(', ', $deletedItems) : '';
                    $message = "Style '{$oldData['style_code']}' permanently deleted successfully{$dependentMsg}.";
                    
                    logActivity('styles', $id, 'DELETE', $oldData);
                } else {
                    $db->rollback();
                    $dbErr = method_exists($db, 'getLastError') ? $db->getLastError() : '';
                    if ($dbErr) {
                        error_log("Style delete failed: {$dbErr}");
                    }
                    $message = 'Error deleting style.' . ($dbErr ? ' (DB: ' . htmlspecialchars($dbErr) . ')' : '');
                    $messageType = 'error';
                }
                
            } catch (Exception $e) {
                $db->rollback();
                error_log("Style cascade delete error: " . $e->getMessage());
                $message = 'Error deleting style and dependent records: ' . htmlspecialchars($e->getMessage());
                $messageType = 'error';
            }
        }
    }
}

// Handle search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12; // Items per page
$offset = ($page - 1) * $perPage;

// Build search query
$searchWhere = '';
$searchParams = [];

if (!empty($search)) {
    $searchWhere = " WHERE (s.style_code LIKE ? OR s.description LIKE ? OR s.product LIKE ? OR s.fabric LIKE ?)";
    $searchTerm = "%{$search}%";
    $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Get total count for pagination
$totalQuery = "
    SELECT COUNT(*) as total
    FROM styles s 
    {$searchWhere}
";
$totalResult = $db->queryOne($totalQuery, $searchParams);
$totalItems = $totalResult['total'] ?? 0;
$totalPages = ceil($totalItems / $perPage);

// Get styles with pagination and search
$styles = $db->query("
    SELECT s.*, 
           COUNT(DISTINCT ob.ob_id) as ob_count,
           COUNT(DISTINCT tcr.tcr_id) as tcr_count
    FROM styles s 
    LEFT JOIN ob ON s.style_id = ob.style_id 
    LEFT JOIN tcr ON s.style_id = tcr.style_id 
    {$searchWhere}
    GROUP BY s.style_id
    ORDER BY s.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
", $searchParams);

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
                        <h1 class="text-3xl font-bold text-gray-900">Styles</h1>
                        <p class="text-gray-600 mt-2">Manage garment style designs and specifications</p>
                    </div>
                    <?php if (true): ?>
                    <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Style
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

            <!-- Search and Filters -->
            <div class="mb-6 bg-white rounded-lg shadow p-6">
                <form method="GET" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Styles</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Search by code, description, product, or fabric...">
                    </div>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="?" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Results Summary -->
            <div class="mb-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <?php if (!empty($search)): ?>
                    Showing <?php echo count($styles); ?> of <?php echo $totalItems; ?> results for "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                    Showing <?php echo count($styles); ?> of <?php echo $totalItems; ?> styles
                    <?php endif; ?>
                    (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)
                </div>
                
                <!-- View Toggle -->
                <div class="flex space-x-2">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'cards'])); ?>" 
                       class="px-3 py-1 text-sm <?php echo ($_GET['view'] ?? 'cards') === 'cards' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded">
                        <i class="fas fa-th mr-1"></i>Cards
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'list'])); ?>" 
                       class="px-3 py-1 text-sm <?php echo ($_GET['view'] ?? 'cards') === 'list' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded">
                        <i class="fas fa-list mr-1"></i>List
                    </a>
                </div>
            </div>

            <?php $viewMode = $_GET['view'] ?? 'cards'; ?>
            
            <?php if ($viewMode === 'list'): ?>
            <!-- List View -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Style Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fabric</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($styles as $style): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($style['style_code']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars($style['description'] ?: 'No description'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($style['product'] ?: '—'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($style['fabric'] ?: '—'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $style['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $style['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="../ob/ob_list.php?style_id=<?php echo $style['style_id']; ?>" 
                                   class="inline-flex items-center px-2 py-1 rounded text-xs font-medium text-blue-600 hover:text-blue-800 border border-blue-300 hover:bg-blue-50 transition-all">OB</a>
                                <a href="../tcr/tcr_list.php?style_id=<?php echo $style['style_id']; ?>" 
                                   class="inline-flex items-center px-2 py-1 rounded text-xs font-medium text-purple-600 hover:text-purple-800 border border-purple-300 hover:bg-purple-50 transition-all">TCR</a>
                                <?php if (true): ?>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($style)); ?>)" 
                                        class="text-green-600 hover:text-green-800">Edit</button>
                                <?php endif; ?>
                                <?php if (true): ?>
                                <button onclick="confirmDelete(<?php echo $style['style_id']; ?>, '<?php echo htmlspecialchars($style['style_code'], ENT_QUOTES); ?>')" 
                                        class="text-red-600 hover:text-red-800">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <!-- Cards View -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($styles as $style): ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <!-- Card Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($style['style_code']); ?></h3>
                                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($style['description'] ?: 'No description'); ?></p>
                            </div>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $style['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $style['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Product:</span>
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($style['product'] ?: '—'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Fabric:</span>
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($style['fabric'] ?: '—'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">SPI:</span>
                                <span class="text-sm text-gray-900"><?php echo $style['spi'] ? number_format($style['spi'], 2) : '—'; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Stitch Length:</span>
                                <span class="text-sm text-gray-900"><?php echo $style['stitch_length'] ? number_format($style['stitch_length'], 2) . 'mm' : '—'; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="px-6 py-4 bg-gray-50 rounded-b-lg">
                        <div class="flex flex-wrap gap-2 justify-center">
                            <a href="../ob/ob_list.php?style_id=<?php echo $style['style_id']; ?>" 
                               class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium text-blue-600 hover:text-blue-800 border border-blue-300 hover:bg-blue-50 transition-all">OB</a>
                            <a href="../tcr/tcr_list.php?style_id=<?php echo $style['style_id']; ?>" 
                               class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium text-purple-600 hover:text-purple-800 border border-purple-300 hover:bg-purple-50 transition-all">TCR</a>
                            <?php if (true): ?>
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($style)); ?>)" 
                                    class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-green-100 text-green-700 hover:bg-green-200 border-2 border-green-300 hover:border-green-400 transition-all">
                                    <i class="fas fa-edit mr-1"></i>Edit</button>
                            <?php endif; ?>
                            <?php if (true): ?>
                            <button onclick="confirmDelete(<?php echo $style['style_id']; ?>, '<?php echo htmlspecialchars($style['style_code'], ENT_QUOTES); ?>')" 
                                    class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-red-100 text-red-700 hover:bg-red-200 border-2 border-red-300 hover:border-red-400 transition-all">
                                    <i class="fas fa-trash mr-1"></i>Delete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Empty State -->
                <?php if (empty($styles)): ?>
                <div class="col-span-full">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No styles found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new style.</p>
                        <?php if (true): ?>
                        <div class="mt-6">
                            <button onclick="openCreateModal()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New Style
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

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
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create Style</h3>
            <form method="POST" id="createForm">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Style Code *</label>
                    <input type="text" name="style_code" required maxlength="50" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="SS26-KD-1J-DRS-00028">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" maxlength="255" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Style description"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Type</label>
                    <select name="product" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select product type</option>
                        <option value="DRESS">Dress</option>
                        <option value="T-SHIRT">T-Shirt</option>
                        <option value="SHIRT">Shirt</option>
                        <option value="PANT">Pant</option>
                        <option value="JACKET">Jacket</option>
                        <option value="SKIRT">Skirt</option>
                        <option value="BLOUSE">Blouse</option>
                        <option value="SHORTS">Shorts</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fabric Type</label>
                    <select name="fabric" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select fabric type</option>
                        <option value="JERSEY">Jersey</option>
                        <option value="COTTON">Cotton</option>
                        <option value="DENIM">Denim</option>
                        <option value="POLYESTER">Polyester</option>
                        <option value="SILK">Silk</option>
                        <option value="LINEN">Linen</option>
                        <option value="WOOL">Wool</option>
                        <option value="BLEND">Blend</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">SPI (Stitches Per Inch)</label>
                    <input type="number" name="spi" step="0.01" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="12.00">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stitch Length (mm)</label>
                    <input type="number" name="stitch_length" step="0.1" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="2.5">
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
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Style</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Style Code</label>
                    <input type="text" id="editStyleCode" readonly 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="editDescription" maxlength="255" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Type</label>
                    <select name="product" id="editProduct" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select product type</option>
                        <option value="DRESS">Dress</option>
                        <option value="T-SHIRT">T-Shirt</option>
                        <option value="SHIRT">Shirt</option>
                        <option value="PANT">Pant</option>
                        <option value="JACKET">Jacket</option>
                        <option value="SKIRT">Skirt</option>
                        <option value="BLOUSE">Blouse</option>
                        <option value="SHORTS">Shorts</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fabric Type</label>
                    <select name="fabric" id="editFabric" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select fabric type</option>
                        <option value="JERSEY">Jersey</option>
                        <option value="COTTON">Cotton</option>
                        <option value="DENIM">Denim</option>
                        <option value="POLYESTER">Polyester</option>
                        <option value="SILK">Silk</option>
                        <option value="LINEN">Linen</option>
                        <option value="WOOL">Wool</option>
                        <option value="BLEND">Blend</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">SPI (Stitches Per Inch)</label>
                    <input type="number" name="spi" id="editSpi" step="0.01" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stitch Length (mm)</label>
                    <input type="number" name="stitch_length" id="editStitchLength" step="0.1" min="0" 
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
            <h3 class="text-lg font-medium text-gray-900 mt-4">Delete Style</h3>
            <div class="mt-2 px-7 py-3 text-sm text-gray-500">
                <p class="mb-2">
                    Are you sure you want to delete style "<span id="deleteStyleName" class="font-medium"></span>"?
                </p>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-2 mb-2">
                    <p class="text-yellow-800 text-xs">
                        <i class="fas fa-warning mr-1"></i>
                        <strong>Warning:</strong> This will also permanently delete all associated Operation Breakdowns (OB) and Thread Consumption Records (TCR) for this style.
                    </p>
                </div>
                <p class="text-red-600 text-xs font-medium">
                    This action cannot be undone.
                </p>
            </div>
            <form id="deleteForm" method="POST" class="mt-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteStyleId">
                
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

function openEditModal(style) {
    document.getElementById('editId').value = style.style_id;
    document.getElementById('editStyleCode').value = style.style_code;
    document.getElementById('editDescription').value = style.description || '';
    document.getElementById('editProduct').value = style.product || '';
    document.getElementById('editFabric').value = style.fabric || '';
    document.getElementById('editSpi').value = style.spi || '';
    document.getElementById('editStitchLength').value = style.stitch_length || '';
    document.getElementById('editActive').checked = style.is_active == 1;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(styleId, styleCode) {
    document.getElementById('deleteStyleId').value = styleId;
    document.getElementById('deleteStyleName').textContent = styleCode;
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