<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\PayrollRecord;
use App\Services\AttendanceTimingResolver;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeAttendanceCalendarController extends Controller
{
    /**
     * Get attendance calendar data and metrics for a user and date range.
     */
    public function getData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userId = $request->query('user_id');

        if ($userId && $userId != $user->id) {
            $employee = User::findOrFail($userId);
            // Access control: only admins and managers can view other profiles
            if ($user->role === 'employee') {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            if ($user->role === 'manager') {
                if ($employee->role !== 'employee' || $employee->manager_id !== $user->id) {
                    return response()->json(['error' => 'Unauthorized action.'], 403);
                }
            }
        } else {
            $employee = $user;
        }

        $startDateStr = $request->query('start_date');
        $endDateStr = $request->query('end_date');
        $month = $request->query('month'); // 0-indexed month from Alpine.js (0 = January)
        $year = $request->query('year');

        if ($startDateStr && $endDateStr) {
            $startDate = Carbon::parse($startDateStr)->startOfDay();
            $endDate = Carbon::parse($endDateStr)->endOfDay();
            
            // Custom Range: grid range matches the start of the week of start_date and the end of the week of end_date
            $gridStart = $startDate->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd = $endDate->copy()->endOfWeek(Carbon::SUNDAY);
        } else {
            $year = $year !== null ? (int)$year : today()->year;
            $month = $month !== null ? (int)$month + 1 : today()->month; // Convert 0-indexed to 1-indexed

            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();

            // Month navigation: show exactly 42 days grid starting from the Monday of the first week
            $startOffset = ($startDate->dayOfWeekIso - 1);
            $gridStart = $startDate->copy()->subDays($startOffset);
            $gridEnd = $gridStart->copy()->addDays(41)->endOfDay();
        }

        // Fetch user relations needed
        $employee->load(['department']);

        $attendanceService = app(\App\Services\AttendanceService::class);
        $states = $attendanceService->getAttendanceStatesForRange($employee, $gridStart, $gridEnd);

        $payroll = PayrollRecord::where('user_id', $employee->id)
            ->whereHas('payrollCycle', function ($q) use ($gridStart, $gridEnd) {
                $q->where('start_date', '<=', $gridEnd->format('Y-m-d'))
                  ->where('end_date', '>=', $gridStart->format('Y-m-d'));
            })
            ->first();

        $gridDays = [];
        foreach ($states as $dateStr => $state) {
            $currentDate = Carbon::parse($dateStr);
            $inRange = $currentDate->between($startDate, $endDate);

            // Shift Timings Info
            $shiftStart = $state['shift_start'];
            $shiftEnd = $state['shift_end'];
            $timings = $state['timings'];
            $graceMinutes = $timings ? $timings['grace_minutes'] : 15;

            $status = $state['status'];
            $isWorking = in_array($status, ['present', 'late', 'half']);

            // Expected shift hours
            $expectedHours = 8.5;
            if ($shiftStart && $shiftEnd) {
                $expectedHours = (float) number_format($shiftStart->diffInMinutes($shiftEnd, true) / 60.0, 1);
            }

            // Hours worked
            $hours = (float) $state['hours'];

            // Overtime hours
            $overtime = 0.0;
            if ($isWorking && $hours > $expectedHours) {
                $overtime = (float) number_format($hours - $expectedHours, 1);
            }

            // Late minutes
            $lateMin = 0;
            if ($state['check_in_time'] && $timings) {
                $checkInMin = $state['check_in_time']->copy()->second(0)->microsecond(0);
                $graceThreshold = $timings['grace_threshold']->copy()->second(0)->microsecond(0);
                if ($checkInMin->gt($graceThreshold)) {
                    $lateMin = (int) $checkInMin->diffInMinutes($graceThreshold, true);
                }
            }

            // Early exit minutes
            $earlyMin = 0;
            if ($isWorking && $state['check_out_time'] && $shiftEnd) {
                $checkOutMin = $state['check_out_time']->copy()->second(0)->microsecond(0);
                $shiftEndMin = $shiftEnd->copy()->second(0)->microsecond(0);
                if ($checkOutMin->lt($shiftEndMin)) {
                    $earlyMin = (int) $checkOutMin->diffInMinutes($shiftEndMin, true);
                }
            }

            // Classification string
            $classification = 'Non-Working';
            if ($isWorking) {
                $classification = ($state['classification'] === 'half_day') ? 'Half Day' : 'Full Day';
            } elseif ($status === 'off') {
                $classification = 'Non-Working';
            } elseif (in_array($status, ['planned', 'upa', 'upr', 'hdp', 'hd_upa', 'hd_upr', 'bday'])) {
                $classification = 'Leave';
            }

            // Resolve payroll impact dynamically in the UI layer
            $payrollImpact = 'None';
            if ($payroll) {
                $breakdown = $payroll->calculation_metadata['daily_breakdown'] ?? [];
                if (isset($breakdown[$dateStr])) {
                    $deduction = (float)($breakdown[$dateStr]['deduction_factor'] ?? 0.0);
                    if ($deduction > 0.0) {
                        $payrollImpact = 'Deduction applied';
                    }
                }
            } else {
                $dummyBalance = (float) $employee->leave_balance;
                $dailyRate = 1.0;
                $pClassification = PayrollService::resolvePayrollClassification($status, $dummyBalance, $dailyRate);
                if ($pClassification['deduction_factor'] > 0.0) {
                    $payrollImpact = 'Deduction applied';
                }
            }

            $gridDays[] = [
                'iso' => $dateStr,
                'num' => $currentDate->day,
                'inMonth' => $inRange,
                'inRange' => $inRange,
                'isToday' => $currentDate->isToday(),
                'status' => $status,
                'dow' => ($currentDate->dayOfWeekIso - 1),
                'checkIn' => $state['check_in_time'] ? $state['check_in_time']->timezone('Asia/Kolkata')->format('h:i A') : null,
                'checkOut' => $state['check_out_time'] ? $state['check_out_time']->timezone('Asia/Kolkata')->format('h:i A') : null,
                'hours' => $hours,
                'expectedHours' => $expectedHours,
                'overtime' => $overtime,
                'lateMin' => $lateMin,
                'earlyMin' => $earlyMin,
                'classification' => $classification,
                'leaveType' => $state['leave_type'] ?? '—',
                'override' => (bool)$state['is_overridden'],
                'overrideReason' => $state['notes'] ?? '',
                'checkInDevice' => $state['check_in_device'],
                'checkInLocation' => $state['check_in_location'],
                'checkOutDevice' => $state['check_out_device'],
                'checkOutLocation' => $state['check_out_location'],
                'approvedBy' => $state['approved_by'],
                'department' => $employee->department?->name ?? '—',
                'shift' => $shiftStart && $shiftEnd ? $shiftStart->format('h:i A') . ' – ' . $shiftEnd->format('h:i A') : '09:00 AM – 05:30 PM',
                'grace' => $graceMinutes . ' minutes',
                'lateThreshold' => $timings && $timings['grace_threshold'] ? $timings['grace_threshold']->format('h:i A') : '—',
                'payrollImpact' => $payrollImpact,
                'notes' => $state['notes'] ?? 'No irregularities recorded.',
                'dateLabel' => $currentDate->format('F j, Y'),
                'dayName' => $currentDate->format('l'),
            ];
        }

        // Metrics are calculated only for days within the requested range
        $rangeDays = array_filter($gridDays, fn($d) => $d['inRange']);

        $count = fn($k) => count(array_filter($rangeDays, fn($d) => $d['status'] === $k));

        $present = $count('present');
        $late = $count('late');
        $half = $count('half');
        $absent = $count('absent') + $count('upr') + $count('hd_upr');
        $off = $count('off');
        $future = $count('future');
        
        $leaveDays = $count('planned') + $count('upa') + $count('hdp') + $count('hd_upa') + $count('bday') + $count('on_leave');

        // Working days count
        $workingDaysCount = count(array_filter($rangeDays, fn($d) => !in_array($d['status'], ['off', 'future'])));
        $attendanceRate = $workingDaysCount > 0 
            ? (int) round((($present + $late + $half) / $workingDaysCount) * 100) 
            : 0;

        $workedDays = array_filter($rangeDays, fn($d) => in_array($d['status'], ['present', 'late', 'half']) && $d['checkIn']);

        $avgCheckIn = '—';
        $avgCheckOut = '—';
        $avgHoursWorked = 0.0;
        $totalHoursWorked = 0.0;

        if (count($workedDays) > 0) {
            $checkInMinutes = [];
            $checkOutMinutes = [];
            $hoursList = [];

            foreach ($workedDays as $wd) {
                $hoursList[] = $wd['hours'];

                if ($wd['checkIn']) {
                    $parsedCI = Carbon::createFromFormat('h:i A', $wd['checkIn']);
                    $checkInMinutes[] = $parsedCI->hour * 60 + $parsedCI->minute;
                }
                if ($wd['checkOut']) {
                    $parsedCO = Carbon::createFromFormat('h:i A', $wd['checkOut']);
                    $checkOutMinutes[] = $parsedCO->hour * 60 + $parsedCO->minute;
                }
            }

            $totalHoursWorked = array_sum($hoursList);
            $avgHoursWorked = count($hoursList) > 0 ? (float) number_format(array_sum($hoursList) / count($hoursList), 1) : 0.0;

            if (count($checkInMinutes) > 0) {
                $avgCIMin = (int) round(array_sum($checkInMinutes) / count($checkInMinutes));
                $avgCheckIn = Carbon::today()->startOfDay()->addMinutes($avgCIMin)->format('h:i A');
            }
            if (count($checkOutMinutes) > 0) {
                $avgCOMin = (int) round(array_sum($checkOutMinutes) / count($checkOutMinutes));
                $avgCheckOut = Carbon::today()->startOfDay()->addMinutes($avgCOMin)->format('h:i A');
            }
        }

        $overtime = array_sum(array_column($rangeDays, 'overtime'));
        $lateMinutes = array_sum(array_column($rangeDays, 'lateMin'));
        $earlyExitMinutes = array_sum(array_column($rangeDays, 'earlyMin'));
        $overrideCount = count(array_filter($rangeDays, fn($d) => $d['override']));
        $payrollEligibleDays = count(array_filter($rangeDays, fn($d) => $d['payrollImpact'] === 'None'));

        return response()->json([
            'gridDays' => $gridDays,
            'departments' => \App\Models\Department::pluck('name')->toArray(),
            'employee' => [
                'name' => $employee->name,
                'dept' => $employee->department?->name ?? 'Not Assigned',
                'id' => $employee->employee_id ?? ('EMP-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT)),
            ],
            'metrics' => [
                'attendanceRate' => $attendanceRate . '%',
                'presentDays' => $present,
                'lateDays' => $late,
                'halfDays' => $half,
                'absentDays' => $absent,
                'leaveDays' => $leaveDays,
                'avgCheckIn' => $avgCheckIn,
                'avgCheckOut' => $avgCheckOut,
                'avgHoursWorked' => $avgHoursWorked . 'h',
                'totalHoursWorked' => $totalHoursWorked . 'h',
                'overtime' => $overtime . 'h',
                'lateMinutes' => $lateMinutes . 'm',
                'earlyExitMinutes' => $earlyExitMinutes . 'm',
                'overrideCount' => $overrideCount,
                'payrollEligibleDays' => $payrollEligibleDays,
            ]
        ]);
    }

    /**
     * Resolve calendar status.
     */
    private function resolveCalendarStatus(Carbon $date, ?Attendance $record, ?\App\Models\LeaveRequest $leave): string
    {
        if ($record) {
            $status = $record->status;
            if ($record->classification === 'half_day') {
                return 'half';
            }
            if ($status === 'present') {
                return 'present';
            }
            if ($status === 'late') {
                return 'late';
            }
            if ($status === 'wfh') {
                return 'wfh';
            }
            if ($status === 'weekly_off' || $status === 'off') {
                return 'off';
            }
            if ($status === 'paid_leave' || $status === 'paid') {
                return 'paid';
            }
            if ($status === 'unpaid_leave' || $status === 'unpaid') {
                return 'unpaid';
            }
            if ($status === 'absent') {
                return 'absent';
            }
            return $status;
        }

        if ($leave) {
            if ($leave->leave_type === 'work_from_home') {
                return 'wfh';
            }
            $isBirthday = $leave->leave_type === 'complimentary' || 
                          $leave->leave_type === 'birthday_leave' || 
                          ($leave->metadata && isset($leave->metadata['is_birthday']) && $leave->metadata['is_birthday']);
            if ($isBirthday) {
                return 'bday';
            }
            return $leave->is_paid ? 'paid' : 'unpaid';
        }

        if (AttendanceTimingResolver::isWeeklyOff($date)) {
            return 'off';
        }

        return 'absent';
    }
}
