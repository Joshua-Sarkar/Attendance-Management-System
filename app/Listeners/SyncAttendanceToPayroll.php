<?php

namespace App\Listeners;

use App\Events\AttendanceOverridden;
use App\Services\PayrollService;
use App\Models\PayrollRecord;
use Carbon\Carbon;

class SyncAttendanceToPayroll
{
    /**
     * Handle the event.
     */
    public function handle(AttendanceOverridden $event): void
    {
        $dateStr = $event->date->format('Y-m-d');
        
        // Find any unlocked payroll record whose period contains the affected employee & date
        $records = PayrollRecord::where('user_id', $event->employee->id)
            ->where('locked', false)
            ->whereHas('payrollCycle', function ($q) use ($dateStr) {
                $q->where('start_date', '<=', $dateStr)
                  ->where('end_date', '>=', $dateStr);
            })
            ->get();

        foreach ($records as $record) {
            PayrollService::recalculateRecord($record, $event->actor);
        }
    }
}
