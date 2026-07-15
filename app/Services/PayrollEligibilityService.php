<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class PayrollEligibilityService
{
    /**
     * Get all eligible users for a given cycle period (year & month).
     */
    public static function getEligibleEmployees(int $year, int $month): \Illuminate\Support\Collection
    {
        // First resolve target month range to do a preliminary check
        $targetMonthStart = Carbon::create($year, $month, 1)->startOfDay();
        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth()->startOfDay();

        return User::where('role', '!=', 'admin')
            ->whereHas('payrollProfile', function ($query) {
                $query->where('payroll_enabled', true);
            })
            ->get()
            ->filter(function ($user) use ($year, $month, $targetMonthStart, $targetMonthEnd) {
                // Respect joining date boundaries
                $joiningDate = $user->joining_date;
                if ($joiningDate) {
                    $joiningDate = Carbon::parse($joiningDate)->startOfDay();
                    if ($joiningDate->gt($targetMonthEnd)) {
                        return false;
                    }
                }

                // Respect separation/termination date boundaries
                $profile = $user->employeeProfile;
                if ($profile) {
                    $separationDate = $profile->separation_date ?? $profile->last_working_day;
                    if ($separationDate) {
                        $separationDate = Carbon::parse($separationDate)->startOfDay();
                        
                        // Resolve the actual cycle start date for this user
                        $resolver = new PayrollCycleResolver();
                        $cycleInfo = $resolver->resolve($user, $year, $month);
                        if (!$cycleInfo) {
                            return false;
                        }
                        
                        $cycleStart = $cycleInfo['start_date'];
                        if ($separationDate->lt($cycleStart)) {
                            return false;
                        }
                    }
                }

                // Call resolver to ensure the user is employed in this cycle
                $resolver = new PayrollCycleResolver();
                $cycleInfo = $resolver->resolve($user, $year, $month);
                return $cycleInfo !== null;
            });
    }
}
