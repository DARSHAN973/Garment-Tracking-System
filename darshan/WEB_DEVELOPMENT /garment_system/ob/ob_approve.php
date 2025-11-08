<?php
header('Content-Type: application/json');
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Check permissions
if (!hasPermission($_SESSION['role'], 'ob', 'write')) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$obId = intval($input['ob_id'] ?? 0);

if (!$obId) {
    echo json_encode(['success' => false, 'message' => 'Invalid OB ID']);
    exit;
}

$db = new DatabaseHelper();

try {
    // Check if OB exists and is in DRAFT status
    $ob = $db->queryOne("SELECT ob_id, status FROM ob WHERE ob_id = ? AND is_deleted = 0", [$obId]);
    
    if (!$ob) {
        echo json_encode(['success' => false, 'message' => 'OB not found']);
        exit;
    }
    
    if ($ob['status'] !== 'DRAFT') {
        echo json_encode(['success' => false, 'message' => 'Only DRAFT OBs can be approved']);
        exit;
    }
    
    // Update OB status to APPROVED
    $result = $db->update('ob', $obId, [
        'status' => 'APPROVED',
        'approved_at' => date('Y-m-d H:i:s'),
        'approved_by' => $_SESSION['user_id']
    ]);
    
    if ($result) {
        logActivity('ob', $obId, 'APPROVE');
        echo json_encode(['success' => true, 'message' => 'OB approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve OB']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>