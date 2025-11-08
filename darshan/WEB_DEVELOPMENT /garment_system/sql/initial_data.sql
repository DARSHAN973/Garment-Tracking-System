-- Initial data for Garment Production System
-- This file contains sample master data to get started

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role, is_active, created_at) VALUES
('admin', 'admin@garment.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', TRUE, NOW()),
('ie_user', 'ie@garment.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'IE', TRUE, NOW()),
('planner', 'planner@garment.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Planner', TRUE, NOW());

-- Insert Machine Types (based on Excel sheets)
INSERT INTO machine_types (code, name, is_active, created_by) VALUES
('SNLS', 'Single Needle Lock Stitch', TRUE, 1),
('3-TH O/L', '3 Thread Over Lock', TRUE, 1),
('4-TH O/L', '4 Thread Over Lock', TRUE, 1),
('5-TH O/L', '5 Thread Over Lock', TRUE, 1),
('5-TH F/L', '5 Thread Flat Lock', TRUE, 1),
('3-TH F/L', '3 Thread Flat Lock', TRUE, 1),
('COVERSTITCH', 'Cover Stitch Machine', TRUE, 1),
('FLATLOCK', 'Flat Lock Machine', TRUE, 1),
('BARTACK', 'Bar Tack Machine', TRUE, 1),
('BUTTONHOLE', 'Button Hole Machine', TRUE, 1),
('BUTTON ATT', 'Button Attachment Machine', TRUE, 1),
('BLIND STITCH', 'Blind Stitch Machine', TRUE, 1),
('FUSING', 'Fusing Machine', TRUE, 1),
('PRESS', 'Pressing Machine', TRUE, 1);

-- Insert Thread Factors (sample data based on common machine types)
INSERT INTO thread_factors (machine_type_id, factor_per_cm, needle_count, looper_count, pct_needle, pct_bobbin, pct_looper, backtack_cm, end_waste_cm, created_by) VALUES
-- SNLS (ID: 1)
(1, 2.2000, 1, 0, 0.5000, 0.5000, 0.0000, 1.5, 2.0, 1),
-- 3-TH O/L (ID: 2)
(2, 3.5000, 1, 2, 0.3333, 0.0000, 0.6667, 1.0, 1.5, 1),
-- 4-TH O/L (ID: 3)
(3, 4.2000, 2, 2, 0.5000, 0.0000, 0.5000, 1.0, 1.5, 1),
-- 5-TH O/L (ID: 4)
(4, 5.0000, 2, 3, 0.4000, 0.0000, 0.6000, 1.0, 1.5, 1),
-- 5-TH F/L (ID: 5)
(5, 4.8000, 2, 3, 0.4167, 0.0000, 0.5833, 0.8, 1.2, 1),
-- 3-TH F/L (ID: 6)
(6, 3.2000, 1, 2, 0.3125, 0.0000, 0.6875, 0.8, 1.2, 1),
-- COVERSTITCH (ID: 7)
(7, 4.5000, 2, 1, 0.6667, 0.0000, 0.3333, 1.0, 1.8, 1),
-- FLATLOCK (ID: 8)
(8, 3.8000, 1, 2, 0.2632, 0.0000, 0.7368, 0.5, 1.0, 1);

-- Insert Operation Catalog (common garment operations)
INSERT INTO operation_catalog (code, name, category, default_machine_type_id, created_by) VALUES
('OP010', 'Join Shoulder Seam', 'JOINING', 2, 1),
('OP020', 'Attach Sleeve', 'JOINING', 4, 1),
('OP030', 'Join Side Seam', 'JOINING', 5, 1),
('OP040', 'Hem Bottom', 'HEMMING', 8, 1),
('OP050', 'Hem Sleeve', 'HEMMING', 8, 1),
('OP060', 'Attach Collar', 'JOINING', 1, 1),
('OP070', 'Topstitch Collar', 'TOPSTITCH', 1, 1),
('OP080', 'Make Buttonhole', 'FINISHING', 10, 1),
('OP090', 'Attach Button', 'FINISHING', 11, 1),
('OP100', 'Label Attachment', 'FINISHING', 1, 1),
('OP110', 'Blind Stitch Hem', 'HEMMING', 12, 1),
('OP120', 'Bartack Pocket', 'REINFORCEMENT', 9, 1),
('OP130', 'Overlock Seam', 'FINISHING', 3, 1),
('OP140', 'Press Garment', 'PRESSING', 14, 1),
('OP150', 'Final Inspection', 'QC', NULL, 1);

-- Insert GSD Elements (basic motion elements)
INSERT INTO gsd_elements (code, category, description, std_time_sec, cond_len_5_sec, cond_len_15_sec, cond_len_30_sec, created_by) VALUES
-- Basic Hand Movements
('G1A', 'REACH', 'Reach - Short Distance (≤5cm)', 2.5, 2.5, 0, 0, 1),
('G1B', 'REACH', 'Reach - Medium Distance (5-15cm)', 3.5, 0, 3.5, 0, 1),
('G1C', 'REACH', 'Reach - Long Distance (15-30cm)', 5.0, 0, 0, 5.0, 1),
('G2A', 'GRASP', 'Grasp - Simple', 2.0, 2.0, 2.0, 2.0, 1),
('G2B', 'GRASP', 'Grasp - Re-grasp', 5.6, 5.6, 5.6, 5.6, 1),
('G3A', 'MOVE', 'Move - Short Distance (≤5cm)', 2.5, 2.5, 0, 0, 1),
('G3B', 'MOVE', 'Move - Medium Distance (5-15cm)', 3.8, 0, 3.8, 0, 1),
('G3C', 'MOVE', 'Move - Long Distance (15-30cm)', 5.5, 0, 0, 5.5, 1),
('G4A', 'POSITION', 'Position - Loose Fit', 5.6, 5.6, 5.6, 5.6, 1),
('G4B', 'POSITION', 'Position - Close Fit', 9.1, 9.1, 9.1, 9.1, 1),
('G4C', 'POSITION', 'Position - Exact Fit', 14.7, 14.7, 14.7, 14.7, 1),
('G5', 'RELEASE', 'Release Load', 2.0, 2.0, 2.0, 2.0, 1),

-- Eye Movements
('E1', 'EYE_TRAVEL', 'Eye Travel Time', 7.3, 7.3, 7.3, 7.3, 1),
('E2', 'EYE_FOCUS', 'Eye Focus Time', 7.3, 7.3, 7.3, 7.3, 1),

-- Body Movements
('B1', 'BEND', 'Bend and Arise', 61.1, 61.1, 61.1, 61.1, 1),
('B2', 'STOOP', 'Stoop', 106.0, 106.0, 106.0, 106.0, 1),
('B3', 'KNEEL', 'Kneel on One Knee', 69.4, 69.4, 69.4, 69.4, 1),
('B4', 'ARISE', 'Arise from Kneel', 76.7, 76.7, 76.7, 76.7, 1),
('B5', 'SIT', 'Sit', 79.8, 79.8, 79.8, 79.8, 1),
('B6', 'STAND', 'Stand from Sitting', 43.4, 43.4, 43.4, 43.4, 1),

-- Machine Elements
('M1', 'MACHINE_ON', 'Start Machine', 10.0, 10.0, 10.0, 10.0, 1),
('M2', 'MACHINE_OFF', 'Stop Machine', 8.0, 8.0, 8.0, 8.0, 1),
('M3', 'STITCH', 'Stitching Time (per cm)', 28.0, 28.0, 28.0, 28.0, 1),
('M4', 'THREAD_TRIM', 'Thread Trimming', 15.0, 15.0, 15.0, 15.0, 1),
('M5', 'NEEDLE_UP', 'Needle Up Position', 5.0, 5.0, 5.0, 5.0, 1),
('M6', 'PRESSER_UP', 'Presser Foot Up', 8.0, 8.0, 8.0, 8.0, 1),
('M7', 'PRESSER_DOWN', 'Presser Foot Down', 8.0, 8.0, 8.0, 8.0, 1),

-- Fabric Handling
('F1', 'PICK_UP', 'Pick Up Fabric', 25.0, 25.0, 25.0, 25.0, 1),
('F2', 'LINE_UP', 'Line Up Fabric', 32.0, 32.0, 32.0, 32.0, 1),
('F3', 'FOLD', 'Fold Fabric', 45.0, 45.0, 45.0, 45.0, 1),
('F4', 'TURN', 'Turn Fabric', 28.0, 28.0, 28.0, 28.0, 1),
('F5', 'SMOOTH', 'Smooth Fabric', 35.0, 35.0, 35.0, 35.0, 1),
('F6', 'ALIGN', 'Align Edges', 40.0, 40.0, 40.0, 40.0, 1),

-- Quality Control
('Q1', 'INSPECT', 'Visual Inspection', 25.0, 25.0, 25.0, 25.0, 1),
('Q2', 'MEASURE', 'Measure with Gauge', 35.0, 35.0, 35.0, 35.0, 1),
('Q3', 'CHECK_STITCH', 'Check Stitch Quality', 20.0, 20.0, 20.0, 20.0, 1),

-- Allowances
('A1', 'FATIGUE', 'Fatigue Allowance', 0.0, 0.0, 0.0, 0.0, 1),
('A2', 'PERSONAL', 'Personal Allowance', 0.0, 0.0, 0.0, 0.0, 1),
('A3', 'DELAY', 'Unavoidable Delay', 0.0, 0.0, 0.0, 0.0, 1);

-- Insert Sample Style
INSERT INTO styles (style_code, description, product, fabric, spi, stitch_length, created_by) VALUES
('SS26-KD-1J-DRS-00028', 'Kids 1 Jersey Dress Style 28', 'DRESS', 'JERSEY', 12.00, 2.5, 1),
('TS24-AD-CT-SHT-00015', 'Adult Cotton T-Shirt Style 15', 'T-SHIRT', 'COTTON', 14.00, 2.2, 1),
('JN25-CH-DN-PNT-00042', 'Children Denim Pant Style 42', 'PANT', 'DENIM', 10.00, 3.0, 1);

-- Sample Operation Breakdown for style 1
INSERT INTO ob (style_id, plan_efficiency, working_hours, target_at_100, status, created_by) VALUES
(1, 0.70, 8, 870, 'Draft', 1);

-- Sample OB Items
INSERT INTO ob_items (ob_id, seq, operation_id, machine_type_id, smv_min, created_by) VALUES
(1, 10, 1, 2, 0.60, 1),  -- Join Shoulder Seam
(1, 20, 2, 4, 0.85, 1),  -- Attach Sleeve
(1, 30, 3, 5, 0.75, 1),  -- Join Side Seam
(1, 40, 4, 8, 0.45, 1),  -- Hem Bottom
(1, 50, 5, 8, 0.35, 1);  -- Hem Sleeve

-- Update calculated fields for OB items (this would normally be done by application logic)
UPDATE ob_items SET 
    target_per_hour = ROUND((60 / smv_min) * (SELECT plan_efficiency FROM ob WHERE ob_id = ob_items.ob_id), 2),
    target_per_day = ROUND(((60 / smv_min) * (SELECT plan_efficiency FROM ob WHERE ob_id = ob_items.ob_id)) * (SELECT working_hours FROM ob WHERE ob_id = ob_items.ob_id), 2),
    operators_required = ROUND((SELECT target_at_100 FROM ob WHERE ob_id = ob_items.ob_id) / (((60 / smv_min) * (SELECT plan_efficiency FROM ob WHERE ob_id = ob_items.ob_id)) * (SELECT working_hours FROM ob WHERE ob_id = ob_items.ob_id)), 3),
    operators_rounded = CEIL((SELECT target_at_100 FROM ob WHERE ob_id = ob_items.ob_id) / (((60 / smv_min) * (SELECT plan_efficiency FROM ob WHERE ob_id = ob_items.ob_id)) * (SELECT working_hours FROM ob WHERE ob_id = ob_items.ob_id)))
WHERE ob_id = 1;

-- Sample Thread Consumption Report
INSERT INTO tcr (style_id, status, created_by) VALUES (1, 'Draft', 1);

-- Sample TCR Items
INSERT INTO tcr_items (tcr_id, operation_id, machine_type_id, rows, seam_len_cm, factor_per_cm, pct_needle, pct_bobbin, pct_looper, created_by) VALUES
(1, 1, 2, 2, 25.0, 3.5000, 0.3333, 0.0000, 0.6667, 1),  -- Shoulder seam
(1, 3, 5, 2, 45.0, 4.8000, 0.4167, 0.0000, 0.5833, 1),  -- Side seam
(1, 4, 8, 1, 95.0, 3.8000, 0.2632, 0.0000, 0.7368, 1);  -- Bottom hem

-- Update calculated fields for TCR items
UPDATE tcr_items SET
    total_cm = ROUND(rows * seam_len_cm * factor_per_cm, 2),
    needle_cm = ROUND(rows * seam_len_cm * factor_per_cm * pct_needle, 2),
    bobbin_cm = ROUND(rows * seam_len_cm * factor_per_cm * pct_bobbin, 2),
    looper_cm = ROUND(rows * seam_len_cm * factor_per_cm * pct_looper, 2)
WHERE tcr_id = 1;

-- Sample Method Analysis for first OB item
INSERT INTO method_analysis (ob_item_id, product, fabric, stitch_length, spi, speed, layers, status, created_by) VALUES
(1, 'DRESS', 'JERSEY', 2.5, 12.00, 3500, 2, 'Draft', 1);

-- Sample Method Elements
INSERT INTO method_elements (method_id, element_id, count, time_sec, allowance_sec) VALUES
(1, 21, 2, 25.0, 0),    -- Pick up fabric x2
(1, 22, 1, 32.0, 0),    -- Line up fabric
(1, 19, 1, 28.0, 0),    -- Stitching
(1, 20, 1, 15.0, 0),    -- Thread trim
(1, 29, 1, 25.0, 5.0);  -- Inspect with allowance