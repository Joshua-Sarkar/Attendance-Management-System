<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Payslip - {{ $user->name }} - {{ $cycle->period }}</title>
    <style>
        @page {
            margin: 15px 20px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #2A1B14;
            background: #ffffff;
            margin: 0;
            padding: 0;
            font-size: 9.5px;
            line-height: 1.3;
        }
        
        .payslip-wrapper {
            border: 2px solid #2A1B14;
            padding: 10px;
            background-color: #ffffff;
            box-sizing: border-box;
        }

        /* Tables styling */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .grid-table td {
            border: 1px solid #2A1B14;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 9.5px;
        }
        .lbl-cell {
            font-weight: bold;
            color: #2A1B14;
            background-color: #FAF6EC;
            width: 13%;
        }
        .val-cell {
            color: #262422;
            width: 20%;
        }

        .num-col {
            text-align: right;
            font-family: monospace;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }

        /* Special border classes for the main payroll layout */
        .payroll-cell-all {
            border: 1px solid #2A1B14;
        }
        .payroll-cell-blank-left {
            border-left: 1px solid #2A1B14;
            border-right: none;
            border-top: none;
            border-bottom: none;
        }
        .payroll-cell-blank-mid {
            border-left: none;
            border-right: none;
            border-top: none;
            border-bottom: none;
        }
        .payroll-cell-blank-right {
            border-left: none;
            border-right: 1px solid #2A1B14;
            border-top: none;
            border-bottom: none;
        }
    </style>
