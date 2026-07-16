<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PayrollRecord;
use App\Models\PayrollCycle;
use App\Models\PayrollDispute;
use App\Models\PayrollAuditLog;
use App\Models\LeaveRequest;
use App\Services\PayrollService;
use App\Services\PayrollCycleResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Dompdf\Dompdf;
use Dompdf\Options;

class EmployeePayrollController extends Controller
{
    /**
     * Display the employee's own payroll dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', PayrollCycle::orderBy('start_date', 'desc')->value('period') ?: 'June 2026');

        $cycle = PayrollCycle::where('period', $period)->first();
        if (!$cycle) {
            // Dry run calculation if cycle record doesn't exist yet for this period
            $carbonPeriod = Carbon::parse($period);
            $resolver = new PayrollCycleResolver();
            $cycleInfo = $resolver->resolve($user, $carbonPeriod->year, $carbonPeriod->month);
            
            if ($cycleInfo) {
                $calc = PayrollService::calculateMonthlyPayroll($user, $carbonPeriod->year, $carbonPeriod->month);
                $record = new PayrollRecord([
                    'user_id' => $user->id,
                    'base_salary' => $calc['base_salary'],
                    'gross_salary' => $calc['gross_salary'],
                    'net_salary' => $calc['net_salary'],
                    'attendance_deductions' => $calc['attendance_deductions'],
                    'leave_deductions' => $calc['leave_deductions'],
                    'statutory_deductions' => $calc['statutory_deductions'],
                    'tax_deductions' => $calc['tax_deductions'],
                    'overtime_hours' => $calc['overtime_hours'],
                    'overtime_pay' => $calc['overtime_pay'],
                    'bonuses' => $calc['bonuses'],
                    'allowances' => $calc['allowances'],
                    'working_days' => $calc['working_days'],
                    'present_days' => $calc['present_days'],
                    'absent_days' => $calc['absent_days'],
                    'leave_days' => $calc['leave_days'],
                    'unpaid_leave_days' => $calc['unpaid_leave_days'],
                    'birthday_leave_days' => $calc['birthday_leave_days'],
                    'half_days' => $calc['half_days'],
                    'late_days' => $calc['late_days'],
                    'wfh_days' => $calc['wfh_days'],
                    'employee_review_status' => 'pending',
                    'calculation_version' => 1,
                    'calculation_metadata' => [
                        'pf' => $calc['pf'],
                        'esi' => $calc['esi'],
                        'prof_tax' => $calc['prof_tax'],
                        'cycle_type' => $calc['cycle_type'],
                        'daily_breakdown' => $calc['daily_breakdown'],
                        'daily_rate' => $calc['daily_rate'],
                        'hourly_rate' => $calc['hourly_rate'],
                        'calendar_days' => $calc['calendar_days'],
                    ],
                ]);
            } else {
                $record = null;
            }
        } else {
            $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)
                ->where('user_id', $user->id)
                ->first();

            // If it doesn't exist but the cycle exists, we can process/calculate it
            if (!$record && $user->payrollProfile && $user->payrollProfile->payroll_enabled) {
                $carbonPeriod = Carbon::parse($period);
                $calc = PayrollService::calculateMonthlyPayroll($user, $carbonPeriod->year, $carbonPeriod->month);
                
                $record = PayrollRecord::create([
                    'payroll_cycle_id' => $cycle->id,
                    'user_id' => $user->id,
                    'base_salary' => $calc['base_salary'],
                    'gross_salary' => $calc['gross_salary'],
                    'net_salary' => $calc['net_salary'],
                    'attendance_deductions' => $calc['attendance_deductions'],
                    'leave_deductions' => $calc['leave_deductions'],
                    'statutory_deductions' => $calc['statutory_deductions'],
                    'tax_deductions' => $calc['tax_deductions'],
                    'overtime_hours' => $calc['overtime_hours'],
                    'overtime_pay' => $calc['overtime_pay'],
                    'bonuses' => $calc['bonuses'],
                    'allowances' => $calc['allowances'],
                    'working_days' => $calc['working_days'],
                    'present_days' => $calc['present_days'],
                    'absent_days' => $calc['absent_days'],
                    'leave_days' => $calc['leave_days'],
                    'unpaid_leave_days' => $calc['unpaid_leave_days'],
                    'birthday_leave_days' => $calc['birthday_leave_days'],
                    'half_days' => $calc['half_days'],
                    'late_days' => $calc['late_days'],
                    'wfh_days' => $calc['wfh_days'],
                    'employee_review_status' => 'pending',
                    'calculation_version' => 1,
                    'calculation_metadata' => [
                        'pf' => $calc['pf'],
                        'esi' => $calc['esi'],
                        'prof_tax' => $calc['prof_tax'],
                        'cycle_type' => $calc['cycle_type'],
                        'daily_breakdown' => $calc['daily_breakdown'],
                        'daily_rate' => $calc['daily_rate'],
                        'hourly_rate' => $calc['hourly_rate'],
                        'calendar_days' => $calc['calendar_days'],
                    ],
                ]);
            }
        }

        $allPeriods = PayrollCycle::orderBy('start_date', 'desc')->pluck('period')->toArray() ?: ['June 2026'];
        
        $activeDisputes = $record ? $record->disputes()->orderBy('created_at', 'desc')->get() : collect();

        // Format employee data for review view matching BRS details
        $formattedRecord = null;
        if ($record) {
            $metadata = $record->calculation_metadata ?? [];
            $dailyBreakdown = $metadata['daily_breakdown'] ?? [];
            
            $attendanceSnapshot = [];
            $dayIndex = 1;
            foreach ($dailyBreakdown as $dateStr => $dayData) {
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

            // Leaves
            $leaves = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', ($cycle ? $cycle->end_date->format('Y-m-d 23:59:59') : now()->endOfMonth()->format('Y-m-d 23:59:59')))
                ->where('start_date', '>=', ($cycle ? $cycle->start_date->format('Y-m-d 00:00:00') : now()->startOfMonth()->format('Y-m-d 00:00:00')))
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

            $pf = $metadata['pf'] ?? 0.00;
            $esi = $metadata['esi'] ?? 0.00;
            $pt = $metadata['prof_tax'] ?? 0.00;
            $explanation = "Salary computed for {$user->name}. Base: ₹{$record->base_salary}, Allowances: ₹{$record->allowances}. " .
                           "Overtime Pay: ₹{$record->overtime_pay} ({$record->overtime_hours} hrs worked). " .
                           "Deductions: Attendance Deduction: ₹{$record->attendance_deductions}. " .
                           "Net Disbursement: ₹{$record->net_salary}.";

            $formattedRecord = [
                'record_id' => $record->id,
                'id' => $user->employee_id ?? 'EMP-' . $user->id,
                'name' => $user->name,
                'dept' => $user->department->name ?? 'Unassigned',
                'designation' => $user->employeeProfile->designation ?? 'Employee',
                'workingDays' => $record->working_days,
                'present' => (float)$record->present_days,
                'late' => $record->late_days,
                'halfDay' => $record->half_days,
                'paidLeave' => (float)$record->leave_days,
                'unpaidLeave' => (float)$record->unpaid_leave_days,
                'wfh' => $record->wfh_days,
                'overtimeHours' => (float)$record->overtime_hours,
                'bonuses' => (float)$record->bonuses,
                'deductions' => (float)$record->attendance_deductions,
                'gross' => (float)$record->gross_salary,
                'net' => (float)$record->net_salary,
                'status' => $record->status,
                'employee_review_status' => $record->employee_review_status,
                'employee_approved_at' => $record->employee_approved_at ? $record->employee_approved_at->format('d M, g:i A') : null,
                'admin_approved_at' => $record->admin_approved_at ? $record->admin_approved_at->format('d M, g:i A') : null,
                'locked' => $record->locked,
                'baseSalary' => (float)$record->base_salary,
                'dailyRate' => (float)($metadata['daily_rate'] ?? round($record->base_salary / 30, 2)),
                'hourlyRate' => (float)($metadata['hourly_rate'] ?? round($record->base_salary / 240, 2)),
                'calendarDays' => (int)($metadata['calendar_days'] ?? 30),
                'allowances' => (float)$record->allowances,
                'taxAmt' => (float)$record->tax_deductions,
                'pf' => (float)$pf,
                'esi' => (float)$esi,
                'profTax' => (float)$pt,
                'calculation_version' => $record->calculation_version,
                'attendanceSnapshot' => $attendanceSnapshot,
                'leaveHistory' => $leaves,
                'systemExplanation' => $explanation,
                'payslip_status' => $record->payslip_status,
                'attendanceDeductions' => (float)$record->attendance_deductions,
                'leaveDeductions' => (float)$record->leave_deductions,
                'birthdayLeave' => (float)$record->birthday_leave_days,
                'overtimePay' => (float)$record->overtime_pay,
            ];
        }

        return view('employee.payroll.index', [
            'user' => $user,
            'period' => $period,
            'record' => $formattedRecord,
            'disputes' => $activeDisputes,
            'allPeriods' => $allPeriods,
        ]);
    }

    /**
     * Self-service approve payroll.
     */
    public function approve(Request $request)
    {
        $request->validate([
            'record_id' => 'required|exists:payroll_records,id',
        ]);

        $record = PayrollRecord::findOrFail($request->input('record_id'));
        if ($record->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized access.');
        }

        if ($record->locked) {
            return back()->with('error', 'Cannot approve. Payroll is locked.');
        }

        $record->update([
            'employee_review_status' => 'approved',
            'employee_approved_at' => now(),
        ]);

        PayrollAuditLog::record(
            $record->user_id,
            Auth::id(),
            "Employee approved calculation version {$record->calculation_version}",
            "Payroll Correction",
            null,
            null,
            "Employee self-service approval"
        );

        return back()->with('success', 'Payroll record approved successfully.');
    }

