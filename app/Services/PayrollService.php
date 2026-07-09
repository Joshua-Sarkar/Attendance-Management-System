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

    /**
     * Administrative update of an employee's payroll profile.
     */
    public static function updateProfile(User $user, array $data): void
    {
        $profile = $user->payrollProfile()->firstOrCreate([], [
            'base_salary' => null,
            'salary_effective_date' => null,
            'payroll_enabled' => false,
            'import_source' => 'Manual',
        ]);

        $baseSalary = isset($data['base_salary']) && $data['base_salary'] !== '' ? (float) $data['base_salary'] : null;
        $effectiveDate = isset($data['salary_effective_date']) && $data['salary_effective_date'] !== '' ? $data['salary_effective_date'] : null;
        $payrollEnabled = filter_var($data['payroll_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Check if revision is needed
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
        }

        $profile->update([
            'payroll_enabled' => $payrollEnabled,
        ]);
    }
}
