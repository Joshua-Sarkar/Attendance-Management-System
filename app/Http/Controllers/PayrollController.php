<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\PayrollCorrection;
use App\Models\PayrollException;
use App\Models\PayrollSetting;
use App\Models\PayrollAuditLog;
use App\Models\LeaveRequest;
use App\Models\PayrollDispute;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Display the Payroll Control Center.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', PayrollCycle::orderBy('start_date', 'desc')->value('period') ?: 'June 2026');
        $actor = auth()->user();
        
        $routeName = $request->route() ? $request->route()->getName() : 'admin.payroll.index';
        $activeTab = 'dashboard';
        
        switch ($routeName) {
            case 'admin.payroll.employees':
                $activeTab = 'employees';
                break;
            case 'admin.payroll.ledger':
                $activeTab = 'ledger';
                break;
            case 'admin.payroll.corrections':
                $activeTab = 'corrections';
                break;
            case 'admin.payroll.exceptions':
                $activeTab = 'exceptions';
                break;
            case 'admin.payroll.lock':
                $activeTab = 'lock';
                break;
            case 'admin.payroll.payslips':
                $activeTab = 'payslips';
                break;
            case 'admin.payroll.audit':
                $activeTab = 'audit';
                break;
            case 'admin.payroll.reports':
                $activeTab = 'reports';
                break;
            case 'admin.payroll.settings':
                $activeTab = 'settings';
                break;
            default:
                $activeTab = 'dashboard';
                break;
        }

        // 1. Process or load cycle
        $cycle = PayrollCycle::where('period', $period)->first();
        if (!$cycle) {
            $cycle = PayrollService::processCycle($period, $actor);
        }

        $cycleInstances = PayrollCycle::orderBy('start_date', 'desc')->get()->map(function($c) {
            $eligibleCount = \App\Services\PayrollEligibilityService::getEligibleEmployees($c->start_date->year, $c->start_date->month)->count();
            $recordCount = $c->payrollRecords()->count();
            $lockedCount = $c->payrollRecords()->where('locked', true)->count();
            $paymentDay = (int) (\App\Models\PayrollSetting::getValue('payroll')['salaryPaymentDay'] ?? 7);
            $paymentDate = $c->end_date->copy()->addMonth()->setDay($paymentDay);
            
            return [
                'id' => $c->id,
                'period' => $c->period,
                'start_date' => $c->start_date->format('d M Y'),
                'end_date' => $c->end_date->format('d M Y'),
                'payment_date' => $paymentDate->format('d M Y'),
                'status' => $c->status,
                'eligible_count' => $eligibleCount,
                'record_count' => $recordCount,
                'locked_count' => $lockedCount,
            ];
        })->toArray();

        $reportFilter = $request->get('report_filter', 'current_cycle');
        $reportCycle = $request->get('report_cycle', $cycle->period);
        $reportStartDate = $request->get('start_date') ?: $cycle->start_date->format('Y-m-d');
        $reportEndDate = $request->get('end_date') ?: $cycle->end_date->format('Y-m-d');

        $range = $this->resolveReportRange($request, $cycle);
        $startDate = $range['start'];
        $endDate = $range['end'];
        $resolvedRangeLabel = $range['label'];

        // Resolve affected cycles for reports
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');
        $cycleIds = PayrollCycle::where(function ($q) use ($startDateStr, $endDateStr) {
            $q->whereBetween('start_date', [$startDateStr, $endDateStr])
              ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
              ->orWhere(function ($q2) use ($startDateStr, $endDateStr) {
                  $q2->where('start_date', '<=', $startDateStr)
                     ->where('end_date', '>=', $endDateStr);
              });
        })->pluck('id');

        $reportRecords = PayrollRecord::with(['user.department', 'user.employeeProfile'])
            ->whereIn('payroll_cycle_id', $cycleIds)
            ->get();

        // 2. Fetch records and format them for the BRS UI contract
        $records = PayrollRecord::with(['user.department', 'user.employeeProfile', 'lastModifiedBy', 'disputes'])
            ->where('payroll_cycle_id', $cycle->id)
            ->get();

        $employeesData = $records->map(function ($r) use ($cycle) {
            $user = $r->user;
            $profile = $user->employeeProfile;
            
            // Format initials
            $names = explode(' ', $user->name);
            $initials = '';
            foreach ($names as $n) {
                $initials .= substr($n, 0, 1);
            }
            $initials = strtoupper(substr($initials, 0, 2));

            // Format daily breakdown snapshot (last 10 days or all)
            $metadata = $r->calculation_metadata ?? [];
            $dailyBreakdown = $metadata['daily_breakdown'] ?? [];
            
            $attendanceSnapshot = [];
            $dayIndex = 1;
            foreach ($dailyBreakdown as $dateStr => $dayData) {
                $dateObj = Carbon::parse($dateStr);
                $status = $dayData['status'];
                
                // Map status to what UI expects
                $uiStatus = 'present';
                if (in_array($status, ['present', 'late'])) {
                    $uiStatus = $status === 'late' ? 'late' : 'present';
                } elseif (in_array($status, ['half', 'hd_upr', 'hd_upa', 'hdp'])) {
                    $uiStatus = 'half';
                } elseif (in_array($status, ['planned', 'upa', 'bday'])) {
                    $uiStatus = 'leave';
                } elseif ($status === 'wfh') {
                    $uiStatus = 'wfh';
                } elseif ($status === 'absent' || $status === 'upr') {
                    $uiStatus = 'absent';
                } elseif ($status === 'off') {
                    $uiStatus = 'off';
                }

                $attendanceSnapshot[] = [
                    'day' => $dayIndex++,
                    'status' => $uiStatus,
                    'date' => $dateObj->format('d M'),
                    'dayOfWeek' => $dateObj->format('D'),
                    'check_in' => $dayData['check_in'] ?? null,
                    'check_out' => $dayData['check_out'] ?? null,
                    'hours_worked' => $dayData['hours_worked'] ?? 0.00,
                    'original_status' => $dayData['original_status'] ?? $status,
                    'is_override' => $dayData['is_override'] ?? false,
                    'deducted_amount' => $dayData['deducted_amount'] ?? 0.00,
                ];
            }
            
            // Fetch leaves list for leaveHistory
            $leaves = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $cycle->end_date->format('Y-m-d 23:59:59'))
                ->where('end_date', '>=', $cycle->start_date->format('Y-m-d 00:00:00'))
                ->get()
                ->map(function ($l) {
                    $start = Carbon::parse($l->start_date);
                    $end = Carbon::parse($l->end_date);
                    $diff = $start->diffInDays($end) + 1;
                    return [
                        'date' => $start->format('d M') . ($diff > 1 ? '–' . $end->format('d M') : ''),
                        'type' => $l->leave_type_label,
                        'days' => $l->is_half_day ? 0.5 : $diff,
                    ];
                })->toArray();

            // Set system explanation text
            $explanation = "Salary computed for {$user->name}. Base: ₹{$r->base_salary}, Allowances: ₹{$r->allowances}. " .
                           "Overtime Pay: ₹{$r->overtime_pay} ({$r->overtime_hours} hrs worked). " .
                           "Total Attendance Deductions: ₹{$r->attendance_deductions}. " .
                           "Net Disbursement: ₹{$r->net_salary}.";

            // Determine correction status
            $correctionStatus = 'none';
            $correctionReason = null;
            if ($r->status === 'correction') {
                $correctionStatus = 'pending';
                $correctionReason = $r->correction_reason;
            } elseif ($r->status === 'approved') {
                $correctionStatus = 'resolved';
            }

            // Calculate dynamic employment category based on joining date and probation days setting
            $joiningDate = $user->joining_date ?? ($profile->joining_date ?? null);
            $probationDays = (int) (\App\Models\PayrollSetting::getValue('lifecycle')['probationDays'] ?? 90);
            $employmentCategory = 'Permanent';
            if ($joiningDate) {
                $daysDiff = $joiningDate->diffInDays($cycle->end_date);
                if ($daysDiff < $probationDays) {
                    $employmentCategory = 'Probation';
                }
            }

            return [
                'record_id' => $r->id,
                'id' => $user->employee_id ?? 'EMP-' . $user->id,
                'name' => $user->name,
                'initials' => $initials,
                'dept' => $user->department->name ?? 'Unassigned',
                'designation' => $profile->designation ?? 'Employee',
                'joiningDate' => $joiningDate ? $joiningDate->format('Y-m-d') : null,
                'employment_category' => $employmentCategory,
                'workingDays' => $r->working_days,
                'present' => (float)$r->present_days,
                'late' => $r->late_days,
                'halfDay' => $r->half_days,
                'paidLeave' => (float)$r->leave_days,
                'unpaidLeave' => (float)$r->unpaid_leave_days,
                'wfh' => $r->wfh_days,
                'overtimeHours' => (float)$r->overtime_hours,
                'bonuses' => (float)$r->bonuses,
                'deductions' => (float)$r->attendance_deductions,
                'gross' => (float)$r->gross_salary,
                'net' => (float)$r->net_salary,
                'status' => $r->status,
                'correctionReason' => $correctionReason,
                'baseSalary' => (float)$r->base_salary,
                'allowances' => (float)$r->allowances,
                'taxAmt' => 0.00,
                'pf' => 0.00,
                'esi' => 0.00,
                'profTax' => 0.00,
                'dailyRate' => (float)($metadata['daily_rate'] ?? 0.00),
                'hourlyRate' => (float)($metadata['hourly_rate'] ?? 0.00),
                'calendarDays' => (int)($metadata['calendar_days'] ?? 30),
                'attendanceDeductions' => (float)$r->attendance_deductions,
                'leaveDeductions' => 0.00,
                'statutoryDeductions' => 0.00,
                'overtimePay' => (float)$r->overtime_pay,
                'correctionStatus' => $correctionStatus,
                'locked' => (bool)$r->locked,
                'locked_at' => $r->locked_at ? $r->locked_at->format('d M, g:i A') : null,
                'lastModified' => $r->last_modified_at ? $r->last_modified_at->format('d M, g:i A') : $r->updated_at->format('d M, g:i A'),
                'modifiedBy' => $r->lastModifiedBy->name ?? 'System',
                'importSource' => $user->payrollProfile->import_source ?? 'Zimyo Import',
                'generatedDate' => $cycle->locked_at ? $cycle->locked_at->format('d M Y') : '—',
                'downloadStatus' => $r->locked ? 'downloaded' : 'pending',
                'emailStatus' => $r->locked ? 'sent' : 'not sent',
                'attendanceSnapshot' => $attendanceSnapshot,
                'leaveHistory' => $leaves,
                'systemExplanation' => $explanation,
                'remarks' => $r->correction_reason,
                'employee_review_status' => $r->employee_review_status ?? 'pending',
                'employee_approved_at' => $r->employee_approved_at ? $r->employee_approved_at->format('d M, g:i A') : null,
                'admin_approved_at' => $r->admin_approved_at ? $r->admin_approved_at->format('d M, g:i A') : null,
                'calculation_version' => $r->calculation_version ?? 1,
                'fingerprint' => $r->fingerprint,
                'payslip_status' => $r->payslip_status ?? 'pending',
                'payslip_generated_at' => $r->payslip_generated_at ? $r->payslip_generated_at->format('d M, g:i A') : null,
                'payslip_published_at' => $r->payslip_published_at ? $r->payslip_published_at->format('d M, g:i A') : null,
                'deductionBreakdown' => PayrollService::getAttendanceDeductionBreakdown($r),
                'disputes' => $r->disputes->map(function($d) {
                    return [
                        'id' => $d->id,
                        'category' => $d->category,
                        'description' => $d->description,
                        'expected_correction' => $d->expected_correction,
                        'status' => $d->status,
                        'affected_date' => $d->affected_date ? $d->affected_date->format('Y-m-d') : null,
                        'resolution_notes' => $d->resolution_notes,
                        'created_at' => $d->created_at->format('d M Y, g:i A'),
                    ];
                })->toArray(),
            ];
        });

        // 3. Load policies and format settingsGroups
        $settingsGroups = [
            [
                'id' => 'general',
                'title' => 'General',
                'desc' => 'Company profile and branding used across payroll documents.',
                'fields' => [
                    ['key' => 'company_name', 'label' => 'Company name', 'hint' => 'Shown on payslips and reports', 'type' => 'text', 'value' => 'Venture Request'],
                    ['key' => 'default_currency', 'label' => 'Default currency', 'hint' => 'Used across all payroll calculations', 'type' => 'text', 'value' => 'INR (₹)'],
                    ['key' => 'fiscal_year_start', 'label' => 'Fiscal year start', 'hint' => 'Month the financial year begins', 'type' => 'text', 'value' => 'April'],
                ]
            ],
            [
                'id' => 'lifecycle',
                'title' => 'Employee Lifecycle Policy',
                'desc' => 'BRS §1 — governs when Probation employees auto-promote to Permanent. The Lifecycle Engine reads only these values.',
                'fields' => [
                    ['key' => 'probationDays', 'label' => 'Probation duration', 'hint' => 'Employment Duration below this = Probation (in days)', 'type' => 'number', 'value' => (int)PayrollSetting::getValue('lifecycle')['probationDays']],
                    ['key' => 'autoPromote', 'label' => 'Auto-promote on expiry', 'hint' => 'AMS changes category automatically — no manual step', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('lifecycle')['autoPromote']],
                    ['key' => 'probationLeaveBalance', 'label' => 'Probation paid-leave balance', 'hint' => 'Leave balance granted during probation', 'type' => 'number', 'value' => (float)PayrollSetting::getValue('lifecycle')['probationLeaveBalance']],
                    ['key' => 'probationPayrollCycle', 'label' => 'Probation payroll cycle', 'hint' => 'Cycle used before day 90', 'type' => 'select', 'options' => [['value' => '20th-to-20th', 'label' => '20th to 20th']], 'value' => PayrollSetting::getValue('lifecycle')['probationPayrollCycle']],
                ]
            ],
            [
                'id' => 'shifts',
                'title' => 'Shift Templates',
                'desc' => 'BRS §2 — shift timings must always come from here. Attendance calculations must never contain hardcoded timings.',
                'fields' => array_map(function ($s) {
                    return [
                        'label' => $s['label'],
                        'hint' => "{$s['type']} · {$s['start']}–{$s['end']} · grace: {$s['graceMinutes']} min",
                        'type' => 'text',
                        'value' => "{$s['start']}–{$s['end']}",
                    ];
                }, PayrollSetting::getValue('shifts'))
            ],
            [
                'id' => 'cycle', // maps to 'payroll' setting key in database
                'title' => 'Payroll Cycle',
                'desc' => 'BRS §12–13 — cycle length and disbursement date, independent of employee category.',
                'fields' => [
                    ['key' => 'permanentCycle', 'label' => 'Permanent employee cycle', 'hint' => 'Calendar month, 1st to last day', 'type' => 'select', 'options' => [['value' => 'calendar-month', 'label' => 'Calendar Month (1st to last day)'], ['value' => '20th-to-20th', 'label' => '20th to 20th']], 'value' => PayrollSetting::getValue('payroll')['permanentCycle']],
                    ['key' => 'probationCycle', 'label' => 'Probation employee cycle', 'hint' => 'Bridges into calendar-month cycle at day 90', 'type' => 'select', 'options' => [['value' => '20th-to-20th', 'label' => '20th to 20th'], ['value' => 'calendar-month', 'label' => 'Calendar Month (1st to last day)']], 'value' => PayrollSetting::getValue('payroll')['probationCycle']],
                    ['key' => 'salaryPaymentDay', 'label' => 'Salary payment date', 'hint' => 'Day of month every employee is paid', 'type' => 'select', 'options' => array_map(fn($d) => ['value' => $d, 'label' => $d . 'th'], range(1, 28)), 'value' => (int)PayrollSetting::getValue('payroll')['salaryPaymentDay']],
                    ['key' => 'autoLock', 'label' => 'Auto-lock on cycle end', 'hint' => 'Locks payroll automatically after payment date', 'type' => 'toggle', 'value' => (bool)(PayrollSetting::getValue('payroll')['autoLock'] ?? false)],
                ]
            ],
            [
                'id' => 'attendance',
                'title' => 'Attendance Policy',
                'desc' => 'BRS §3–5 — governs working days, resolution order, and thresholds.',
                'fields' => [
                    ['key' => 'workingDays', 'label' => 'Working days', 'hint' => 'Standard working days per week', 'type' => 'text', 'value' => implode(', ', PayrollSetting::getValue('workWeek')['workingDays'])],
                    ['key' => 'weeklyOff', 'label' => 'Weekly off', 'hint' => 'Treat as weekly off', 'type' => 'text', 'value' => implode(', ', PayrollSetting::getValue('workWeek')['weeklyOff'])],
                    ['key' => 'minWorkingHoursForPresent', 'label' => 'Minimum hours for Present', 'hint' => 'Below this, day auto-resolves to Half Day', 'type' => 'number', 'value' => (int)PayrollSetting::getValue('attendance')['minWorkingHoursForPresent']],
                    ['key' => 'autoHalfDayOnLateArrival', 'label' => 'Late auto-converts to Half Day', 'hint' => 'Unless manually overridden by an administrator', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('attendance')['autoHalfDayOnLateArrival']],
                ]
            ],
            [
                'id' => 'attendance-order',
                'title' => 'Attendance Resolution Order',
                'desc' => 'BRS §4 — deterministic evaluation order. Reorder here only; the engine must never hardcode this sequence.',
                'isOrderList' => true,
                'orderItems' => PayrollSetting::getValue('attendance')['resolutionOrder'],
                'fields' => []
            ],
            [
                'id' => 'leave',
                'title' => 'Leave Policy',
                'desc' => 'BRS §7–9 — Planned, Unplanned, and Birthday Leave rules.',
                'fields' => [
                    ['key' => 'monthlyCreditAmount', 'label' => 'Monthly leave credit (Permanent)', 'hint' => 'Credited on the 1st of every month', 'type' => 'number', 'value' => (int)PayrollSetting::getValue('leave')['monthlyCreditAmount']],
                    ['key' => 'allowFutureDates', 'label' => 'Unplanned leave — future dates', 'hint' => 'Only Today and Past dates are allowed', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('leave')['unplanned']['allowFutureDates']],
                    ['key' => 'eligibleFromDaysBefore', 'label' => 'Birthday Leave eligibility', 'hint' => 'Days before birthday the credit appears', 'type' => 'number', 'value' => (int)PayrollSetting::getValue('leave')['birthday']['eligibleFromDaysBefore']],
                    ['key' => 'autoApproved', 'label' => 'Birthday Leave auto-approved', 'hint' => 'Never consumes standard balance', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('leave')['birthday']['autoApproved']],
                    ['key' => 'halfDayPlannedConsumes', 'label' => 'Half Day Planned consumption', 'hint' => 'Fraction of Planned Leave balance consumed', 'type' => 'number', 'step' => '0.1', 'value' => (float)PayrollSetting::getValue('leave')['halfDayPlanned']['consumes']],
                    ['key' => 'halfDayUnplannedConsumes', 'label' => 'Half Day Unplanned consumption', 'hint' => 'Fraction of Unplanned Leave balance consumed', 'type' => 'number', 'step' => '0.1', 'value' => (float)PayrollSetting::getValue('leave')['halfDayUnplanned']['consumes']],
                    ['key' => 'rejectOverBalanceImmediately', 'label' => 'Reject over-balance requests immediately', 'hint' => 'Bypass request creation if it exceeds balance', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('leave')['rejectOverBalanceImmediately']],
                    ['key' => 'carryForward', 'label' => 'Paid leave carry-forward', 'hint' => 'Unused paid leave rolls to next cycle', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('leave')['carryForward']],
                ]
            ],
            [
                'id' => 'overtime',
                'title' => 'Overtime Rules',
                'desc' => 'How extra hours convert into overtime pay.',
                'fields' => [
                    ['key' => 'multiplier', 'label' => 'Overtime rate multiplier', 'hint' => 'Applied to hourly rate beyond standard hours', 'type' => 'number', 'step' => '0.1', 'value' => (float)(PayrollSetting::getValue('overtime')['multiplier'] ?? 1.5)],
                    ['key' => 'eligibility', 'label' => 'Overtime eligibility', 'hint' => 'Minimum hours before overtime accrues', 'type' => 'number', 'value' => (int)(PayrollSetting::getValue('overtime')['eligibility'] ?? 8)],
                    ['key' => 'cap', 'label' => 'Cap overtime hours per cycle', 'hint' => 'Maximum OT hours counted per employee', 'type' => 'number', 'value' => (int)(PayrollSetting::getValue('overtime')['cap'] ?? 30)],
                ]
            ],
            [
                'id' => 'payrollmapping',
                'title' => 'Payroll Mapping',
                'desc' => 'BRS §15 — canonical Attendance State → Payroll Effect table. The Payroll Engine looks this table up.',
                'isMappingTable' => true,
                'fields' => []
            ],
            [
                'id' => 'lockrules',
                'title' => 'Lock Rules',
                'desc' => 'Constraints applied when locking a cycle.',
                'fields' => [
                    ['key' => 'excludeUnresolvedFromLock', 'label' => 'Exclude unresolved corrections from lock', 'hint' => 'Flagged employees block lock if unresolved', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('lock')['excludeUnresolvedFromLock']],
                    ['key' => 'requireDualSignoffToUnlock', 'label' => 'Require dual sign-off to unlock', 'hint' => 'Two admins must approve any post-lock unlock', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('lock')['requireDualSignoffToUnlock']],
                ]
            ]
        ];

        // 4. Fetch exceptions flat
        $exceptionsFlat = PayrollException::with('user.department')
            ->where('payroll_cycle_id', $cycle->id)
            ->get()
            ->map(function ($exc) {
                return [
                    'id' => $exc->id,
                    'emp' => $exc->user->name,
                    'dept' => $exc->user->department->name ?? 'Unassigned',
                    'type' => $exc->type,
                    'priority' => $exc->priority,
                    'severity' => $exc->severity,
                    'admin' => 'Rhea Sarin',
                    'date' => $exc->created_at->format('d M Y'),
                    'resolved' => $exc->resolved,
                ];
            });

        // Compute group counts for exceptions on left panel
        $exceptionsGrouped = [
            ['title' => 'Missing Attendance', 'count' => $exceptionsFlat->where('type', 'Missing Attendance')->count(), 'items' => $exceptionsFlat->where('type', 'Missing Attendance')->pluck('emp')->toArray()],
            ['title' => 'Negative Salary', 'count' => $exceptionsFlat->where('type', 'Negative Salary')->count(), 'items' => $exceptionsFlat->where('type', 'Negative Salary')->pluck('emp')->toArray()],
            ['title' => 'Conflicting Attendance', 'count' => $exceptionsFlat->where('type', 'Conflicting Attendance')->count(), 'items' => $exceptionsFlat->where('type', 'Conflicting Attendance')->pluck('emp')->toArray()],
            ['title' => 'Missing Salary Structure', 'count' => $exceptionsFlat->where('type', 'Missing Salary Structure')->count(), 'items' => $exceptionsFlat->where('type', 'Missing Salary Structure')->pluck('emp')->toArray()],
        ];

        // 5. Audit Trail
        $auditTrail = PayrollAuditLog::with(['user', 'actor'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'emp' => $log->user->name ?? null,
                    'actor' => $log->actor->name ?? 'System',
                    'action' => $log->action,
                    'type' => strtolower($log->category),
                    'category' => $log->category,
                    'timestamp' => $log->created_at->format('d M Y, g:i A'),
                    'oldValue' => $log->old_value,
                    'newValue' => $log->new_value,
                ];
            });

        // 6. Reports (CSS-based rendering data)
        $depts = Department::all();
        $deptPayroll = [];
        $deptAttendance = [];
        $deptOT = [];
        $deptCost = [];

        foreach ($depts as $d) {
            $rSubset = $reportRecords->filter(fn($rec) => $rec->user && $rec->user->department_id === $d->id);
            $sumNet = (float) $rSubset->sum('net_salary');
            $sumGross = (float) $rSubset->sum('gross_salary');
            $sumOT = (float) $rSubset->sum('overtime_hours');
            $avgAtt = $rSubset->count() > 0 ? round($rSubset->avg(fn($rec) => ($rec->present_days / ($rec->working_days ?: 26)) * 100)) : 0;
            
            // Employer cost is gross salary
            $employerCost = $sumGross;

            $label = substr($d->name, 0, 4);
            $deptPayroll[] = ['label' => $label, 'value' => $sumNet];
            $deptAttendance[] = ['label' => $label, 'value' => $avgAtt];
            $deptOT[] = ['label' => $label, 'value' => $sumOT];
            $deptCost[] = ['label' => $label, 'value' => round($employerCost)];
        }

        $maxPayroll = count($deptPayroll) > 0 ? max(array_column($deptPayroll, 'value')) : 0;
        if ($maxPayroll <= 0) $maxPayroll = 1000;
        
        $maxOT = count($deptOT) > 0 ? max(array_column($deptOT, 'value')) : 0;
        if ($maxOT <= 0) $maxOT = 10;
        
        $maxCost = count($deptCost) > 0 ? max(array_column($deptCost, 'value')) : 0;
        if ($maxCost <= 0) $maxCost = 1000;

        // Salary bands donut data
        $band30_40 = $reportRecords->filter(fn($rec) => $rec->net_salary >= 30000 && $rec->net_salary < 40000)->count();
        $band40_55 = $reportRecords->filter(fn($rec) => $rec->net_salary >= 40000 && $rec->net_salary < 55000)->count();
        $band55_70 = $reportRecords->filter(fn($rec) => $rec->net_salary >= 55000 && $rec->net_salary < 70000)->count();
        $band70plus = $reportRecords->filter(fn($rec) => $rec->net_salary >= 70000)->count();
        
        $highestEarners = $reportRecords->sortByDesc('net_salary')->take(5)->map(function ($rec) {
            $parts = explode(' ', $rec->user->name);
            $shortName = count($parts) > 1 ? substr($parts[0], 0, 1) . '. ' . $parts[1] : $rec->user->name;
            return ['label' => $shortName, 'value' => (float)$rec->net_salary];
        })->values()->toArray();

        // Dynamic Payroll Trend calculation
        $trendQuery = \App\Models\PayrollRecord::select('payroll_cycle_id', \DB::raw('SUM(net_salary) as total_net'))
            ->groupBy('payroll_cycle_id')
            ->with('payrollCycle')
            ->orderBy('payroll_cycle_id', 'asc')
            ->take(6)
            ->get();
            
        $trendData = [];
        foreach ($trendQuery as $tRow) {
            if ($tRow->payrollCycle) {
                $trendData[] = [
                    'label' => substr($tRow->payrollCycle->period, 0, 3) . ' ' . substr($tRow->payrollCycle->period, -2),
                    'value' => round((float)$tRow->total_net / 100000, 1)
                ];
            }
        }
        $maxTrendValue = count($trendData) > 0 ? max(array_column($trendData, 'value')) : 0;
        if ($maxTrendValue <= 0) $maxTrendValue = 10;

        // Dynamic Leave Deduction Trend calculation
        $leaveTrendQuery = \App\Models\PayrollRecord::select('payroll_cycle_id', \DB::raw('SUM(leave_deductions + attendance_deductions) as total_ded'))
            ->groupBy('payroll_cycle_id')
            ->with('payrollCycle')
            ->orderBy('payroll_cycle_id', 'asc')
            ->take(6)
            ->get();
            
        $leaveTrendData = [];
        foreach ($leaveTrendQuery as $lRow) {
            if ($lRow->payrollCycle) {
                $leaveTrendData[] = [
                    'label' => substr($lRow->payrollCycle->period, 0, 3) . ' ' . substr($lRow->payrollCycle->period, -2),
                    'value' => (float)$lRow->total_ded
                ];
            }
        }
        $maxLeaveTrend = count($leaveTrendData) > 0 ? max(array_column($leaveTrendData, 'value')) : 0;
        if ($maxLeaveTrend <= 0) $maxLeaveTrend = 1000;

        $reports = [
            ['title' => 'Department Payroll', 'desc' => 'Net disbursement by department', 'type' => 'bar', 'max' => $maxPayroll, 'data' => $deptPayroll],
            ['title' => 'Salary Distribution', 'desc' => 'Employees by salary band', 'type' => 'donut', 'data' => [
                ['label' => '₹30–40k', 'value' => $band30_40],
                ['label' => '₹40–55k', 'value' => $band40_55],
                ['label' => '₹55–70k', 'value' => $band55_70],
                ['label' => '₹70k+', 'value' => $band70plus],
            ]],
            ['title' => 'Payroll Trend', 'desc' => 'Net payroll, last 6 cycles (Lakhs)', 'type' => 'line', 'max' => $maxTrendValue, 'data' => $trendData],
            ['title' => 'Leave Deduction Trend', 'desc' => 'Unpaid leave impact by month', 'type' => 'bar', 'max' => $maxLeaveTrend, 'data' => $leaveTrendData],
            ['title' => 'Attendance Impact', 'desc' => 'Average attendance % by department', 'type' => 'bar', 'max' => 100, 'data' => $deptAttendance],
            ['title' => 'Overtime Trend', 'desc' => 'OT hours by department', 'type' => 'bar', 'max' => $maxOT, 'data' => $deptOT],
            ['title' => 'Highest Earners', 'desc' => 'Highest net salary this cycle', 'type' => 'list', 'max' => count($highestEarners) > 0 ? max(array_column($highestEarners, 'value')) : 1000, 'data' => $highestEarners],
            ['title' => 'Department Cost Comparison', 'desc' => 'Total cost incl. employer contributions', 'type' => 'bar', 'max' => $maxCost, 'data' => $deptCost],
        ];

        // 7. Dynamic Workflow Run Pipeline
        $empCount = max($records->count(), 1);
        $attendanceImportedCount = $records->count(); // Assuming all loaded employees have attendance
        
        $openDisputes = \App\Models\PayrollDispute::whereIn('payroll_record_id', $records->pluck('id'))
            ->where('status', 'open')
            ->count();
            
        $staleCount = $records->where('employee_review_status', 'stale')->count();
        $adminAwaiting = $records->whereNull('admin_approved_at')->count();
        $lockedCount = $records->where('locked', true)->count();
        $payslipsGenCount = $records->whereIn('payslip_status', ['generated', 'published'])->count();
        $payslipsPubCount = $records->where('payslip_status', 'published')->count();
        
        $pipeline = [
            [
                'id' => 'attendance_ready',
                'label' => 'Attendance Source Ready',
                'completed' => $attendanceImportedCount,
                'total' => $empCount,
                'pct' => round(($attendanceImportedCount / $empCount) * 100),
                'status' => $attendanceImportedCount === $empCount ? 'done' : 'current',
                'reason' => '',
            ],
            [
                'id' => 'attendance_verified',
                'label' => 'Attendance Verified',
                'completed' => $attendanceImportedCount,
                'total' => $empCount,
                'pct' => round(($attendanceImportedCount / $empCount) * 100),
                'status' => 'done',
                'reason' => '',
            ],
            [
                'id' => 'payroll_calculated',
                'label' => 'Payroll Calculated',
                'completed' => $records->count(),
                'total' => $empCount,
                'pct' => round(($records->count() / $empCount) * 100),
                'status' => $records->count() === $empCount ? 'done' : 'current',
                'reason' => '',
            ],
            [
                'id' => 'published_review',
                'label' => 'Published for Review',
                'completed' => $cycle->status !== 'draft' ? 1 : 0,
                'total' => 1,
                'pct' => $cycle->status !== 'draft' ? 100 : 0,
                'status' => $cycle->status !== 'draft' ? 'done' : 'current',
                'reason' => $cycle->status === 'draft' ? 'Draft cycle' : '',
            ],
            [
                'id' => 'employee_review',
                'label' => 'Employee Review/Approval',
                'completed' => $records->where('employee_review_status', 'approved')->count(),
                'total' => $empCount,
                'pct' => round(($records->where('employee_review_status', 'approved')->count() / $empCount) * 100),
                'status' => $openDisputes > 0 ? 'blocked' : ($records->where('employee_review_status', 'approved')->count() === $empCount ? 'done' : 'current'),
                'reason' => $openDisputes > 0 ? "{$openDisputes} active Payroll disputes" : ($staleCount > 0 ? "{$staleCount} approvals stale after recalculation" : ""),
            ],
            [
                'id' => 'admin_review',
                'label' => 'Admin Review/Approval',
                'completed' => $records->whereNotNull('admin_approved_at')->count(),
                'total' => $empCount,
                'pct' => round(($records->whereNotNull('admin_approved_at')->count() / $empCount) * 100),
                'status' => $adminAwaiting > 0 ? 'current' : 'done',
                'reason' => $adminAwaiting > 0 ? "{$adminAwaiting} records awaiting admin approval" : '',
            ],
            [
                'id' => 'payroll_locked',
                'label' => 'Employee Payroll Locked',
                'completed' => $lockedCount,
                'total' => $empCount,
                'pct' => round(($lockedCount / $empCount) * 100),
                'status' => $lockedCount === $empCount ? 'done' : 'current',
                'reason' => ($empCount - $lockedCount) > 0 ? ($empCount - $lockedCount) . " open records unlocked" : '',
            ],
            [
                'id' => 'payslips_generated',
                'label' => 'Payslips Generated',
                'completed' => $payslipsGenCount,
                'total' => $empCount,
                'pct' => round(($payslipsGenCount / $empCount) * 100),
                'status' => $payslipsGenCount === $empCount ? 'done' : 'current',
                'reason' => ($lockedCount - $payslipsGenCount) > 0 ? ($lockedCount - $payslipsGenCount) . " locked records missing generated payslips" : '',
            ],
            [
                'id' => 'payslips_published',
                'label' => 'Payslips Published',
                'completed' => $payslipsPubCount,
                'total' => $empCount,
                'pct' => round(($payslipsPubCount / $empCount) * 100),
                'status' => $payslipsPubCount === $empCount ? 'done' : 'current',
                'reason' => ($empCount - $payslipsPubCount) > 0 ? ($empCount - $payslipsPubCount) . " payslips pending publication" : '',
            ],
            [
                'id' => 'cycle_completed',
                'label' => 'Cycle Completed',
                'completed' => ($lockedCount === $empCount && $payslipsPubCount === $empCount) ? 1 : 0,
                'total' => 1,
                'pct' => ($lockedCount === $empCount && $payslipsPubCount === $empCount) ? 100 : 0,
                'status' => ($lockedCount === $empCount && $payslipsPubCount === $empCount) ? 'done' : 'upcoming',
                'reason' => '',
            ],
        ];

        // 8. General KPIs for Dashboard
        $grossSum = $records->sum('gross_salary');
        $netSum = $records->sum('net_salary');
        $dedSum = $records->sum(fn($rec) => $rec->gross_salary - $rec->net_salary);
        $avgNet = $records->count() > 0 ? round($records->avg('net_salary')) : 0;
        $avgAttGlobal = $records->count() > 0 ? round($records->avg(fn($rec) => ($rec->present_days / ($rec->working_days ?: 26)) * 100)) : 90;
        $avgOTGlobal = $records->count() > 0 ? round($records->avg('overtime_hours'), 1) : 0;

        $kpis = [
            ['label' => 'Total Employees', 'value' => (string)$records->count(), 'sub' => 'Active this cycle'],
            ['label' => 'Approved', 'value' => (string)$records->where('employee_review_status', 'approved')->count(), 'sub' => 'Signed off by employee', 'tone' => 'forest'],
            ['label' => 'Pending Review', 'value' => (string)$records->where('employee_review_status', 'pending')->count(), 'sub' => 'Awaiting employee', 'tone' => 'oxblood'],
            ['label' => 'Locked', 'value' => (string)$records->where('status', 'locked')->count(), 'sub' => 'Finalized records'],
            ['label' => 'Exceptions', 'value' => (string)$exceptionsFlat->where('resolved', false)->count(), 'sub' => 'Flagged records', 'tone' => 'oxblood'],
            ['label' => 'Payroll Completion', 'value' => ($records->count() > 0 ? round(($records->whereIn('status', ['approved', 'locked'])->count() / $records->count()) * 100) : 0) . '%', 'sub' => 'Of cycle processed'],
            ['label' => 'Gross Payroll', 'value' => '₹' . round($grossSum / 100000, 1) . 'L', 'sub' => 'Before deductions'],
            ['label' => 'Net Payroll', 'value' => '₹' . round($netSum / 100000, 1) . 'L', 'sub' => 'After all deductions', 'tone' => 'forest'],
            ['label' => 'Deductions', 'value' => '₹' . round($dedSum / 100000, 1) . 'L', 'sub' => 'Attendance Deductions', 'tone' => 'oxblood'],
            ['label' => 'Average Salary', 'value' => '₹' . number_format($avgNet), 'sub' => 'Net, per employee'],
            ['label' => 'Average Attendance', 'value' => $avgAttGlobal . '%', 'sub' => 'Across all departments'],
            ['label' => 'Average Overtime', 'value' => $avgOTGlobal . 'h', 'sub' => 'Per employee this cycle'],
        ];

        $lockReadiness = PayrollService::checkLockReadiness($cycle);

        return view('admin.payroll.index', [
            'cycle' => $cycle,
            'period' => $period,
            'employeesData' => $employeesData,
            'settingsGroups' => $settingsGroups,
            'exceptionsFlat' => $exceptionsFlat,
            'exceptionsGrouped' => $exceptionsGrouped,
            'auditTrail' => $auditTrail,
            'reports' => $reports,
            'kpis' => $kpis,
            'pipeline' => $pipeline,
            'lockReadiness' => $lockReadiness,
            'allPeriods' => PayrollCycle::orderBy('start_date', 'desc')->pluck('period')->toArray() ?: ['June 2026'],
            'cycleInstances' => $cycleInstances,
            'activeTab' => $activeTab,
            'reportFilter' => $reportFilter,
            'reportCycle' => $reportCycle,
            'reportStartDate' => $reportStartDate,
            'reportEndDate' => $reportEndDate,
            'resolvedRangeLabel' => $resolvedRangeLabel,
        ]);
    }

    /**
     * Recalculate payroll for the cycle period.
     */
    public function process(Request $request)
    {
        $period = $request->input('period', 'June 2026');
        $cycle = PayrollCycle::where('period', $period)->first();

        if ($cycle && $cycle->status === 'locked') {
            return back()->with('error', "Cannot recalculate payroll cycle {$period}. The cycle is locked and immutable.");
        }

        $actor = auth()->user();
        PayrollService::processCycle($period, $actor);

        return redirect()->route('admin.payroll.index', ['period' => $period])
            ->with('success', 'Payroll re-calculated successfully.');
    }

    /**
     * Lock the payroll cycle.
     */
    public function lock(Request $request)
    {
        $period = $request->input('period', 'June 2026');
        $cycle = PayrollCycle::where('period', $period)->firstOrFail();
        $actor = auth()->user();

        $result = PayrollService::lockCycle($cycle, $actor);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Unlock the payroll cycle.
     */
    public function unlock(Request $request)
    {
        $period = $request->input('period', 'June 2026');
        $reason = $request->input('reason', 'Administrative unlock request');
        $cycle = PayrollCycle::where('period', $period)->firstOrFail();
        $actor = auth()->user();

        PayrollService::unlockCycle($cycle, $reason, $actor);

        return back()->with('success', "Payroll cycle {$period} unlocked.");
    }

    /**
     * Submit a manual correction / adjustment.
     */
    public function correctionStore(Request $request)
    {
        $request->validate([
            'record_id' => 'required|exists:payroll_records,id',
            'new_net_salary' => 'required|numeric|min:0',
            'reason' => 'required|string|min:5',
        ]);

        $record = PayrollRecord::findOrFail($request->input('record_id'));
        $newNet = (float)$request->input('new_net_salary');
        $reason = $request->input('reason');
        $actor = auth()->user();

        $correction = PayrollService::submitCorrection($record, $newNet, $reason, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Correction submitted successfully.',
            'correction' => $correction,
        ]);
    }

    /**
     * Approve manual correction.
     */
    public function correctionApprove(Request $request, $id)
    {
        $correction = PayrollCorrection::where('id', $id)
            ->orWhere(function($q) use ($id) {
                $q->where('payroll_record_id', $id)->where('approval_status', 'pending');
            })
            ->firstOrFail();
            
        $actor = auth()->user();

        PayrollService::approveCorrection($correction, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Correction approved successfully.',
        ]);
    }

    /**
     * Update settings keys.
     */
    public function settingsUpdate(Request $request)
    {
        $group = $request->input('group');
        $fields = $request->input('fields', []);

        $policy = PayrollSetting::getValue($group, []);

        foreach ($fields as $key => $val) {
            // Check box states, etc.
            if ($val === 'true') $val = true;
            if ($val === 'false') $val = false;
            
            $policy[$key] = $val;
        }

        PayrollSetting::setValue($group, $policy);

        PayrollAuditLog::record(
            null,
            auth()->id(),
            "Updated policy configurations for group: {$group}",
            "Settings",
            null,
            json_encode($policy)
        );

        return back()->with('success', "Policy parameters updated for {$group}.");
    }

    /**
     * Export the Salary Disbursement Ledger to Excel.
     */
    public function exportLedger(Request $request)
    {
        $period = $request->get('period', 'June 2026');
        $cycle = PayrollCycle::where('period', $period)->firstOrFail();

        $records = PayrollRecord::with(['user.employeeProfile', 'user.department'])
            ->where('payroll_cycle_id', $cycle->id)
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // Sheet 1: Salary Ledger Summary
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Salary Ledger');
        
        $headers1 = [
            'Employee ID', 'Name', 'Department', 'Designation', 'Bank Name', 
            'Account Holder Name', 'Account Number', 'IFSC Code', 'Base Salary', 
            'Gross Salary', 'Total Deductions', 'Net Disbursement'
        ];
        
        $sheet1->fromArray($headers1, NULL, 'A1');
        
        $rowIdx = 2;
        foreach ($records as $r) {
            $user = $r->user;
            $p = $user->employeeProfile;
            $deductions = ($r->attendance_deductions + $r->leave_deductions + $r->statutory_deductions + $r->tax_deductions);
            
            $sheet1->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $p->designation ?? 'Employee',
                $p->bank_name ?? '—',
                $p->account_holder_name ?? $user->name,
                $p->account_no ? '*******' . substr($p->account_no, -4) : '—',
                $p->ifsc_code ?? '—',
                (float)$r->base_salary,
                (float)$r->gross_salary,
                (float)$deductions,
                (float)$r->net_salary
            ], NULL, 'A' . $rowIdx);
            $rowIdx++;
        }
        
        // Sheet 2: Attendance & Deductions Breakdown
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Attendance & Details');
        
        $headers2 = [
            'Employee ID', 'Name', 'Eligible Days', 'Present Days', 'Absent Days', 
            'Leave Days', 'Overtime Hours', 'Overtime Pay', 'Half Days', 'Unpaid Leave Days', 'Late Penalties', 'Override Adjustments', 'Manual Adjustments', 'Total Deductions'
        ];
        $sheet2->fromArray($headers2, NULL, 'A1');
        
        $rowIdx = 2;
        foreach ($records as $r) {
            $user = $r->user;
            $breakdown = PayrollService::getAttendanceDeductionBreakdown($r);
            $sheet2->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                (int)$r->working_days,
                (float)$r->present_days,
                (float)$r->absent_days,
                (float)$r->leave_days,
                (float)$r->overtime_hours,
                (float)$r->overtime_pay,
                (float)$breakdown['half_days']['amount'],
                (float)$breakdown['unpaid_leaves']['amount'],
                (float)$breakdown['late_penalties']['amount'],
                (float)$breakdown['override_adjustments']['amount'],
                (float)$breakdown['manual_adjustments']['amount'],
                (float)$r->attendance_deductions
            ], NULL, 'A' . $rowIdx);
            $rowIdx++;
        }
        
        $fileName = "Salary_Ledger_{$period}.xlsx";
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Admin approves an employee's payroll record.
     */
    public function recordApprove(Request $request, $id)
    {
        $record = PayrollRecord::findOrFail($id);
        $actor = auth()->user();

        if ($record->locked) {
            return response()->json(['success' => false, 'message' => 'Cannot approve a locked payroll record.']);
        }

        $record->update([
            'admin_approved_at' => now(),
            'admin_approved_by_id' => $actor->id,
            'status' => 'approved',
        ]);

        PayrollAuditLog::record(
            $record->user_id,
            $actor->id,
            "Admin approved employee payroll statement version {$record->calculation_version}",
            "Payroll Correction"
        );

        // Try automatic lock
        $locked = PayrollService::lockRecord($record, $actor);

        return response()->json([
            'success' => true,
            'message' => $locked ? 'Record approved and locked successfully!' : 'Record approved successfully!',
            'locked' => $locked,
        ]);
    }

    /**
     * Admin locks an employee's payroll record.
     */
    public function recordLock(Request $request, $id)
    {
        $record = PayrollRecord::findOrFail($id);
        $actor = auth()->user();

        if ($record->locked) {
            return response()->json(['success' => true, 'message' => 'Record is already locked.']);
        }

        $success = PayrollService::lockRecord($record, $actor);

        if (!$success) {
            $disputesOpen = \App\Models\PayrollDispute::where('payroll_record_id', $record->id)->where('status', 'open')->exists();
            $msg = 'Record cannot be locked. Ensure both employee and admin approvals are signed off and no active disputes exist.';
            if ($disputesOpen) {
                $msg = 'Record cannot be locked. There is an active unresolved dispute raised by the employee.';
            }
            return response()->json(['success' => false, 'message' => $msg]);
        }

        return response()->json(['success' => true, 'message' => 'Record locked successfully and snapshot created.']);
    }

    /**
     * Admin unlocks an employee's payroll record.
     */
    public function recordUnlock(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $record = PayrollRecord::findOrFail($id);
        $actor = auth()->user();

        if ($actor->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor. Only admins can unlock payroll records.'], 403);
        }

        if (!$record->locked) {
            return response()->json(['success' => true, 'message' => 'Record is already unlocked.']);
        }

        PayrollService::unlockRecord($record, $request->input('reason'), $actor);

        return response()->json(['success' => true, 'message' => 'Record unlocked successfully.']);
    }

    /**
     * Admin reopens an employee's payroll record.
     */
    public function recordReopen(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $record = PayrollRecord::findOrFail($id);
        $actor = auth()->user();

        if ($actor->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor. Only admins can reopen payroll records.'], 403);
        }

        if (!$record->locked) {
            return response()->json(['success' => false, 'message' => 'Record is not locked.']);
        }

        PayrollService::reopenRecord($record, $request->input('reason'), $actor);

        return response()->json(['success' => true, 'message' => 'Record reopened and recalculated successfully.']);
    }

    /**
     * Resolve dispute.
     */
    public function disputeResolve(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:resolved,rejected',
            'notes' => 'required|string|min:5',
        ]);

        $dispute = PayrollDispute::findOrFail($id);
        $actor = auth()->user();

        $dispute->update([
            'status' => $request->input('status'),
            'resolved_at' => now(),
            'resolved_by_id' => $actor->id,
            'resolution_notes' => $request->input('notes'),
        ]);

        $record = $dispute->payrollRecord;
        if ($record && $record->employee_review_status === 'disputed') {
            // Revert review status to pending to allow employee to re-approve
            $record->update([
                'employee_review_status' => 'pending',
            ]);
        }

        PayrollAuditLog::record(
            $dispute->user_id,
            $actor->id,
            "Resolved employee dispute (Status: {$request->input('status')}). Notes: {$request->input('notes')}",
            "Payroll Correction"
        );

        return response()->json(['success' => true, 'message' => 'Dispute resolved successfully.']);
    }

    /**
     * Generate payslip.
     */
    public function payslipGenerate(Request $request, $id)
    {
        $record = PayrollRecord::findOrFail($id);
        if (!$record->locked) {
            return response()->json(['success' => false, 'message' => 'Cannot generate payslip for an unlocked record.']);
        }

        $record->update([
            'payslip_status' => 'generated',
            'payslip_generated_at' => now(),
        ]);

        PayrollAuditLog::record(
            $record->user_id,
            auth()->id(),
            "Generated payslip for {$record->user->name}",
            "Locks"
        );

        return response()->json(['success' => true, 'message' => 'Payslip generated successfully.']);
    }

    /**
     * Publish payslip.
     */
    public function payslipPublish(Request $request, $id)
    {
        $record = PayrollRecord::findOrFail($id);
        if (!$record->locked) {
            return response()->json(['success' => false, 'message' => 'Cannot publish payslip for an unlocked record.']);
        }

        $record->update([
            'payslip_status' => 'published',
            'payslip_published_at' => now(),
        ]);

        PayrollAuditLog::record(
            $record->user_id,
            auth()->id(),
            "Published payslip for {$record->user->name}",
            "Locks"
        );

        return response()->json(['success' => true, 'message' => 'Payslip published successfully.']);
    }

    /**
     * Bulk generate payslips.
     */
    public function payslipBulkGenerate(Request $request)
    {
        $period = $request->input('period', 'June 2026');
        $cycle = PayrollCycle::where('period', $period)->firstOrFail();

        $records = PayrollRecord::where('payroll_cycle_id', $cycle->id)
            ->where('locked', true)
            ->get();

        foreach ($records as $r) {
            $r->update([
                'payslip_status' => 'generated',
                'payslip_generated_at' => now(),
            ]);
        }

        PayrollAuditLog::record(
            null,
            auth()->id(),
            "Bulk generated payslips for cycle period: {$period}",
            "Locks"
        );

        return response()->json(['success' => true, 'message' => 'Payslips generated in bulk successfully.']);
    }

    /**
     * Bulk publish payslips.
     */
    public function payslipBulkPublish(Request $request)
    {
        $period = $request->input('period', 'June 2026');
        $cycle = PayrollCycle::where('period', $period)->firstOrFail();

        $records = PayrollRecord::where('payroll_cycle_id', $cycle->id)
            ->where('locked', true)
            ->get();

        foreach ($records as $r) {
            $r->update([
                'payslip_status' => 'published',
                'payslip_published_at' => now(),
            ]);
        }

        PayrollAuditLog::record(
            null,
            auth()->id(),
            "Bulk published payslips for cycle period: {$period}",
            "Locks"
        );

        return response()->json(['success' => true, 'message' => 'Payslips published in bulk successfully.']);
    }

    /**
     * Preview policy update impact.
     */
    public function settingsPreview(Request $request)
    {
        $group = $request->input('group');
        $groupKey = $group === 'cycle' ? 'payroll' : $group;
        $fields = $request->input('fields', []);
        $period = $request->input('period', 'June 2026');

        $carbonPeriod = Carbon::parse($period);
        $year = $carbonPeriod->year;
        $month = $carbonPeriod->month;

        $cycle = PayrollCycle::where('period', $period)->first();
        if (!$cycle) {
            return response()->json(['success' => false, 'message' => 'Active cycle does not exist for preview.']);
        }

        $records = PayrollRecord::where('payroll_cycle_id', $cycle->id)
            ->where('locked', false)
            ->get();

        $affectedCount = 0;
        $grossDelta = 0.00;
        $deductionsDelta = 0.00;
        $netDelta = 0.00;
        $staleApprovalsCount = 0;

        $oldSettings = PayrollSetting::getValue($groupKey, []);
        $mockSettings = $oldSettings;
        foreach ($fields as $k => $v) {
            if ($v === 'true') $v = true;
            if ($v === 'false') $v = false;
            if (is_numeric($v)) {
                if (str_contains($v, '.')) {
                    $v = (float)$v;
                } else {
                    $v = (int)$v;
                }
            }

            if ($groupKey === 'leave') {
                if ($k === 'allowFutureDates') {
                    $mockSettings['unplanned']['allowFutureDates'] = $v;
                } elseif ($k === 'eligibleFromDaysBefore') {
                    $mockSettings['birthday']['eligibleFromDaysBefore'] = $v;
                } elseif ($k === 'autoApproved') {
                    $mockSettings['birthday']['autoApproved'] = $v;
                } elseif ($k === 'halfDayPlannedConsumes') {
                    $mockSettings['halfDayPlanned']['consumes'] = $v;
                } elseif ($k === 'halfDayUnplannedConsumes') {
                    $mockSettings['halfDayUnplanned']['consumes'] = $v;
                } else {
                    $mockSettings[$k] = $v;
                }
            } else {
                $mockSettings[$k] = $v;
            }
        }

        PayrollSetting::setValue($groupKey, $mockSettings);

        try {
            foreach ($records as $r) {
                $calc = PayrollService::calculateMonthlyPayroll($r->user, $year, $month);
                
                $oldGross = (float)$r->gross_salary;
                $oldNet = (float)$r->net_salary;
                $oldDeductions = (float)($r->attendance_deductions + $r->leave_deductions + $r->statutory_deductions + $r->tax_deductions);

                $newGross = (float)$calc['gross_salary'];
                $newNet = (float)$calc['net_salary'];
                $newDeductions = (float)($calc['attendance_deductions'] + $calc['leave_deductions'] + $calc['statutory_deductions'] + $calc['tax_deductions']);

                if (abs($newGross - $oldGross) > 0.01 || abs($newNet - $oldNet) > 0.01) {
                    $affectedCount++;
                    $grossDelta += ($newGross - $oldGross);
                    $deductionsDelta += ($newDeductions - $oldDeductions);
                    $netDelta += ($newNet - $oldNet);

                    if ($r->employee_review_status === 'approved' || !is_null($r->admin_approved_at)) {
                        $staleApprovalsCount++;
                    }
                }
            }
        } finally {
            PayrollSetting::setValue($groupKey, $oldSettings);
        }

        return response()->json([
            'success' => true,
            'affected_records' => $affectedCount,
            'gross_delta' => round($grossDelta, 2),
            'deductions_delta' => round($deductionsDelta, 2),
            'net_delta' => round($netDelta, 2),
            'stale_approvals' => $staleApprovalsCount,
        ]);
    }

    /**
     * Save settings and force recalculation of affected unlocked records.
     */
    public function settingsSaveRecalculate(Request $request)
    {
        $group = $request->input('group');
        $groupKey = $group === 'cycle' ? 'payroll' : $group;
        $fields = $request->input('fields', []);
        $period = $request->input('period', 'June 2026');

        $policy = PayrollSetting::getValue($groupKey, []);
        foreach ($fields as $key => $val) {
            if ($val === 'true') $val = true;
            if ($val === 'false') $val = false;
            if (is_numeric($val)) {
                if (str_contains($val, '.')) {
                    $val = (float)$val;
                } else {
                    $val = (int)$val;
                }
            }

            if ($groupKey === 'leave') {
                if ($key === 'allowFutureDates') {
                    $policy['unplanned']['allowFutureDates'] = $val;
                } elseif ($key === 'eligibleFromDaysBefore') {
                    $policy['birthday']['eligibleFromDaysBefore'] = $val;
                } elseif ($key === 'autoApproved') {
                    $policy['birthday']['autoApproved'] = $val;
                } elseif ($key === 'halfDayPlannedConsumes') {
                    $policy['halfDayPlanned']['consumes'] = $val;
                } elseif ($key === 'halfDayUnplannedConsumes') {
                    $policy['halfDayUnplanned']['consumes'] = $val;
                } else {
                    $policy[$key] = $val;
                }
            } else {
                $policy[$key] = $val;
            }
        }

        PayrollSetting::setValue($groupKey, $policy);

        PayrollAuditLog::record(
            null,
            auth()->id(),
            "Saved & Recalculated config policy parameters for {$groupKey}.",
            "Settings",
            null,
            json_encode($policy)
        );

        PayrollService::processCycle($period, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'Settings saved and payroll cycle recalculated successfully.',
        ]);
    }

    /**
     * Resolve the report period selection and date range.
     */
    private function resolveReportRange(Request $request, PayrollCycle $cycle)
    {
        $filter = $request->get('report_filter', 'current_cycle');
        $startDate = null;
        $endDate = null;
        $label = '';

        switch ($filter) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today()->endOfDay();
                $label = $startDate->format('d M Y');
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday()->endOfDay();
                $label = $startDate->format('d M Y');
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek()->endOfDay();
                $label = $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y');
                break;
            case 'prev_week':
                $startDate = Carbon::now()->subWeek()->startOfWeek();
                $endDate = Carbon::now()->subWeek()->endOfWeek()->endOfDay();
                $label = $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y');
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth()->endOfDay();
                $label = $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y');
                break;
            case 'prev_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth()->endOfDay();
                $label = $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y');
                break;
            case 'prev_cycle':
                $prevPeriod = Carbon::parse($cycle->period)->subMonth()->format('F Y');
                $prevCycle = PayrollCycle::where('period', $prevPeriod)->first();
                if (!$prevCycle) {
                    $startDate = Carbon::parse($cycle->period)->subMonth()->startOfMonth();
                    $endDate = Carbon::parse($cycle->period)->subMonth()->endOfMonth()->endOfDay();
                } else {
                    $startDate = $prevCycle->start_date;
                    $endDate = $prevCycle->end_date->endOfDay();
                }
                $label = "Previous Cycle: " . ($prevCycle ? $prevCycle->period : $prevPeriod) . " (" . $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y') . ")";
                break;
            case 'specific_cycle':
                $specPeriod = $request->get('report_cycle', $cycle->period);
                $specCycle = PayrollCycle::where('period', $specPeriod)->first();
                if (!$specCycle) {
                    $startDate = Carbon::parse($specPeriod)->startOfMonth();
                    $endDate = Carbon::parse($specPeriod)->endOfMonth()->endOfDay();
                } else {
                    $startDate = $specCycle->start_date;
                    $endDate = $specCycle->end_date->endOfDay();
                }
                $label = "Payroll Cycle: " . $specPeriod . " (" . $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y') . ")";
                break;
            case 'custom':
                $startInput = $request->get('start_date');
                $endInput = $request->get('end_date');
                
                if ($startInput && $endInput) {
                    $parsedStart = Carbon::parse($startInput)->startOfDay();
                    $parsedEnd = Carbon::parse($endInput)->endOfDay();
                    if ($parsedStart->lte($parsedEnd)) {
                        $startDate = $parsedStart;
                        $endDate = $parsedEnd;
                    } else {
                        $startDate = $cycle->start_date;
                        $endDate = $cycle->end_date->endOfDay();
                    }
                } else {
                    $startDate = $cycle->start_date;
                    $endDate = $cycle->end_date->endOfDay();
                }
                $label = $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y');
                break;
            case 'current_cycle':
            default:
                $startDate = $cycle->start_date;
                $endDate = $cycle->end_date->endOfDay();
                $label = "Payroll Cycle: " . $cycle->period . " (" . $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y') . ")";
                break;
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
            'label' => $label,
            'filter' => $filter,
        ];
    }

    /**
     * Export selected payroll/attendance report to formatted Excel workbook.
     */
    public function exportReport(Request $request)
    {
        $category = $request->input('category', 'payroll_summary');
        
        $period = $request->input('report_cycle', 'June 2026');
        $cycle = PayrollCycle::where('period', $period)->first();
        if (!$cycle) {
            $cycle = PayrollCycle::orderBy('id', 'desc')->first();
        }
        
        $range = $this->resolveReportRange($request, $cycle);
        
        return \App\Services\PayrollExportService::export(
            $category,
            $range['start'],
            $range['end'],
            $range['label'],
            $cycle
        );
    }

    /**
     * Preview the next payroll cycle before opening it.
     */
    public function previewNextCycle(Request $request)
    {
        $latestCycle = PayrollCycle::orderBy('start_date', 'desc')->first();
        if (!$latestCycle) {
            $nextPeriodDate = Carbon::create(2026, 6, 1);
        } else {
            $nextPeriodDate = Carbon::parse($latestCycle->period)->addMonth();
        }
        $nextPeriod = $nextPeriodDate->format('F Y');

        $year = $nextPeriodDate->year;
        $month = $nextPeriodDate->month;

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->startOfDay();
        $paymentDay = (int) (\App\Models\PayrollSetting::getValue('payroll')['salaryPaymentDay'] ?? 7);
        $paymentDate = $endDate->copy()->addMonth()->setDay($paymentDay)->startOfDay();

        // Eligible employees
        $eligibleUsers = \App\Services\PayrollEligibilityService::getEligibleEmployees($year, $month);
        
        $newlyEntering = [];
        $excluded = [];

        $allPayrollUsers = User::where('role', '!=', 'admin')
            ->whereHas('payrollProfile', function ($query) {
                $query->where('payroll_enabled', true);
            })
            ->get();

        foreach ($allPayrollUsers as $user) {
            $isEligible = $eligibleUsers->contains('id', $user->id);
            if ($isEligible) {
                $hadPrevious = false;
                if ($latestCycle) {
                    $hadPrevious = PayrollRecord::where('payroll_cycle_id', $latestCycle->id)
                        ->where('user_id', $user->id)
                        ->exists();
                }
                if (!$hadPrevious) {
                    $newlyEntering[] = [
                        'name' => $user->name,
                        'employee_id' => $user->employee_id ?? 'EMP-' . $user->id,
                        'joining_date' => $user->joining_date ? Carbon::parse($user->joining_date)->format('d M Y') : '—',
                    ];
                }
            } else {
                $reason = 'Excluded by payroll policy';
                $joiningDate = $user->joining_date;
                if ($joiningDate) {
                    $joiningDate = Carbon::parse($joiningDate)->startOfDay();
                    if ($joiningDate->gt($endDate)) {
                        $reason = 'Joined after cycle ended (' . $joiningDate->format('d M Y') . ')';
                    }
                }
                
                $profile = $user->employeeProfile;
                if ($profile) {
                    $separationDate = $profile->separation_date ?? $profile->last_working_day;
                    if ($separationDate) {
                        $separationDate = Carbon::parse($separationDate)->startOfDay();
                        if ($separationDate->lt($startDate)) {
                            $reason = 'Separated before cycle started (' . $separationDate->format('d M Y') . ')';
                        }
                    }
                }

                if (!$user->payrollProfile || !$user->payrollProfile->payroll_enabled) {
                    $reason = 'Payroll disabled';
                }

                $excluded[] = [
                    'name' => $user->name,
                    'employee_id' => $user->employee_id ?? 'EMP-' . $user->id,
                    'reason' => $reason,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'period' => $nextPeriod,
            'start_date' => $startDate->format('d M Y'),
            'end_date' => $endDate->format('d M Y'),
            'payment_date' => $paymentDate->format('d M Y'),
            'eligible_count' => $eligibleUsers->count(),
            'newly_entering' => $newlyEntering,
            'excluded' => $excluded,
        ]);
    }

    /**
     * Create / Open the next payroll cycle.
     */
    public function createNextCycle(Request $request)
    {
        $actor = auth()->user();
        
        $nextPeriod = $request->input('period');
        if (!$nextPeriod) {
            $latestCycle = PayrollCycle::orderBy('start_date', 'desc')->first();
            if (!$latestCycle) {
                $nextPeriodDate = Carbon::create(2026, 6, 1);
            } else {
                $nextPeriodDate = Carbon::parse($latestCycle->period)->addMonth();
            }
            $nextPeriod = $nextPeriodDate->format('F Y');
        }

        $cycle = PayrollCycle::where('period', $nextPeriod)->first();
        if ($cycle) {
            return response()->json([
                'success' => true,
                'message' => "Payroll cycle {$nextPeriod} already exists.",
                'period' => $nextPeriod,
            ]);
        }

        DB::transaction(function () use ($nextPeriod, $actor) {
            PayrollService::processCycle($nextPeriod, $actor);
        });

        PayrollAuditLog::record(
            null,
            $actor->id,
            "Created/Opened new payroll cycle instance: {$nextPeriod}",
            "Cycle"
        );

        return response()->json([
            'success' => true,
            'message' => "Payroll cycle {$nextPeriod} created and employee records generated successfully.",
            'period' => $nextPeriod,
        ]);
    }
}