    /**
     * Self-service dispute payroll.
     */
    public function dispute(Request $request)
    {
        $request->validate([
            'record_id' => 'required|exists:payroll_records,id',
            'category' => 'required|in:Attendance,Leave,Salary,Deduction,Other',
            'description' => 'required|string|min:5',
            'expected_correction' => 'required|string|min:5',
            'affected_date' => 'nullable|date',
        ]);

        $record = PayrollRecord::findOrFail($request->input('record_id'));
        if ($record->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized access.');
        }

        if ($record->locked) {
            return back()->with('error', 'Cannot dispute. Payroll is locked.');
        }

        PayrollDispute::create([
            'payroll_record_id' => $record->id,
            'user_id' => Auth::id(),
            'category' => $request->input('category'),
            'affected_date' => $request->input('affected_date'),
            'description' => $request->input('description'),
            'expected_correction' => $request->input('expected_correction'),
            'status' => 'open',
        ]);

        $record->update([
            'employee_review_status' => 'disputed',
        ]);

        PayrollAuditLog::record(
            $record->user_id,
            Auth::id(),
            "Employee disputed calculation version {$record->calculation_version} (Category: {$request->input('category')})",
            "Payroll Correction",
            null,
            null,
            $request->input('description')
        );

        return back()->with('success', 'Dispute raised successfully. HR/Admin will review it.');
    }

