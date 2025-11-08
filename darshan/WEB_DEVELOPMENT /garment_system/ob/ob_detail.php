<?php
$pageTitle = 'Operation Breakdown Details';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';
require_once '../utils/Calculator.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();
$calculator = new Calculator();
$obId = intval($_GET['id'] ?? 0);

if (!$obId) {
    header('Location: ob_list.php');
    exit;
}

// Get OB details with style information
$ob = $db->queryOne("
    SELECT o.*, s.style_code, s.description as style_desc, s.product, s.fabric, s.spi, s.stitch_length
    FROM ob o 
    INNER JOIN styles s ON o.style_id = s.style_id 
    WHERE o.ob_id = ? AND o.is_deleted = 0
", [$obId]);

if (!$ob) {
    header('Location: ob_list.php');
    exit;
}

// Get operation details
$operations = $db->query("
    SELECT od.*, op.operation_name, op.category, mt.machine_name
    FROM ob_details od
    INNER JOIN operations op ON od.operation_id = op.operation_id
    INNER JOIN machine_types mt ON od.machine_type_id = mt.machine_type_id
    WHERE od.ob_id = ?
    ORDER BY od.sequence_no
", [$obId]);

// Calculate performance metrics
$totalSmv = array_sum(array_column($operations, 'smv'));
$targetPerHour = $calculator->calculateTargetPerHour($totalSmv);
$targetPerDay = $targetPerHour * 8; // 8 hour shift
$cycleTime = $totalSmv * 60; // in seconds

// Group operations by category for analysis
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
        <div class="max-w-7xl mx-auto">
            
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Operation Breakdown Details</h1>
                        <p class="text-gray-600 mt-2">
                            Style: <span class="font-medium"><?php echo htmlspecialchars($ob['style_code']); ?></span>
                            <?php if ($ob['ob_name']): ?>
                             - <?php echo htmlspecialchars($ob['ob_name']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="ob_list.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                            ← Back to List
                        </a>
                        <?php if (true): ?>
                        <a href="ob_edit.php?id=<?php echo $ob['ob_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                            Edit OB
                        </a>
                        <?php endif; ?>
                        <?php if (true && $ob['status'] === 'DRAFT'): ?>
                        <button onclick="approveOB()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                            Approve OB
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <!-- OB Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">OB Information</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Style Code</label>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($ob['style_code']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                                <?php
                                $statusColors = [
                                    'DRAFT' => 'bg-gray-100 text-gray-800',
                                    'APPROVED' => 'bg-blue-100 text-blue-800',
                                    'ACTIVE' => 'bg-green-100 text-green-800',
                                    'INACTIVE' => 'bg-red-100 text-red-800'
                                ];
                                $colorClass = $statusColors[$ob['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $colorClass; ?>">
                                    <?php echo htmlspecialchars($ob['status']); ?>
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Product Type</label>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($ob['product'] ?: '—'); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Fabric</label>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($ob['fabric'] ?: '—'); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">SPI</label>
                                <p class="text-sm text-gray-900"><?php echo $ob['spi'] ? number_format($ob['spi'], 2) : '—'; ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Stitch Length</label>
                                <p class="text-sm text-gray-900"><?php echo $ob['stitch_length'] ? number_format($ob['stitch_length'], 2) . 'mm' : '—'; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($ob['description']): ?>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Description</label>
                            <p class="text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($ob['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div>
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Metrics</h3>
                        
                        <div class="space-y-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600"><?php echo number_format($totalSmv, 3); ?></div>
                                <div class="text-sm text-blue-800">Total SMV</div>
                            </div>
                            
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600"><?php echo number_format($targetPerHour); ?></div>
                                <div class="text-sm text-green-800">Target per Hour</div>
                            </div>
                            
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600"><?php echo number_format($targetPerDay); ?></div>
                                <div class="text-sm text-purple-800">Target per Day (8h)</div>
                            </div>
                            
                            <div class="bg-orange-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-orange-600"><?php echo number_format($cycleTime, 1); ?>s</div>
                                <div class="text-sm text-orange-800">Cycle Time</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operations Breakdown -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Operations Sequence</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Machine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMV</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target/Hr</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% of Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($operations as $index => $operation): 
                                $operationTarget = $operation['smv'] > 0 ? round(60 / $operation['smv']) : 0;
                                $percentage = $totalSmv > 0 ? ($operation['smv'] / $totalSmv) * 100 : 0;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $operation['sequence_no']; ?></td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($operation['operation_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($operation['category'] ?: 'Other'); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($operation['machine_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo number_format($operation['smv'], 3); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($operationTarget); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <span class="text-xs text-gray-600"><?php echo number_format($percentage, 1); ?>%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500"><?php echo htmlspecialchars($operation['description'] ?: '—'); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr class="font-medium">
                                <td colspan="3" class="px-6 py-4 text-right text-sm text-gray-900">Total:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($totalSmv, 3); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($targetPerHour); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">100.0%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Category Analysis -->
            <?php if (count($operationsByCategory) > 1): ?>
            <div class="mt-8 bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Category Analysis</h3>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($operationsByCategory as $category => $categoryOps): 
                            $categorySmv = array_sum(array_column($categoryOps, 'smv'));
                            $categoryPercentage = $totalSmv > 0 ? ($categorySmv / $totalSmv) * 100 : 0;
                            $categoryTarget = $categorySmv > 0 ? round(60 / $categorySmv) : 0;
                        ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($category); ?></h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Operations:</span>
                                    <span class="text-gray-900"><?php echo count($categoryOps); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SMV:</span>
                                    <span class="text-gray-900"><?php echo number_format($categorySmv, 3); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">% of Total:</span>
                                    <span class="text-gray-900"><?php echo number_format($categoryPercentage, 1); ?>%</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Audit Trail -->
            <div class="mt-8 bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Audit Trail</h3>
                </div>
                
                <div class="p-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Created:</span>
                            <span class="text-gray-900"><?php echo date('F j, Y g:i A', strtotime($ob['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="text-gray-900"><?php echo date('F j, Y g:i A', strtotime($ob['updated_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Operations:</span>
                            <span class="text-gray-900"><?php echo count($operations); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function approveOB() {
    if (confirm('Are you sure you want to approve this Operation Breakdown? This action cannot be undone.')) {
        fetch('../ob/ob_approve.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ob_id: <?php echo $obId; ?> })
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