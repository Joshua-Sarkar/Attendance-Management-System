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
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class PayrollController extends Controller
{
    /**
     * Display the Payroll Control Center.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'June 2026');
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

        // 2. Fetch records and format them for the BRS UI contract
        $records = PayrollRecord::with(['user.department', 'user.employeeProfile', 'lastModifiedBy'])
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
                // Map status to what UI expects: present, absent, half, leave, wfh, off, late
                $uiStatus = 'present';
                $status = $dayData['status'];
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
                    'date' => Carbon::parse($dateStr)->format('d M'),
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
            $pf = $metadata['pf'] ?? 0.00;
            $esi = $metadata['esi'] ?? 0.00;
            $pt = $metadata['prof_tax'] ?? 0.00;
            
            $explanation = "Salary computed for {$user->name}. Base: ₹{$r->base_salary}, Allowances: ₹{$r->allowances}. " .
                           "Overtime Pay: ₹{$r->overtime_pay} ({$r->overtime_hours} hrs worked). " .
                           "Deductions applied: Attendance & Unpaid Leave Deductions: ₹" . ($r->attendance_deductions + $r->leave_deductions) . 
                           ", PF: ₹{$pf}, ESI: ₹{$esi}, PT: ₹{$pt}, TDS Tax: ₹{$r->tax_deductions}. " .
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

            return [
                'record_id' => $r->id,
                'id' => $user->employee_id ?? 'EMP-' . $user->id,
                'name' => $user->name,
                'initials' => $initials,
                'dept' => $user->department->name ?? 'Unassigned',
                'designation' => $profile->designation ?? 'Employee',
                'workingDays' => $r->working_days,
                'present' => (float)$r->present_days,
                'late' => $r->late_days,
                'halfDay' => $r->half_days,
                'paidLeave' => (float)$r->leave_days,
                'unpaidLeave' => (float)$r->unpaid_leave_days,
                'wfh' => $r->wfh_days,
                'overtimeHours' => (float)$r->overtime_hours,
                'bonuses' => (float)$r->bonuses,
                'deductions' => (float)($r->attendance_deductions + $r->leave_deductions + $r->statutory_deductions + $r->tax_deductions),
                'gross' => (float)$r->gross_salary,
                'net' => (float)$r->net_salary,
                'status' => $r->status,
                'correctionReason' => $correctionReason,
                'baseSalary' => (float)$r->base_salary,
                'allowances' => (float)$r->allowances,
                'taxAmt' => (float)$r->tax_deductions,
                'pf' => (float)$pf,
                'esi' => (float)$esi,
                'profTax' => (float)$pt,
                'correctionStatus' => $correctionStatus,
                'locked' => (bool)$r->locked,
                'lastModified' => $r->last_modified_at ? $r->last_modified_at->format('d M, g:i A') : $r->updated_at->format('d M, g:i A'),
                'modifiedBy' => $r->lastModifiedBy->name ?? 'System',
                'importSource' => $user->payrollProfile->import_source ?? 'Zimyo Import',
                'generatedDate' => $cycle->locked_at ? $cycle->locked_at->format('d M Y') : '—',
                'downloadStatus' => $r->locked ? 'downloaded' : 'pending',
                'emailStatus' => $r->locked ? 'sent' : 'not sent',
                'attendanceSnapshot' => array_slice($attendanceSnapshot, -10), // last 10 days
                'leaveHistory' => $leaves,
                'systemExplanation' => $explanation,
                'remarks' => $r->correction_reason,
            ];
        });

        // 3. Load policies and format settingsGroups
        $settingsGroups = [
            [
                'id' => 'general',
                'title' => 'General',
                'desc' => 'Company profile and branding used across payroll documents.',
                'fields' => [
                    ['label' => 'Company name', 'hint' => 'Shown on payslips and reports', 'type' => 'text', 'value' => 'Venture Request'],
                    ['label' => 'Default currency', 'hint' => 'Used across all payroll calculations', 'type' => 'text', 'value' => 'INR (₹)'],
                    ['label' => 'Fiscal year start', 'hint' => 'Month the financial year begins', 'type' => 'text', 'value' => 'April'],
                ]
            ],
            [
                'id' => 'lifecycle',
                'title' => 'Employee Lifecycle Policy',
                'desc' => 'BRS §1 — governs when Probation employees auto-promote to Permanent. The Lifecycle Engine reads only these values.',
                'fields' => [
                    ['label' => 'Probation duration', 'hint' => 'Employment Duration below this = Probation', 'type' => 'text', 'value' => PayrollSetting::getValue('lifecycle')['probationDays'] . ' days'],
                    ['label' => 'Auto-promote on expiry', 'hint' => 'AMS changes category automatically — no manual step', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('lifecycle')['autoPromote']],
                    ['label' => 'Probation paid-leave balance', 'hint' => 'Leave balance granted during probation', 'type' => 'text', 'value' => (string)PayrollSetting::getValue('lifecycle')['probationLeaveBalance']],
                    ['label' => 'Probation payroll cycle', 'hint' => 'Cycle used before day 90', 'type' => 'text', 'value' => PayrollSetting::getValue('lifecycle')['probationPayrollCycle']],
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
                'id' => 'cycle',
                'title' => 'Payroll Cycle',
                'desc' => 'BRS §12–13 — cycle length and disbursement date, independent of employee category.',
                'fields' => [
                    ['label' => 'Permanent employee cycle', 'hint' => 'Calendar month, 1st to last day', 'type' => 'text', 'value' => PayrollSetting::getValue('payroll')['permanentCycle']],
                    ['label' => 'Probation employee cycle', 'hint' => 'Bridges into calendar-month cycle at day 90', 'type' => 'text', 'value' => PayrollSetting::getValue('payroll')['probationCycle']],
                    ['label' => 'Salary payment date', 'hint' => 'Day of month every employee is paid', 'type' => 'text', 'value' => PayrollSetting::getValue('payroll')['salaryPaymentDay'] . 'th'],
                    ['label' => 'Auto-lock on cycle end', 'hint' => 'Locks payroll automatically after payment date', 'type' => 'toggle', 'value' => false],
                ]
            ],
            [
                'id' => 'attendance',
                'title' => 'Attendance Policy',
                'desc' => 'BRS §3–5 — governs working days, resolution order, and thresholds.',
                'fields' => [
                    ['label' => 'Working days', 'hint' => 'Standard working days per week', 'type' => 'text', 'value' => implode(', ', PayrollSetting::getValue('workWeek')['workingDays'])],
                    ['label' => 'Weekly off', 'hint' => 'Treat as weekly off', 'type' => 'text', 'value' => implode(', ', PayrollSetting::getValue('workWeek')['weeklyOff'])],
                    ['label' => 'Minimum hours for Present', 'hint' => 'Below this, day auto-resolves to Half Day', 'type' => 'text', 'value' => PayrollSetting::getValue('attendance')['minWorkingHoursForPresent'] . 'h'],
                    ['label' => 'Late auto-converts to Half Day', 'hint' => 'Unless manually overridden by an administrator', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('attendance')['autoHalfDayOnLateArrival']],
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
                    ['label' => 'Monthly leave credit (Permanent)', 'hint' => 'Credited on the 1st of every month', 'type' => 'text', 'value' => '+' . PayrollSetting::getValue('leave')['monthlyCreditAmount'] . ' leaves'],
                    ['label' => 'Unplanned leave — future dates', 'hint' => 'Only Today and Past dates are allowed', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('leave')['unplanned']['allowFutureDates']],
                    ['label' => 'Birthday Leave eligibility', 'hint' => 'Days before birthday the credit appears', 'type' => 'text', 'value' => PayrollSetting::getValue('leave')['birthday']['eligibleFromDaysBefore'] . ' day before'],
                    ['label' => 'Birthday Leave auto-approved', 'hint' => 'Never consumes standard balance', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('leave')['birthday']['autoApproved']],
                    ['label' => 'Half Day Planned consumption', 'hint' => 'Fraction of Planned Leave balance consumed', 'type' => 'text', 'value' => (string)PayrollSetting::getValue('leave')['halfDayPlanned']['consumes']],
                    ['label' => 'Half Day Unplanned consumption', 'hint' => 'Fraction of Unplanned Leave balance consumed', 'type' => 'text', 'value' => (string)PayrollSetting::getValue('leave')['halfDayUnplanned']['consumes']],
                    ['label' => 'Reject over-balance requests immediately', 'hint' => 'Bypass request creation if it exceeds balance', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('leave')['rejectOverBalanceImmediately']],
                    ['label' => 'Paid leave carry-forward', 'hint' => 'Unused paid leave rolls to next cycle', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('leave')['carryForward']],
                ]
            ],
            [
                'id' => 'overtime',
                'title' => 'Overtime Rules',
                'desc' => 'How extra hours convert into overtime pay.',
                'fields' => [
                    ['label' => 'Overtime rate multiplier', 'hint' => 'Applied to hourly rate beyond standard hours', 'type' => 'text', 'value' => PayrollSetting::getValue('overtime')['multiplier'] ?? '1.5x'],
                    ['label' => 'Overtime eligibility', 'hint' => 'Minimum hours before overtime accrues', 'type' => 'text', 'value' => PayrollSetting::getValue('overtime')['eligibility'] ?? '8h/day'],
                    ['label' => 'Cap overtime hours per cycle', 'hint' => 'Maximum OT hours counted per employee', 'type' => 'text', 'value' => PayrollSetting::getValue('overtime')['cap'] ?? '30h'],
                ]
            ],
            [
                'id' => 'pf',
                'title' => 'Provident Fund (PF)',
                'desc' => 'Employer and employee PF contribution settings.',
                'fields' => [
                    ['label' => 'Employee PF rate', 'hint' => 'Percentage of basic salary', 'type' => 'text', 'value' => PayrollSetting::getValue('pf')['employee_rate'] . '%'],
                    ['label' => 'PF applicable above wage ceiling', 'hint' => 'Apply PF even above statutory ceiling (15,000)', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('pf')['applicable_above_wage_ceiling']],
                ]
            ],
            [
                'id' => 'esi',
                'title' => 'ESI',
                'desc' => 'Employee State Insurance contribution settings.',
                'fields' => [
                    ['label' => 'ESI eligibility ceiling', 'hint' => 'Gross salary below which ESI applies', 'type' => 'text', 'value' => '₹' . PayrollSetting::getValue('esi')['eligibility_ceiling']],
                    ['label' => 'Employee ESI rate', 'hint' => 'Percentage of gross salary', 'type' => 'text', 'value' => PayrollSetting::getValue('esi')['employee_rate'] . '%'],
                ]
            ],
            [
                'id' => 'ptax',
                'title' => 'Professional Tax',
                'desc' => 'State-mandated professional tax slabs.',
                'fields' => [
                    ['label' => 'State', 'hint' => 'Determines applicable slab', 'type' => 'text', 'value' => PayrollSetting::getValue('ptax')['state'] ?? 'Uttarakhand'],
                    ['label' => 'Monthly professional tax', 'hint' => 'Flat deduction per employee', 'type' => 'text', 'value' => '₹' . PayrollSetting::getValue('ptax')['monthly_professional_tax']],
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
                    ['label' => 'Exclude unresolved corrections from lock', 'hint' => 'Flagged employees block lock if unresolved', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('lock')['excludeUnresolvedFromLock']],
                    ['label' => 'Require dual sign-off to unlock', 'hint' => 'Two admins must approve any post-lock unlock', 'type' => 'toggle', 'value' => (bool)PayrollSetting::getValue('lock')['requireDualSignoffToUnlock']],
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
        // Eng, Design, Marketing, Ops, Sales
        $depts = Department::all();
        $deptPayroll = [];
        $deptAttendance = [];
        $deptOT = [];
        $deptCost = [];

        foreach ($depts as $d) {
            $rSubset = $records->filter(fn($rec) => $rec->user->department_id === $d->id);
            $sumNet = (float) $rSubset->sum('net_salary');
            $sumGross = (float) $rSubset->sum('gross_salary');
            $sumOT = (float) $rSubset->sum('overtime_hours');
            $avgAtt = $rSubset->count() > 0 ? round($rSubset->avg(fn($rec) => ($rec->present_days / ($rec->working_days ?: 26)) * 100)) : 90;
            
            // Employer cost: gross + 12% PF matching
            $employerCost = $sumGross + ($rSubset->sum('base_salary') * 0.12);

            $label = substr($d->name, 0, 4);
            $deptPayroll[] = ['label' => $label, 'value' => $sumNet];
            $deptAttendance[] = ['label' => $label, 'value' => $avgAtt];
            $deptOT[] = ['label' => $label, 'value' => $sumOT];
            $deptCost[] = ['label' => $label, 'value' => round($employerCost)];
        }

        // Add defaults if department tables are empty
        if (empty($deptPayroll)) {
            $deptPayroll = [['label' => 'Eng', 'value' => 620000], ['label' => 'Design', 'value' => 310000], ['label' => 'Mktg', 'value' => 275000]];
            $deptAttendance = [['label' => 'Eng', 'value' => 91], ['label' => 'Design', 'value' => 94], ['label' => 'Mktg', 'value' => 86]];
            $deptOT = [['label' => 'Eng', 'value' => 38], ['label' => 'Design', 'value' => 14], ['label' => 'Mktg', 'value' => 6]];
            $deptCost = [['label' => 'Eng', 'value' => 668000], ['label' => 'Design', 'value' => 334000], ['label' => 'Mktg', 'value' => 296000]];
        }

        $maxPayroll = count($deptPayroll) > 0 ? max(array_column($deptPayroll, 'value')) : 650000;
        $maxOT = count($deptOT) > 0 ? max(array_column($deptOT, 'value')) : 60;
        $maxCost = count($deptCost) > 0 ? max(array_column($deptCost, 'value')) : 700000;

        // Salary bands donut data
        $band30_40 = $records->filter(fn($rec) => $rec->net_salary >= 30000 && $rec->net_salary < 40000)->count();
        $band40_55 = $records->filter(fn($rec) => $rec->net_salary >= 40000 && $rec->net_salary < 55000)->count();
        $band55_70 = $records->filter(fn($rec) => $rec->net_salary >= 55000 && $rec->net_salary < 70000)->count();
        $band70plus = $records->filter(fn($rec) => $rec->net_salary >= 70000)->count();
        
        $highestEarners = $records->sortByDesc('net_salary')->take(5)->map(function ($rec) {
            $parts = explode(' ', $rec->user->name);
            $shortName = count($parts) > 1 ? substr($parts[0], 0, 1) . '. ' . $parts[1] : $rec->user->name;
            return ['label' => $shortName, 'value' => (float)$rec->net_salary];
        })->values()->toArray();

        $reports = [
            ['title' => 'Department Payroll', 'desc' => 'Net disbursement by department', 'type' => 'bar', 'max' => $maxPayroll, 'data' => $deptPayroll],
            ['title' => 'Salary Distribution', 'desc' => 'Employees by salary band', 'type' => 'donut', 'data' => [
                ['label' => '₹30–40k', 'value' => $band30_40 ?: 2],
                ['label' => '₹40–55k', 'value' => $band40_55 ?: 4],
                ['label' => '₹55–70k', 'value' => $band55_70 ?: 3],
                ['label' => '₹70k+', 'value' => $band70plus ?: 1],
            ]],
            ['title' => 'Payroll Trend', 'desc' => 'Net payroll, last 6 cycles', 'type' => 'line', 'max' => 20, 'data' => [
                ['label' => 'Jan', 'value' => 15.2], ['label' => 'Feb', 'value' => 16.1], ['label' => 'Mar', 'value' => 15.8],
                ['label' => 'Apr', 'value' => 17.4], ['label' => 'May', 'value' => 17.9], ['label' => 'Jun', 'value' => round((float)$records->sum('net_salary') / 100000, 1) ?: 18.4]
            ]],
            ['title' => 'Leave Deduction Trend', 'desc' => 'Unpaid leave impact by month', 'type' => 'bar', 'max' => 45000, 'data' => [
                ['label' => 'Jan', 'value' => 22000], ['label' => 'Feb', 'value' => 18000], ['label' => 'Mar', 'value' => 31000],
                ['label' => 'Apr', 'value' => 25000], ['label' => 'May', 'value' => 29000], ['label' => 'Jun', 'value' => (float)$records->sum('attendance_deductions') ?: 38500]
            ]],
            ['title' => 'Attendance Impact', 'desc' => 'Average attendance % by department', 'type' => 'bar', 'max' => 100, 'data' => $deptAttendance],
            ['title' => 'Overtime Trend', 'desc' => 'OT hours by department', 'type' => 'bar', 'max' => $maxOT ?: 60, 'data' => $deptOT],
            ['title' => 'Highest Earners', 'desc' => 'Highest net salary this cycle', 'type' => 'list', 'max' => count($highestEarners) > 0 ? max(array_column($highestEarners, 'value')) : 75000, 'data' => $highestEarners],
            ['title' => 'Department Cost Comparison', 'desc' => 'Total cost incl. employer contributions', 'type' => 'bar', 'max' => $maxCost, 'data' => $deptCost],
        ];

        // 7. General KPIs for Dashboard
        $grossSum = $records->sum('gross_salary');
        $netSum = $records->sum('net_salary');
        $dedSum = $records->sum(fn($rec) => $rec->gross_salary - $rec->net_salary);
        $avgNet = $records->count() > 0 ? round($records->avg('net_salary')) : 0;
        $avgAttGlobal = $records->count() > 0 ? round($records->avg(fn($rec) => ($rec->present_days / ($rec->working_days ?: 26)) * 100)) : 90;
        $avgOTGlobal = $records->count() > 0 ? round($records->avg('overtime_hours'), 1) : 0;

        $kpis = [
            ['label' => 'Total Employees', 'value' => (string)$records->count(), 'sub' => 'Active this cycle'],
            ['label' => 'Approved', 'value' => (string)$records->where('status', 'approved')->count(), 'sub' => 'Signed off', 'tone' => 'forest'],
            ['label' => 'Pending Review', 'value' => (string)$records->where('status', 'pending')->count(), 'sub' => 'Awaiting approval', 'tone' => 'oxblood'],
            ['label' => 'Locked', 'value' => (string)$records->where('status', 'locked')->count(), 'sub' => 'Finalized records'],
            ['label' => 'Exceptions', 'value' => (string)$exceptionsFlat->where('resolved', false)->count(), 'sub' => 'Flagged records', 'tone' => 'oxblood'],
            ['label' => 'Payroll Completion', 'value' => ($records->count() > 0 ? round(($records->whereIn('status', ['approved', 'locked'])->count() / $records->count()) * 100) : 0) . '%', 'sub' => 'Of cycle processed'],
            ['label' => 'Gross Payroll', 'value' => '₹' . round($grossSum / 100000, 1) . 'L', 'sub' => 'Before deductions'],
            ['label' => 'Net Payroll', 'value' => '₹' . round($netSum / 100000, 1) . 'L', 'sub' => 'After all deductions', 'tone' => 'forest'],
            ['label' => 'Deductions', 'value' => '₹' . round($dedSum / 100000, 1) . 'L', 'sub' => 'Tax, PF, ESI', 'tone' => 'oxblood'],
            ['label' => 'Average Salary', 'value' => '₹' . number_format($avgNet), 'sub' => 'Net, per employee'],
            ['label' => 'Average Attendance', 'value' => $avgAttGlobal . '%', 'sub' => 'Across all departments'],
            ['label' => 'Average Overtime', 'value' => $avgOTGlobal . 'h', 'sub' => 'Per employee this cycle'],
        ];

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
            'allPeriods' => ['June 2026', 'July 2026', 'August 2026', 'September 2026'],
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Recalculate payroll for the cycle period.
     */
    public function process(Request $request)
    {
        $period = $request->input('period', 'June 2026');
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

        $success = PayrollService::lockCycle($cycle, $actor);

        if (!$success) {
            return back()->with('error', 'Cannot lock payroll. There are unresolved critical exceptions.');
        }

        return back()->with('success', "Payroll cycle {$period} locked successfully.");
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
     * Export the Salary Disbursement Ledger to CSV.
     */
    public function exportLedger(Request $request)
    {
        $period = $request->get('period', 'June 2026');
        $cycle = PayrollCycle::where('period', $period)->firstOrFail();

        $records = PayrollRecord::with(['user.employeeProfile', 'user.department'])
            ->where('payroll_cycle_id', $cycle->id)
            ->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Salary_Ledger_{$period}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Employee ID', 'Name', 'Department', 'Designation', 'Bank Name', 
            'Account Holder Name', 'Account Number', 'IFSC Code', 'Base Salary', 
            'Gross Salary', 'Total Deductions', 'Net Disbursement'
        ];

        $callback = function() use($records, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($records as $r) {
                $user = $r->user;
                $p = $user->employeeProfile;
                
                fputcsv($file, [
                    $user->employee_id ?? 'EMP-' . $user->id,
                    $user->name,
                    $user->department->name ?? 'Unassigned',
                    $p->designation ?? 'Employee',
                    $p->bank_name ?? '—',
                    $p->account_holder_name ?? $user->name,
                    $p->account_no ? '*******' . substr($p->account_no, -4) : '—', // Mask bank account for privacy
                    $p->ifsc_code ?? '—',
                    $r->base_salary,
                    $r->gross_salary,
                    ($r->attendance_deductions + $r->leave_deductions + $r->statutory_deductions + $r->tax_deductions),
                    $r->net_salary
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
