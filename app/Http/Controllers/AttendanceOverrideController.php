<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceOverrideController extends Controller
{
    /**
     * Store an individual or bulk attendance override.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|string|in:present,absent,paid_leave,unpaid_leave,weekly_off,wfh',
            'classification' => 'nullable|string|in:full_day,half_day',
            'override_reason' => 'required|string|min:5',
            'user_id' => 'nullable|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();
        $status = $validated['status'];
        $classification = $validated['classification'] ?? null;
        $reason = $validated['override_reason'];
        
        $userIds = [];
        $overrideType = 'individual';

        if (!empty($validated['user_ids'])) {
            $userIds = $validated['user_ids'];
            $overrideType = count($userIds) > 1 ? 'bulk' : 'individual';
        } elseif (!empty($validated['user_id'])) {
            $userIds = [$validated['user_id']];
            $overrideType = 'individual';
        } else {
            return back()->withErrors(['user_ids' => 'You must select at least one employee.']);
        }

        $now = now();

        foreach ($userIds as $userId) {
            $attendance = Attendance::firstOrNew([
                'user_id' => $userId,
                'date' => $date,
            ]);

            if (!$attendance->exists) {
                // Determine automatic values
                $dateStr = $date->format('Y-m-d');
                $leave = \App\Models\LeaveRequest::where('user_id', $userId)
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $dateStr . ' 23:59:59')
                    ->where('end_date', '>=', $dateStr . ' 00:00:00')
                    ->first();
                
                $autoStatus = $leave ? ($leave->leave_type === 'work_from_home' ? 'wfh' : 'on_leave') : ($date->isSunday() ? 'weekly_off' : 'absent');
                $autoClassification = 'full_day';

                $attendance->automatic_status = $autoStatus;
                $attendance->automatic_classification = $autoClassification;
                $attendance->automatic_classification_reason = null;
            } else {
                if (is_null($attendance->automatic_status)) {
                    $attendance->automatic_status = $attendance->status;
                }
                if (is_null($attendance->automatic_classification)) {
                    $attendance->automatic_classification = $attendance->classification;
                }
            }

            $attendance->status = $status;
            
            // If classification is blank, preserve the automatically calculated classification
            if (empty($classification)) {
                $attendance->classification = $attendance->automatic_classification ?? 'full_day';
            } else {
                $attendance->classification = $classification;
            }
            
            $attendance->is_overridden = true;
            $attendance->overridden_by = $request->user()->id;
            $attendance->overridden_at = $now; // Set the exact same timestamp
            $attendance->override_reason = $reason;
            $attendance->override_type = $overrideType;
            $attendance->save();
        }

        return back()->with('success', 'Attendance override applied successfully.');
    }
}
