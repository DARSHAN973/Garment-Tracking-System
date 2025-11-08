<?php
$pageTitle = 'Thread Consumption Report';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';
require_once '../utils/Calculator.php';

requirePermission('tcr', 'read');

$db = new DatabaseHelper();
$calculator = new Calculator();
$message = '';
$messageType = 'success';

// Get filter parameters
$styleFilter = $_GET['style_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$pageSize = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $pageSize;

// Build WHERE clause
$whereConditions = ["t.is_deleted = 0"];
$params = [];

if (!empty($styleFilter)) {
    $whereConditions[] = "t.style_id = ?";
    $params[] = $styleFilter;
}

if (!empty($statusFilter)) {
    $whereConditions[] = "t.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) as total 
    FROM tcr t 
    INNER JOIN styles s ON t.style_id = s.style_id 
    WHERE $whereClause
";
$totalResult = $db->queryOne($countQuery, $params);
if ($totalResult && isset($totalResult['total'])) {
    $totalRecords = intval($totalResult['total']);
} else {
    // Default to zero if query failed or table is missing
    $totalRecords = 0;
}
$totalPages = $totalRecords > 0 ? ceil($totalRecords / $pageSize) : 1;

// Get TCR records with calculations
$tcrRecords = $db->query("
    SELECT t.*, s.style_code, s.description as style_desc,
           COUNT(td.thread_type) as thread_count,
           SUM(td.total_consumption) as total_consumption
    FROM tcr t 
    INNER JOIN styles s ON t.style_id = s.style_id 
    LEFT JOIN tcr_details td ON t.tcr_id = td.tcr_id
    WHERE $whereClause
    GROUP BY t.tcr_id
    ORDER BY t.created_at DESC
    LIMIT $pageSize OFFSET $offset
", $params);

if (!$tcrRecords || !is_array($tcrRecords)) {
    // Ensure $tcrRecords is always an array to prevent "Trying to access array offset on null" warnings
    $tcrRecords = [];
}

// Add safe role resolution to avoid undefined index warnings
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

// Get styles for filter dropdown
$styles = $db->query("SELECT style_id, style_code FROM styles WHERE is_active = 1 ORDER BY style_code");

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
                        <h1 class="text-3xl font-bold text-gray-900">Thread Consumption Report</h1>
                        <p class="text-gray-600 mt-2">Calculate and manage thread consumption for garment production</p>
                    </div>
                    <?php if (hasPermission($userRole, 'tcr', 'write')): ?>
                    <a href="tcr_create.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create TCR
                    </a>
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

            <!-- Filters -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Style</label>
                        <select name="style_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">All Styles</option>
                            <?php foreach ($styles as $style): ?>
                            <option value="<?php echo $style['style_id']; ?>" <?php echo $styleFilter == $style['style_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($style['style_code']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex-1 min-w-32">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="DRAFT" <?php echo $statusFilter === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                            <option value="APPROVED" <?php echo $statusFilter === 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                            <option value="ACTIVE" <?php echo $statusFilter === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                            <option value="INACTIVE" <?php echo $statusFilter === 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Filter
                        </button>
                        <a href="tcr_list.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- TCR Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($tcrRecords as $tcr): ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <!-- Card Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($tcr['style_code']); ?></h3>
                                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($tcr['style_desc'] ?: 'No description'); ?></p>
                                <?php if ($tcr['tcr_name']): ?>
                                <p class="text-xs text-gray-400 mt-1">TCR: <?php echo htmlspecialchars($tcr['tcr_name']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php
                            $statusColors = [
                                'DRAFT' => 'bg-gray-100 text-gray-800',
                                'APPROVED' => 'bg-blue-100 text-blue-800',
                                'ACTIVE' => 'bg-green-100 text-green-800',
                                'INACTIVE' => 'bg-red-100 text-red-800'
                            ];
                            $colorClass = $statusColors[$tcr['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $colorClass; ?>">
                                <?php echo htmlspecialchars($tcr['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500">Thread Types:</span>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-purple-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900"><?php echo $tcr['thread_count']; ?></span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500">Total Consumption:</span>
                                <span class="text-lg font-bold text-purple-600"><?php echo number_format($tcr['total_consumption'] ?? 0, 2); ?> m</span>
                            </div>
                            <?php if ($tcr['total_cost']): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500">Cost per Garment:</span>
                                <span class="text-sm font-medium text-green-600">₹<?php echo number_format($tcr['total_cost'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="px-6 py-4 bg-gray-50 rounded-b-lg">
                        <div class="flex justify-between items-center">
                            <div class="text-xs text-gray-500">
                                Created: <?php echo date('M j, Y', strtotime($tcr['created_at'])); ?>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="tcr_detail.php?id=<?php echo $tcr['tcr_id']; ?>" 
                                   class="text-purple-600 hover:text-purple-800 text-sm font-medium">View</a>
                                <?php if (hasPermission($userRole, 'tcr', 'write')): ?>
                                <a href="tcr_edit.php?id=<?php echo $tcr['tcr_id']; ?>" 
                                   class="text-green-600 hover:text-green-800 text-sm font-medium">Edit</a>
                                <?php endif; ?>
                                <?php if (hasPermission($userRole, 'tcr', 'write') && $tcr['status'] === 'DRAFT'): ?>
                                <button onclick="approveTCR(<?php echo $tcr['tcr_id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">Approve</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Empty State -->
                <?php if (empty($tcrRecords)): ?>
                <div class="col-span-full">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No TCR records found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new Thread Consumption Report.</p>
                        <?php if (hasPermission($userRole, 'tcr', 'write')): ?>
                        <div class="mt-6">
                            <a href="tcr_create.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create TCR
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-8 bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-lg shadow-sm">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                            <span class="font-medium"><?php echo min($offset + $pageSize, $totalRecords); ?></span> of 
                            <span class="font-medium"><?php echo $totalRecords; ?></span> results
                        </p>
                    </div>
                    <div>
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
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
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
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function approveTCR(tcrId) {
    if (confirm('Are you sure you want to approve this Thread Consumption Report? This action cannot be undone.')) {
        fetch('tcr_approve.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ tcr_id: tcrId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>