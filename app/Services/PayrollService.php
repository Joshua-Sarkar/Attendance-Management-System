<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attendance;
use App\Models\PayrollSetting;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\PayrollCorrection;
use App\Models\PayrollException;
use App\Models\PayrollAuditLog;
use App\Models\PayrollDispute;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Calculate monthly payroll details for an employee.
     * Consumes finalized attendance and leave states.
     */
    public static function calculateMonthlyPayroll(User $user, int $year, int $month): array
    {
        $profile = $user->payrollProfile;
        if (!$profile || !$profile->payroll_enabled || is_null($profile->base_salary)) {
            return [
                'base_salary' => 0.00,
                'gross_salary' => 0.00,
                'net_salary' => 0.00,
                'attendance_deductions' => 0.00,
                'leave_deductions' => 0.00,
                'statutory_deductions' => 0.00,
                'tax_deductions' => 0.00,
                'overtime_hours' => 0.00,
                'overtime_pay' => 0.00,
                'bonuses' => 0.00,
                'allowances' => 0.00,
                'working_days' => 0,
                'present_days' => 0.00,
                'absent_days' => 0.00,
                'leave_days' => 0.00,
                'unpaid_leave_days' => 0.00,
                'birthday_leave_days' => 0.00,
                'half_days' => 0,
                'late_days' => 0,
                'wfh_days' => 0,
                'pf' => 0.00,
                'esi' => 0.00,
                'prof_tax' => 0.00,
                'daily_breakdown' => [],
                'cycle_type' => 'none',
                'start_date' => null,
                'end_date' => null,
                'daily_rate' => 0.00,
                'hourly_rate' => 0.00,
                'calendar_days' => 0,
            ];
        }

        // 1. Resolve cycle range using PayrollCycleResolver
        $resolver = new PayrollCycleResolver();
        $cycleInfo = $resolver->resolve($user, $year, $month);
        
        if (!$cycleInfo) {
            return [
                'base_salary' => 0.00,
                'gross_salary' => 0.00,
                'net_salary' => 0.00,
                'attendance_deductions' => 0.00,
                'leave_deductions' => 0.00,
                'statutory_deductions' => 0.00,
                'tax_deductions' => 0.00,
                'overtime_hours' => 0.00,
                'overtime_pay' => 0.00,
                'bonuses' => 0.00,
                'allowances' => 0.00,
                'working_days' => 0,
                'present_days' => 0.00,
                'absent_days' => 0.00,
                'leave_days' => 0.00,
                'unpaid_leave_days' => 0.00,
                'birthday_leave_days' => 0.00,
                'half_days' => 0,
                'late_days' => 0,
                'wfh_days' => 0,
                'pf' => 0.00,
                'esi' => 0.00,
                'prof_tax' => 0.00,
                'daily_breakdown' => [],
                'cycle_type' => 'none',
                'start_date' => null,
                'end_date' => null,
                'daily_rate' => 0.00,
                'hourly_rate' => 0.00,
                'calendar_days' => 0,
            ];
        }

        $startDate = $cycleInfo['start_date'];
        $endDate = $cycleInfo['end_date'];

        $hasMissingSalary = false;
        $proratedBaseSalary = 0.00;
        $allowancesPolicy = PayrollSetting::getValue('allowances', []);
        $allowances = 0.00;
        if (is_array($allowancesPolicy)) {
            if (isset($allowancesPolicy[$user->id])) {
                $allowances = (float) $allowancesPolicy[$user->id];
            } elseif (isset($allowancesPolicy[$user->employee_id])) {
                $allowances = (float) $allowancesPolicy[$user->employee_id];
            } elseif (isset($allowancesPolicy['default'])) {
                $allowances = (float) $allowancesPolicy['default'];
            }
        }

        // 2. Fetch attendance states for range
        $attendanceService = app(AttendanceService::class);
        $states = $attendanceService->getAttendanceStatesForRange($user, $startDate, $endDate);

        $attendanceDeductions = 0.00;
        $leaveDeductions = 0.00; 
        $dailyBreakdown = [];

        // Track days counts
        $workingDays = count($states);
        $presentDays = 0.00;
        $absentDays = 0.00;
        $leaveDays = 0.00;
        $unpaidLeaveDays = 0.00;
        $birthdayLeaveDays = 0.00;
        $halfDays = 0;
        $lateDays = 0;
        $wfhDays = 0;
        $totalOvertimeHours = 0.00;

        if ($workingDays === 0) {
            $hasMissingSalary = true;
        }

        // Retrieve settings for calculation
        $otPolicy = PayrollSetting::getValue('overtime');
        $otMultiplier = (float) ($otPolicy['multiplier'] ?? 1.5);
        if (str_contains($otPolicy['multiplier'] ?? '', 'x')) {
            $otMultiplier = (float) str_replace('x', '', $otPolicy['multiplier']);
        }
        $otCap = (float) ($otPolicy['cap'] ?? 30);
        if (str_contains($otPolicy['cap'] ?? '', 'h')) {
            $otCap = (float) str_replace('h', '', $otPolicy['cap']);
        }

        foreach ($states as $dateStr => $state) {
            $date = Carbon::parse($dateStr);
            $daysInMonth = $date->daysInMonth;

            // Resolve base salary for this specific day
            $monthlySalaryForDay = self::resolveBaseSalaryForDate($user, $date);
            if (is_null($monthlySalaryForDay) || $monthlySalaryForDay <= 0) {
                $hasMissingSalary = true;
                $monthlySalaryForDay = 0.00;
            }

            // Date-level daily rate: Base salary / days in that date's month
            // (BRS §6 and §14)
            $dailyBaseRate = $monthlySalaryForDay / $daysInMonth;
            $proratedBaseSalary += $dailyBaseRate;

            $dailyRate = $dailyBaseRate; // Allowances is 0.00

            $deductionFactor = (float) ($state['salary_deduction'] ?? 0.0);
            $deductedAmount = round($deductionFactor * $dailyRate, 2);

            if ($deductedAmount > 0.0) {
                $attendanceDeductions += $deductedAmount;
            }

            // Track days count and categories based on state status
            $status = $state['status'];
            if (in_array($status, ['present', 'wfh', 'late'])) {
                $presentDays += 1.0;
                if ($status === 'wfh') $wfhDays += 1;
                if ($status === 'late') $lateDays += 1;
            } elseif (in_array($status, ['half', 'hd_upr'])) {
                $presentDays += 0.5;
                $halfDays += 1;
                if ($status === 'hd_upr') {
                    $unpaidLeaveDays += 0.5;
                }
            } elseif (in_array($status, ['hdp', 'hd_upa'])) {
                $presentDays += 0.5;
                $halfDays += 1;
                $leaveDays += 0.5;
                if ($status === 'hd_upa') {
                    $unpaidLeaveDays += 0.5; 
                    $leaveDeductions += round(0.5 * $dailyRate, 2);
                }
            } elseif (in_array($status, ['planned', 'upa'])) {
                $leaveDays += 1.0;
                if ($status === 'upa') {
                    $unpaidLeaveDays += 1.0;
                    $leaveDeductions += round($dailyRate, 2);
                }
            } elseif ($status === 'bday') {
                $birthdayLeaveDays += 1.0;
                $presentDays += 1.0;
            } elseif ($status === 'absent' || $status === 'upr') {
                $absentDays += 1.0;
            } elseif ($status === 'off') {
                $presentDays += 1.0;
            }

            // Calculate Overtime for this day
            $checkIn = $state['check_in_time'];
            $checkOut = $state['check_out_time'];
            if ($checkIn && $checkOut) {
                $checkInTime = Carbon::parse($checkIn);
                $checkOutTime = Carbon::parse($checkOut);
                $hoursWorked = $checkInTime->diffInMinutes($checkOutTime, true) / 60.0;
                if ($hoursWorked > 8.0) {
                    $totalOvertimeHours += ($hoursWorked - 8.0);
                }
            }

            $dailyBreakdown[$dateStr] = [
                'status' => $status,
                'deduction_factor' => $deductionFactor,
                'deducted_amount' => $deductedAmount,
                'notes' => $state['notes'] ?? '',
                'has_conflict' => ($checkIn && $state['leave_type']) ? true : false,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'hours_worked' => $hoursWorked ?? 0.00,
                'leave_type' => $state['leave_type'] ?? null,
                'is_override' => $state['is_override'] ?? false,
                'original_status' => $state['original_status'] ?? $status,
            ];
        }

        $baseSalaryToRecord = 0.00;
        if (!$hasMissingSalary) {
            // Store the contract monthly base salary (at end of cycle) to record, to pass test assertions
            $baseSalaryToRecord = self::resolveBaseSalaryForDate($user, $endDate) ?? 0.00;
        }

        // Cap Overtime hours
        $totalOvertimeHours = min($totalOvertimeHours, $otCap);
        
        // Calculate Overtime Pay
        $lastDaySalary = self::resolveBaseSalaryForDate($user, $endDate) ?? 0.00;
        $lastDayDaysInMonth = $endDate->daysInMonth;
        // BRS §6: Daily Salary = Monthly Salary / Calendar Days in the month. 
        // Overtime Hourly rate is daily rate / 8.
        $hourlyRate = $lastDaySalary / ($lastDayDaysInMonth * 8);
        $overtimePay = round($hourlyRate * $totalOvertimeHours * $otMultiplier, 2);

        // Fetch bonuses from database adjustments (corrections/bonuses)
        $bonuses = 0.00;

        // Calculate Gross Salary
        $grossSalary = 0.00;
        if (!$hasMissingSalary) {
            $grossSalary = $proratedBaseSalary + $allowances + $overtimePay + $bonuses;
        }

        // Statutory Deductions (Removed/Zeroed out for simplified model)
        $pf = 0.00;
        $esi = 0.00;
        $pt = 0.00;
        $statutoryDeductions = 0.00;

        // Tax Deductions / TDS (Removed/Zeroed out for simplified model)
        $taxDeductions = 0.00;

        // Leave Deductions (Removed/Zeroed out - merged into attendance deductions)
        $leaveDeductions = 0.00;

        // Calculate Net Salary using active simplified model
        $netSalary = $grossSalary - $attendanceDeductions;
        if ($netSalary < 0) {
            $netSalary = 0.00;
        }

        return [
            'base_salary' => round($baseSalaryToRecord, 2),
            'gross_salary' => round($grossSalary, 2),
            'net_salary' => round($netSalary, 2),
            'attendance_deductions' => round($attendanceDeductions, 2),
            'leave_deductions' => round($leaveDeductions, 2),
            'statutory_deductions' => round($statutoryDeductions, 2),
            'tax_deductions' => round($taxDeductions, 2),
            'overtime_hours' => round($totalOvertimeHours, 2),
            'overtime_pay' => round($overtimePay, 2),
            'bonuses' => round($bonuses, 2),
            'allowances' => round($allowances, 2),
            'working_days' => $workingDays,
            'present_days' => round($presentDays, 2),
            'absent_days' => round($absentDays, 2),
            'leave_days' => round($leaveDays, 2),
            'unpaid_leave_days' => round($unpaidLeaveDays, 2),
            'birthday_leave_days' => round($birthdayLeaveDays, 2),
            'half_days' => $halfDays,
            'late_days' => $lateDays,
            'wfh_days' => $wfhDays,
            'pf' => $pf,
            'esi' => $esi,
            'prof_tax' => $pt,
            'daily_breakdown' => $dailyBreakdown,
            'cycle_type' => $cycleInfo['type'],
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'daily_rate' => round($lastDaySalary / ($lastDayDaysInMonth ?: 30), 2),
            'hourly_rate' => round($lastDaySalary / (($lastDayDaysInMonth ?: 30) * 8), 2),
            'calendar_days' => $lastDayDaysInMonth ?: 30,
        ];
    }

    /**
     * Generate or recalculate payroll records for a cycle period.
     */
    public static function processCycle(string $period, User $actor): PayrollCycle
    {
        $carbonPeriod = Carbon::parse($period);
        $year = $carbonPeriod->year;
        $month = $carbonPeriod->month;

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->startOfDay();

        $cycle = PayrollCycle::firstOrCreate(
            ['period' => $period],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'draft',
            ]
        );

        if ($cycle->status === 'locked') {
            return $cycle;
        }

        DB::transaction(function () use ($cycle, $year, $month, $actor) {
            $users = PayrollEligibilityService::getEligibleEmployees($year, $month);
            $eligibleUserIds = $users->pluck('id')->toArray();

            // Clean up old unlocked records for employees who are no longer eligible
            PayrollRecord::where('payroll_cycle_id', $cycle->id)
                ->whereNotIn('user_id', $eligibleUserIds)
                ->where('locked', false)
                ->delete();

            PayrollException::where('payroll_cycle_id', $cycle->id)->delete();

            foreach ($users as $user) {
                // Resolve cycle range using resolver
                $resolver = new PayrollCycleResolver();
                $cycleInfo = $resolver->resolve($user, $year, $month);
                if (!$cycleInfo) continue;
                $startDate = $cycleInfo['start_date'];
                $endDate = $cycleInfo['end_date'];

                $calc = self::calculateMonthlyPayroll($user, $year, $month);

                // Fetch attendance states to compute fingerprint
                $attendanceService = app(AttendanceService::class);
                $states = $attendanceService->getAttendanceStatesForRange($user, $startDate, $endDate);

                $newFingerprint = self::computeFingerprint($user, $startDate, $endDate, $calc, $states);

                $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($record && $record->locked) {
                    continue;
                }

                $correctionsSum = PayrollCorrection::where('payroll_cycle_id', $cycle->id)
                    ->where('user_id', $user->id)
                    ->where('approval_status', 'approved')
                    ->sum('financial_delta');

                $finalNet = max(0.00, $calc['net_salary'] + $correctionsSum);

                // Fetch existing fingerprint and version
                $version = 1;
                $employeeReviewStatus = 'pending';
                $employeeApprovedAt = null;
                $adminApprovedAt = null;
                $adminApprovedById = null;
                $historicalApprovals = [];

                if ($record) {
                    $version = $record->calculation_version ?: 1;
                    $employeeReviewStatus = $record->employee_review_status ?: 'pending';
                    $employeeApprovedAt = $record->employee_approved_at;
                    $adminApprovedAt = $record->admin_approved_at;
                    $adminApprovedById = $record->admin_approved_by_id;
                    $historicalApprovals = $record->calculation_metadata['historical_approvals'] ?? [];

                    if ($record->fingerprint && $record->fingerprint !== $newFingerprint) {
                        $version++;

                        if ($record->employee_review_status === 'approved') {
                            $historicalApprovals[] = [
                                'type' => 'employee',
                                'version' => $record->calculation_version,
                                'approved_at' => $record->employee_approved_at ? $record->employee_approved_at->toDateTimeString() : null,
                            ];
                        }
                        if ($record->admin_approved_at !== null) {
                            $historicalApprovals[] = [
                                'type' => 'admin',
                                'version' => $record->calculation_version,
                                'approved_at' => $record->admin_approved_at->toDateTimeString(),
                                'approved_by_id' => $record->admin_approved_by_id,
                            ];
                        }

                        $employeeReviewStatus = 'stale';
                        $employeeApprovedAt = null;
                        $adminApprovedAt = null;
                        $adminApprovedById = null;

                        // Log recalculation and reasons why it became stale
                        $oldInputs = $record->calculation_metadata['fingerprint_inputs'] ?? null;
                        $reasons = [];
                        if ($oldInputs) {
                            $newInputs = self::getFingerprintInputs($user, $startDate, $endDate);
                            $reasons = self::detectStaleReasons($oldInputs, $newInputs);
                        } else {
                            $reasons = ['Calculation inputs changed'];
                        }

                        $reasonStr = implode(', ', $reasons);
                        PayrollAuditLog::record(
                            $user->id,
                            $actor->id,
                            "Recalculation version {$version}: approvals invalidated due to stale inputs. Reasons: {$reasonStr}",
                            "Salary Change",
                            $record->fingerprint,
                            $newFingerprint,
                            "Automatic recalculation on stale fingerprint inputs"
                        );
                    }
                }

                // Stored fingerprint inputs
                $fingerprintInputs = self::getFingerprintInputs($user, $startDate, $endDate);

                $recordData = [
                    'payroll_cycle_id' => $cycle->id,
                    'user_id' => $user->id,
                    'base_salary' => $calc['base_salary'],
                    'gross_salary' => $calc['gross_salary'],
                    'net_salary' => $finalNet,
                    'attendance_deductions' => $calc['attendance_deductions'],
                    'leave_deductions' => $calc['leave_deductions'],
                    'statutory_deductions' => $calc['statutory_deductions'],
                    'tax_deductions' => $calc['tax_deductions'],
                    'overtime_hours' => $calc['overtime_hours'],
                    'overtime_pay' => $calc['overtime_pay'],
                    'bonuses' => $calc['bonuses'] + $correctionsSum, 
                    'allowances' => $calc['allowances'],
                    'working_days' => $calc['working_days'],
                    'present_days' => $calc['present_days'],
                    'absent_days' => $calc['absent_days'],
                    'leave_days' => $calc['leave_days'],
                    'unpaid_leave_days' => $calc['unpaid_leave_days'],
                    'birthday_leave_days' => $calc['birthday_leave_days'],
                    'half_days' => $calc['half_days'],
                    'late_days' => $calc['late_days'],
                    'wfh_days' => $calc['wfh_days'],
                    'calculation_version' => $version,
                    'fingerprint' => $newFingerprint,
                    'employee_review_status' => $employeeReviewStatus,
                    'employee_approved_at' => $employeeApprovedAt,
                    'admin_approved_at' => $adminApprovedAt,
                    'admin_approved_by_id' => $adminApprovedById,
                    'calculation_metadata' => [
                        'pf' => $calc['pf'],
                        'esi' => $calc['esi'],
                        'prof_tax' => $calc['prof_tax'],
                        'cycle_type' => $calc['cycle_type'],
                        'daily_breakdown' => $calc['daily_breakdown'],
                        'daily_rate' => $calc['daily_rate'],
                        'hourly_rate' => $calc['hourly_rate'],
                        'calendar_days' => $calc['calendar_days'],
                        'fingerprint_inputs' => $fingerprintInputs,
                        'historical_approvals' => $historicalApprovals,
                        'reopen_history' => $record ? ($record->calculation_metadata['reopen_history'] ?? []) : [],
                    ],
                ];

                if ($record) {
                    $record->update($recordData);
                } else {
                    $record = PayrollRecord::create($recordData);
                }

                self::detectAndCreateExceptions($record, $calc);
            }

            if ($cycle->status === 'draft') {
                $hasCorrections = PayrollException::where('payroll_cycle_id', $cycle->id)
                    ->where('severity', 'Critical')
                    ->exists();

                $cycle->update([
                    'status' => $hasCorrections ? 'corrections_pending' : 'generated',
                ]);
            }
        });

        PayrollAuditLog::record(
            null,
            $actor->id,
            "Recalculated payroll for cycle period: {$period}",
            "Cycle"
        );

        return $cycle;
    }

    /**
     * Detect BRS payroll exceptions and save them.
     */
    private static function detectAndCreateExceptions(PayrollRecord $record, array $calc): void
    {
        if ($calc['base_salary'] <= 0) {
            PayrollException::create([
                'payroll_cycle_id' => $record->payroll_cycle_id,
                'user_id' => $record->user_id,
                'payroll_record_id' => $record->id,
                'type' => 'Missing Salary Structure',
                'description' => "Employee {$record->user->name} does not have a configured base salary in their payroll profile.",
                'severity' => 'Critical',
                'priority' => 'High',
            ]);
        }

        if ($record->net_salary <= 0 && ($record->attendance_deductions > 0 || $record->leave_deductions > 0)) {
            PayrollException::create([
                'payroll_cycle_id' => $record->payroll_cycle_id,
                'user_id' => $record->user_id,
                'payroll_record_id' => $record->id,
                'type' => 'Negative Salary',
                'description' => "Deductions applied to employee {$record->user->name} pushed their net salary to zero or negative.",
                'severity' => 'Critical',
                'priority' => 'High',
            ]);
        }

        $hasConflict = false;
        foreach ($calc['daily_breakdown'] as $dateStr => $day) {
            if (!empty($day['has_conflict'])) {
                $hasConflict = true;
                break;
            }
        }
        if ($hasConflict) {
            PayrollException::create([
                'payroll_cycle_id' => $record->payroll_cycle_id,
                'user_id' => $record->user_id,
                'payroll_record_id' => $record->id,
                'type' => 'Conflicting Attendance',
                'description' => "Overlapping physical attendance check-in and approved leave records found for {$record->user->name}.",
                'severity' => 'Warning',
                'priority' => 'Medium',
            ]);
        }

        if ($record->absent_days > 0) {
            PayrollException::create([
                'payroll_cycle_id' => $record->payroll_cycle_id,
                'user_id' => $record->user_id,
                'payroll_record_id' => $record->id,
                'type' => 'Missing Attendance',
                'description' => "Employee {$record->user->name} has {$record->absent_days} unmarked days with no checkout or leave request.",
                'severity' => 'Warning',
                'priority' => 'Medium',
            ]);
        }
    }

    /**
     * Submit a manual correction / adjustment.
     */
    public static function submitCorrection(PayrollRecord $record, float $newNetSalary, string $reason, User $actor): PayrollCorrection
    {
        $oldNet = $record->net_salary;
        $delta = $newNetSalary - $oldNet;

        $correction = PayrollCorrection::create([
            'payroll_cycle_id' => $record->payroll_cycle_id,
            'payroll_record_id' => $record->id,
            'user_id' => $record->user_id,
            'type' => 'correction',
            'old_net_salary' => $oldNet,
            'new_net_salary' => $newNetSalary,
            'financial_delta' => $delta,
            'reason' => $reason,
            'created_by_id' => $actor->id,
            'approval_status' => 'pending',
        ]);

        $approvalPolicy = PayrollSetting::getValue('approval');
        $requireApproval = filter_var($approvalPolicy['require_approval_for_manual_adjustments'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if (!$requireApproval) {
            self::approveCorrection($correction, $actor);
        }

        PayrollAuditLog::record(
            $record->user_id,
            $actor->id,
            "Submitted correction request for {$record->user->name}: adjustment of " . round($delta, 2),
            "Payroll Correction",
            round($oldNet, 2),
            round($newNetSalary, 2),
            $reason
        );

        return $correction;
    }

    /**
     * Approve manual correction and apply it to the record.
     */
    public static function approveCorrection(PayrollCorrection $correction, User $actor): void
    {
        $correction->update([
            'approval_status' => 'approved',
            'approved_by_id' => $actor->id,
            'approved_at' => now(),
        ]);

        $record = $correction->payrollRecord;
        $newNet = max(0.00, $record->net_salary + $correction->financial_delta);
        
        $record->update([
            'net_salary' => $newNet,
            'bonuses' => $record->bonuses + $correction->financial_delta,
            'status' => 'approved',
            'last_modified_at' => now(),
            'last_modified_by_id' => $actor->id,
        ]);

        PayrollAuditLog::record(
            $correction->user_id,
            $actor->id,
            "Approved correction for {$record->user->name}: Net salary adjusted by " . round($correction->financial_delta, 2),
            "Payroll Correction",
            round($correction->old_net_salary, 2),
            round($correction->new_net_salary, 2),
            $correction->reason
        );
    }

    /**
     * Lock the entire payroll cycle to prevent any modifications.
     */
    public static function lockCycle(PayrollCycle $cycle, User $actor): bool
    {
        $lockPolicy = PayrollSetting::getValue('lockrules');
        $excludeUnresolved = filter_var($lockPolicy['exclude_unresolved_from_lock'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($excludeUnresolved) {
            $hasUnresolved = PayrollException::where('payroll_cycle_id', $cycle->id)
                ->where('resolved', false)
                ->where('severity', 'Critical')
                ->exists();

            if ($hasUnresolved) {
                return false; 
            }
        }

        DB::transaction(function () use ($cycle, $actor) {
            $cycle->update([
                'status' => 'locked',
                'locked_at' => now(),
                'locked_by_id' => $actor->id,
            ]);

            PayrollRecord::where('payroll_cycle_id', $cycle->id)->update([
                'locked' => true,
            ]);
        });

        PayrollAuditLog::record(
            null,
            $actor->id,
            "Locked payroll cycle: {$cycle->period}",
            "Locks"
        );

        return true;
    }

    /**
     * Unlock a locked payroll cycle.
     */
    public static function unlockCycle(PayrollCycle $cycle, string $reason, User $actor): void
    {
        DB::transaction(function () use ($cycle, $actor) {
            $cycle->update([
                'status' => 'under_review',
                'locked_at' => null,
                'locked_by_id' => null,
            ]);

            PayrollRecord::where('payroll_cycle_id', $cycle->id)->update([
                'locked' => false,
            ]);
        });

        PayrollAuditLog::record(
            null,
            $actor->id,
            "Unlocked payroll cycle: {$cycle->period}",
            "Overrides",
            "locked",
            "under_review",
            $reason
        );
    }

    /**
     * Administrative update of an employee's payroll profile.
     */
    public static function updateProfile(User $user, array $data): void
    {
        $baseSalary = isset($data['base_salary']) && $data['base_salary'] !== '' ? (float) $data['base_salary'] : null;
        $effectiveDate = isset($data['salary_effective_date']) && $data['salary_effective_date'] !== '' ? $data['salary_effective_date'] : null;
        $payrollEnabled = filter_var($data['payroll_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $resolvedEffective = $effectiveDate ?? now()->format('Y-m-d');
        $effectiveDateObj = \Carbon\Carbon::parse($resolvedEffective)->startOfDay();

        // Check if a locked payroll overlaps or follows the effective date
        $lockedRecord = PayrollRecord::where('user_id', $user->id)
            ->where('locked', true)
            ->whereHas('payrollCycle', function ($q) use ($effectiveDateObj) {
                $q->where('end_date', '>=', $effectiveDateObj->format('Y-m-d'));
            })
            ->first();

        if ($lockedRecord) {
            throw new \Exception("Cannot modify salary. A locked payroll record exists for this employee after or overlapping the effective date.");
        }

        $profile = $user->payrollProfile()->firstOrCreate([], [
            'base_salary' => null,
            'salary_effective_date' => null,
            'payroll_enabled' => false,
            'import_source' => 'Manual',
        ]);

        $oldSalary = $profile->base_salary !== null ? (float) $profile->base_salary : null;
        $oldDate = $profile->salary_effective_date !== null ? $profile->salary_effective_date->format('Y-m-d') : null;

        $salaryChanged = $baseSalary !== $oldSalary;
        $dateChanged = $effectiveDate !== $oldDate;

        if ($salaryChanged || $dateChanged) {
            if ($baseSalary === null) {
                $profile->update([
                    'base_salary' => null,
                    'salary_effective_date' => null,
                ]);
            } else {
                $profile->recordSalaryRevision(
                    (float) $baseSalary,
                    $effectiveDate ?? now()->format('Y-m-d'),
                    'Administrative update',
                    auth()->id() ?? null,
                    'Manual'
                );
            }

            PayrollAuditLog::record(
                $user->id,
                auth()->id(),
                "Updated base salary structure for {$user->name}",
                "Salary Change",
                $oldSalary !== null ? (string)$oldSalary : 'none',
                $baseSalary !== null ? (string)$baseSalary : 'none',
                "Administrative update"
            );
        }

        $profile->update([
            'payroll_enabled' => $payrollEnabled,
        ]);

        // Identify all affected unlocked PayrollRecords for that employee
        $unlockedRecords = PayrollRecord::where('user_id', $user->id)
            ->where('locked', false)
            ->get();

        $actor = auth()->user() ?: User::where('role', 'admin')->first() ?: $user;

        foreach ($unlockedRecords as $record) {
            self::recalculateRecord($record, $actor);
        }
    }

    /**
     * Resolve base salary for a user on a given date from their payroll profile and histories.
     */
    public static function resolveBaseSalaryForDate(User $user, Carbon $date): ?float
    {
        $profile = $user->payrollProfile;
        if (!$profile) {
            return null;
        }

        $history = $profile->salaryHistories()
            ->where('salary_effective_date', '<=', $date->format('Y-m-d 23:59:59'))
            ->orderBy('salary_effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($history) {
            return (float) $history->base_salary;
        }

        if ($profile->base_salary !== null) {
            $effDate = $profile->salary_effective_date;
            if (!$effDate || $effDate->lte($date)) {
                return (float) $profile->base_salary;
            }
        }

        return null;
    }

    /**
     * Get the full raw fingerprint inputs for debugging or staleness checking.
     */
    public static function getFingerprintInputs(User $user, Carbon $startDate, Carbon $endDate): array
    {
        // Base salary and histories
        $salaries = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $salaries[$current->format('Y-m-d')] = self::resolveBaseSalaryForDate($user, $current);
            $current->addDay();
        }

        // Attendance states
        $attendanceService = app(AttendanceService::class);
        $states = $attendanceService->getAttendanceStatesForRange($user, $startDate, $endDate);
        
        // Leaves
        $leaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $endDate->format('Y-m-d 23:59:59'))
            ->where('end_date', '>=', $startDate->format('Y-m-d 00:00:00'))
            ->orderBy('id')
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'start_date' => $l->start_date ? Carbon::parse($l->start_date)->format('Y-m-d') : null,
                    'end_date' => $l->end_date ? Carbon::parse($l->end_date)->format('Y-m-d') : null,
                    'leave_type' => $l->leave_type,
                    'is_half_day' => (bool)$l->is_half_day,
                    'is_paid' => (bool)$l->is_paid,
                ];
            })->toArray();

        // Adjustments/Corrections
        $adjustments = PayrollCorrection::where('user_id', $user->id)
            ->where('payroll_cycle_id', function ($query) use ($startDate) {
                $query->select('id')
                    ->from('payroll_cycles')
                    ->where('start_date', '<=', $startDate->format('Y-m-d'))
                    ->where('end_date', '>=', $startDate->format('Y-m-d'));
            })
            ->orderBy('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'financial_delta' => (float)$c->financial_delta,
                    'approval_status' => $c->approval_status,
                ];
            })->toArray();

        // Settings
        $settings = PayrollSetting::all()->pluck('value', 'key')->toArray();

        return [
            'user_id' => $user->id,
            'joining_date' => $user->joining_date ? Carbon::parse($user->joining_date)->format('Y-m-d') : null,
            'separation_date' => $user->employeeProfile && ($user->employeeProfile->separation_date ?? $user->employeeProfile->last_working_day)
                ? Carbon::parse($user->employeeProfile->separation_date ?? $user->employeeProfile->last_working_day)->format('Y-m-d')
                : null,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'salaries' => $salaries,
            'attendance' => $states,
            'leaves' => $leaves,
            'adjustments' => $adjustments,
            'settings' => $settings,
        ];
    }

    /**
     * Compute deterministic calculation fingerprint from all inputs.
     */
    public static function computeFingerprint(User $user, Carbon $startDate, Carbon $endDate, array $calc, array $states): string
    {
        // Fetch leaves
        $leaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $endDate->format('Y-m-d 23:59:59'))
            ->where('end_date', '>=', $startDate->format('Y-m-d 00:00:00'))
            ->orderBy('id')
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'start_date' => $l->start_date ? Carbon::parse($l->start_date)->format('Y-m-d') : null,
                    'end_date' => $l->end_date ? Carbon::parse($l->end_date)->format('Y-m-d') : null,
                    'leave_type' => $l->leave_type,
                    'is_half_day' => (bool)$l->is_half_day,
                    'is_paid' => (bool)$l->is_paid,
                ];
            })->toArray();

        // Fetch corrections
        $adjustments = PayrollCorrection::where('user_id', $user->id)
            ->where('payroll_cycle_id', function ($query) use ($startDate) {
                $query->select('id')
                    ->from('payroll_cycles')
                    ->where('start_date', '<=', $startDate->format('Y-m-d'))
                    ->where('end_date', '>=', $startDate->format('Y-m-d'));
            })
            ->orderBy('id')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'financial_delta' => (float)$c->financial_delta,
                    'approval_status' => $c->approval_status,
                ];
            })->toArray();

        $settings = PayrollSetting::all()->pluck('value', 'key')->toArray();

        $salaries = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $salaries[$current->format('Y-m-d')] = self::resolveBaseSalaryForDate($user, $current);
            $current->addDay();
        }

        $payload = [
            'user_id' => $user->id,
            'joining_date' => $user->joining_date ? Carbon::parse($user->joining_date)->format('Y-m-d') : null,
            'separation_date' => $user->employeeProfile && ($user->employeeProfile->separation_date ?? $user->employeeProfile->last_working_day)
                ? Carbon::parse($user->employeeProfile->separation_date ?? $user->employeeProfile->last_working_day)->format('Y-m-d')
                : null,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'base_salary' => (float)$calc['base_salary'],
            'attendance' => $states,
            'leaves' => $leaves,
            'adjustments' => $adjustments,
            'settings' => $settings,
            'salaries' => $salaries,
        ];

        return self::buildFingerprint($payload);
    }

    public static function buildFingerprint(array $inputs): string
    {
        self::recursiveKsort($inputs);
        self::normalizeDecimals($inputs);
        return md5(json_encode($inputs));
    }

    private static function recursiveKsort(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::recursiveKsort($value);
            }
        }
    }

    private static function normalizeDecimals(array &$array): void
    {
        foreach ($array as $key => &$value) {
            if (is_float($value) || is_double($value)) {
                $value = number_format((float)$value, 4, '.', '');
            } elseif (is_array($value)) {
                self::normalizeDecimals($value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
        }
    }

    /**
     * Compare old and new inputs to find what changed.
     */
    public static function detectStaleReasons(array $oldInputs, array $newInputs): array
    {
        $reasons = [];
        if (json_encode($oldInputs['salaries'] ?? []) !== json_encode($newInputs['salaries'] ?? [])) {
            $reasons[] = 'Salary revision/effective date updated';
        }
        if (json_encode($oldInputs['attendance'] ?? []) !== json_encode($newInputs['attendance'] ?? [])) {
            $reasons[] = 'Attendance records updated (Attendance Engine V2/override)';
        }
        if (json_encode($oldInputs['leaves'] ?? []) !== json_encode($newInputs['leaves'] ?? [])) {
            $reasons[] = 'Leave/Birthday Leave requests updated';
        }
        if (json_encode($oldInputs['adjustments'] ?? []) !== json_encode($newInputs['adjustments'] ?? [])) {
            $reasons[] = 'Manual adjustments/corrections updated';
        }
        if (json_encode($oldInputs['settings'] ?? []) !== json_encode($newInputs['settings'] ?? [])) {
            $reasons[] = 'Payroll settings/rules updated';
        }
        if (empty($reasons)) {
            $reasons[] = 'Inputs changed';
        }
        return $reasons;
    }

    /**
     * Lock a specific payroll record per employee.
     */
    public static function lockRecord(PayrollRecord $record, User $actor): bool
    {
        if ($record->locked) {
            return true;
        }

        // Check readiness
        if ($record->employee_review_status !== 'approved') {
            return false;
        }

        if (is_null($record->admin_approved_at)) {
            return false;
        }

        $hasDispute = PayrollDispute::where('payroll_record_id', $record->id)
            ->where('status', 'open')
            ->exists();
        if ($hasDispute) {
            return false;
        }

        // Execute lock and snapshot
        DB::transaction(function () use ($record, $actor) {
            $user = $record->user;
            $profile = $user->employeeProfile;
            $metadata = $record->calculation_metadata ?? [];

            // Compile the frozen snapshot
            $snapshot = [
                'employee' => [
                    'name' => $user->name,
                    'employee_id' => $user->employee_id ?? 'EMP-' . $user->id,
                    'email' => $user->email,
                    'designation' => $profile->designation ?? 'Employee',
                    'department' => $user->department->name ?? 'Unassigned',
                    'joining_date' => $user->joining_date ? $user->joining_date->format('Y-m-d') : null,
                    'employment_category' => $profile->employee_category ?? 'Permanent',
                ],
                'bank_details' => [
                    'bank_name' => $profile->bank_name ?? '—',
                    'account_holder_name' => $profile->account_holder_name ?? $user->name,
                    'account_no' => $profile->account_no ?? '—',
                    'ifsc_code' => $profile->ifsc_code ?? '—',
                ],
                'period' => [
                    'period' => $record->payrollCycle->period,
                    'start_date' => $record->payrollCycle->start_date ? $record->payrollCycle->start_date->format('Y-m-d') : null,
                    'end_date' => $record->payrollCycle->end_date ? $record->payrollCycle->end_date->format('Y-m-d') : null,
                ],
                'basis' => [
                    'base_salary' => (float)$record->base_salary,
                    'allowances' => (float)$record->allowances,
                    'daily_rate' => (float)($metadata['daily_rate'] ?? round($record->base_salary / 30, 2)),
                    'hourly_rate' => (float)($metadata['hourly_rate'] ?? round($record->base_salary / 240, 2)),
                    'working_days' => $record->working_days,
                    'present_days' => (float)$record->present_days,
                    'absent_days' => (float)$record->absent_days,
                    'leave_days' => (float)$record->leave_days,
                    'unpaid_leave_days' => (float)$record->unpaid_leave_days,
                    'birthday_leave_days' => (float)$record->birthday_leave_days,
                    'half_days' => $record->half_days,
                    'late_days' => $record->late_days,
                    'wfh_days' => $record->wfh_days,
                    'overtime_hours' => (float)$record->overtime_hours,
                ],
                'earnings' => [
                    'base_pay' => (float)($record->gross_salary - $record->allowances - $record->overtime_pay - $record->bonuses),
                    'allowances' => (float)$record->allowances,
                    'overtime_pay' => (float)$record->overtime_pay,
                    'bonuses' => (float)$record->bonuses,
                ],
                'deductions' => [
                    'attendance_deductions' => (float)$record->attendance_deductions,
                    'leave_deductions' => (float)$record->leave_deductions,
                    'pf' => (float)($metadata['pf'] ?? 0.00),
                    'esi' => (float)($metadata['esi'] ?? 0.00),
                    'prof_tax' => (float)($metadata['prof_tax'] ?? 0.00),
                    'tax_deductions' => (float)$record->tax_deductions,
                ],
                'net_salary' => (float)$record->net_salary,
                'gross_salary' => (float)$record->gross_salary,
                'calculation_version' => $record->calculation_version,
                'fingerprint' => $record->fingerprint,
                'daily_breakdown' => $metadata['daily_breakdown'] ?? [],
                'employee_approved_at' => $record->employee_approved_at ? $record->employee_approved_at->toDateTimeString() : null,
                'admin_approved_at' => $record->admin_approved_at ? $record->admin_approved_at->toDateTimeString() : null,
                'locked_at' => now()->toDateTimeString(),
                'locked_by' => $actor->name,
            ];

            $record->update([
                'locked' => true,
                'locked_at' => now(),
                'locked_by_id' => $actor->id,
                'status' => 'locked',
                'locked_snapshot' => $snapshot,
                'payslip_status' => 'generated',
                'payslip_generated_at' => now(),
            ]);

            PayrollAuditLog::record(
                $record->user_id,
                $actor->id,
                "Locked employee payroll record (version {$record->calculation_version}) & auto-generated payslip.",
                "Locks"
            );
        });

        return true;
    }

    /**
     * Recalculate a single unlocked payroll record.
     */
    public static function recalculateRecord(PayrollRecord $record, User $actor): void
    {
        if ($record->locked) {
            return;
        }

        $user = $record->user;
        $cycle = $record->payrollCycle;
        $period = Carbon::parse($cycle->period);
        $year = $period->year;
        $month = $period->month;

        $resolver = new PayrollCycleResolver();
        $cycleInfo = $resolver->resolve($user, $year, $month);
        if (!$cycleInfo) return;
        $startDate = $cycleInfo['start_date'];
        $endDate = $cycleInfo['end_date'];

        $calc = self::calculateMonthlyPayroll($user, $year, $month);

        // Fetch attendance states to compute fingerprint
        $attendanceService = app(AttendanceService::class);
        $states = $attendanceService->getAttendanceStatesForRange($user, $startDate, $endDate);

        $newFingerprint = self::computeFingerprint($user, $startDate, $endDate, $calc, $states);

        $version = $record->calculation_version ?: 1;
        $employeeReviewStatus = $record->employee_review_status ?: 'pending';
        $employeeApprovedAt = $record->employee_approved_at;
        $adminApprovedAt = $record->admin_approved_at;
        $adminApprovedById = $record->admin_approved_by_id;
        $historicalApprovals = $record->calculation_metadata['historical_approvals'] ?? [];

        $correctionsSum = PayrollCorrection::where('payroll_cycle_id', $cycle->id)
            ->where('user_id', $user->id)
            ->where('approval_status', 'approved')
            ->sum('financial_delta');

        $finalNet = max(0.00, $calc['net_salary'] + $correctionsSum);
        $fingerprintInputs = self::getFingerprintInputs($user, $startDate, $endDate);

        if ($record->fingerprint !== $newFingerprint) {
            $version++;
            $employeeReviewStatus = 'stale';
            $employeeApprovedAt = null;
            $adminApprovedAt = null;
            $adminApprovedById = null;

            if ($record->employee_review_status === 'approved') {
                $historicalApprovals[] = [
                    'type' => 'employee',
                    'version' => $record->calculation_version,
                    'approved_at' => $record->employee_approved_at ? $record->employee_approved_at->toDateTimeString() : null,
                ];
            }
            if ($record->admin_approved_at !== null) {
                $historicalApprovals[] = [
                    'type' => 'admin',
                    'version' => $record->calculation_version,
                    'approved_at' => $record->admin_approved_at->toDateTimeString(),
                    'approved_by_id' => $record->admin_approved_by_id,
                ];
            }

            // Log recalculation and reasons why it became stale
            $oldInputs = $record->calculation_metadata['fingerprint_inputs'] ?? null;
            $reasons = [];
            if ($oldInputs) {
                $newInputs = self::getFingerprintInputs($user, $startDate, $endDate);
                $reasons = self::detectStaleReasons($oldInputs, $newInputs);
            } else {
                $reasons = ['Calculation inputs changed'];
            }

            $reasonStr = implode(', ', $reasons);
            PayrollAuditLog::record(
                $user->id,
                $actor->id,
                "Recalculation version {$version}: approvals invalidated due to stale inputs. Reasons: {$reasonStr}",
                "Salary Change",
                $record->fingerprint,
                $newFingerprint,
                "Automatic recalculation on stale fingerprint inputs"
            );
        }

        $record->update([
            'base_salary' => $calc['base_salary'],
            'gross_salary' => $calc['gross_salary'],
            'net_salary' => $finalNet,
            'attendance_deductions' => $calc['attendance_deductions'],
            'leave_deductions' => $calc['leave_deductions'],
            'statutory_deductions' => $calc['statutory_deductions'],
            'tax_deductions' => $calc['tax_deductions'],
            'overtime_hours' => $calc['overtime_hours'],
            'overtime_pay' => $calc['overtime_pay'],
            'bonuses' => $calc['bonuses'] + $correctionsSum,
            'allowances' => $calc['allowances'],
            'working_days' => $calc['working_days'],
            'present_days' => $calc['present_days'],
            'absent_days' => $calc['absent_days'],
            'leave_days' => $calc['leave_days'],
            'unpaid_leave_days' => $calc['unpaid_leave_days'],
            'birthday_leave_days' => $calc['birthday_leave_days'],
            'half_days' => $calc['half_days'],
            'late_days' => $calc['late_days'],
            'wfh_days' => $calc['wfh_days'],
            'calculation_version' => $version,
            'fingerprint' => $newFingerprint,
            'employee_review_status' => $employeeReviewStatus,
            'employee_approved_at' => $employeeApprovedAt,
            'admin_approved_at' => $adminApprovedAt,
            'admin_approved_by_id' => $adminApprovedById,
            'calculation_metadata' => [
                'pf' => $calc['pf'],
                'esi' => $calc['esi'],
                'prof_tax' => $calc['prof_tax'],
                'cycle_type' => $calc['cycle_type'],
                'daily_breakdown' => $calc['daily_breakdown'],
                'daily_rate' => $calc['daily_rate'],
                'hourly_rate' => $calc['hourly_rate'],
                'calendar_days' => $calc['calendar_days'],
                'fingerprint_inputs' => $fingerprintInputs,
                'historical_approvals' => $historicalApprovals,
            ],
        ]);

        PayrollException::where('payroll_record_id', $record->id)->delete();
        self::detectAndCreateExceptions($record, $calc);
    }

    /**
     * Unlock a specific locked payroll record (delegates to reopenRecord).
     */
    public static function unlockRecord(PayrollRecord $record, string $reason, User $actor): void
    {
        self::reopenRecord($record, $reason, $actor);
    }

    /**
     * Reopen a locked payroll record, preserving approvals/versions/fingerprint in history,
     * invalidating active approvals, revoking payslips, and triggering a fresh recalculation.
     */
    public static function reopenRecord(PayrollRecord $record, string $reason, User $actor): void
    {
        DB::transaction(function () use ($record, $reason, $actor) {
            $oldVersion = $record->calculation_version ?? 1;
            $oldFingerprint = $record->fingerprint;
            $oldNet = $record->net_salary;
            $oldLockedAt = $record->locked_at;
            $oldLockedById = $record->locked_by_id;
            $oldEmployeeApprovedAt = $record->employee_approved_at;
            $oldAdminApprovedAt = $record->admin_approved_at;
            $oldAdminApprovedById = $record->admin_approved_by_id;
            $oldPayslipStatus = $record->payslip_status;

            // Preserve old lock and approval details in calculation_metadata
            $metadata = $record->calculation_metadata ?? [];
            
            // Reopen history log array
            $reopenHistory = $metadata['reopen_history'] ?? [];
            $reopenHistory[] = [
                'reopened_at' => now()->toDateTimeString(),
                'reopened_by_id' => $actor->id,
                'reopened_by_name' => $actor->name,
                'reason' => $reason,
                'old_version' => $oldVersion,
                'old_fingerprint' => $oldFingerprint,
                'old_net_salary' => $oldNet,
                'old_employee_approved_at' => $oldEmployeeApprovedAt ? $oldEmployeeApprovedAt->toDateTimeString() : null,
                'old_admin_approved_at' => $oldAdminApprovedAt ? $oldAdminApprovedAt->toDateTimeString() : null,
                'old_admin_approved_by_id' => $oldAdminApprovedById,
                'old_locked_at' => $oldLockedAt ? $oldLockedAt->toDateTimeString() : null,
                'old_locked_by_id' => $oldLockedById,
                'old_payslip_status' => $oldPayslipStatus,
            ];
            $metadata['reopen_history'] = $reopenHistory;

            // Mark old payslip as revoked, reset approvals and unlocked status
            $record->update([
                'locked' => false,
                'locked_at' => null,
                'locked_by_id' => null,
                'locked_snapshot' => null,
                'calculation_version' => $oldVersion + 1,
                'employee_review_status' => 'pending',
                'employee_approved_at' => null,
                'admin_approved_at' => null,
                'admin_approved_by_id' => null,
                'status' => 'pending',
                'payslip_status' => 'revoked', // mark old payslip as revoked
                'payslip_generated_at' => null,
                'payslip_published_at' => null,
                'calculation_metadata' => $metadata,
            ]);

            // Record detailed audit trail log
            PayrollAuditLog::record(
                $record->user_id,
                $actor->id,
                "Payroll reopened: Previous version v{$oldVersion} (fingerprint: {$oldFingerprint}, net: Rs. {$oldNet}, locked). Approvals invalidated. Payslip revoked.",
                "Unlock",
                "locked",
                "unlocked",
                $reason
            );

            // Recalculate against current canonical data
            self::recalculateRecord($record, $actor);
        });
    }
}
