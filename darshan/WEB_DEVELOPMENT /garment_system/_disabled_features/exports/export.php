<?php
/**
 * Excel Export Controller
 * Handles all export requests
 */

require_once '../auth/session_check.php';
require_once '../utils/ExcelExporter.php';

// Check permissions
// Permission check removed for single user system;

$export_type = $_GET['type'] ?? '';
$record_id = $_GET['id'] ?? null;

try {
    $exporter = new ExcelExporter();
    
    switch ($export_type) {
        case 'operations':
            $exporter->exportOperations();
            break;
            
        case 'machine_types':
            $exporter->exportMachineTypes();
            break;
            
        case 'styles':
            $exporter->exportStyles();
            break;
            
        case 'gsd_elements':
            $exporter->exportGSDElements();
            break;
            
        case 'ob':
            $exporter->exportOB($record_id);
            break;
            
        case 'tcr':
            $exporter->exportTCR($record_id);
            break;
            
        case 'method_analysis':
            $exporter->exportMethodAnalysis($record_id);
            break;
            
        case 'activity_log':
            $days = $_GET['days'] ?? 30;
            $exporter->exportActivityLog($days);
            break;
            
        default:
            header('Location: ../dashboard.php?error=Invalid export type');
            exit();
    }
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header('Location: ../dashboard.php?error=Export failed');
    exit();
}
?>