<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceOverrideController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected \App\Services\AttendanceService $attendanceService
    ) {}

    /**
     * Return list of active employees for search autocomplete.
     */
    public function employees(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $allActiveEmployees = User::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn($e) => [
                'id' => (string)$e->id,
                'name' => $e->name,
                'employee_id' => $e->employee_id,
                'dept_id' => (string)$e->department_id
            ]);

        return response()->json($allActiveEmployees);
    }

    /**
     * Preview counts and conflicts for a bulk attendance override.
     */
    public function preview(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // Normalize legacy format if passed to preview
        if (!$request->has('scope_type')) {
            if ($request->has('user_ids')) {
                $request->merge([
                    'scope_type' => 'employee',
                    'employee_ids' => $request->input('user_ids'),
                    'date_mode' => 'single',
                    'conflict_handling' => 'replace',
                ]);
            } elseif ($request->has('user_id')) {
                $request->merge([
                    'scope_type' => 'employee',
                    'employee_ids' => [$request->input('user_id')],
                    'date_mode' => 'single',
                    'conflict_handling' => 'replace',
                ]);
            }
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'half_day') {
                $request->merge(['status' => 'half']);
            } elseif ($status === 'paid_leave') {
                $request->merge(['status' => 'planned']);
            } elseif ($status === 'unpaid_leave') {
                $request->merge(['status' => 'upa']);
            } elseif ($status === 'weekly_off') {
                $request->merge(['status' => 'off']);
            }
        }

        $validated = $request->validate([
            'scope_type' => 'required|string|in:employee,department,all',
            'employee_ids' => 'required_if:scope_type,employee|array',
            'employee_ids.*' => 'exists:users,id',
            'department_ids' => 'required_if:scope_type,department|array',
            'department_ids.*' => 'exists:departments,id',
            'date_mode' => 'required|string|in:single,range,multiple',
            'date' => 'required_if:date_mode,single|nullable|date',
            'start_date' => 'required_if:date_mode,range|nullable|date',
            'end_date' => 'required_if:date_mode,range|nullable|date|after_or_equal:start_date',
            'dates' => 'required_if:date_mode,multiple|array',
            'dates.*' => 'date',
            'working_days_only' => 'nullable',
            'include_sundays' => 'nullable',
            'skip_leaves' => 'nullable',
            'skip_overrides' => 'nullable',
            'status' => 'required|string|in:' . implode(',', array_merge(array_keys(\App\Services\AttendanceStateRegistry::getStates()), ['wfh'])),
            'classification' => 'nullable|string|in:automatic,full_day,half_day',
            'override_reason' => 'required|string|min:5',
            'conflict_handling' => 'required|string|in:skip,replace,cancel',
        ]);

        $validated['working_days_only'] = filter_var($request->input('working_days_only'), FILTER_VALIDATE_BOOLEAN);
        $validated['include_sundays'] = filter_var($request->input('include_sundays'), FILTER_VALIDATE_BOOLEAN);
        $validated['skip_leaves'] = filter_var($request->input('skip_leaves'), FILTER_VALIDATE_BOOLEAN);
        $validated['skip_overrides'] = filter_var($request->input('skip_overrides'), FILTER_VALIDATE_BOOLEAN);

        try {
            $preview = $this->attendanceService->getBulkOverridePreview($validated, $request->user());
            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Store bulk or individual attendance overrides.
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // Normalize legacy format to new format
        if (!$request->has('scope_type')) {
            if ($request->has('user_ids')) {
                $request->merge([
                    'scope_type' => 'employee',
                    'employee_ids' => $request->input('user_ids'),
                    'date_mode' => 'single',
                    'conflict_handling' => 'replace',
                ]);
            } elseif ($request->has('user_id')) {
                $request->merge([
                    'scope_type' => 'employee',
                    'employee_ids' => [$request->input('user_id')],
                    'date_mode' => 'single',
                    'conflict_handling' => 'replace',
                ]);
            }
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'half_day') {
                $request->merge(['status' => 'half']);
            } elseif ($status === 'paid_leave') {
                $request->merge(['status' => 'planned']);
            } elseif ($status === 'unpaid_leave') {
                $request->merge(['status' => 'upa']);
            } elseif ($status === 'weekly_off') {
                $request->merge(['status' => 'off']);
            }
        }

        $validated = $request->validate([
            'scope_type' => 'required|string|in:employee,department,all',
            'employee_ids' => 'required_if:scope_type,employee|array',
            'employee_ids.*' => 'exists:users,id',
            'department_ids' => 'required_if:scope_type,department|array',
            'department_ids.*' => 'exists:departments,id',
            'date_mode' => 'required|string|in:single,range,multiple',
            'date' => 'required_if:date_mode,single|nullable|date',
            'start_date' => 'required_if:date_mode,range|nullable|date',
            'end_date' => 'required_if:date_mode,range|nullable|date|after_or_equal:start_date',
            'dates' => 'required_if:date_mode,multiple|array',
            'dates.*' => 'date',
            'working_days_only' => 'nullable',
            'include_sundays' => 'nullable',
            'skip_leaves' => 'nullable',
            'skip_overrides' => 'nullable',
            'status' => 'required|string|in:' . implode(',', array_merge(array_keys(\App\Services\AttendanceStateRegistry::getStates()), ['wfh'])),
            'classification' => 'nullable|string|in:automatic,full_day,half_day',
            'override_reason' => 'required|string|min:5',
            'conflict_handling' => 'required|string|in:skip,replace,cancel',
        ]);

        $validated['working_days_only'] = filter_var($request->input('working_days_only'), FILTER_VALIDATE_BOOLEAN);
        $validated['include_sundays'] = filter_var($request->input('include_sundays'), FILTER_VALIDATE_BOOLEAN);
        $validated['skip_leaves'] = filter_var($request->input('skip_leaves'), FILTER_VALIDATE_BOOLEAN);
        $validated['skip_overrides'] = filter_var($request->input('skip_overrides'), FILTER_VALIDATE_BOOLEAN);

        try {
            $result = $this->attendanceService->applyBulkOverride($validated, $request->user());
            return back()->with('success', "Applied overrides successfully to {$result['applied_count']} record(s).");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['override_reason' => $e->getMessage()]);
        }
    }
}
