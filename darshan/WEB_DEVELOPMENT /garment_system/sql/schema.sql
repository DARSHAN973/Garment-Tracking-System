-- Garment Production System Database Schema
-- Based on the functional requirements specification

SET foreign_key_checks = 0;

-- Drop tables if they exist (for clean setup)
DROP TABLE IF EXISTS method_elements;
DROP TABLE IF EXISTS method_analysis;
DROP TABLE IF EXISTS tcr_items;
DROP TABLE IF EXISTS tcr;
DROP TABLE IF EXISTS ob_items;
DROP TABLE IF EXISTS ob;
DROP TABLE IF EXISTS thread_factors;
DROP TABLE IF EXISTS gsd_elements;
DROP TABLE IF EXISTS operation_catalog;
DROP TABLE IF EXISTS machine_types;
DROP TABLE IF EXISTS styles;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS users;

-- =====================================================
-- SYSTEM TABLES
-- =====================================================

-- Users table for authentication
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('IE', 'Planner', 'Admin') NOT NULL DEFAULT 'IE',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- User sessions for security
CREATE TABLE user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Audit log for tracking changes
CREATE TABLE audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('CREATE', 'UPDATE', 'DELETE', 'APPROVE') NOT NULL,
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- MASTER DATA TABLES
-- =====================================================

-- Styles master
CREATE TABLE styles (
    style_id INT AUTO_INCREMENT PRIMARY KEY,
    style_code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    product VARCHAR(100),
    fabric VARCHAR(100),
    spi DECIMAL(5,2),
    stitch_length DECIMAL(8,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_style_code (style_code),
    INDEX idx_is_active (is_active)
);

-- Machine Types master
CREATE TABLE machine_types (
    machine_type_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active)
);

-- Operation Catalog master
CREATE TABLE operation_catalog (
    operation_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    default_machine_type_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (default_machine_type_id) REFERENCES machine_types(machine_type_id) ON SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
);

-- GSD Elements master for Method Analysis
CREATE TABLE gsd_elements (
    element_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    category VARCHAR(50),
    description VARCHAR(255),
    std_time_sec DECIMAL(8,3) NOT NULL DEFAULT 0,
    cond_len_5_sec DECIMAL(8,3) DEFAULT 0,
    cond_len_15_sec DECIMAL(8,3) DEFAULT 0,
    cond_len_30_sec DECIMAL(8,3) DEFAULT 0,
    cond_len_45_sec DECIMAL(8,3) DEFAULT 0,
    cond_len_80_sec DECIMAL(8,3) DEFAULT 0,
    short_time_sec DECIMAL(8,3) DEFAULT 0,
    long_time_sec DECIMAL(8,3) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_code (code),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
);

