<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enterprise Payslip - {{ $user->name }} - {{ $cycle->period }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #262422;
            background: #ffffff;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.45;
        }
        
        /* Header & Branding */
        .brand-header {
            width: 100%;
            border-bottom: 2px solid #C6941C;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-title {
            font-size: 20px;
            font-weight: bold;
            color: #2A1B14;
            letter-spacing: -0.5px;
        }
        .company-subtitle {
            font-size: 10.5px;
            color: #6E655C;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 15px;
            font-weight: bold;
            color: #C6941C;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: right;
        }
        .period-pill {
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            color: #2A1B14;
            background-color: #F6F1E1;
            border: 1px solid #E3D8B9;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
        }

        /* Cards / Info Tables */
        .section-label {
            font-size: 10px;
            font-weight: bold;
            color: #8C7B6B;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }
        .card-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #FAF8F5;
            border: 1px solid #E6DFC8;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .card-table td {
            padding: 8px 12px;
            vertical-align: top;
            border-bottom: 1px solid #F0EAD6;
        }
        .card-table tr:last-child td {
            border-bottom: none;
        }
        .lbl {
            font-size: 9.5px;
            font-weight: bold;
            color: #7A6F64;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .val {
            font-size: 11.5px;
            color: #1A1816;
            font-weight: 500;
        }
        .val-mono {
            font-family: monospace;
            font-size: 11px;
        }

        /* 2-Column Earnings & Deductions Layout */
        .breakdown-wrapper {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .breakdown-col {
            width: 49%;
            vertical-align: top;
        }
        .breakdown-col.right {
            padding-left: 2%;
        }

        .comp-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #E6DFC8;
            background-color: #ffffff;
            border-radius: 6px;
        }
        .comp-table th {
            background-color: #F5EFE0;
            border-bottom: 1px solid #E6DFC8;
            padding: 7px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #2A1B14;
            text-align: left;
        }
        .comp-table th.deductions-head {
            background-color: #F8EFEF;
            color: #7A2E2E;
            border-bottom-color: #EBC6C6;
        }
        .comp-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #F3EDE0;
            font-size: 11px;
        }
        .comp-table tr:last-child td {
            border-bottom: none;
        }
        .num-col {
            text-align: right;
            font-family: monospace;
            font-weight: 500;
        }
        .total-row td {
            font-weight: bold;
            background-color: #FAF6EC;
            border-top: 1.5px solid #E6DFC8;
            color: #2A1B14;
            font-size: 11.5px;
        }
        .total-row.deductions-total td {
            background-color: #FDF4F4;
            border-top: 1.5px solid #EBC6C6;
            color: #7A2E2E;
        }
        
        .sub-text {
            font-size: 9px;
            color: #8C7B6B;
            display: block;
            margin-top: 1px;
        }

        /* Itemized Breakdown Table */
        .itemized-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #E6DFC8;
            margin-bottom: 16px;
        }
        .itemized-table th {
            background-color: #FAF8F5;
            border-bottom: 1px solid #E6DFC8;
            padding: 6px 10px;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #5C5248;
            text-align: left;
        }
        .itemized-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #F3EDE0;
            font-size: 10.5px;
        }

        /* Summary Payout Box */
        .payout-box {
            background-color: #F4F8F5;
            border: 1.5px solid #4C7A5D;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .payout-amount {
            font-size: 20px;
            font-weight: bold;
            color: #1E3D30;
            font-family: monospace;
        }
        .words-line {
            font-size: 10.5px;
            color: #4C5952;
            margin-top: 4px;
            border-top: 1px dashed #C2D6CA;
            padding-top: 4px;
        }

        /* Footer Notice */
        .footer-notice {
            border-top: 1px solid #E6DFC8;
            padding-top: 10px;
            text-align: center;
            font-size: 9.5px;
            color: #9E9182;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <!-- BRAND HEADER -->
    <div class="brand-header">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="company-title">Venture Request</div>
                    <div class="company-subtitle">Enterprise Workforce & Payroll Control Center · HQ Dehradun, Uttarakhand</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <div class="doc-title">Official Payslip</div>
                    <div class="period-pill">{{ $cycle->period }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- EMPLOYEE SUMMARY CARD -->
    <div class="section-label">Employee Summary</div>
    <table class="card-table">
        <tr>
            <td style="width: 25%;">
                <div class="lbl">Employee Name</div>
                <div class="val" style="font-weight: bold;">{{ $user->name }}</div>
            </td>
            <td style="width: 25%;">
                <div class="lbl">Employee ID</div>
                <div class="val val-mono">{{ $user->employee_id ?? 'EMP-'.$user->id }}</div>
            </td>
            <td style="width: 25%;">
                <div class="lbl">Department</div>
                <div class="val">{{ $user->department->name ?? 'Unassigned' }}</div>
            </td>
            <td style="width: 25%;">
                <div class="lbl">Designation</div>
                <div class="val">{{ $profile->designation ?? 'Employee' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="lbl">Date of Joining</div>
                <div class="val">{{ $user->joining_date ? \Carbon\Carbon::parse($user->joining_date)->format('d M Y') : '—' }}</div>
            </td>
            <td>
                <div class="lbl">Bank Name</div>
                <div class="val">{{ $profile->bank_name ?? 'State Bank of India' }}</div>
            </td>
            <td>
                <div class="lbl">Account Number</div>
                <div class="val val-mono">
                    @if($profile->account_no)
                        {{ '*******'.substr($profile->account_no, -4) }}
                    @else
                        —
                    @endif
                </div>
            </td>
            <td>
                <div class="lbl">Statement Version</div>
                <div class="val val-mono">v{{ $record->calculation_version }} (Ref: {{ substr($record->fingerprint, 0, 8) }})</div>
            </td>
        </tr>
    </table>

    <!-- EARNINGS & ATTENDANCE DEDUCTIONS breakdown -->
    <table class="breakdown-wrapper">
        <tr>
            <!-- EARNINGS COLUMN -->
            <td class="breakdown-col">
                <div class="section-label">Gross Earnings</div>
                <table class="comp-table">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th class="num-col">Amount (INR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                Base Salary
                                <span class="sub-text">Contractual base component</span>
                            </td>
                            <td class="num-col">Rs. {{ number_format($record->base_salary, 2) }}</td>
                        </tr>
                        <tr>
                            <td>
                                Standard Allowances
                                <span class="sub-text">Special & flexible allowances</span>
                            </td>
                            <td class="num-col">Rs. {{ number_format($record->allowances, 2) }}</td>
                        </tr>
                        @if($record->overtime_pay > 0)
                            <tr>
                                <td>
                                    Overtime Pay
                                    <span class="sub-text">{{ (float)$record->overtime_hours }} hrs @ 1.5x hourly rate</span>
                                </td>
                                <td class="num-col">Rs. {{ number_format($record->overtime_pay, 2) }}</td>
                            </tr>
                        @endif
                        @if($record->bonuses > 0)
                            <tr>
                                <td>
                                    Discretionary Adjustments
                                    <span class="sub-text">Approved corrections / bonus</span>
                                </td>
                                <td class="num-col">Rs. {{ number_format($record->bonuses, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td>Total Gross Earnings</td>
                            <td class="num-col">Rs. {{ number_format($record->gross_salary, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>

            <!-- ATTENDANCE DEDUCTIONS COLUMN -->
            <td class="breakdown-col right">
                <div class="section-label">Attendance Deductions</div>
                <table class="comp-table">
                    <thead>
                        <tr>
                            <th class="deductions-head">Deduction Type</th>
                            <th class="deductions-head" style="text-align: center;">Qty × Rate</th>
                            <th class="deductions-head num-col">Amount (INR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                Half Days
                                <span class="sub-text">Half day shift impact</span>
                            </td>
                            <td style="text-align: center; font-family: monospace; font-size: 10px;">
                                {{ $deductionBreakdown['half_days']['quantity'] }} × Rs. {{ number_format($deductionBreakdown['half_days']['rate'], 2) }}
                            </td>
                            <td class="num-col" style="color: #7A2E2E;">
                                Rs. {{ number_format($deductionBreakdown['half_days']['amount'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Unpaid Leave Days
                                <span class="sub-text">Unpaid / unexcused leave</span>
                            </td>
                            <td style="text-align: center; font-family: monospace; font-size: 10px;">
                                {{ $deductionBreakdown['unpaid_leaves']['quantity'] }} × Rs. {{ number_format($deductionBreakdown['unpaid_leaves']['rate'], 2) }}
                            </td>
                            <td class="num-col" style="color: #7A2E2E;">
                                Rs. {{ number_format($deductionBreakdown['unpaid_leaves']['amount'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Late Penalties
                                <span class="sub-text">Threshold arrival penalties</span>
                            </td>
                            <td style="text-align: center; font-family: monospace; font-size: 10px;">
                                {{ $deductionBreakdown['late_penalties']['quantity'] }} × Rs. {{ number_format($deductionBreakdown['late_penalties']['rate'], 2) }}
                            </td>
                            <td class="num-col" style="color: #7A2E2E;">
                                Rs. {{ number_format($deductionBreakdown['late_penalties']['amount'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Override Adjustments
                                <span class="sub-text">Ledger override impact</span>
                            </td>
                            <td style="text-align: center; font-family: monospace; font-size: 10px;">
                                {{ $deductionBreakdown['override_adjustments']['quantity'] }} × Rs. {{ number_format($deductionBreakdown['override_adjustments']['rate'], 2) }}
                            </td>
                            <td class="num-col" style="color: #7A2E2E;">
                                Rs. {{ number_format($deductionBreakdown['override_adjustments']['amount'], 2) }}
                            </td>
                        </tr>
                        @if($deductionBreakdown['manual_adjustments']['quantity'] > 0)
                            <tr>
                                <td>
                                    Manual Adjustments
                                    <span class="sub-text">Approved payroll correction</span>
                                </td>
                                <td style="text-align: center; font-family: monospace; font-size: 10px;">
                                    {{ $deductionBreakdown['manual_adjustments']['quantity'] }} × Rs. {{ number_format($deductionBreakdown['manual_adjustments']['rate'], 2) }}
                                </td>
                                <td class="num-col" style="color: #7A2E2E;">
                                    Rs. {{ number_format($deductionBreakdown['manual_adjustments']['amount'], 2) }}
                                </td>
                            </tr>
                        @endif
                        <tr class="total-row deductions-total">
                            <td colspan="2">Total Attendance Deductions</td>
                            <td class="num-col">Rs. {{ number_format($deductionBreakdown['total_deductions'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- ITEMIZED DEDUCTION DATES BREAKDOWN -->
    @if(count($deductionBreakdown['itemized_dates']) > 0)
        <div class="section-label">Attendance Deduction Date Breakdown</div>
        <table class="itemized-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Date</th>
                    <th style="width: 25%;">Category</th>
                    <th style="width: 35%;">Reason / Basis</th>
                    <th style="width: 15%; text-align: right;">Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deductionBreakdown['itemized_dates'] as $item)
                    <tr>
                        <td style="font-family: monospace; font-weight: bold;">{{ $item['date'] }}</td>
                        <td>{{ $item['type'] }}</td>
                        <td style="color: #6E655C;">{{ $item['reason'] }}</td>
                        <td style="text-align: right; font-family: monospace; color: #7A2E2E;">-Rs. {{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- NET DISBURSEMENT SUMMARY BOX -->
    <div class="payout-box">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div style="font-size: 9.5px; font-weight: bold; color: #4C7A5D; text-transform: uppercase; letter-spacing: 0.8px;">Net Disbursement Amount</div>
                    <div class="payout-amount">Rs. {{ number_format($record->net_salary, 2) }}</div>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div style="font-size: 11px; font-weight: bold; color: #1E3D30; text-transform: uppercase;">Disbursed & Finalized</div>
                    <div style="font-size: 10px; color: #6E655C;">Scheduled Payment: 07 {{ \Carbon\Carbon::parse($cycle->end_date)->addMonth()->format('M Y') }}</div>
                </td>
            </tr>
        </table>
        <div class="words-line">
            <strong>Amount in Words:</strong> Indian Rupees {{ $netInWords }} Only
        </div>
    </div>

    <!-- FOOTER NOTICE -->
    <div class="footer-notice">
        This is a system-generated official payslip issued by the AMS-V1 Payroll Control Center and does not require a physical signature.<br>
        <strong>Security Token:</strong> <span style="font-family: monospace;">{{ $record->fingerprint }}</span> · <strong>Finalized Date:</strong> {{ $record->locked_at ? $record->locked_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s') }}
    </div>

</body>
</html>
