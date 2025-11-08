<?php
header('Content-Type: application/json');
require_once '../auth/session_check.php';
require_once '../utils/Database.php';

// Check permissions
if (!true) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$tcrId = intval($input['tcr_id'] ?? 0);

if (!$tcrId) {
    echo json_encode(['success' => false, 'message' => 'Invalid TCR ID']);
    exit;
}

$db = new DatabaseHelper();

try {
    // Check if TCR exists and is in DRAFT status
    $tcr = $db->queryOne("SELECT tcr_id, status FROM tcr WHERE tcr_id = ? AND is_deleted = 0", [$tcrId]);
    
    if (!$tcr) {
        echo json_encode(['success' => false, 'message' => 'TCR not found']);
        exit;
    }
    
    if ($tcr['status'] !== 'DRAFT') {
        echo json_encode(['success' => false, 'message' => 'Only DRAFT TCRs can be approved']);
        exit;
    }
    
    // Update TCR status to APPROVED
    $result = $db->update('tcr', $tcrId, [
        'status' => 'APPROVED',
        'approved_at' => date('Y-m-d H:i:s'),
        'approved_by' => $_SESSION['user_id']
    ]);
    
    if ($result) {
        logActivity('tcr', $tcrId, 'APPROVE');
        echo json_encode(['success' => true, 'message' => 'TCR approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve TCR']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>