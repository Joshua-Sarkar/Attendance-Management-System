<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $user->name }} - {{ $cycle->period }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #2A1B14;
            background: #ffffff;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid #C6941C;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2A1B14;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #C6941C;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .details-table td {
            padding: 6px 10px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #6E655C;
            font-size: 11px;
            text-transform: uppercase;
        }
        .value {
            color: #262624;
            font-size: 12px;
        }
        .components-container {
            width: 100%;
            margin-bottom: 25px;
        }
        .components-table {
            width: 48%;
            border: 1px solid #E3D8B9;
            border-collapse: collapse;
            float: left;
        }
        .components-table.deductions {
            float: right;
        }
        .components-table th {
            background-color: #FBF8EF;
            border-bottom: 1px solid #E3D8B9;
            padding: 8px 10px;
            font-size: 11px;
            text-transform: uppercase;
            text-align: left;
            color: #2A1B14;
        }
        .components-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #F6F1E1;
            font-size: 12px;
        }
        .amount {
            text-align: right;
            font-family: monospace;
        }
        .total-row td {
            font-weight: bold;
            background-color: #FBF8EF;
            border-top: 1px solid #E3D8B9;
            border-bottom: 1px solid #E3D8B9 !important;
        }
        .clear {
            clear: both;
        }
        .summary-box {
            background-color: #FBF8EF;
            border: 1px solid #C6941C;
            padding: 15px;
            margin-top: 30px;
            border-radius: 6px;
        }
        .net-amount {
            font-size: 18px;
            font-weight: bold;
            color: #1E3D30;
        }
        .net-words {
            font-style: italic;
            margin-top: 5px;
            color: #6E655C;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #E3D8B9;
            padding-top: 15px;
            text-align: center;
            font-size: 11px;
            color: #A79C87;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="company-name">Venture Request</div>
                    <div style="color: #6E655C; font-size: 11px;">HQ, Dehradun, Uttarakhand</div>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div class="title">Payslip Report</div>
                    <div style="font-weight: bold; font-size: 12px; color: #2A1B14;">{{ $cycle->period }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="details-table">
        <tr>
            <td style="width: 25%;">
                <span class="label">Employee ID</span><br>
                <span class="value">{{ $user->employee_id ?? 'EMP-'.$user->id }}</span>
            </td>
            <td style="width: 25%;">
                <span class="label">Employee Name</span><br>
                <span class="value">{{ $user->name }}</span>
            </td>
            <td style="width: 25%;">
                <span class="label">Department</span><br>
                <span class="value">{{ $user->department->name ?? 'Unassigned' }}</span>
            </td>
            <td style="width: 25%;">
                <span class="label">Designation</span><br>
                <span class="value">{{ $profile->designation ?? 'Employee' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Date of Joining</span><br>
                <span class="value">{{ $user->joining_date ? \Carbon\Carbon::parse($user->joining_date)->format('d M Y') : '—' }}</span>
            </td>
            <td>
                <span class="label">Bank Name</span><br>
                <span class="value">{{ $profile->bank_name ?? 'SBI' }}</span>
            </td>
            <td>
                <span class="label">Account Number</span><br>
                <span class="value">
                    @if($profile->account_no)
                        {{ '*******'.substr($profile->account_no, -4) }}
                    @else
                        —
                    @endif
                </span>
            </td>
            <td>
                <span class="label">Calculation Version</span><br>
                <span class="value">v{{ $record->calculation_version }} (Ref: {{ substr($record->fingerprint, 0, 8) }})</span>
            </td>
        </tr>
    </table>

    <div class="components-container">
        <!-- Earnings Table -->
        <table class="components-table">
            <thead>
                <tr>
                    <th colspan="2">Earnings</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="amount">Rs. {{ number_format($record->base_salary, 2) }}</td>
                </tr>
                <tr>
                    <td>Allowances</td>
                    <td class="amount">Rs. {{ number_format($record->allowances, 2) }}</td>
                </tr>
                @if($record->overtime_pay > 0)
                    <tr>
                        <td>Overtime Pay ({{ (float)$record->overtime_hours }} hrs)</td>
                        <td class="amount">Rs. {{ number_format($record->overtime_pay, 2) }}</td>
                    </tr>
                @endif
                @if($record->bonuses > 0)
                    <tr>
                        <td>Discretionary Adjustments / Bonuses</td>
                        <td class="amount">Rs. {{ number_format($record->bonuses, 2) }}</td>
                    </tr>
                @endif
                <!-- Empty rows for spacing balance -->
                @for($i = 0; $i < 2; $i++)
                    <tr>
                        <td style="color: transparent;">Spacer</td>
                        <td class="amount" style="color: transparent;">Rs. 0.00</td>
                    </tr>
                @endfor
                <tr class="total-row">
                    <td>Gross Earnings</td>
                    <td class="amount">Rs. {{ number_format($record->gross_salary, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Deductions Table -->
        <table class="components-table deductions">
            <thead>
                <tr>
                    <th colspan="2">Deductions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Attendance Deduction</td>
                    <td class="amount">Rs. {{ number_format($record->attendance_deductions, 2) }}</td>
                </tr>
                <!-- Spacer rows to align heights -->
                @for($i = 0; $i < 4; $i++)
                    <tr>
                        <td style="color: transparent;">Spacer</td>
                        <td class="amount" style="color: transparent;">Rs. 0.00</td>
                    </tr>
                @endfor
                <tr class="total-row">
                    <td>Total Deductions</td>
                    <td class="amount">Rs. {{ number_format($record->attendance_deductions, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="clear"></div>
    </div>

    <div class="summary-box">
        <table style="width: 100%;">
            <tr>
                <td>
                    <span class="label">Net Disbursement Amount</span><br>
                    <span class="net-amount">Rs. {{ number_format($record->net_salary, 2) }}</span>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div style="font-size: 11px; font-weight: bold; color: #1E3D30; text-transform: uppercase;">Disbursed & Closed</div>
                    <div style="font-size: 10px; color: #A79C87;">Payment Date: 07 {{ \Carbon\Carbon::parse($cycle->end_date)->addMonth()->format('M Y') }}</div>
                </td>
            </tr>
        </table>
        <div class="net-words">
            <strong>In Words:</strong> Indian Rupees {{ $netInWords }} Only
        </div>
    </div>

    <div class="footer">
        This is a system generated payslip from the AMS-V1 Payroll Control Center and does not require a physical signature.<br>
        Trace Token: {{ $record->fingerprint }} · Locked Date: {{ $record->locked_at ? $record->locked_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s') }}
    </div>

</body>
</html>