-- Thread Factors master for TCR calculations
CREATE TABLE thread_factors (
    thread_factor_id INT AUTO_INCREMENT PRIMARY KEY,
    machine_type_id INT NOT NULL,
    factor_per_cm DECIMAL(8,4) NOT NULL,
    needle_count INT DEFAULT 0,
    looper_count INT DEFAULT 0,
    pct_needle DECIMAL(5,4) DEFAULT 0,
    pct_bobbin DECIMAL(5,4) DEFAULT 0,
    pct_looper DECIMAL(5,4) DEFAULT 0,
    backtack_cm DECIMAL(6,2) DEFAULT 0,
    end_waste_cm DECIMAL(6,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (machine_type_id) REFERENCES machine_types(machine_type_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_machine_type_id (machine_type_id),
    INDEX idx_is_active (is_active),
    -- Constraint to ensure percentage splits don't exceed 100%
    CONSTRAINT chk_thread_pct_valid CHECK (
        pct_needle >= 0 AND pct_bobbin >= 0 AND pct_looper >= 0 AND
        (pct_needle + pct_bobbin + pct_looper) <= 1.0
    )
);

-- =====================================================
-- TRANSACTION TABLES
-- =====================================================

-- Operation Breakdown (OB) header
CREATE TABLE ob (
    ob_id INT AUTO_INCREMENT PRIMARY KEY,
    style_id INT NOT NULL,
    plan_efficiency DECIMAL(4,3) NOT NULL,
    working_hours INT NOT NULL,
    target_at_100 INT NOT NULL DEFAULT 0,
    status ENUM('Draft', 'Approved') DEFAULT 'Draft',
    version INT DEFAULT 1,
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (style_id) REFERENCES styles(style_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_style_id (style_id),
    INDEX idx_status (status),
    INDEX idx_version (version),
    -- Business rule constraints
    CONSTRAINT chk_ob_efficiency CHECK (plan_efficiency > 0 AND plan_efficiency <= 1),
    CONSTRAINT chk_ob_hours CHECK (working_hours >= 6 AND working_hours <= 12)
);

-- Operation Breakdown items
CREATE TABLE ob_items (
    ob_item_id INT AUTO_INCREMENT PRIMARY KEY,
    ob_id INT NOT NULL,
    seq INT NOT NULL,
    operation_id INT NOT NULL,
    machine_type_id INT NOT NULL,
    smv_min DECIMAL(8,4) NOT NULL,
    -- Calculated fields (stored for performance and audit)
    target_per_hour DECIMAL(10,2),
    target_per_day DECIMAL(10,2),
    operators_required DECIMAL(8,3),
    operators_rounded INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ob_id) REFERENCES ob(ob_id) ON DELETE CASCADE,
    FOREIGN KEY (operation_id) REFERENCES operation_catalog(operation_id) ON DELETE RESTRICT,
    FOREIGN KEY (machine_type_id) REFERENCES machine_types(machine_type_id) ON DELETE RESTRICT,
    INDEX idx_ob_id (ob_id),
    INDEX idx_seq (seq),
    UNIQUE KEY uk_ob_seq (ob_id, seq),
    -- Business rule constraints
    CONSTRAINT chk_ob_smv CHECK (smv_min > 0),
    CONSTRAINT chk_ob_seq CHECK (seq > 0)
);

-- Thread Consumption Report (TCR) header
CREATE TABLE tcr (
    tcr_id INT AUTO_INCREMENT PRIMARY KEY,
    style_id INT NOT NULL,
    status ENUM('Draft', 'Approved') DEFAULT 'Draft',
    version INT DEFAULT 1,
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (style_id) REFERENCES styles(style_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_style_id (style_id),
    INDEX idx_status (status),
    INDEX idx_version (version)
);

-- Thread Consumption Report items
CREATE TABLE tcr_items (
    tcr_item_id INT AUTO_INCREMENT PRIMARY KEY,
    tcr_id INT NOT NULL,
    operation_id INT NOT NULL,
    machine_type_id INT NOT NULL,
    rows INT NOT NULL,
    seam_len_cm DECIMAL(8,2) NOT NULL,
    -- Resolved factors (stored to maintain historical accuracy)
    factor_per_cm DECIMAL(8,4) NOT NULL,
    pct_needle DECIMAL(5,4) DEFAULT 0,
    pct_bobbin DECIMAL(5,4) DEFAULT 0,
    pct_looper DECIMAL(5,4) DEFAULT 0,
    -- Calculated consumption values
    total_cm DECIMAL(12,2),
    needle_cm DECIMAL(12,2),
    bobbin_cm DECIMAL(12,2),
    looper_cm DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tcr_id) REFERENCES tcr(tcr_id) ON DELETE CASCADE,
    FOREIGN KEY (operation_id) REFERENCES operation_catalog(operation_id) ON DELETE RESTRICT,
    FOREIGN KEY (machine_type_id) REFERENCES machine_types(machine_type_id) ON DELETE RESTRICT,
    INDEX idx_tcr_id (tcr_id),
    INDEX idx_operation_id (operation_id),
    -- Business rule constraints
    CONSTRAINT chk_tcr_rows CHECK (rows >= 1),
    CONSTRAINT chk_tcr_seam_len CHECK (seam_len_cm > 0),
    CONSTRAINT chk_tcr_factor CHECK (factor_per_cm > 0)
);

-- Method Analysis header
CREATE TABLE method_analysis (
    method_id INT AUTO_INCREMENT PRIMARY KEY,
    ob_item_id INT NOT NULL,
    product VARCHAR(100),
    fabric VARCHAR(100),
    stitch_length DECIMAL(8,2),
    spi DECIMAL(5,2),
    speed DECIMAL(8,2),
    layers INT DEFAULT 1,
    machine_time_sec DECIMAL(10,3),
    needle_time_pct DECIMAL(5,2),
    status ENUM('Draft', 'Approved') DEFAULT 'Draft',
    version INT DEFAULT 1,
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (ob_item_id) REFERENCES ob_items(ob_item_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON SET NULL,
    INDEX idx_ob_item_id (ob_item_id),
    INDEX idx_status (status),
    INDEX idx_version (version),
    -- Business rule constraints
    CONSTRAINT chk_method_layers CHECK (layers >= 1)
);

-- Method Analysis elements
CREATE TABLE method_elements (
    method_elem_id INT AUTO_INCREMENT PRIMARY KEY,
    method_id INT NOT NULL,
    element_id INT NOT NULL,
    count INT NOT NULL DEFAULT 1,
    time_sec DECIMAL(8,3) NOT NULL DEFAULT 0,
    allowance_sec DECIMAL(8,3) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (method_id) REFERENCES method_analysis(method_id) ON DELETE CASCADE,
    FOREIGN KEY (element_id) REFERENCES gsd_elements(element_id) ON DELETE RESTRICT,
    INDEX idx_method_id (method_id),
    INDEX idx_element_id (element_id),
    -- Business rule constraints
    CONSTRAINT chk_method_count CHECK (count >= 1),
    CONSTRAINT chk_method_time CHECK (time_sec >= 0),
    CONSTRAINT chk_method_allowance CHECK (allowance_sec >= 0)
);

SET foreign_key_checks = 1;

-- =====================================================
-- TRIGGERS FOR AUDIT LOGGING
-- =====================================================

DELIMITER $$

-- Audit trigger for styles
CREATE TRIGGER audit_styles_insert AFTER INSERT ON styles
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, table_name, record_id, action, new_values)
    VALUES (NEW.created_by, 'styles', NEW.style_id, 'CREATE', JSON_OBJECT(
        'style_code', NEW.style_code,
        'description', NEW.description,
        'product', NEW.product
    ));
END$$

CREATE TRIGGER audit_styles_update AFTER UPDATE ON styles
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, table_name, record_id, action, old_values, new_values)
    VALUES (NEW.updated_by, 'styles', NEW.style_id, 'UPDATE',
        JSON_OBJECT('style_code', OLD.style_code, 'description', OLD.description),
        JSON_OBJECT('style_code', NEW.style_code, 'description', NEW.description)
    );
END$$

-- Audit trigger for OB approval
CREATE TRIGGER audit_ob_approve AFTER UPDATE ON ob
FOR EACH ROW
BEGIN
    IF OLD.status = 'Draft' AND NEW.status = 'Approved' THEN
        INSERT INTO audit_log (user_id, table_name, record_id, action, new_values)
        VALUES (NEW.approved_by, 'ob', NEW.ob_id, 'APPROVE', JSON_OBJECT(
            'approved_at', NEW.approved_at,
            'version', NEW.version
        ));
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Composite indexes for common queries
CREATE INDEX idx_ob_style_status ON ob(style_id, status);
CREATE INDEX idx_tcr_style_status ON tcr(style_id, status);
CREATE INDEX idx_method_ob_status ON method_analysis(ob_item_id, status);

-- Indexes for audit queries
CREATE INDEX idx_audit_table_action ON audit_log(table_name, action);
CREATE INDEX idx_audit_user_date ON audit_log(user_id, created_at);