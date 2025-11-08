-- Migration: Add missing GSD element time columns
-- This script adds the missing time columns to match client requirements

-- Add missing conditional length and time category columns to gsd_elements table
ALTER TABLE gsd_elements 
ADD COLUMN cond_len_45_sec DECIMAL(8,3) DEFAULT 0 AFTER cond_len_30_sec,
ADD COLUMN cond_len_80_sec DECIMAL(8,3) DEFAULT 0 AFTER cond_len_45_sec,
ADD COLUMN short_time_sec DECIMAL(8,3) DEFAULT 0 AFTER cond_len_80_sec,
ADD COLUMN long_time_sec DECIMAL(8,3) DEFAULT 0 AFTER short_time_sec;

-- Update any existing records to have default values
UPDATE gsd_elements 
SET 
    cond_len_45_sec = 0,
    cond_len_80_sec = 0,
    short_time_sec = 0,
    long_time_sec = 0
WHERE 
    cond_len_45_sec IS NULL 
    OR cond_len_80_sec IS NULL 
    OR short_time_sec IS NULL 
    OR long_time_sec IS NULL;