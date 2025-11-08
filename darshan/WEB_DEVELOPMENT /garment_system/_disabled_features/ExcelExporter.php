<?php
/**
 * Excel Export Utility Class
 * Provides Excel export functionality for all modules
 */

class ExcelExporter {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = getDbConnection();
    }
    
    /**
     * Export data to CSV format (Excel compatible)
     */
    public function exportToCSV($data, $headers, $filename) {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Export Master Data - Operations
     */
    public function exportOperations() {
        $stmt = $this->db->query("
            SELECT 
                o.code as 'Operation Code',
                o.name as 'Operation Name',
                o.category as 'Category',
                o.standard_smv as 'Standard SMV',
                mt.name as 'Default Machine Type',
                CASE WHEN o.is_active = 1 THEN 'Active' ELSE 'Inactive' END as 'Status',
                o.created_at as 'Created Date'
            FROM operations o
            LEFT JOIN machine_types mt ON o.default_machine_type_id = mt.machine_type_id
            ORDER BY o.code
        ");
        
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Operation Code', 'Operation Name', 'Category', 'Standard SMV', 'Default Machine Type', 'Status', 'Created Date'];
        
        $this->logExport('operations', count($data));
        $this->exportToCSV($data, $headers, 'operations_' . date('Y-m-d_H-i-s'));
    }
    
    /**
     * Export Master Data - Machine Types
     */
    public function exportMachineTypes() {
        $stmt = $this->db->query("
            SELECT 
                code as 'Machine Code',
                name as 'Machine Name',
                category as 'Category',
                efficiency_factor as 'Efficiency Factor',
                CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as 'Status',
                created_at as 'Created Date'
            FROM machine_types
            ORDER BY code
        ");
        
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Machine Code', 'Machine Name', 'Category', 'Efficiency Factor', 'Status', 'Created Date'];
        
        $this->logExport('machine_types', count($data));
        $this->exportToCSV($data, $headers, 'machine_types_' . date('Y-m-d_H-i-s'));
    }
    
    /**
     * Export Master Data - Styles
     */
    public function exportStyles() {
        $stmt = $this->db->query("
            SELECT 
                style_code as 'Style Code',
                description as 'Description',
                product as 'Product',
                fabric as 'Fabric',
                season as 'Season',
                spi as 'SPI',
                stitch_length as 'Stitch Length',
                CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as 'Status',
                created_at as 'Created Date'
            FROM styles
            ORDER BY style_code
        ");
        
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Style Code', 'Description', 'Product', 'Fabric', 'Season', 'SPI', 'Stitch Length', 'Status', 'Created Date'];
        
        $this->logExport('styles', count($data));
        $this->exportToCSV($data, $headers, 'styles_' . date('Y-m-d_H-i-s'));
    }
    
    /**
     * Export Master Data - GSD Elements
     */
    public function exportGSDElements() {
        $stmt = $this->db->query("
            SELECT 
                code as 'Element Code',
                category as 'Category',
                description as 'Description',
                standard_time as 'Standard Time (min)',
                frequency_type as 'Frequency Type',
                CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as 'Status',
                created_at as 'Created Date'
            FROM gsd_elements
            ORDER BY code
        ");
        
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Element Code', 'Category', 'Description', 'Standard Time (min)', 'Frequency Type', 'Status', 'Created Date'];
        
        $this->logExport('gsd_elements', count($data));
        $this->exportToCSV($data, $headers, 'gsd_elements_' . date('Y-m-d_H-i-s'));
    }
    
    /**
     * Export Operation Breakdown (OB) Report
     */
    public function exportOB($ob_id = null) {
        $whereClause = $ob_id ? "WHERE ob.ob_id = " . intval($ob_id) : "";
        
        $stmt = $this->db->query("
            SELECT 
                s.style_code as 'Style Code',
                ob.ob_name as 'OB Name',
                ob.plan_efficiency as 'Plan Efficiency',
                ob.working_hours as 'Working Hours',
                ob.target_at_100 as 'Target at 100%',
                obi.seq as 'Sequence',
                o.name as 'Operation',
                mt.name as 'Machine Type',
                obi.smv_min as 'SMV (min)',
                obi.target_per_hour as 'Target/Hour',
                obi.target_per_day as 'Target/Day',
                obi.operators_required as 'Operators Required',
                obi.operators_rounded as 'Operators Rounded',
                ob.status as 'Status',
                ob.created_at as 'Created Date'
            FROM ob
            JOIN styles s ON ob.style_id = s.style_id
            LEFT JOIN ob_items obi ON ob.ob_id = obi.ob_id
            LEFT JOIN operations o ON obi.operation_id = o.operation_id
            LEFT JOIN machine_types mt ON obi.machine_type_id = mt.machine_type_id
            $whereClause
            ORDER BY s.style_code, ob.ob_id, obi.seq
        ");
        
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Style Code', 'OB Name', 'Plan Efficiency', 'Working Hours', 'Target at 100%', 
                   'Sequence', 'Operation', 'Machine Type', 'SMV (min)', 'Target/Hour', 'Target/Day', 
                   'Operators Required', 'Operators Rounded', 'Status', 'Created Date'];
        
        $filename = $ob_id ? "ob_$ob_id" : 'all_ob_reports';
        $this->logExport('ob', count($data));
        $this->exportToCSV($data, $headers, $filename . '_' . date('Y-m-d_H-i-s'));
    }
    
    /**
     * Export Thread Consumption Report (TCR)
     */
    public function exportTCR($tcr_id = null) {
        $whereClause = $tcr_id ? "WHERE tcr.tcr_id = " . intval($tcr_id) : "";
        
        $stmt = $this->db->query("
            SELECT 
                s.style_code as 'Style Code',
                tcr.tcr_name as 'TCR Name',
                o.name as 'Operation',
                mt.name as 'Machine Type',
                tcri.rows as 'Rows',
                tcri.seam_len_cm as 'Seam Length (cm)',
                tcri.factor_per_cm as 'Factor per CM',
                tcri.pct_needle as 'Needle %',
                tcri.pct_bobbin as 'Bobbin %',
                tcri.pct_looper as 'Looper %',
                tcri.total_cm as 'Total CM',
                tcri.needle_cm as 'Needle CM',
                tcri.bobbin_cm as 'Bobbin CM',
                tcri.looper_cm as 'Looper CM',
                tcr.status as 'Status',
                tcr.created_at as 'Created Date'
            FROM tcr
            JOIN styles s ON tcr.style_id = s.style_id
            LEFT JOIN tcr_items tcri ON tcr.tcr_id = tcri.tcr_id
            LEFT JOIN operations o ON tcri.operation_id = o.operation_id
            LEFT JOIN machine_types mt ON tcri.machine_type_id = mt.machine_type_id
            $whereClause
            ORDER BY s.style_code, tcr.tcr_id
        ");
        
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Style Code', 'TCR Name', 'Operation', 'Machine Type', 'Rows', 'Seam Length (cm)', 
                   'Factor per CM', 'Needle %', 'Bobbin %', 'Looper %', 'Total CM', 'Needle CM', 
                   'Bobbin CM', 'Looper CM', 'Status', 'Created Date'];
        
        $filename = $tcr_id ? "tcr_$tcr_id" : 'all_tcr_reports';
        $this->logExport('tcr', count($data));
        $this->exportToCSV($data, $headers, $filename . '_' . date('Y-m-d_H-i-s'));
    }
    
    /**
     * Export Method Analysis Report
     */
    public function exportMethodAnalysis($method_id = null) {
        $whereClause = $method_id ? "WHERE ma.method_id = " . intval($method_id) : "";
        
        $stmt = $this->db->query("
            SELECT 
                ma.operation_name as 'Operation Name',
                ma.method_name as 'Method Name',
                ma.product as 'Product',
                ma.fabric as 'Fabric',
                ma.stitch_length as 'Stitch Length',
                ma.spi as 'SPI',
                ma.speed as 'Speed',
                ma.layers as 'Layers',
                ma.machine_time_sec as 'Machine Time (sec)',
                ma.needle_time_pct as 'Needle Time %',
                ma.total_smv as 'Total SMV',
                ge.code as 'GSD Element Code',
                ge.description as 'GSD Description',
                me.count as 'Element Count',
                me.time_sec as 'Time (sec)',
                me.allowance_sec as 'Allowance (sec)',
                ma.status as 'Status',
                ma.created_at as 'Created Date'
            FROM method_analysis ma
            LEFT JOIN method_elements me ON ma.method_id = me.method_id
            LEFT JOIN gsd_elements ge ON me.element_id = ge.element_id
            $whereClause
            ORDER BY ma.method_id, me.method_elem_id
        ");
        
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['Operation Name', 'Method Name', 'Product', 'Fabric', 'Stitch Length', 'SPI', 
                   'Speed', 'Layers', 'Machine Time (sec)', 'Needle Time %', 'Total SMV', 
                   'GSD Element Code', 'GSD Description', 'Element Count', 'Time (sec)', 
                   'Allowance (sec)', 'Status', 'Created Date'];
        
        $filename = $method_id ? "method_analysis_$method_id" : 'all_method_analysis';
        $this->logExport('method_analysis', count($data));
        $this->exportToCSV($data, $headers, $filename . '_' . date('Y-m-d_H-i-s'));
    }
    
    /**
     * Export Activity Log
     */
    public function exportActivityLog($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                u.username as 'User',
                al.table_name as 'Table',
                al.record_id as 'Record ID',
                al.action as 'Action',
                al.ip_address as 'IP Address',
                al.created_at as 'Date/Time'
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.user_id
            WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY al.created_at DESC
        ");
        
        $stmt->execute([$days]);
        $data = $stmt->fetchAll(PDO::FETCH_NUM);
        $headers = ['User', 'Table', 'Record ID', 'Action', 'IP Address', 'Date/Time'];
        
        $this->logExport('activity_log', count($data));
        $this->exportToCSV($data, $headers, 'activity_log_' . $days . 'days_' . date('Y-m-d_H-i-s'));
    }
    
    /**
     * Log export activity
     */
    private function logExport($table_name, $record_count) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (user_id, table_name, record_id, action, new_data, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $table_name,
                0, // No specific record ID for exports
                'EXPORT',
                json_encode(['record_count' => $record_count, 'export_type' => 'CSV']),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Failed to log export activity: " . $e->getMessage());
        }
    }

    // Alias methods for dashboard compatibility
    public function exportObReports() {
        return $this->exportOB();
    }
    
    public function exportTcrReports() {
        return $this->exportTCR();
    }
    
    public function exportActivityLogs() {
        return $this->exportActivityLog();
    }
}
?>