</head>
<body>

    @php
        $dailyBreakdown = $metadata['daily_breakdown'] ?? [];
        $presentCount = count(array_filter($dailyBreakdown, fn($d) => in_array($d['status'] ?? '', ['present', 'wfh', 'late'])));
        $weeklyOffCount = count(array_filter($dailyBreakdown, fn($d) => ($d['status'] ?? '') === 'off'));
        $birthdayLeaveCount = (float)$record->birthday_leave_days;
        $halfDaysCount = (float)$record->half_days;
        $unpaidLeaveCount = (float)$record->unpaid_leave_days;
        $paidDaysCount = min(30.0, max(0.0, (float)$record->present_days + (float)$record->leave_days - (float)$record->unpaid_leave_days));
    @endphp

    <div class="payslip-wrapper">
        
        <!-- HEADER BLOCK -->
        <table style="width: 100%; border-collapse: collapse; border-bottom: 2px solid #2A1B14; margin-bottom: 8px; padding-bottom: 8px;">
            <tr>
                <td style="width: 15%; vertical-align: middle; padding: 2px;">
                    <div style="border: 1px solid #2A1B14; width: 70px; height: 70px; text-align: center; line-height: 70px; font-weight: bold; color: #C6941C; font-size: 26px; background-color: #FAF8F5;">VR</div>
                </td>
                <td style="width: 85%; text-align: center; vertical-align: middle;">
                    <div style="font-size: 16px; font-weight: bold; color: #2A1B14; text-transform: uppercase; letter-spacing: 0.5px;">Venture Request</div>
                    <div style="font-size: 11px; font-weight: bold; color: #2A1B14; margin-top: 5px;">Salary Slip For The Month Of {{ $cycle->period }}</div>
                </td>
            </tr>
        </table>

        <!-- EMPLOYEE INFORMATION GRID -->
        <table class="grid-table">
            <tr>
                <td class="lbl-cell">Emp Name</td>
                <td class="val-cell" style="font-weight: bold;">{{ $user->name }}</td>
                <td class="lbl-cell">Department</td>
                <td class="val-cell">{{ $user->department->name ?? 'Unassigned' }}</td>
                <td class="lbl-cell">PF No.</td>
                <td class="val-cell">{{ $profile->pf_no ?? 'Not Available' }}</td>
            </tr>
            <tr>
                <td class="lbl-cell">Father Name</td>
                <td class="val-cell">{{ $profile->father_name ?? 'Not Available' }}</td>
                <td class="lbl-cell">Designation</td>
                <td class="val-cell">{{ $profile->designation ?? 'Employee' }}</td>
                <td class="lbl-cell">UAN</td>
                <td class="val-cell">{{ $profile->pf_uan ?? 'Not Available' }}</td>
            </tr>
            <tr>
                <td class="lbl-cell">Emp Code</td>
                <td class="val-cell" style="font-family: monospace;">{{ $user->employee_id ?? 'EMP-'.$user->id }}</td>
                <td class="lbl-cell">Joining Date</td>
                <td class="val-cell">{{ $user->joining_date ? \Carbon\Carbon::parse($user->joining_date)->format('d-m-Y') : 'Not Available' }}</td>
                <td class="lbl-cell">PAN No.</td>
                <td class="val-cell" style="font-family: monospace;">{{ $profile->pan ?? 'Not Available' }}</td>
            </tr>
            <tr>
                <td class="lbl-cell">Bank Name</td>
                <td class="val-cell">{{ $profile->bank_name ?? 'Not Available' }}</td>
                <td class="lbl-cell">Account No.</td>
                <td class="val-cell" style="font-family: monospace;">{{ $profile->account_no ?? 'Not Available' }}</td>
                <td class="lbl-cell">State</td>
                <td class="val-cell">{{ $profile->location ?? $profile->current_state ?? $profile->state_name ?? 'Not Available' }}</td>
            </tr>
        </table>

        <!-- MAIN PAYROLL AND ATTENDANCE/LEAVE TABLE -->
        <table class="grid-table" style="margin-bottom: 0px;">
            <thead>
                <tr>
                    <th colspan="2" style="border: 1px solid #2A1B14; text-align: left; padding: 4px 6px; font-weight: bold; background-color: #FAF6EC; width: 28%;">Rate of Salary/ wages</th>
                    <th style="border: 1px solid #2A1B14; text-align: left; padding: 4px 6px; font-weight: bold; background-color: #FAF6EC; width: 10%;">Earning</th>
                    <th style="border: 1px solid #2A1B14; text-align: left; padding: 4px 6px; font-weight: bold; background-color: #FAF6EC; width: 8%;">Arrear</th>
                    <th colspan="2" style="border: 1px solid #2A1B14; text-align: left; padding: 4px 6px; font-weight: bold; background-color: #FAF6EC; width: 28%;">Deductions</th>
                    <th colspan="2" style="border: 1px solid #2A1B14; text-align: left; padding: 4px 6px; font-weight: bold; background-color: #FAF6EC; width: 26%;">Attendance/Leave</th>
                </tr>
            </thead>
            <tbody>
                <!-- ROW 1 -->
                <tr>
                    <td class="payroll-cell-all" style="width: 18%;">BASIC</td>
                    <td class="payroll-cell-all num-col" style="width: 10%;">{{ number_format($record->base_salary, 2) }}</td>
                    <td class="payroll-cell-all num-col" style="width: 10%;">{{ number_format($record->base_salary - $record->attendance_deductions, 2) }}</td>
                    <td class="payroll-cell-all num-col" style="width: 8%;">0.00</td>
                    <td class="payroll-cell-all" style="width: 18%;">&nbsp;</td>
                    <td class="payroll-cell-all num-col" style="width: 10%;">0.00</td>
                    <td class="payroll-cell-all" style="width: 18%;">Total Days</td>
                    <td class="payroll-cell-all num-col" style="width: 8%;">30</td>
                </tr>
                <!-- ROW 2 -->
                <tr>
                    <td class="payroll-cell-blank-left">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-all">Present Days</td>
                    <td class="payroll-cell-all num-col">{{ number_format($presentCount, 1) }}</td>
                </tr>
                <!-- ROW 3 -->
                <tr>
                    <td class="payroll-cell-blank-left">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-all">Leave</td>
                    <td class="payroll-cell-all num-col">{{ number_format($record->leave_days, 1) }}</td>
                </tr>
                <!-- ROW 4 -->
                <tr>
                    <td class="payroll-cell-blank-left">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-all">&nbsp;</td>
                    <td class="payroll-cell-all">&nbsp;</td>
                </tr>
                <!-- ROW 5 -->
                <tr>
                    <td class="payroll-cell-blank-left">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-all">Paid Days</td>
                    <td class="payroll-cell-all num-col">{{ number_format($paidDaysCount, 1) }}</td>
                </tr>
                <!-- ROW 6 -->
                <tr>
                    <td class="payroll-cell-blank-left">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-blank-mid">&nbsp;</td>
                    <td class="payroll-cell-blank-right">&nbsp;</td>
                    <td class="payroll-cell-all">Loss Of Pay</td>
                    <td class="payroll-cell-all num-col">{{ number_format($unpaidLeaveCount, 1) }}</td>
                </tr>
                <!-- TOTALS ROW -->
                <tr style="background-color: #FAF6EC; font-weight: bold;">
                    <td class="payroll-cell-all">Earning</td>
                    <td class="payroll-cell-all num-col">{{ number_format($record->base_salary, 2) }}</td>
                    <td class="payroll-cell-all num-col">{{ number_format($record->base_salary - $record->attendance_deductions, 2) }}</td>
                    <td class="payroll-cell-all num-col">0.00</td>
                    <td class="payroll-cell-all">Deduction</td>
                    <td class="payroll-cell-all num-col">0.00</td>
                    <td class="payroll-cell-all">Net Salary</td>
                    <td class="payroll-cell-all num-col">{{ number_format($record->net_salary, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- AMOUNT IN WORDS TABLE -->
        <table class="grid-table" style="margin-top: -1px; margin-bottom: 8px;">
            <tr>
                <td style="width: 28%; font-weight: bold; background-color: #FAF6EC; border-top: none;">Net Salary/Wages(In Words)</td>
                <td style="width: 72%; font-weight: bold; border-top: none;">INR {{ $netInWords }} Only.</td>
            </tr>
        </table>

        <!-- LEAVE BALANCE SECTION -->
        <table class="grid-table">
            <tr>
                <td colspan="4" style="font-weight: bold; background-color: #FAF6EC; font-size: 10px; text-align: left;">Leave Balance</td>
            </tr>
            <tr style="background-color: #FAF8F5; font-weight: bold;">
                <td style="width: 40%;">Leave Type</td>
                <td style="width: 20%; text-align: center;">Prev. Bal</td>
                <td style="width: 20%; text-align: center;">Availed</td>
                <td style="width: 20%; text-align: center;">Current Balance</td>
            </tr>
            @if($leaveBalance)
                <tr>
                    <td>Planned Leave</td>
                    <td class="text-center" style="font-family: monospace;">{{ number_format($leaveBalance->remaining_leave + $leaveBalance->utilized_leave, 1) }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ number_format($leaveBalance->utilized_leave, 1) }}</td>
                    <td class="text-center font-bold" style="font-family: monospace;">{{ number_format($leaveBalance->remaining_leave, 1) }}</td>
                </tr>
                <tr>
                    <td>Unplanned Leave</td>
                    <td class="text-center" style="font-family: monospace;">{{ number_format($leaveBalance->unplanned_leave, 1) }}</td>
                    <td class="text-center" style="font-family: monospace;">0.0</td>
                    <td class="text-center" style="font-family: monospace;">{{ number_format($leaveBalance->unplanned_leave, 1) }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="4" class="text-center" style="color: #6E655C; font-style: italic;">
                        No active leave balance record available.
                    </td>
                </tr>
            @endif
        </table>

        <!-- ATTENDANCE DEDUCTION BREAKDOWN (IF ANY) -->
        @if(count($deductionBreakdown['itemized_dates']) > 0)
            <table class="grid-table" style="margin-top: 8px;">
                <tr>
                    <td colspan="4" style="font-weight: bold; background-color: #FAF6EC; font-size: 10px; text-align: left;">Attendance Deduction Breakdown</td>
                </tr>
                <tr style="background-color: #FAF8F5; font-weight: bold;">
                    <td style="width: 20%;">Date</td>
                    <td style="width: 25%;">Deduction Category</td>
                    <td style="width: 40%;">Reason / Calculation Basis</td>
                    <td style="width: 15%; text-align: right;">Amount</td>
                </tr>
                @foreach($deductionBreakdown['itemized_dates'] as $item)
                    <tr>
                        <td style="font-family: monospace;">{{ $item['date'] }}</td>
                        <td>{{ $item['type'] }}</td>
                        <td style="color: #6E655C;">{{ $item['reason'] }}</td>
                        <td class="num-col font-bold" style="color: #7A2E2E;">-{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <!-- FOOTER DISCLAIMER BLOCK -->
        <div style="border: 1px solid #2A1B14; padding: 4px; text-align: center; font-weight: bold; font-size: 9.5px; margin-top: 8px; margin-bottom: 4px;">
            ***This is computer generated salary slip No signature required***
        </div>
        <div style="text-align: center; font-size: 8px; color: #7A6F64; line-height: 1.2;">
            <strong>Security Fingerprint:</strong> <span style="font-family: monospace;">{{ $record->fingerprint }}</span> &nbsp;·&nbsp; <strong>Finalized Date:</strong> {{ $record->locked_at ? $record->locked_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s') }}
        </div>

    </div>

</body>
</html>
