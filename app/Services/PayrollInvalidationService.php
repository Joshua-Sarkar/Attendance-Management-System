<?php

namespace App\Services;

use App\Models\User;
use App\Models\PayrollRecord;
use App\Models\PayrollCycle;
use App\Models\PayrollAuditLog;
use Carbon\Carbon;

class PayrollInvalidationService
{
    /**
     * Synchronize a specific employee's payroll for a given date.
     */
    public static function syncEmployeeForDate(User $employee, Carbon $date, string $reason): void
    {
        $dateStr = $date->format('Y-m-d');
        $actor = auth()->user() ?: User::where('role', 'admin')->first() ?: $employee;

        // Find payroll cycle(s) containing that date
        $cycles = PayrollCycle::where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->get();

        foreach ($cycles as $cycle) {
            // Check if record exists for this employee
            $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)
                ->where('user_id', $employee->id)
                ->first();

            if ($record) {
                if ($record->locked) {
                    // Create an audit event for reconciliation conflict
                    PayrollAuditLog::record(
                        $employee->id,
                        $actor->id,
                        "Reconciliation Conflict: Attendance mutated on locked cycle period date {$dateStr}. Locked figures preserved.",
                        "System Alert",
                        $record->fingerprint,
                        $record->fingerprint,
                        "Reason: {$reason}"
                    );
                } else {
                    PayrollService::recalculateRecord($record, $actor);
                }
            }
        }
    }

    /**
     * Synchronize a specific employee's payroll for a date range.
     */
    public static function syncEmployeeForDateRange(User $employee, Carbon $startDate, Carbon $endDate, string $reason): void
    {
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            self::syncEmployeeForDate($employee, $current, $reason);
            $current->addDay();
        }
    }

    /**
     * Synchronize all open payroll records for a specific employee.
     */
    public static function syncEmployeeAllOpenCycles(User $employee, string $reason): void
    {
        $actor = auth()->user() ?: User::where('role', 'admin')->first() ?: $employee;

        $records = PayrollRecord::where('user_id', $employee->id)
            ->where('locked', false)
            ->get();

        foreach ($records as $record) {
            PayrollService::recalculateRecord($record, $actor);
        }
    }
}
