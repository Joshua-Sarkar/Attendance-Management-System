<?php

namespace App\Services;

use App\Models\User;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\PayrollRecord;
use App\Models\PayrollCycle;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PayrollExportService
{
    /**
     * Generate and download Excel report.
     */
    public static function export(string $category, Carbon $startDate, Carbon $endDate, string $rangeLabel, PayrollCycle $cycle)
    {
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        // Resolve affected cycles
        $cycleIds = PayrollCycle::where(function ($q) use ($startDateStr, $endDateStr) {
            $q->whereBetween('start_date', [$startDateStr, $endDateStr])
              ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
              ->orWhere(function ($q2) use ($startDateStr, $endDateStr) {
                  $q2->where('start_date', '<=', $startDateStr)
                     ->where('end_date', '>=', $endDateStr);
              });
        })->pluck('id');

        $records = PayrollRecord::with(['user.department', 'user.employeeProfile', 'payrollCycle'])
            ->whereIn('payroll_cycle_id', $cycleIds)
            ->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Remove default sheet

        switch ($category) {
            case 'payroll_summary':
                self::generatePayrollSummary($spreadsheet, $records, $rangeLabel);
                break;
            case 'attendance_export':
                self::generateAttendanceExport($spreadsheet, $startDate, $endDate, $rangeLabel);
                break;
            case 'monthly_attendance':
                self::generateMonthlyAttendance($spreadsheet, $startDate, $endDate, $rangeLabel);
                break;
            case 'leave_report':
                self::generateLeaveReport($spreadsheet, $startDate, $endDate, $rangeLabel);
                break;
            case 'deduction_report':
                self::generateDeductionReport($spreadsheet, $records, $rangeLabel);
                break;
            case 'salary_report':
                self::generateSalaryReport($spreadsheet, $records, $rangeLabel);
                break;
            case 'payroll_reconciliation':
                self::generatePayrollReconciliation($spreadsheet, $records, $rangeLabel);
                break;
            case 'employee_payroll_detail':
                self::generateEmployeePayrollDetail($spreadsheet, $records, $rangeLabel);
                break;
            case 'department_payroll':
                self::generateDepartmentPayroll($spreadsheet, $records, $rangeLabel);
                break;
            case 'overtime_report':
                throw new \InvalidArgumentException("Overtime Report is disabled (overtime is not supported).");
            case 'disbursement_register':
                self::generateDisbursementRegister($spreadsheet, $records, $rangeLabel);
                break;
            default:
                throw new \InvalidArgumentException("Invalid export category: {$category}");
        }

        $fileName = "Payroll_" . ucfirst(str_replace('_', ' ', $category)) . "_{$startDateStr}_to_{$endDateStr}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($fileName) . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // Styles & Helpers
    private static function applyTitleBlock($sheet, string $title, string $rangeLabel)
    {
        $sheet->setCellValue('A1', 'VENTURE REQUEST — ATTENDANCE & PAYROLL SYSTEM');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('C6941C'));
        
        $sheet->setCellValue('A2', strtoupper($title));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('4F5B46'));
        
        $sheet->setCellValue('A3', "Resolved Range: {$rangeLabel}");
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
        
        $sheet->setCellValue('A4', "Generated At: " . now()->format('d M Y, g:i A'));
        $sheet->getStyle('A4')->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('7F7F7F'));
        
        $sheet->insertNewRowBefore(5, 2); // add 2 blank rows
    }

    private static function applyHeaderStyles($sheet, string $range)
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('4F5B46');
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getRowDimension(substr($range, 1, 1))->setRowHeight(25);
    }

    private static function applyTableBorders($sheet, int $startRow, int $endRow, string $maxCol)
    {
        $range = 'A' . $startRow . ':' . $maxCol . $endRow;
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'D9D9D9'],
                ],
            ],
        ];
        $sheet->getStyle($range)->applyFromArray($styleArray);
    }

    private static function autofit($sheet)
    {
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    // 1. Payroll Summary
    private static function generatePayrollSummary($spreadsheet, $records, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Payroll Summary');
        self::applyTitleBlock($sheet, 'Payroll Summary Report', $rangeLabel);

        $headers = ['Employee ID', 'Employee Name', 'Department', 'Designation', 'Base Salary', 'Attendance Deductions', 'Net Salary', 'Lock Status'];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:H7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $p = $user->employeeProfile;
            $deductions = $r->attendance_deductions;

            $sheet->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $p->designation ?? 'Employee',
                (float)$r->base_salary,
                (float)$deductions,
                (float)$r->net_salary,
                $r->locked ? 'Locked' : 'Unlocked'
            ], null, 'A' . $rowIdx);

            $sheet->getStyle('E'.$rowIdx.':G'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }

        // Totals Row
        $sheet->setCellValue('A' . $rowIdx, 'TOTAL');
        $sheet->setCellValue('E' . $rowIdx, "=SUM(E8:E" . ($rowIdx - 1) . ")");
        $sheet->setCellValue('F' . $rowIdx, "=SUM(F8:F" . ($rowIdx - 1) . ")");
        $sheet->setCellValue('G' . $rowIdx, "=SUM(G8:G" . ($rowIdx - 1) . ")");
        $sheet->getStyle('A'.$rowIdx.':H'.$rowIdx)->getFont()->setBold(true);
        $sheet->getStyle('E'.$rowIdx.':G'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');

        self::applyTableBorders($sheet, 7, $rowIdx, 'H');
        $sheet->setAutoFilter('A7:H7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }

    // 2. Attendance Export
    private static function generateAttendanceExport($spreadsheet, Carbon $startDate, Carbon $endDate, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Attendance Evidence');
        self::applyTitleBlock($sheet, 'Detailed Attendance Export', $rangeLabel);

        $headers = [
            'Employee ID', 'Employee Name', 'Department', 'Designation', 'Manager', 
            'Employment Category', 'Date', 'Day', 'Working Day', 'Weekly Off', 
            'Shift Name', 'Shift Start', 'Shift End', 'Grace Minutes', 'Check-In', 
            'Check-Out', 'Worked Hours', 'Late Minutes', 'Overtime Hours', 'Automatic Status', 
            'Automatic Classification', 'Leave Type', 'WFH State', 'Manual Override', 
            'Override Type', 'Override Reason', 'Override Actor', 'Override Timestamp', 
            'Final Resolved Status', 'Payroll Classification'
        ];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:AD7');

        $users = User::where('role', 'employee')->where('status', 'active')->with(['department', 'employeeProfile', 'manager'])->get();
        $attendanceService = app(AttendanceService::class);

        $rowIdx = 8;
        foreach ($users as $user) {
            $states = $attendanceService->getAttendanceStatesForRange($user, $startDate, $endDate);
            $p = $user->employeeProfile;
            $joiningDate = $user->joining_date ?? ($p->joining_date ?? null);
            $probationDays = (int) (\App\Models\PayrollSetting::getValue('lifecycle')['probationDays'] ?? 90);
            
            foreach ($states as $dateStr => $state) {
                $dateObj = Carbon::parse($dateStr);
                $isWeeklyOff = AttendanceTimingResolver::isWeeklyOff($dateObj);
                $isWorkingDay = !$isWeeklyOff;

                // Resolve shift name
                $shiftName = 'Standard Shift';
                if ($user->department) {
                    $dName = strtolower($user->department->name);
                    if (str_contains($dName, 'healthcare') || str_contains($dName, 'hlt')) {
                        $shiftName = 'Healthcare Shift';
                    }
                }

                // Determine employment category
                $category = 'Permanent';
                if ($joiningDate) {
                    if ($joiningDate->diffInDays($dateObj) < $probationDays) {
                        $category = 'Probation';
                    }
                }

                // Fetch original attendance record for override details
                $attendanceRecord = Attendance::where('user_id', $user->id)->whereDate('date', $dateStr)->first();
                $isOverridden = $attendanceRecord ? (bool)$attendanceRecord->is_overridden : false;
                $overrideType = $attendanceRecord ? $attendanceRecord->override_type : null;
                $overrideReason = $attendanceRecord ? $attendanceRecord->override_reason : null;
                $overrideActor = $attendanceRecord && $attendanceRecord->overriddenBy ? $attendanceRecord->overriddenBy->name : null;
                $overrideTime = $attendanceRecord && $attendanceRecord->overridden_at ? $attendanceRecord->overridden_at->format('Y-m-d H:i:s') : null;

                $checkIn = $state['check_in_time'] ? Carbon::parse($state['check_in_time'])->format('H:i:s') : '—';
                $checkOut = $state['check_out_time'] ? Carbon::parse($state['check_out_time'])->format('H:i:s') : '—';

                // Calculate late minutes
                $lateMinutes = 0;
                if ($state['check_in_time'] && isset($state['timings']['grace_threshold'])) {
                    $ci = Carbon::parse($state['check_in_time']);
                    $gt = Carbon::parse($state['timings']['grace_threshold']);
                    if ($ci->greaterThan($gt)) {
                        $lateMinutes = $ci->diffInMinutes($gt);
                    }
                }

                // Calculate OT hours if worked > 8
                $worked = (float)$state['hours'];
                $ot = ($worked > 8.0) ? round($worked - 8.0, 1) : 0.0;

                $sheet->fromArray([
                    $user->employee_id ?? 'EMP-' . $user->id,
                    $user->name,
                    $user->department->name ?? 'Unassigned',
                    $p->designation ?? 'Employee',
                    $user->manager->name ?? 'None',
                    $category,
                    $dateStr,
                    $dateObj->format('l'),
                    $isWorkingDay ? 'Yes' : 'No',
                    $isWeeklyOff ? 'Yes' : 'No',
                    $shiftName,
                    $state['timings']['start_time'] ?? '09:30:00',
                    $state['timings']['end_time'] ?? '18:30:00',
                    $state['timings']['grace_minutes'] ?? 15,
                    $checkIn,
                    $checkOut,
                    $worked,
                    $lateMinutes,
                    $ot,
                    $state['automatic_status'] ?? $state['status'],
                    $state['automatic_classification'] ?? $state['classification'],
                    $state['leave_type'] ?? '—',
                    ($state['status'] === 'wfh') ? 'Yes' : 'No',
                    $isOverridden ? 'Yes' : 'No',
                    $overrideType ?? '—',
                    $overrideReason ?? '—',
                    $overrideActor ?? '—',
                    $overrideTime ?? '—',
                    $state['status'],
                    $state['classification']
                ], null, 'A' . $rowIdx);

                $rowIdx++;
            }
        }

        self::applyTableBorders($sheet, 7, $rowIdx - 1, 'AD');
        $sheet->setAutoFilter('A7:AD7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }

    // 3. Monthly Attendance Regularity
    private static function generateMonthlyAttendance($spreadsheet, Carbon $startDate, Carbon $endDate, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Regularity Report');
        self::applyTitleBlock($sheet, 'Monthly Attendance Regularity Report', $rangeLabel);

        $headers = [
            'Employee ID', 'Employee Name', 'Department', 'Designation', 'Total Days', 
            'Working Days', 'Present Days', 'Late Days', 'Half Days', 'Absent Days', 
            'WFH Days', 'Paid Leave Days', 'Unpaid Leave Days', 'Weekly Off Days', 
            'Total Hours', 'Avg Hours/Day', 'Total Late Minutes', 'Total Overtime Hours', 
            'Attendance Rate', 'Punctuality Rate', 'Absenteeism Rate', 'Overrides Count'
        ];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:V7');

        $users = User::where('role', 'employee')->where('status', 'active')->with(['department', 'employeeProfile'])->get();
        $attendanceService = app(AttendanceService::class);

        $rowIdx = 8;
        foreach ($users as $user) {
            $states = $attendanceService->getAttendanceStatesForRange($user, $startDate, $endDate);
            
            $totalDays = count($states);
            $workingDays = 0;
            $present = 0.0;
            $late = 0;
            $half = 0;
            $absent = 0.0;
            $wfh = 0;
            $paidLeave = 0.0;
            $unpaidLeave = 0.0;
            $weeklyOff = 0;
            $totalHours = 0.0;
            $totalLateMin = 0;
            $totalOT = 0.0;
            $overrideCount = 0;

            foreach ($states as $dateStr => $state) {
                $dateObj = Carbon::parse($dateStr);
                $isOff = AttendanceTimingResolver::isWeeklyOff($dateObj);
                if ($isOff) {
                    $weeklyOff++;
                } else {
                    $workingDays++;
                }

                $status = $state['status'];
                if (in_array($status, ['present', 'wfh', 'late'])) {
                    $present += 1.0;
                    if ($status === 'wfh') $wfh++;
                    if ($status === 'late') $late++;
                } elseif (in_array($status, ['half', 'hd_upr'])) {
                    $present += 0.5;
                    $half++;
                    if ($status === 'hd_upr') $unpaidLeave += 0.5;
                } elseif (in_array($status, ['hdp', 'hd_upa'])) {
                    $present += 0.5;
                    $half++;
                    $paidLeave += 0.5;
                    if ($status === 'hd_upa') $unpaidLeave += 0.5;
                } elseif (in_array($status, ['planned', 'bday'])) {
                    $paidLeave += 1.0;
                } elseif ($status === 'absent' || $status === 'upr' || $status === 'upa') {
                    $absent += 1.0;
                    if ($status === 'upa' || $status === 'upr') $unpaidLeave += 1.0;
                }

                $totalHours += (float)$state['hours'];
                if ($state['hours'] > 8.0) {
                    $totalOT += round($state['hours'] - 8.0, 1);
                }

                if ($state['is_overridden']) {
                    $overrideCount++;
                }

                // Late minutes
                if ($state['check_in_time'] && isset($state['timings']['grace_threshold'])) {
                    $ci = Carbon::parse($state['check_in_time']);
                    $gt = Carbon::parse($state['timings']['grace_threshold']);
                    if ($ci->greaterThan($gt)) {
                        $totalLateMin += $ci->diffInMinutes($gt);
                    }
                }
            }

            $attRate = $workingDays > 0 ? ($present / $workingDays) * 100 : 0;
            $punctRate = $present > 0 ? (($present - $late) / $present) * 100 : 0;
            $absRate = $workingDays > 0 ? ($absent / $workingDays) * 100 : 0;
            $avgHours = $workingDays > 0 ? ($totalHours / $workingDays) : 0;

            $sheet->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $user->employeeProfile->designation ?? 'Employee',
                $totalDays,
                $workingDays,
                $present,
                $late,
                $half,
                $absent,
                $wfh,
                $paidLeave,
                $unpaidLeave,
                $weeklyOff,
                $totalHours,
                round($avgHours, 1),
                $totalLateMin,
                $totalOT,
                round($attRate, 1) . '%',
                round($punctRate, 1) . '%',
                round($absRate, 1) . '%',
                $overrideCount
            ], null, 'A' . $rowIdx);

            $rowIdx++;
        }

        self::applyTableBorders($sheet, 7, $rowIdx - 1, 'V');
        $sheet->setAutoFilter('A7:V7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }

    // 4. Leave Report
    private static function generateLeaveReport($spreadsheet, Carbon $startDate, Carbon $endDate, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Leaves Report');
        self::applyTitleBlock($sheet, 'Detailed Leave Report', $rangeLabel);

        $headers = [
            'Employee ID', 'Employee Name', 'Department', 'Leave Request ID', 'Leave Type', 
            'Start Date', 'End Date', 'Days Count', 'Half Day', 'Paid State', 'Approval Status', 
            'Approver', 'Reason', 'Approval Timestamp'
        ];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:N7');

        $leaves = LeaveRequest::with(['user.department', 'approver'])
            ->where('start_date', '<=', $endDate->format('Y-m-d 23:59:59'))
            ->where('end_date', '>=', $startDate->format('Y-m-d 00:00:00'))
            ->get();

        $rowIdx = 8;
        foreach ($leaves as $l) {
            $user = $l->user;
            if (!$user) continue;

            $start = Carbon::parse($l->start_date);
            $end = Carbon::parse($l->end_date);
            $diff = $start->diffInDays($end) + 1;
            $days = $l->is_half_day ? 0.5 : $diff;

            $sheet->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $l->id,
                $l->leave_type_label,
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $days,
                $l->is_half_day ? 'Yes' : 'No',
                $l->is_paid ? 'Paid' : 'Unpaid',
                $l->status,
                $l->approver ? $l->approver->name : 'System',
                $l->reason ?? '—',
                $l->approved_at ? $l->approved_at->format('Y-m-d H:i:s') : '—'
            ], null, 'A' . $rowIdx);

            $rowIdx++;
        }

        self::applyTableBorders($sheet, 7, $rowIdx - 1, 'N');
        $sheet->setAutoFilter('A7:N7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }

    // 5. Deduction Report
    private static function generateDeductionReport($spreadsheet, $records, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Deductions Breakdown');
        self::applyTitleBlock($sheet, 'Detailed Deduction Report', $rangeLabel);
        $headers = [
            'Employee ID', 'Employee Name', 'Department', 'Base Salary', 
            'Attendance Deduction', 'Net Salary'
        ];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:F7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;

            $sheet->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                (float)$r->base_salary,
                (float)$r->attendance_deductions,
                (float)$r->net_salary
            ], null, 'A' . $rowIdx);

            $sheet->getStyle('D'.$rowIdx.':F'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }

        // Totals Row
        $sheet->setCellValue('A' . $rowIdx, 'TOTAL');
        for ($col = 'D'; $col <= 'F'; $col++) {
            $sheet->setCellValue($col . $rowIdx, "=SUM({$col}8:{$col}" . ($rowIdx - 1) . ")");
        }
        $sheet->getStyle('A'.$rowIdx.':F'.$rowIdx)->getFont()->setBold(true);
        $sheet->getStyle('D'.$rowIdx.':F'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');

        self::applyTableBorders($sheet, 7, $rowIdx, 'F');
        $sheet->setAutoFilter('A7:F7');
        $sheet->freezePane('A8');
        self::autofit($sheet);      self::autofit($sheet);
    }

    // 6. Salary Report
    private static function generateSalaryReport($spreadsheet, $records, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Salary Master');
        self::applyTitleBlock($sheet, 'Salary Report', $rangeLabel);

        $headers = [
            'Employee ID', 'Employee Name', 'Department', 'Designation', 'Category', 
            'Base Salary', 'Effective Date', 'Allowances'
        ];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:H7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $p = $user->employeeProfile;
            $pp = $user->payrollProfile;

            $sheet->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $p->designation ?? 'Employee',
                $p->employee_category ?? 'Permanent',
                (float)$r->base_salary,
                $pp && $pp->salary_effective_date ? $pp->salary_effective_date->format('Y-m-d') : '—',
                (float)$r->allowances,
            ], null, 'A' . $rowIdx);

            $sheet->getStyle('F'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $sheet->getStyle('H'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }

        self::applyTableBorders($sheet, 7, $rowIdx - 1, 'H');
        $sheet->setAutoFilter('A7:H7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }

    // 7. Payroll Reconciliation Export (The Multi-Sheet Reconciliation workbook)
    private static function generatePayrollReconciliation($spreadsheet, $records, $rangeLabel)
    {
        // Sheet 1: Payroll Summary
        $sheet1 = $spreadsheet->createSheet();
        $sheet1->setTitle('Payroll Summary');
        self::applyTitleBlock($sheet1, 'Payroll Summary (Reconciliation)', $rangeLabel);

        $headers1 = ['Employee ID', 'Employee Name', 'Department', 'Designation', 'Base Salary', 'Attendance Deductions', 'Net Salary', 'Lock Status'];
        $sheet1->fromArray($headers1, null, 'A7');
        self::applyHeaderStyles($sheet1, 'A7:H7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $p = $user->employeeProfile;
            $deductions = $r->attendance_deductions;

            $sheet1->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $p->designation ?? 'Employee',
                (float)$r->base_salary,
                (float)$deductions,
                (float)$r->net_salary,
                $r->locked ? 'Locked' : 'Unlocked'
            ], null, 'A' . $rowIdx);
            $sheet1->getStyle('E'.$rowIdx.':G'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }
        self::applyTableBorders($sheet1, 7, $rowIdx - 1, 'H');
        $sheet1->setAutoFilter('A7:H7');
        $sheet1->freezePane('A8');
        self::autofit($sheet1);

        // Sheet 2: Attendance Basis
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Attendance Basis');
        self::applyTitleBlock($sheet2, 'Attendance Basis Details', $rangeLabel);

        $headers2 = ['Employee ID', 'Employee Name', 'Working Days', 'Present Days', 'Absent Days', 'Late Days', 'Half Days', 'Leave Days', 'WFH Days'];
        $sheet2->fromArray($headers2, null, 'A7');
        self::applyHeaderStyles($sheet2, 'A7:I7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $sheet2->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                30,
                (float)$r->present_days,
                (float)$r->absent_days,
                (int)$r->late_days,
                (int)$r->half_days,
                (float)$r->leave_days,
                (int)$r->wfh_days
            ], null, 'A' . $rowIdx);
            $rowIdx++;
        }
        self::applyTableBorders($sheet2, 7, $rowIdx - 1, 'I');
        $sheet2->setAutoFilter('A7:I7');
        $sheet2->freezePane('A8');
        self::autofit($sheet2);

        // Sheet 3: Leave Detail
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Leave Detail');
        self::applyTitleBlock($sheet3, 'Leaves Allocation & Consumption', $rangeLabel);

        $headers3 = ['Employee ID', 'Employee Name', 'Leave Days', 'Unpaid Leave Days', 'Birthday Leave Days'];
        $sheet3->fromArray($headers3, null, 'A7');
        self::applyHeaderStyles($sheet3, 'A7:E7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $sheet3->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                (float)$r->leave_days,
                (float)$r->unpaid_leave_days,
                (float)$r->birthday_leave_days
            ], null, 'A' . $rowIdx);
            $rowIdx++;
        }
        self::applyTableBorders($sheet3, 7, $rowIdx - 1, 'E');
        $sheet3->setAutoFilter('A7:E7');
        $sheet3->freezePane('A8');
        self::autofit($sheet3);

        // Sheet 4: Deduction Detail
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Deduction Detail');
        self::applyTitleBlock($sheet4, 'Base Salary & Deductions Reconciliation', $rangeLabel);

        $headers4 = ['Employee ID', 'Employee Name', 'Base Salary', 'Attendance Ded', 'Net Salary'];
        $sheet4->fromArray($headers4, null, 'A7');
        self::applyHeaderStyles($sheet4, 'A7:E7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;

            $sheet4->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                (float)$r->base_salary,
                (float)$r->attendance_deductions,
                (float)$r->net_salary
            ], null, 'A' . $rowIdx);
            $sheet4->getStyle('C'.$rowIdx.':E'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }
        self::applyTableBorders($sheet4, 7, $rowIdx - 1, 'E');
        $sheet4->setAutoFilter('A7:E7');
        $sheet4->freezePane('A8');
        self::autofit($sheet4);

        // Sheet 5: Approval & Lock Status
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Approval & Lock Status');
        self::applyTitleBlock($sheet5, 'Approvals Reconcile Registry', $rangeLabel);

        $headers5 = ['Employee ID', 'Employee Name', 'Version', 'Fingerprint', 'Employee Approval', 'Admin Approval', 'Lock State', 'Locked At', 'Locked By'];
        $sheet5->fromArray($headers5, null, 'A7');
        self::applyHeaderStyles($sheet5, 'A7:I7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $sheet5->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $r->calculation_version,
                $r->fingerprint ?? '—',
                $r->employee_review_status,
                $r->admin_approved_at ? $r->admin_approved_at->format('Y-m-d H:i:s') : 'Pending',
                $r->locked ? 'Locked' : 'Unlocked',
                $r->locked_at ? $r->locked_at->format('Y-m-d H:i:s') : '—',
                $r->locked_by_id ? 'Admin ID ' . $r->locked_by_id : '—'
            ], null, 'A' . $rowIdx);
            $rowIdx++;
        }
        self::applyTableBorders($sheet5, 7, $rowIdx - 1, 'I');
        $sheet5->setAutoFilter('A7:I7');
        $sheet5->freezePane('A8');
        self::autofit($sheet5);
    }

    // 8. Employee Payroll Detail
    private static function generateEmployeePayrollDetail($spreadsheet, $records, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Payroll Detail');
        self::applyTitleBlock($sheet, 'Employee Payroll Detail', $rangeLabel);

        $headers = [
            'Employee ID', 'Employee Name', 'Department', 'Designation', 'Base Salary', 
            'Attendance Deduction', 'Net Salary'
        ];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:G7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $p = $user->employeeProfile;

            $sheet->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $p->designation ?? 'Employee',
                (float)$r->base_salary,
                (float)$r->attendance_deductions,
                (float)$r->net_salary
            ], null, 'A' . $rowIdx);
            $sheet->getStyle('E'.$rowIdx.':G'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }

        self::applyTableBorders($sheet, 7, $rowIdx - 1, 'G');
        $sheet->setAutoFilter('A7:G7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }

    // 9. Department Payroll
    private static function generateDepartmentPayroll($spreadsheet, $records, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Department Expenditures');
        self::applyTitleBlock($sheet, 'Department Payroll Cost Report', $rangeLabel);

        $headers = ['Department', 'Headcount', 'Total Base Salary', 'Net Disbursement'];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:D7');

        $depts = Department::all();
        $rowIdx = 8;
        foreach ($depts as $d) {
            $rSubset = $records->filter(fn($rec) => $rec->user && $rec->user->department_id === $d->id);
            $count = $rSubset->count();
            $base = (float)$rSubset->sum('base_salary');
            $net = (float)$rSubset->sum('net_salary');

            $sheet->fromArray([
                $d->name,
                $count,
                $base,
                $net
            ], null, 'A' . $rowIdx);
            $sheet->getStyle('C'.$rowIdx.':D'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }

        self::applyTableBorders($sheet, 7, $rowIdx - 1, 'D');
        $sheet->setAutoFilter('A7:D7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }

    // 10. Overtime Report
    private static function generateOvertimeReport($spreadsheet, $records, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Overtime Details');
        self::applyTitleBlock($sheet, 'Overtime Summary Report', $rangeLabel);

        $headers = ['Employee ID', 'Employee Name', 'Department', 'Designation', 'Overtime Hours', 'Hourly Rate', 'Overtime Multiplier', 'Overtime Pay'];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:H7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $p = $user->employeeProfile;
            $metadata = $r->calculation_metadata ?? [];
            $hourly = (float)($metadata['hourly_rate'] ?? 0.00);
            $otPolicy = \App\Models\PayrollSetting::getValue('overtime');
            $mult = (float)($otPolicy['multiplier'] ?? 1.5);

            $sheet->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $p->designation ?? 'Employee',
                (float)$r->overtime_hours,
                $hourly,
                $mult . 'x',
                (float)$r->overtime_pay
            ], null, 'A' . $rowIdx);
            $sheet->getStyle('F'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $sheet->getStyle('H'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }

        self::applyTableBorders($sheet, 7, $rowIdx - 1, 'H');
        $sheet->setAutoFilter('A7:H7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }

    // 11. Disbursement Register
    private static function generateDisbursementRegister($spreadsheet, $records, $rangeLabel)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Disbursement Register');
        self::applyTitleBlock($sheet, 'Disbursement Register & Bank Details', $rangeLabel);

        $headers = ['Employee ID', 'Employee Name', 'Department', 'Bank Name', 'Account Holder Name', 'Account Number', 'IFSC Code', 'Net Salary', 'Lock Status', 'Employee Approval'];
        $sheet->fromArray($headers, null, 'A7');
        self::applyHeaderStyles($sheet, 'A7:J7');

        $rowIdx = 8;
        foreach ($records as $r) {
            $user = $r->user;
            $p = $user->employeeProfile;

            $sheet->fromArray([
                $user->employee_id ?? 'EMP-' . $user->id,
                $user->name,
                $user->department->name ?? 'Unassigned',
                $p->bank_name ?? '—',
                $p->account_holder_name ?? $user->name,
                $p->account_no ? ' ' . $p->account_no : '—', // force string prefix space to prevent formatting truncation in excel
                $p->ifsc_code ?? '—',
                (float)$r->net_salary,
                $r->locked ? 'Locked' : 'Unlocked',
                $r->employee_review_status
            ], null, 'A' . $rowIdx);

            $sheet->getStyle('H'.$rowIdx)->getNumberFormat()->setFormatCode('₹#,##0.00');
            $rowIdx++;
        }

        self::applyTableBorders($sheet, 7, $rowIdx - 1, 'J');
        $sheet->setAutoFilter('A7:J7');
        $sheet->freezePane('A8');
        self::autofit($sheet);
    }
}