    /**
     * Download payslip PDF.
     */
    public function downloadPayslip($id)
    {
        $record = PayrollRecord::with(['user.department', 'user.employeeProfile', 'payrollCycle'])->findOrFail($id);
        
        // Authorization check: Employee can only see their own payslips
        if (Auth::user()->role !== 'admin' && $record->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Must be locked or published
        if (!$record->locked) {
            abort(400, 'Payslip is not available yet. Payroll cycle is not locked.');
        }

        if (Auth::user()->role !== 'admin' && $record->payslip_status !== 'published') {
            abort(403, 'Payslip is not published yet.');
        }

        $user = $record->user;
        $profile = $user->employeeProfile;
        $cycle = $record->payrollCycle;
        $metadata = $record->calculation_metadata ?? [];

        // Convert net salary to words using robust fallback-guarded Indian numbering format
        $netInWords = \App\Services\NumberToWordsFormatter::convert($record->net_salary);

        $html = view('employee.payroll.payslip_pdf', [
            'record' => $record,
            'user' => $user,
            'profile' => $profile,
            'cycle' => $cycle,
            'metadata' => $metadata,
            'netInWords' => $netInWords,
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultPaperSize', 'A4');
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->render();

        $record->update([
            'payslip_status' => 'published', // ensure published if admin downloads it
        ]);

        return $dompdf->stream("Payslip_{$user->name}_{$cycle->period}.pdf", [
            "Attachment" => true
        ]);
    }
}
