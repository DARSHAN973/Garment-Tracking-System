<?php
header('Content-Type: application/json');
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Check permissions
if (!hasPermission($_SESSION['role'], 'method_analysis', 'write')) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$methodId = intval($input['method_analysis_id'] ?? 0);

if (!$methodId) {
    echo json_encode(['success' => false, 'message' => 'Invalid Method Analysis ID']);
    exit;
}

$db = new DatabaseHelper();

try {
    // Check if Method Analysis exists and is in COMPLETED status
    $method = $db->queryOne("SELECT method_analysis_id, status FROM method_analysis WHERE method_analysis_id = ? AND is_deleted = 0", [$methodId]);
    
    if (!$method) {
        echo json_encode(['success' => false, 'message' => 'Method Analysis not found']);
        exit;
    }
    
    if ($method['status'] !== 'COMPLETED') {
        echo json_encode(['success' => false, 'message' => 'Only COMPLETED Method Analysis can be approved']);
        exit;
    }
    
    // Update Method Analysis status to APPROVED
    $result = $db->update('method_analysis', $methodId, [
        'status' => 'APPROVED',
        'approved_at' => date('Y-m-d H:i:s'),
        'approved_by' => $_SESSION['user_id']
    ]);
    
    if ($result) {
        logActivity('method_analysis', $methodId, 'APPROVE');
        echo json_encode(['success' => true, 'message' => 'Method Analysis approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve Method Analysis']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>