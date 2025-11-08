<?php
require_once '../utils/Database.php';

try {
    $db = new DatabaseHelper();
    
    echo "Starting capacity analysis module migration...\n\n";
    
    // 1. Create capacity_scenarios table
    echo "1. Creating capacity_scenarios table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS capacity_scenarios (
            scenario_id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            target_production INT NOT NULL DEFAULT 0,
            working_hours_per_day DECIMAL(4,2) NOT NULL DEFAULT 8.00,
            working_days_per_week INT NOT NULL DEFAULT 5,
            efficiency_factor DECIMAL(5,4) NOT NULL DEFAULT 0.8500,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_created_by (created_by),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB
    ");
    
    // 2. Create line_configurations table
    echo "2. Creating line_configurations table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS line_configurations (
            line_config_id INT PRIMARY KEY AUTO_INCREMENT,
            scenario_id INT NOT NULL,
            line_name VARCHAR(50) NOT NULL,
            total_operators INT NOT NULL DEFAULT 1,
            helper_operators INT NOT NULL DEFAULT 0,
            line_efficiency DECIMAL(5,4) NOT NULL DEFAULT 0.8500,
            bottle_neck_time DECIMAL(8,4) DEFAULT NULL,
            cycle_time DECIMAL(8,4) DEFAULT NULL,
            theoretical_output INT DEFAULT NULL,
            actual_output INT DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (scenario_id) REFERENCES capacity_scenarios(scenario_id) ON DELETE CASCADE,
            INDEX idx_scenario (scenario_id),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB
    ");
    
    // 3. Create operation_assignments table
    echo "3. Creating operation_assignments table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS operation_assignments (
            assignment_id INT PRIMARY KEY AUTO_INCREMENT,
            line_config_id INT NOT NULL,
            operation_id INT NOT NULL,
            station_number INT NOT NULL,
            assigned_operators INT NOT NULL DEFAULT 1,
            operator_grade ENUM('A', 'B', 'C') DEFAULT 'B',
            allocated_smv DECIMAL(8,4) NOT NULL,
            actual_cycle_time DECIMAL(8,4) DEFAULT NULL,
            efficiency_rating DECIMAL(5,4) DEFAULT 0.8500,
            sequence_order INT NOT NULL,
            is_bottleneck BOOLEAN DEFAULT FALSE,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (line_config_id) REFERENCES line_configurations(line_config_id) ON DELETE CASCADE,
            FOREIGN KEY (operation_id) REFERENCES operations(operation_id) ON DELETE CASCADE,
            INDEX idx_line_config (line_config_id),
            INDEX idx_operation (operation_id),
            INDEX idx_station (station_number),
            INDEX idx_sequence (sequence_order),
            UNIQUE KEY unique_line_operation (line_config_id, operation_id)
        ) ENGINE=InnoDB
    ");
    
    // 4. Create capacity_calculations table
    echo "4. Creating capacity_calculations table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS capacity_calculations (
            calculation_id INT PRIMARY KEY AUTO_INCREMENT,
            scenario_id INT NOT NULL,
            total_smv DECIMAL(8,4) NOT NULL,
            total_operators INT NOT NULL,
            theoretical_output INT NOT NULL,
            actual_output INT NOT NULL,
            line_efficiency DECIMAL(5,4) NOT NULL,
            bottleneck_station INT DEFAULT NULL,
            bottleneck_time DECIMAL(8,4) DEFAULT NULL,
            balance_efficiency DECIMAL(5,4) NOT NULL,
            smoothness_index DECIMAL(8,4) NOT NULL,
            calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (scenario_id) REFERENCES capacity_scenarios(scenario_id) ON DELETE CASCADE,
            INDEX idx_scenario (scenario_id),
            INDEX idx_calculated (calculated_at)
        ) ENGINE=InnoDB
    ");
    
    // 5. Create machine_requirements table
    echo "5. Creating machine_requirements table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS machine_requirements (
            requirement_id INT PRIMARY KEY AUTO_INCREMENT,
            scenario_id INT NOT NULL,
            machine_type_id INT NOT NULL,
            required_quantity INT NOT NULL DEFAULT 1,
            available_quantity INT NOT NULL DEFAULT 0,
            utilization_percentage DECIMAL(5,2) DEFAULT 0.00,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (scenario_id) REFERENCES capacity_scenarios(scenario_id) ON DELETE CASCADE,
            FOREIGN KEY (machine_type_id) REFERENCES machine_types(machine_type_id) ON DELETE CASCADE,
            INDEX idx_scenario (scenario_id),
            INDEX idx_machine_type (machine_type_id),
            UNIQUE KEY unique_scenario_machine (scenario_id, machine_type_id)
        ) ENGINE=InnoDB
    ");
    
    echo "\n✅ Capacity analysis module migration completed successfully!\n";
    echo "Created tables:\n";
    echo "- capacity_scenarios: For storing capacity planning scenarios\n";
    echo "- line_configurations: For line setup and configuration data\n";
    echo "- operation_assignments: For assigning operations to workstations\n";
    echo "- capacity_calculations: For storing calculation results\n";
    echo "- machine_requirements: For tracking machine utilization\n\n";
    
    // Show table structures
    echo "=== TABLE STRUCTURES ===\n\n";
    
    $tables = [
        'capacity_scenarios',
        'line_configurations', 
        'operation_assignments',
        'capacity_calculations',
        'machine_requirements'
    ];
    
    foreach ($tables as $table) {
        echo "Table: {$table}\n";
        $columns = $db->query("DESCRIBE {$table}");
        foreach ($columns as $column) {
            echo "  {$column['Field']} - {$column['Type']}\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>