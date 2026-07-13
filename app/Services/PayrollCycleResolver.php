<?php

namespace App\Services;

use App\Models\User;
use App\Models\PayrollSetting;
use Carbon\Carbon;

class PayrollCycleResolver
{
    /**
     * Resolve the payroll cycle for a user in a given calendar month.
     * Returns an array with cycle details, or null if the user was not yet employed.
     */
    public function resolve(User $user, int $year, int $month): ?array
    {
        $joiningDate = $user->joining_date;
        if (!$joiningDate) {
            $joiningDate = '2024-01-01';
        }

        $joiningDate = Carbon::parse($joiningDate)->startOfDay();
        $targetMonthStart = Carbon::create($year, $month, 1)->startOfDay();
        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth()->startOfDay();

        // If they joined after this calendar month, they are not employed yet
        if ($joiningDate->gt($targetMonthEnd)) {
            return null;
        }

        $lifecyclePolicy = PayrollSetting::getValue('lifecycle');
        $transitionDays = (int) ($lifecyclePolicy['payroll_cycle_transition_days'] ?? 120);

        // Helper to check if a date is within their first month of employment
        $isFirstMonth = $joiningDate->year === $year && $joiningDate->month === $month;

        // If they join during this month:
        if ($isFirstMonth) {
            if ($joiningDate->day <= 19) {
                // Initial partial cycle from joining date to 19th of the month
                $start = $joiningDate->copy();
                $end = Carbon::create($year, $month, 19)->startOfDay();
                
                if ($start->gt($end)) {
                    $end = $start->copy();
                }

                return [
                    'type' => 'initial_partial',
                    'start_date' => $start,
                    'end_date' => $end,
                    'payment_date' => Carbon::create($year, $month, 7)->startOfDay()->addMonth(),
                    'description' => 'Initial partial cycle',
                ];
            } else {
                // If they join on or after the 20th, first cycle starts on joining date
                // and goes to the 20th of the next month
                $start = $joiningDate->copy();
                $end = Carbon::create($year, $month, 20)->startOfDay()->addMonth();

                return [
                    'type' => 'initial_partial',
                    'start_date' => $start,
                    'end_date' => $end,
                    'payment_date' => $end->copy()->addMonth()->setDay(7),
                    'description' => 'Initial partial cycle crossing months',
                ];
            }
        }

        // Determine the bridge month using the deterministic transition threshold logic:
        // 120th day of employment
        $transitionDate = $joiningDate->copy()->addDays($transitionDays)->startOfDay();
        
        // Find the bridge month:
        // If transition date falls on or before the 20th of month X, the bridge month is X - 1.
        // Otherwise, it is X.
        $bridgeYear = $transitionDate->year;
        $bridgeMonth = $transitionDate->month;
        
        if ($transitionDate->day <= 20) {
            $bridgeMonth--;
            if ($bridgeMonth === 0) {
                $bridgeMonth = 12;
                $bridgeYear--;
            }
        }

        $bridgeMonthStart = Carbon::create($bridgeYear, $bridgeMonth, 1)->startOfDay();

        // 1. If target month is AFTER the bridge month:
        // They are on the standard calendar-month cycle!
        if ($targetMonthStart->gt($bridgeMonthStart)) {
            return [
                'type' => 'calendar_month',
                'start_date' => $targetMonthStart->copy(),
                'end_date' => $targetMonthEnd->copy(),
                'payment_date' => $targetMonthEnd->copy()->addMonth()->setDay(7),
                'description' => 'Standard calendar-month cycle',
            ];
        }

        // 2. If target month is EQUAL to the bridge month:
        // They are on the bridge cycle!
        if ($targetMonthStart->equalTo($bridgeMonthStart)) {
            $start = Carbon::create($year, $month, 21)->startOfDay()->subMonth();
            $end = $targetMonthEnd->copy();

            return [
                'type' => 'bridge',
                'start_date' => $start,
                'end_date' => $end,
                'payment_date' => $end->copy()->addMonth()->setDay(7),
                'description' => 'Regularization/bridge cycle',
            ];
        }

        // 3. Otherwise (target month is BEFORE the bridge month):
        // They are on the standard 20th-to-20th cycle.
        $start = Carbon::create($year, $month, 21)->startOfDay()->subMonth();
        // Handle June cycle boundary starting July cycle on June 20
        if ($month === 7 && $start->month === 6) {
            $start->setDay(20);
        }
        $end = Carbon::create($year, $month, 20)->startOfDay();

        if ($joiningDate->gt($start)) {
            $start = $joiningDate->copy();
        }

        return [
            'type' => 'standard_20_20',
            'start_date' => $start,
            'end_date' => $end,
            'payment_date' => $end->copy()->addMonth()->setDay(7),
            'description' => 'Standard 20th-to-20th cycle',
        ];
    }
}
