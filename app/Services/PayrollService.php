<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

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
                'daily_rate' => 0.00,
                'total_days' => 0,
                'days_with_deductions' => 0,
                'total_deductions' => 0.00,
                'net_salary' => 0.00,
                'daily_breakdown' => [],
            ];
        }

        $baseSalary = (float) $profile->base_salary;
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->startOfDay();
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $dailyRate = $baseSalary / $totalDays;

        $attendanceService = app(AttendanceService::class);
        $states = $attendanceService->getAttendanceStatesForRange($user, $startDate, $endDate);

        $totalDeductions = 0.00;
        $daysWithDeductions = 0;
        $dailyBreakdown = [];

        foreach ($states as $dateStr => $state) {
            $deductionFactor = (float) ($state['salary_deduction'] ?? 0.0);
            $deductedAmount = $deductionFactor * $dailyRate;

            if ($deductedAmount > 0.0) {
                $totalDeductions += $deductedAmount;
                $daysWithDeductions += $deductionFactor;
            }

            $dailyBreakdown[$dateStr] = [
                'status' => $state['status'],
                'deduction_factor' => $deductionFactor,
                'deducted_amount' => round($deductedAmount, 2),
            ];
        }

        $netSalary = max(0.00, $baseSalary - $totalDeductions);

        return [
            'base_salary' => round($baseSalary, 2),
            'daily_rate' => round($dailyRate, 2),
            'total_days' => $totalDays,
            'days_with_deductions' => $daysWithDeductions,
            'total_deductions' => round($totalDeductions, 2),
            'net_salary' => round($netSalary, 2),
            'daily_breakdown' => $dailyBreakdown,
        ];
    }
}
