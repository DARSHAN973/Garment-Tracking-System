<?php
/**
 * Quick Demo Script - Shows Data Entry Points
 * Demonstrates where users add data in the system
 */

require_once 'config/database.php';

echo "<h1>🏭 Garment Production System - Data Entry Points Demo</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.module { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
.entry-point { background: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 4px solid #007bff; }
.success { color: green; font-weight: bold; }
.info { color: blue; font-weight: bold; }
.example { background: #e7f3ff; padding: 8px; margin: 5px 0; font-family: monospace; }
</style>";

try {
    $db = getDbConnection();
    echo "<div class='success'>✅ Database Connected - Ready for Data Entry</div>";
    echo "<div class='module'>";
    echo "<h2>1️⃣ Master Data Entry Points</h2>";
    echo "<p>Foundation data that users enter to set up the production environment:</p>";
    echo "<div class='entry-point'>";
    echo "<h3>📝 Operations (/masters/operations.php)</h3>";
    echo "<p><strong>Where Users Add:</strong> Operation definitions with SMV values</p>";
    echo "<div class='example'>Example Entry:<br>Code: OP001<br>Name: Attach Label<br>Category: Finishing<br>SMV: 0.5000</div>";
    
    // Show current operations
    $operations = $db->query("SELECT * FROM operations LIMIT 3")->fetchAll();
    echo "<p><strong>Current Operations in System:</strong></p>";
    foreach ($operations as $op) {
        echo "<div class='example'>{$op['code']} - {$op['name']} ({$op['category']}) - {$op['standard_smv']} SMV</div>";
    }
    echo "</div>";
    
    echo "<div class='entry-point'>";
    echo "<h3>🔧 Machine Types (/masters/machine_types.php)</h3>";
    echo "<p><strong>Where Users Add:</strong> Machine specifications and capabilities</p>";
    echo "<div class='example'>Example Entry:<br>Code: SNLS<br>Name: Single Needle Lock Stitch Machine</div>";
    echo "</div>";
    
    echo "<div class='entry-point'>";
    echo "<h3>👗 Styles (/masters/styles.php)</h3>";
    echo "<p><strong>Where Users Add:</strong> Garment style specifications</p>";
    echo "<div class='example'>Example Entry:<br>Style Code: SS26-KD-1J-DRS-00028<br>Product: DRESS<br>Fabric: JERSEY<br>SPI: 12.00</div>";
    echo "</div>";
    
    echo "<div class='entry-point'>";
    echo "<h3>⏱️ GSD Elements (/masters/gsd_elements.php)</h3>";
    echo "<p><strong>Where Users Add:</strong> Time study basic elements</p>";
    echo "<div class='example'>Example Entry:<br>Code: G001<br>Description: Grasp small object<br>Standard Time: 0.0200 TMU</div>";
    
    // Show current GSD elements
    $elements = $db->query("SELECT * FROM gsd_elements LIMIT 3")->fetchAll();
    echo "<p><strong>Current GSD Elements in System:</strong></p>";
    foreach ($elements as $elem) {
        echo "<div class='example'>{$elem['code']} - {$elem['description']} - {$elem['standard_time']} TMU</div>";
    }
    echo "</div>";
    echo "</div>";
    
    echo "<div class='module'>";
    echo "<h2>2️⃣ Production Data Entry Points</h2>";
    echo "<p>Main business transactions where users enter production data:</p>";
    
    echo "<div class='entry-point'>";
    echo "<h3>📋 OB - Operation Breakdown (/ob/ob_create.php)</h3>";
    echo "<p><strong>Where Users Add:</strong> Complete operation sequences for garment production</p>";
    echo "<div class='example'>Entry Process:<br>1. Select Style (dropdown)<br>2. Enter OB Name<br>3. Add Operations Sequence:<br>   - Select Operation → Select Machine → Enter SMV<br>   - Repeat for each operation<br>4. System Calculates: Total SMV, Target per Hour</div>";
    echo "<p><strong>Example:</strong> Ladies Dress with 8 operations, Total SMV = 3.220, Target = 18 pieces/hour</p>";
    echo "</div>";
    
    echo "<div class='entry-point'>";
    echo "<h3>🧵 TCR - Thread Consumption Records (/tcr/tcr_create.php)</h3>";
    echo "<p><strong>Where Users Add:</strong> Thread consumption and cost data</p>";
    echo "<div class='example'>Entry Process:<br>1. Select Style and Related OB<br>2. Enter TCR Name<br>3. Add Thread Consumptions:<br>   - Thread Type → Color → Consumption → Unit Cost<br>   - Repeat for each thread type<br>4. System Calculates: Total thread cost per piece</div>";
    echo "<p><strong>Example:</strong> 5 thread types, Total cost = $0.365 per piece</p>";
    echo "</div>";
    
    echo "<div class='entry-point'>";
    echo "<h3>🔬 Method Analysis (/method_analysis/method_create.php)</h3>";
    echo "<p><strong>Where Users Add:</strong> Detailed time studies using GSD elements</p>";
    echo "<div class='example'>Entry Process:<br>1. Select Style and Operation Name<br>2. Enter Method Name<br>3. Add GSD Elements Sequence:<br>   - Select Element → Set Frequency → Adjust Time<br>   - Repeat for complete element breakdown<br>4. System Calculates: Total method time in TMU</div>";
    echo "<p><strong>Example:</strong> Sleeve Attach operation with 15 elements, Total time = 0.845 TMU</p>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='module'>";
    echo "<h2>3️⃣ Data Flow Summary</h2>";
    echo "<div class='info'>📊 Data Entry → Automatic Calculations → Storage → Analysis → Reports</div>";
    echo "<p><strong>1. Entry:</strong> Users add data through forms (masters, OB, TCR, method analysis)</p>";
    echo "<p><strong>2. Processing:</strong> System automatically calculates SMV totals, costs, time studies</p>";
    echo "<p><strong>3. Storage:</strong> Data stored in linked database tables with relationships</p>";
    echo "<p><strong>4. Analysis:</strong> Real-time analysis on lists and dashboard</p>";
    echo "<p><strong>5. Reports:</strong> Dashboard analytics and detailed views for all data types</p>";
    echo "</div>";
    
    echo "<div class='module'>";
    echo "<h2>🚀 System Capabilities</h2>";
    echo "<div class='success'>✅ Replaces Excel-based tracking with web-based system</div>";
    echo "<div class='success'>✅ Real-time calculations (no manual formula updates)</div>";
    echo "<div class='success'>✅ Data validation and dropdown controls</div>";
    echo "<div class='success'>✅ Multi-user access with role-based permissions</div>";
    echo "<div class='success'>✅ Complete audit trail of all changes</div>";
    echo "<div class='success'>✅ Dashboard analytics for analysis and reporting</div>";
    echo "<div class='success'>✅ Centralized data with proper relationships</div>";
    echo "</div>";
    
    // Get table counts
    $tables = ['operations', 'machine_types', 'styles', 'gsd_elements', 'ob', 'tcr', 'method_analysis'];
    echo "<div class='module'>";
    echo "<h2>📈 Current System Status</h2>";
    foreach ($tables as $table) {
        try {
            $count = $db->query("SELECT COUNT(*) as count FROM `$table`")->fetch()['count'];
            echo "<div class='info'>$table: $count records</div>";
        } catch (Exception $e) {
            echo "<div>$table: Table ready</div>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
}
?>