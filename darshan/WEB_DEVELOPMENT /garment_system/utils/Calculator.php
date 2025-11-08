<?php
/**
 * Calculation Helper Class
 * Contains all business calculation functions
 */

class Calculator {
    
    /**
     * Calculate OB targets and operators
     */
    public static function calculateOBTargets($smv, $planEfficiency, $workingHours, $targetAt100) {
        // Input validation
        if ($smv <= 0 || $planEfficiency <= 0 || $planEfficiency > 1 || $workingHours < 6 || $workingHours > 12) {
            return null;
        }
        
        // Calculate targets per hour
        $targetPerHour = (60 / $smv) * $planEfficiency;
        
        // Calculate targets per day
        $targetPerDay = $targetPerHour * $workingHours;
        
        // Calculate operators required
        $operatorsRequired = $targetAt100 / ($targetPerHour * $workingHours);
        
        // Calculate rounded operators (for planning)
        $operatorsRounded = ceil($operatorsRequired);
        
        return [
            'target_per_hour' => round($targetPerHour, TARGET_PRECISION),
            'target_per_day' => round($targetPerDay, TARGET_PRECISION),
            'operators_required' => round($operatorsRequired, EFFICIENCY_PRECISION),
            'operators_rounded' => $operatorsRounded
        ];
    }
    
    /**
     * Calculate TCR thread consumption
     */
    public static function calculateTCRConsumption($rows, $seamLengthCm, $factorPerCm, $pctNeedle = 0, $pctBobbin = 0, $pctLooper = 0, $backtackCm = 0, $endWasteCm = 0) {
        // Input validation
        if ($rows < 1 || $seamLengthCm <= 0 || $factorPerCm <= 0) {
            return null;
        }
        
        // Check percentage splits
        if (($pctNeedle + $pctBobbin + $pctLooper) > MAX_THREAD_PCT_SUM) {
            return null;
        }
        
        // Calculate adjusted seam length with allowances
        $adjustedSeamLength = $seamLengthCm + $backtackCm + $endWasteCm;
        
        // Calculate total consumption
        $totalCm = $rows * $adjustedSeamLength * $factorPerCm;
        
        // Calculate path-specific consumption
        $needleCm = $totalCm * $pctNeedle;
        $bobbinCm = $totalCm * $pctBobbin;
        $looperCm = $totalCm * $pctLooper;
        
        return [
            'total_cm' => round($totalCm, CONSUMPTION_PRECISION),
            'needle_cm' => round($needleCm, CONSUMPTION_PRECISION),
            'bobbin_cm' => round($bobbinCm, CONSUMPTION_PRECISION),
            'looper_cm' => round($looperCm, CONSUMPTION_PRECISION),
            'adjusted_seam_length' => round($adjustedSeamLength, CONSUMPTION_PRECISION)
        ];
    }
    
    /**
     * Calculate Method Analysis SMV
     */
    public static function calculateMethodSMV($elements) {
        $totalTimeSeconds = 0;
        $machineTimeSeconds = 0;
        
        foreach ($elements as $element) {
            $elementTime = ($element['time_sec'] ?? 0) + ($element['allowance_sec'] ?? 0);
            $elementTime *= ($element['count'] ?? 1);
            
            $totalTimeSeconds += $elementTime;
            
            // If element is machine-related, add to machine time
            if (isset($element['is_machine']) && $element['is_machine']) {
                $machineTimeSeconds += $elementTime;
            }
        }
        
        // Convert to minutes
        $smvMin = $totalTimeSeconds / 60;
        
        // Calculate needle time percentage
        $needleTimePct = $totalTimeSeconds > 0 ? ($machineTimeSeconds / $totalTimeSeconds) * 100 : 0;
        
        return [
            'smv_min' => round($smvMin, SMV_PRECISION),
            'total_time_sec' => round($totalTimeSeconds, 3),
            'machine_time_sec' => round($machineTimeSeconds, 3),
            'needle_time_pct' => round($needleTimePct, 2)
        ];
    }
    
    /**
     * Compare Method SMV with OB SMV
     */
    public static function compareSMV($methodSMV, $obSMV) {
        $delta = $obSMV - $methodSMV;
        $percentageDiff = $obSMV > 0 ? (($delta / $obSMV) * 100) : 0;
        
        return [
            'delta' => round($delta, SMV_PRECISION),
            'percentage_diff' => round($percentageDiff, 2),
            'status' => $delta > 0 ? 'under' : ($delta < 0 ? 'over' : 'equal')
        ];
    }
    
    /**
     * Calculate efficiency based on actual vs target
     */
    public static function calculateEfficiency($actual, $target) {
        if ($target <= 0) return 0;
        return round(($actual / $target) * 100, 2);
    }
    
    /**
     * Validate plan efficiency
     */
    public static function validatePlanEfficiency($efficiency) {
        return $efficiency > MIN_PLAN_EFFICIENCY && $efficiency <= MAX_PLAN_EFFICIENCY;
    }
    
    /**
     * Validate working hours
     */
    public static function validateWorkingHours($hours) {
        return $hours >= MIN_WORKING_HOURS && $hours <= MAX_WORKING_HOURS;
    }
    
    /**
     * Validate SMV
     */
    public static function validateSMV($smv) {
        return $smv >= MIN_SMV;
    }
    
    /**
     * Validate thread percentage splits
     */
    public static function validateThreadPercentages($pctNeedle, $pctBobbin, $pctLooper) {
        $total = $pctNeedle + $pctBobbin + $pctLooper;
        return $total <= MAX_THREAD_PCT_SUM && $pctNeedle >= 0 && $pctBobbin >= 0 && $pctLooper >= 0;
    }
    
    /**
     * Calculate production targets for a given time period
     */
    public static function calculateProductionTargets($targetPerHour, $hours) {
        return [
            'hourly' => round($targetPerHour, TARGET_PRECISION),
            'daily' => round($targetPerHour * $hours, TARGET_PRECISION),
            'weekly' => round($targetPerHour * $hours * 6, TARGET_PRECISION), // 6 working days
            'monthly' => round($targetPerHour * $hours * 26, TARGET_PRECISION) // 26 working days
        ];
    }
    
    /**
     * Calculate cost per piece based on operator cost and efficiency
     */
    public static function calculatePieceCost($operatorCostPerHour, $targetPerHour, $operatorsRequired) {
        if ($targetPerHour <= 0) return 0;
        
        $totalHourlyCost = $operatorCostPerHour * $operatorsRequired;
        return round($totalHourlyCost / $targetPerHour, 4);
    }
    
    /**
     * Calculate line balancing efficiency
     */
    public static function calculateLineBalance($operations) {
        if (empty($operations)) return 0;
        
        $smvSum = array_sum(array_column($operations, 'smv'));
        $maxSMV = max(array_column($operations, 'smv'));
        $operationCount = count($operations);
        
        if ($maxSMV <= 0 || $operationCount <= 0) return 0;
        
        $theoreticalTime = $smvSum / $operationCount;
        $actualTime = $maxSMV;
        
        return round(($theoreticalTime / $actualTime) * 100, 2);
    }
}
?>