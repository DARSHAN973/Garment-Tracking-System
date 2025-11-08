<?php
$pageTitle = 'Thread Consumption Report Details';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';
require_once '../utils/Calculator.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();
$calculator = new Calculator();
$tcrId = intval($_GET['id'] ?? 0);

if (!$tcrId) {
    header('Location: tcr_list.php');
    exit;
}

// Get TCR details with style information
$tcr = $db->queryOne("
    SELECT t.*, s.style_code, s.description as style_desc, s.product, s.fabric,
           o.ob_name, o.total_smv, o.target_per_hour
    FROM tcr t 
    INNER JOIN styles s ON t.style_id = s.style_id 
    LEFT JOIN ob o ON t.ob_id = o.ob_id
    WHERE t.tcr_id = ? AND t.is_deleted = 0
", [$tcrId]);

if (!$tcr) {
    header('Location: tcr_list.php');
    exit;
}

// Get thread consumption details
$threadDetails = $db->query("
    SELECT * FROM tcr_details 
    WHERE tcr_id = ?
    ORDER BY thread_type, thread_color
", [$tcrId]);

// Thread types mapping
$threadTypes = [
    'MAIN' => 'Main Thread',
    'BOBBIN' => 'Bobbin Thread',
    'OVERLOCK' => 'Overlock Thread',
    'CHAIN_STITCH' => 'Chain Stitch Thread',
    'ELASTIC' => 'Elastic Thread',
    'BUTTON_HOLE' => 'Button Hole Thread',
    'BARTACK' => 'Bartack Thread',
    'EMBROIDERY' => 'Embroidery Thread'
];

// Group threads by type for analysis
$threadsByType = [];
foreach ($threadDetails as $thread) {
    $threadsByType[$thread['thread_type']][] = $thread;
}

// Calculate summary statistics
$totalConsumption = array_sum(array_column($threadDetails, 'total_consumption'));
$totalCost = array_sum(array_column($threadDetails, 'total_cost'));
$avgWastage = count($threadDetails) > 0 ? array_sum(array_column($threadDetails, 'wastage_percentage')) / count($threadDetails) : 0;

// Calculate cost per target production
$costPer100Units = $totalCost * 100;
$costPer1000Units = $totalCost * 1000;

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
                        <h1 class="text-3xl font-bold text-gray-900">Thread Consumption Report</h1>
                        <p class="text-gray-600 mt-2">
                            Style: <span class="font-medium"><?php echo htmlspecialchars($tcr['style_code']); ?></span>
                            <?php if ($tcr['tcr_name']): ?>
                             - <?php echo htmlspecialchars($tcr['tcr_name']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="tcr_list.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                            ← Back to List
                        </a>
                        <?php if (true): ?>
                        <a href="tcr_edit.php?id=<?php echo $tcr['tcr_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                            Edit TCR
                        </a>
                        <?php endif; ?>
                        <?php if (true && $tcr['status'] === 'DRAFT'): ?>
                        <button onclick="approveTCR()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                            Approve TCR
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <!-- TCR Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">TCR Information</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Style Code</label>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($tcr['style_code']); ?></p>
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
                                $colorClass = $statusColors[$tcr['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $colorClass; ?>">
                                    <?php echo htmlspecialchars($tcr['status']); ?>
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Product Type</label>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($tcr['product'] ?: '—'); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Fabric</label>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($tcr['fabric'] ?: '—'); ?></p>
                            </div>
                            <?php if ($tcr['ob_name']): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Related OB</label>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($tcr['ob_name']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">OB SMV</label>
                                <p class="text-sm text-gray-900"><?php echo number_format($tcr['total_smv'], 3); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($tcr['description']): ?>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Description</label>
                            <p class="text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($tcr['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Consumption Summary -->
                <div>
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Consumption Summary</h3>
                        
                        <div class="space-y-4">
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600"><?php echo number_format($totalConsumption, 2); ?>m</div>
                                <div class="text-sm text-purple-800">Total Thread per Garment</div>
                            </div>
                            
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">₹<?php echo number_format($totalCost, 2); ?></div>
                                <div class="text-sm text-green-800">Total Cost per Garment</div>
                            </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600"><?php echo count($threadDetails); ?></div>
                                <div class="text-sm text-blue-800">Thread Types Used</div>
                            </div>
                            
                            <div class="bg-orange-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-orange-600"><?php echo number_format($avgWastage, 1); ?>%</div>
                                <div class="text-sm text-orange-800">Average Wastage</div>
                            </div>
                        </div>
                        
                        <!-- Cost projections -->
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <h4 class="font-medium text-gray-900 mb-3">Cost Projections</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Per 100 units:</span>
                                    <span class="font-medium text-gray-900">₹<?php echo number_format($costPer100Units, 0); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Per 1000 units:</span>
                                    <span class="font-medium text-gray-900">₹<?php echo number_format($costPer1000Units, 0); ?></span>
                                </div>
                                <?php if ($tcr['target_per_hour']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Per hour production:</span>
                                    <span class="font-medium text-gray-900">₹<?php echo number_format($totalCost * $tcr['target_per_hour'], 0); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thread Consumption Details -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Thread Consumption Breakdown</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thread Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Consumption</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wastage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Consumption</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost/m</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% of Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($threadDetails as $thread): 
                                $costPercentage = $totalCost > 0 ? ($thread['total_cost'] / $totalCost) * 100 : 0;
                                $consumptionPercentage = $totalConsumption > 0 ? ($thread['total_consumption'] / $totalConsumption) * 100 : 0;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($threadTypes[$thread['thread_type']] ?? $thread['thread_type']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($thread['thread_type']); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($thread['thread_color'] ?: '—'); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo number_format($thread['consumption_per_garment'], 2); ?>m</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900"><?php echo number_format($thread['wastage_percentage'], 1); ?>%</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 mr-2"><?php echo number_format($thread['total_consumption'], 2); ?>m</span>
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $consumptionPercentage; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">₹<?php echo number_format($thread['thread_cost_per_meter'], 2); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-green-600">₹<?php echo number_format($thread['total_cost'], 2); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-xs text-gray-600 mr-2"><?php echo number_format($costPercentage, 1); ?>%</span>
                                        <div class="w-12 bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $costPercentage; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php if ($thread['remarks']): ?>
                            <tr class="bg-gray-25">
                                <td colspan="8" class="px-6 py-2">
                                    <div class="text-xs text-gray-600">
                                        <strong>Note:</strong> <?php echo htmlspecialchars($thread['remarks']); ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr class="font-medium">
                                <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-900">Total:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($totalConsumption, 2); ?>m</td>
                                <td class="px-6 py-4"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₹<?php echo number_format($totalCost, 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">100.0%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Thread Type Analysis -->
            <?php if (count($threadsByType) > 1): ?>
            <div class="mt-8 bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Thread Type Analysis</h3>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($threadsByType as $type => $threads): 
                            $typeConsumption = array_sum(array_column($threads, 'total_consumption'));
                            $typeCost = array_sum(array_column($threads, 'total_cost'));
                            $typePercentage = $totalCost > 0 ? ($typeCost / $totalCost) * 100 : 0;
                        ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($threadTypes[$type] ?? $type); ?></h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Colors:</span>
                                    <span class="text-gray-900"><?php echo count($threads); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Consumption:</span>
                                    <span class="text-gray-900"><?php echo number_format($typeConsumption, 2); ?>m</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Cost:</span>
                                    <span class="text-gray-900">₹<?php echo number_format($typeCost, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">% of Total:</span>
                                    <span class="text-gray-900"><?php echo number_format($typePercentage, 1); ?>%</span>
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
                            <span class="text-gray-900"><?php echo date('F j, Y g:i A', strtotime($tcr['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Updated:</span>
                            <span class="text-gray-900"><?php echo date('F j, Y g:i A', strtotime($tcr['updated_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Thread Types:</span>
                            <span class="text-gray-900"><?php echo count($threadDetails); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Thread Colors:</span>
                            <span class="text-gray-900"><?php echo count(array_filter(array_unique(array_column($threadDetails, 'thread_color')))); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function approveTCR() {
    if (confirm('Are you sure you want to approve this Thread Consumption Report? This action cannot be undone.')) {
        fetch('../tcr/tcr_approve.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ tcr_id: <?php echo $tcrId; ?> })
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