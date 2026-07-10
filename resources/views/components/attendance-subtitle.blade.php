@props(['employee', 'date', 'attendance' => null])

@php
    $dateObj = \Carbon\Carbon::parse($date);
    $att = $attendance ?? $employee->today_attendance;
    $isWeeklyOff = \App\Services\AttendanceTimingResolver::isWeeklyOff($dateObj);
    $status = $att ? $att->status : ($isWeeklyOff ? 'weekly_off' : 'absent');

    // Normalize status names from legacy database entries (consistent with resolveStateForDate)
    if ($status === 'paid_leave') {
        $status = 'planned';
    } elseif ($status === 'unpaid_leave') {
        $status = 'upa';
    } elseif ($status === 'weekly_off') {
        $status = 'off';
    } elseif ($status === 'half_day') {
        $status = 'half';
    }

    // Calculate hours worked (only if checked out)
    $hoursWorkedStr = '';
    if ($att && $att->check_in_time && $att->check_out_time) {
        $hours = $att->check_in_time->diffInMinutes($att->check_out_time, absolute: true) / 60.0;
        $hoursWorkedStr = $hours ? ' · ' . number_format($hours, 1) . 'h worked' : '';
    }

    // Calculate late minutes deterministically
    $lateMinutes = 0;
    if ($att && $att->check_in_time) {
        $timings = \App\Services\AttendanceTimingResolver::resolveTimings($employee, $dateObj);
        $checkInMin = $att->check_in_time->copy()->second(0)->microsecond(0);
        $graceThreshold = $timings['grace_threshold']->copy()->second(0)->microsecond(0);
        $lateMinutes = $checkInMin->gt($graceThreshold) ? (int)$checkInMin->diffInMinutes($graceThreshold, true) : 0;
    }

    $desc = '';
    if ($att) {
        if ($att->check_in_time) {
            // Attendance exists with a valid check-in time
            if (!$att->is_overridden && $att->classification === 'half_day') {
                // Automatic Half Day
                if ($att->automatic_classification_reason === 'late_arrival' || ($lateMinutes > 0 && !$att->automatic_classification_reason)) {
                    if ($hoursWorkedStr) {
                        $desc = 'Late check-in · ' . $lateMinutes . 'm past grace' . $hoursWorkedStr;
                    } else {
                        $desc = 'Late check-in · ' . $lateMinutes . 'm past grace · Automatic Half Day';
                    }
                } elseif ($att->automatic_classification_reason === 'insufficient_hours') {
                    $desc = 'Insufficient working hours' . $hoursWorkedStr . ' · Automatic Half Day';
                } else {
                    $desc = 'Checked in' . $hoursWorkedStr . ' · Automatic Half Day';
                }
            } else {
                // Regular check-in or manual override
                if ($status === 'late' || $lateMinutes > 0) {
                    $desc = 'Checked in · ' . $lateMinutes . 'm past grace' . $hoursWorkedStr;
                } else {
                    $desc = 'Checked in' . $hoursWorkedStr;
                }
            }
        } else {
            // Attendance exists but no check-in time (e.g. leave, wfh, weekly_off temporary records or DB records)
            if (in_array($status, ['planned', 'upa', 'hdp', 'hd_upa', 'bday', 'on_leave'])) {
                $desc = 'Approved leave';
            } elseif ($status === 'wfh') {
                $desc = 'Working from home' . $hoursWorkedStr;
            } elseif ($status === 'off' || $status === 'weekly_off' || $status === 'weekend') {
                $desc = 'Weekly Off · Non-working day';
            } else {
                $isFlagged = ($att->metadata['flagged_for_review'] ?? false) || ($att->metadata['review_pending'] ?? false) || ($att->status === 'review') || ($att->status === 'pending_review');
                if ($isFlagged) {
                    $desc = 'No check-in recorded · flagged for review';
                } else {
                    $desc = 'No check-in recorded';
                }
            }
        }
    } else {
        // No attendance record at all
        $desc = 'No check-in recorded';
    }
@endphp
{{ $desc }}
