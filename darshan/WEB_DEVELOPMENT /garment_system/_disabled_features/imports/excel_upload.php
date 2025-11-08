<?php
$pageTitle = 'Excel Upload';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

requirePermission('imports', 'write');

$db = new DatabaseHelper();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['excel_file'];
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $message = 'File uploaded successfully. Processing feature coming soon.';
        $messageType = 'success';
    } else {
        $message = 'Failed to upload file.';
        $messageType = 'error';
    }
}

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-4xl mx-auto">
            
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Excel Data Upload</h1>
                <p class="text-gray-600 mt-2">Import data from Excel/CSV files</p>
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

            <!-- Upload Form -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Upload Excel/CSV File</h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select File</label>
                        <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-sm text-gray-500 mt-1">Supported formats: Excel (.xlsx, .xls) and CSV (.csv)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Import Type</label>
                        <select name="import_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select import type...</option>
                            <option value="styles">Styles Master</option>
                            <option value="operations">Operations Master</option>
                            <option value="gsd_elements">GSD Elements</option>
                            <option value="thread_factors">Thread Factors</option>
                            <option value="ob_data">Operation Breakdown</option>
                            <option value="tcr_data">Thread Consumption Report</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a href="../dashboard.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Upload & Process
                        </button>
                    </div>
                </form>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
                <h3 class="text-lg font-medium text-blue-900 mb-2">Upload Instructions</h3>
                <div class="text-blue-800 space-y-2">
                    <p>• Ensure your file follows the required format for the selected import type</p>
                    <p>• First row should contain column headers</p>
                    <p>• Remove any empty rows or formatting</p>
                    <p>• Maximum file size: 10MB</p>
                    <p>• Data validation will be performed before import</p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>