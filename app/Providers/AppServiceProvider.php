<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\AttendanceOverridden::class,
            \App\Listeners\SyncAttendanceToPayroll::class
        );

        \App\Models\Attendance::saved(function ($attendance) {
            if ($attendance->user) {
                \App\Services\PayrollInvalidationService::syncEmployeeForDate(
                    $attendance->user,
                    \Carbon\Carbon::parse($attendance->date),
                    'Attendance record saved'
                );
            }
        });

        \App\Models\Attendance::deleted(function ($attendance) {
            if ($attendance->user) {
                \App\Services\PayrollInvalidationService::syncEmployeeForDate(
                    $attendance->user,
                    \Carbon\Carbon::parse($attendance->date),
                    'Attendance record deleted'
                );
            }
        });

        \App\Models\LeaveRequest::saved(function ($leaveRequest) {
            if ($leaveRequest->user) {
                \App\Services\PayrollInvalidationService::syncEmployeeForDateRange(
                    $leaveRequest->user,
                    \Carbon\Carbon::parse($leaveRequest->start_date),
                    \Carbon\Carbon::parse($leaveRequest->end_date),
                    'Leave request status mutated'
                );
            }
        });

        \App\Models\LeaveRequest::deleted(function ($leaveRequest) {
            if ($leaveRequest->user) {
                \App\Services\PayrollInvalidationService::syncEmployeeForDateRange(
                    $leaveRequest->user,
                    \Carbon\Carbon::parse($leaveRequest->start_date),
                    \Carbon\Carbon::parse($leaveRequest->end_date),
                    'Leave request deleted'
                );
            }
        });

        \App\Models\PayrollProfile::saved(function ($profile) {
            if ($profile->user) {
                \App\Services\PayrollInvalidationService::syncEmployeeAllOpenCycles(
                    $profile->user,
                    'Payroll profile updated'
                );
            }
        });

        \App\Models\SalaryHistory::saved(function ($salaryHistory) {
            $user = $salaryHistory->payrollProfile?->user;
            if ($user) {
                \App\Services\PayrollInvalidationService::syncEmployeeAllOpenCycles(
                    $user,
                    'Salary history saved'
                );
            }
        });

        \App\Models\SalaryHistory::deleted(function ($salaryHistory) {
            $user = $salaryHistory->payrollProfile?->user;
            if ($user) {
                \App\Services\PayrollInvalidationService::syncEmployeeAllOpenCycles(
                    $user,
                    'Salary history deleted'
                );
            }
        });
    }
}
