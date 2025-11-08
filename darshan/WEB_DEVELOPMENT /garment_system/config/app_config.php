<?php
/**
 * Application Configuration
 * Garment Production System
 */

// Session Configuration (MUST be set before any session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.gc_maxlifetime', 1800); // Will be updated below with SESSION_TIMEOUT

// Application Settings
define('APP_NAME', 'Garment Production System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/garment_system');

// Security Settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// File Upload Settings
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXCEL_TYPES', ['xlsx', 'xls']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Calculation Precision
define('SMV_PRECISION', 4);
define('EFFICIENCY_PRECISION', 3);
define('TARGET_PRECISION', 2);
define('CONSUMPTION_PRECISION', 2);

// Business Rules
define('MIN_PLAN_EFFICIENCY', 0.01);
define('MAX_PLAN_EFFICIENCY', 1.0);
define('MIN_WORKING_HOURS', 6);
define('MAX_WORKING_HOURS', 12);
define('MIN_SMV', 0.01);
define('MAX_THREAD_PCT_SUM', 1.0);

// User Roles
define('ROLE_IE', 'IE');
define('ROLE_PLANNER', 'Planner');
define('ROLE_ADMIN', 'Admin');

// Status Values
define('STATUS_DRAFT', 'Draft');
define('STATUS_APPROVED', 'Approved');

// Timezone Settings
define('APP_TIMEZONE', 'Asia/Kolkata');
define('DB_TIMEZONE', 'UTC');

// Error Reporting (set to 0 in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set application timezone
date_default_timezone_set(APP_TIMEZONE);

// Update session timeout with the defined constant
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

/**
 * Permission Matrix
 * Defines what each role can do
 */
$PERMISSIONS = [
    ROLE_IE => [
        'masters' => ['read', 'write'],
        'ob' => ['read', 'write'],
        'tcr' => ['read', 'write'],
        'method' => ['read', 'write'],
        'method_analysis' => ['read', 'write'],
        'imports' => ['read', 'write'],
        'approvals' => ['submit', 'approve']
    ],
    ROLE_PLANNER => [
        'masters' => ['read'],
        'ob' => ['read'],
        'tcr' => ['read'],
        'method' => ['read'],
        'method_analysis' => ['read'],
        'imports' => ['read'],
        'approvals' => []
    ],
    ROLE_ADMIN => [
        'masters' => ['read', 'write', 'delete'],
        'ob' => ['read', 'write', 'delete'],
        'tcr' => ['read', 'write', 'delete'],
        'method' => ['read', 'write', 'delete'],
        'method_analysis' => ['read', 'write', 'delete'],
        'imports' => ['read', 'write', 'delete'],
        'approvals' => ['submit', 'approve', 'override']
    ]
];

/**
 * Check if user has permission
 */
function hasPermission($userRole, $module, $action) {
    global $PERMISSIONS;
    return isset($PERMISSIONS[$userRole][$module]) && 
           in_array($action, $PERMISSIONS[$userRole][$module]);
}

/**
 * Get formatted date time for display (IST)
 */
function formatDateTime($utcDateTime) {
    if (!$utcDateTime) return '';
    
    $utc = new DateTime($utcDateTime, new DateTimeZone('UTC'));
    $ist = $utc->setTimezone(new DateTimeZone(APP_TIMEZONE));
    return $ist->format('d-m-Y H:i:s');
}

/**
 * Get current UTC timestamp for database storage
 */
function getCurrentUTC() {
    return gmdate('Y-m-d H:i:s');
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate numeric input with precision
 */
function validateNumeric($value, $min = null, $max = null, $precision = null) {
    if (!is_numeric($value)) return false;
    
    $value = floatval($value);
    
    if ($min !== null && $value < $min) return false;
    if ($max !== null && $value > $max) return false;
    
    if ($precision !== null) {
        $decimals = strlen(substr(strrchr($value, "."), 1));
        if ($decimals > $precision) return false;
    }
    
    return true;
}

/**
 * Format number with proper precision
 */
function formatNumber($number, $precision = 2) {
    return number_format($number, $precision, '.', '');
}
?>