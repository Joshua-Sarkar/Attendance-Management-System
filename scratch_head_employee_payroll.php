@extends('layouts.payroll')

@section('content')
    <!-- MAIN WORKSPACE -->
    <div x-data="employeePayrollApp()" x-init="init()" class="flex min-h-screen">
        
        <!-- ============ SIDEBAR ============ -->
        <aside class="hidden lg:flex flex-col w-[252px] shrink-0 bg-walnut border-r border-walnut-light sticky top-0 h-screen">
            <div class="px-6 pt-7 pb-6 border-b border-walnut-light">
                <p class="font-display text-[22px] leading-none tracking-tight text-cream">AMS <span class="text-brass">·</span> V1</p>
                <p class="text-[11px] uppercase tracking-[0.14em] text-brass mt-1.5 font-semibold">Employee Hub</p>
            </div>
            
            <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
                <a href="{{ route('employee.dashboard') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors text-cream/65 hover:bg-walnut-light hover:text-cream">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0 bg-brass/60"></span>
                    <span class="flex-1 text-left">Dashboard</span>
                </a>
                
                <a href="{{ route('attendance.my-attendance') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors text-cream/65 hover:bg-walnut-light hover:text-cream">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0 bg-brass/60"></span>
                    <span class="flex-1 text-left">My Attendance</span>
                </a>
                
                <a href="{{ route('employee.payroll.index') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors bg-brass text-walnut font-medium shadow-soft">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0 bg-walnut"></span>
                    <span class="flex-1 text-left">My Payroll</span>
                </a>

                <a href="{{ route('leaves.index') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors text-cream/65 hover:bg-walnut-light hover:text-cream">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0 bg-brass/60"></span>
                    <span class="flex-1 text-left">Leaves</span>
                </a>
            </nav>
            
            <div class="px-4 py-5 border-t border-walnut-light">
                <div class="rounded-2xl bg-walnut-light/70 border border-brass/25 p-4 text-xs text-cream/70 space-y-1">
                    <p class="font-bold text-brass uppercase">Need Help?</p>
                    <p>Contact HR/Admin if there are issues with your attendance or calculations.</p>
                </div>
            </div>
        </aside>

        <!-- ============ MAIN ============ -->
        <div class="flex-1 min-w-0 flex flex-col h-screen overflow-y-auto">
            
            <!-- Sticky top bar -->
            <header class="sticky top-0 z-30 bg-ivory/90 backdrop-blur border-b border-line shrink-0">
                <div class="flex items-center justify-between gap-4 px-5 lg:px-9 py-3.5">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="lg:hidden font-display text-lg">AMS</span>
                        <h1 class="font-display text-[19px] truncate text-vellum">My Payroll Review</h1>
                        <span class="hidden sm:inline-flex items-center gap-1.5 text-[11.5px] px-2.5 py-1 rounded-full border bg-brass-light/30 text-brass border-brass/30 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-brass"></span>
                            <span>Self-Service</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <select x-model="selectedPeriod" @change="changePeriod()"
                                class="text-[12.5px] bg-cream border border-line rounded-xl px-3 py-2 outline-none focus:border-brass">
                            <template x-for="p in allPeriods" :key="p">
                                <option :value="p" x-text="p"></option>
                            </template>
                        </select>
                        <div class="flex items-center gap-2">
                            <button class="w-9 h-9 rounded-full bg-brass-dark text-cream font-display text-[13px] flex items-center justify-center font-bold">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="px-5 lg:px-9 py-7 space-y-8 flex-1">
                
                @if (session('success'))
                    <div class="p-4 bg-forest/10 border border-forest/20 text-forest rounded-xl text-xs font-mono">
                        ✓ {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="p-4 bg-burgundy/10 border border-burgundy/20 text-burgundy rounded-xl text-xs font-mono">
                        ⚠ {{ session('error') }}
                    </div>
                @endif

                <template x-if="!record">
                    <div class="text-center py-16 panel bg-cream border border-line rounded-2xl">
                        <p class="text-vellum-faint italic">No active payroll records found for <span x-text="selectedPeriod"></span>.</p>
                        <p class="text-xs text-vellum-muted mt-2">Calculations are generated once HR reviews the month's attendance logs.</p>
                    </div>
                </template>

                <template x-if="record">
                    <div class="space-y-7">
                        
                        <!-- ================= A. PAYROLL HERO ================= -->
                        <div class="panel bg-cream border border-line p-8 rounded-3xl relative overflow-hidden shadow-soft">
                            <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-brass/5"></div>
                            
                            <div class="relative flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-brass font-mono bg-brass-light/20 border border-brass/25 px-2.5 py-1 rounded-full">
                                            Earnings Statement
                                        </span>
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold border"
                                              :class="record.locked ? 'bg-burgundy/10 text-burgundy border-burgundy/20' : 'bg-brass/10 text-brass border-brass/20'">
                                            <span class="w-1.5 h-1.5 rounded-full inline-block mr-1.5" :class="record.locked ? 'bg-burgundy' : 'bg-brass'"></span>
                                            <span x-text="record.locked ? 'LOCKED & FINALISED' : 'CYCLE OPEN'"></span>
                                        </span>
                                    </div>
                                    <h2 class="font-display text-3xl font-bold text-walnut" x-text="selectedPeriod"></h2>
                                    <p class="text-xs text-vellum-faint">
                                        Calculations fingerprint: <span class="font-mono text-vellum font-semibold" x-text="record.fingerprint ? record.fingerprint.substring(0, 16) : '—'"></span>
                                    </p>
                                </div>

                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 lg:text-right">
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-bold text-vellum-faint uppercase tracking-wider font-mono">Net Disbursement Amount</p>
                                        <p class="font-display text-4xl font-black text-forest tracking-tight" x-text="'₹' + record.net.toLocaleString('en-IN')"></p>
                                        <p class="text-[11px] text-vellum-muted">Scheduled Transfer: <span class="font-semibold text-walnut">07th of next month</span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Payslip Section nested in Hero -->
                            <div class="mt-6 pt-6 border-t border-line/60 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div class="space-y-0.5">
                                    <p class="text-xs font-semibold text-walnut">Payslip Document</p>
                                    <p class="text-[11px] text-vellum-muted" x-text="record.locked && record.payslip_status === 'published' ? 'Your official signed payslip is ready.' : 'Payslips are generated and published only after cycle finalisation and lock.'"></p>
                                </div>
                                <div class="shrink-0">
                                    <template x-if="record.locked && record.payslip_status === 'published'">
                                        <a :href="'/my-payslip/' + record.record_id + '/download'" 
                                           class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider font-mono bg-brass hover:bg-brass-dark text-walnut px-5 py-3 rounded-xl transition shadow-soft">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Download Payslip PDF
                                        </a>
                                    </template>
                                    <template x-if="!record.locked || record.payslip_status !== 'published'">
                                        <span class="inline-flex items-center gap-1.5 text-[11px] px-3.5 py-2 rounded-xl bg-surface border border-line text-vellum-muted font-mono uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 rounded-full bg-vellum-faint"></span>
                                            Payslip Unavailable
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- ================= E. PAYROLL REVIEW / SYNCHRONIZATION WORKFLOW ================= -->
                        <div class="panel bg-cream border border-line p-6 rounded-3xl space-y-5">
                            <div class="flex justify-between items-center border-b border-line pb-3">
                                <div>
                                    <h3 class="font-display font-medium text-base text-walnut">Cycle Review & Sign-Off</h3>
                                    <p class="text-[11px] text-vellum-muted">Calculations version: <span class="font-mono text-brass font-bold" x-text="'v' + record.calculation_version"></span></p>
                                </div>
                                <span class="px-3 py-1 rounded-xl text-[10px] font-bold font-mono tracking-wider border"
                                      :class="{
                                          approved: 'bg-forest/10 border-forest/20 text-forest',
                                          pending: 'bg-brass/10 border-brass/20 text-brass-dark',
                                          stale: 'bg-burgundy/10 border-burgundy/20 text-burgundy',
                                          disputed: 'bg-burgundy/10 border-burgundy/20 text-burgundy'
                                      }[record.employee_review_status]"
                                      x-text="record.employee_review_status.toUpperCase()"></span>
                            </div>

                            <!-- Workflow Progress Stepper -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 py-2">
                                <div class="p-3.5 bg-surface/50 border border-line/65 rounded-2xl space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-mono font-bold text-vellum-muted">STAGE 1</span>
                                        <span class="text-forest text-[11px]">✓ Ready</span>
                                    </div>
                                    <p class="text-xs font-semibold text-walnut">Attendance Basis</p>
                                    <p class="text-[10px] text-vellum-muted">Engine resolved all clock-ins & leaves.</p>
                                </div>

                                <div class="p-3.5 bg-surface/50 border border-line/65 rounded-2xl space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-mono font-bold text-vellum-muted">STAGE 2</span>
                                        <span class="text-forest text-[11px]">✓ Generated</span>
                                    </div>
                                    <p class="text-xs font-semibold text-walnut">Payroll Statement</p>
                                    <p class="text-[10px] text-vellum-muted">System mapped rates and statutory components.</p>
                                </div>

                                <div class="p-3.5 border rounded-2xl space-y-1"
                                     :class="record.employee_review_status === 'approved' ? 'bg-surface/50 border-line/65' : 'bg-brass/5 border-brass/25'">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-mono font-bold text-vellum-muted">STAGE 3</span>
                                        <span class="text-[11px]" :class="record.employee_review_status === 'approved' ? 'text-forest' : 'text-brass-dark'" x-text="record.employee_review_status === 'approved' ? '✓ Signed Off' : '• Action Required'"></span>
                                    </div>
                                    <p class="text-xs font-semibold text-walnut">Employee Approval</p>
                                    <p class="text-[10px] text-vellum-muted" x-text="record.employee_approved_at ? 'Approved on ' + record.employee_approved_at : 'Confirm details to lock earnings.'"></p>
                                </div>

                                <div class="p-3.5 border rounded-2xl space-y-1"
                                     :class="record.locked ? 'bg-surface/50 border-line/65' : 'bg-cream border-line'">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-mono font-bold text-vellum-muted">STAGE 4</span>
                                        <span class="text-[11px]" :class="record.locked ? 'text-forest' : 'text-vellum-muted'" x-text="record.locked ? '✓ Locked' : 'Pending Lock'"></span>
                                    </div>
                                    <p class="text-xs font-semibold text-walnut">Admin Sign-Off</p>
                                    <p class="text-[10px] text-vellum-muted">Final lock and disbursement processing.</p>
                                </div>
                            </div>

                            <!-- Stepper Actions -->
                            <template x-if="record.employee_review_status !== 'approved' && !record.locked">
                                <div class="bg-surface border border-line p-4 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <p class="text-[11px] text-vellum-muted max-w-md">
                                        Please review the earnings breakdown, attendance metrics, and daily log snapshot below. Select <span class="font-bold">Confirm</span> if everything is correct, or <span class="font-bold text-burgundy">Raise Dispute</span> if there is a discrepancy.
                                    </p>
                                    <div class="flex gap-2.5 shrink-0 w-full sm:w-auto">
                                        <button @click="openDisputeModal = true" class="flex-1 sm:flex-none text-center px-4 py-2.5 border border-burgundy/60 text-burgundy hover:bg-burgundy/5 rounded-xl font-bold uppercase tracking-wider text-[10px] font-mono transition">
                                            Raise Dispute
                                        </button>
                                        <form method="POST" action="{{ route('employee.payroll.approve') }}" class="flex-1 sm:flex-none">
                                            @csrf
                                            <input type="hidden" name="record_id" :value="record.record_id">
                                            <button type="submit" class="w-full text-center px-5 py-2.5 bg-forest hover:bg-forest-dark text-cream rounded-xl font-bold uppercase tracking-wider text-[10px] font-mono transition shadow-soft">
                                                Confirm & Approve
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </template>

                            <template x-if="record.employee_review_status === 'approved' && !record.locked">
                                <div class="bg-forest/5 border border-forest/10 p-4 rounded-2xl text-[11.5px] text-forest flex items-start gap-2.5">
                                    <span class="text-lg">✓</span>
                                    <p>
                                        You approved this statement on <span class="font-bold" x-text="record.employee_approved_at"></span>. If HR/Admin changes your salary structure or attendance logs, this approval will automatically invalidate to "stale", and you will receive a notification to review and sign-off again.
                                    </p>
                                </div>
                            </template>

                            <template x-if="record.locked">
                                <div class="bg-surface/75 border border-line p-4 rounded-2xl text-[11.5px] text-vellum-muted flex items-start gap-2.5">
                                    <span class="text-lg">✓</span>
                                    <p>
                                        This record is locked and finalised. The statement has been signed off by both the employee and HR administration, rendering it frozen for disbursement.
                                    </p>
                                </div>
                            </template>
                        </div>

                        <!-- ================= B. EARNINGS & C. DEDUCTIONS SUMMARY ================= -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- B. EARNINGS SUMMARY -->
                            <div class="panel bg-cream border border-line p-6 rounded-3xl space-y-4">
                                <h3 class="font-display font-medium text-base text-walnut border-b border-line pb-2.5">Earnings Breakdowns</h3>
                                <div class="space-y-3.5 text-xs">
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <span class="text-vellum-muted">Base Salary</span>
                                        <span class="font-mono font-bold text-vellum" x-text="'₹' + record.baseSalary.toLocaleString('en-IN')"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <span class="text-vellum-muted">Allowances</span>
                                        <span class="font-mono font-bold text-vellum" x-text="'₹' + record.allowances.toLocaleString('en-IN')"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <div>
                                            <span class="text-vellum-muted">Overtime Pay</span>
                                            <span class="block text-[10px] text-vellum-faint" x-text="record.overtimeHours + ' hours worked @ 1.5x multiplier'"></span>
                                        </div>
                                        <span class="font-mono font-bold text-forest" x-text="record.overtimePay > 0 ? '+₹' + record.overtimePay.toLocaleString('en-IN') : '₹0.00'"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <div>
                                            <span class="text-vellum-muted">Bonuses & Adjustments</span>
                                            <span class="block text-[10px] text-vellum-faint">Discretionary adjustments & corrections</span>
                                        </div>
                                        <span class="font-mono font-bold text-forest" x-text="record.bonuses > 0 ? '+₹' + record.bonuses.toLocaleString('en-IN') : '₹0.00'"></span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2.5 font-bold text-walnut text-sm">
                                        <span>TOTAL GROSS EARNINGS</span>
                                        <span class="font-mono" x-text="'₹' + record.gross.toLocaleString('en-IN')"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- C. DEDUCTION SUMMARY -->
                            <div class="panel bg-cream border border-line p-6 rounded-3xl space-y-4">
                                <h3 class="font-display font-medium text-base text-walnut border-b border-line pb-2.5">Deduction Breakdowns</h3>
                                <div class="space-y-3.5 text-xs">
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <div>
                                            <span class="text-vellum-muted">Attendance Deductions</span>
                                            <span class="block text-[10px] text-vellum-faint">Deductions for late arrival thresholds or half days</span>
                                        </div>
                                        <span class="font-mono font-bold text-burgundy" x-text="record.attendanceDeductions > 0 ? '-₹' + record.attendanceDeductions.toLocaleString('en-IN') : '₹0.00'"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <div>
                                            <span class="text-vellum-muted">Unpaid Leave Deductions</span>
                                            <span class="block text-[10px] text-vellum-faint" x-text="record.unpaidLeave + ' unpaid leave days calculated'"></span>
                                        </div>
                                        <span class="font-mono font-bold text-burgundy" x-text="record.leaveDeductions > 0 ? '-₹' + record.leaveDeductions.toLocaleString('en-IN') : '₹0.00'"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <span class="text-vellum-muted">Provident Fund (PF)</span>
                                        <span class="font-mono font-bold text-burgundy" x-text="record.pf > 0 ? '-₹' + record.pf.toLocaleString('en-IN') : '₹0.00'"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <span class="text-vellum-muted">ESI Contribution</span>
                                        <span class="font-mono font-bold text-burgundy" x-text="record.esi > 0 ? '-₹' + record.esi.toLocaleString('en-IN') : '₹0.00'"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <span class="text-vellum-muted">Professional Tax</span>
                                        <span class="font-mono font-bold text-burgundy" x-text="record.profTax > 0 ? '-₹' + record.profTax.toLocaleString('en-IN') : '₹0.00'"></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-line/45">
                                        <span class="text-vellum-muted">Income Tax (TDS)</span>
                                        <span class="font-mono font-bold text-burgundy" x-text="record.taxAmt > 0 ? '-₹' + record.taxAmt.toLocaleString('en-IN') : '₹0.00'"></span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2.5 font-bold text-burgundy text-sm">
                                        <span>TOTAL DEDUCTIONS</span>
                                        <span class="font-mono" x-text="'₹' + record.deductions.toLocaleString('en-IN')"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calculation Forensic Explanation -->
                        <div class="panel bg-cream border border-line p-6 rounded-3xl space-y-3">
                            <h3 class="font-display font-medium text-base text-walnut">System Forensic Calculation Statement</h3>
                            <div class="bg-surface/50 border border-line p-4 rounded-2xl font-mono text-xs leading-relaxed text-vellum-muted" x-text="record.systemExplanation"></div>
                        </div>

                        <!-- ================= D. ATTENDANCE BASIS ================= -->
                        <div class="grid grid-cols-1 gap-6">
                            
                            <!-- Attendance Metrics Grid -->
                            <div class="panel bg-cream border border-line p-6 rounded-3xl space-y-5">
                                <h3 class="font-display font-medium text-base text-walnut border-b border-line pb-2.5">Attendance Basis Counts</h3>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Working Days</span>
                                        <span class="block font-mono text-lg font-bold text-walnut" x-text="record.workingDays + ' Days'"></span>
                                    </div>
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Present Equivalent</span>
                                        <span class="block font-mono text-lg font-bold text-forest" x-text="record.present + ' Days'"></span>
                                    </div>
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Late Arrivals</span>
                                        <span class="block font-mono text-lg font-bold text-brass-dark" x-text="record.late + ' Day(s)'"></span>
                                    </div>
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Half Days</span>
                                        <span class="block font-mono text-lg font-bold text-brass-dark" x-text="record.halfDay + ' Day(s)'"></span>
                                    </div>
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">WFH Days</span>
                                        <span class="block font-mono text-lg font-bold text-forest" x-text="record.wfh + ' Day(s)'"></span>
                                    </div>
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Overtime Hours</span>
                                        <span class="block font-mono text-lg font-bold text-forest" x-text="record.overtimeHours + ' Hrs'"></span>
                                    </div>
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Paid Leaves</span>
                                        <span class="block font-mono text-lg font-bold text-walnut" x-text="record.paidLeave + ' Day(s)'"></span>
                                    </div>
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Unpaid Leaves</span>
                                        <span class="block font-mono text-lg font-bold text-burgundy" x-text="record.unpaidLeave + ' Day(s)'"></span>
                                    </div>
                                    <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                        <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Birthday Leaves</span>
                                        <span class="block font-mono text-lg font-bold text-walnut" x-text="record.birthdayLeave + ' Day(s)'"></span>
                                    </div>
                                </div>

                                <!-- Daily Attendance Calendar Snapshot -->
                                <div class="pt-4 border-t border-line/65 space-y-4">
                                    <div>
                                        <h4 class="font-display font-medium text-sm text-walnut">Daily Attendance & Payroll Impact Calendar</h4>
                                        <p class="text-[11px] text-vellum-muted">A day-by-day lookup mapping shift states resolved by the attendance engine.</p>
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                                        <template x-for="day in record.attendanceSnapshot" :key="day.day">
                                            <div class="p-3 border border-line rounded-2xl text-center bg-surface/35 hover:border-brass/35 transition">
                                                <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono" x-text="day.date"></span>
                                                <span class="block text-[11px] font-semibold text-walnut mt-0.5" x-text="'Day ' + day.day"></span>
                                                <span class="inline-block text-[9px] px-2 py-0.5 font-bold uppercase rounded-lg mt-2 font-mono"
                                                      :class="{
                                                        present: 'bg-forest/10 text-forest border border-forest/15',
                                                        late: 'bg-brass/20 text-brass-dark border border-brass/25',
                                                        half: 'bg-brass/15 text-brass-dark border border-brass/20',
                                                        leave: 'bg-surface border border-line text-vellum-muted',
                                                        wfh: 'bg-forest/10 text-forest border border-forest/15',
                                                        absent: 'bg-burgundy/10 text-burgundy border border-burgundy/15',
                                                        off: 'bg-surface text-vellum-faint border border-line'
                                                      }[day.status]"
                                                      x-text="day.status"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dispute & Dispute History Section -->
                        <div class="grid grid-cols-1 gap-6">
                            <div class="panel bg-cream border border-line p-6 rounded-3xl space-y-4">
                                <h3 class="font-display font-medium text-base text-walnut border-b border-line pb-2.5">Dispute Resolution History</h3>
                                <div class="space-y-4">
                                    @forelse($disputes as $d)
                                        <div class="border border-line rounded-2xl p-4 text-xs space-y-2 bg-surface/30">
                                            <div class="flex justify-between items-center">
                                                <span class="font-bold text-walnut" x-text="'Category: {{ $d->category }}'"></span>
                                                <span class="px-2.5 py-0.5 rounded-lg text-[9px] font-bold font-mono uppercase tracking-wider
                                                             {{ $d->status === 'open' ? 'bg-burgundy/10 border border-burgundy/15 text-burgundy' : 'bg-forest/10 border border-forest/15 text-forest' }}">
                                                    {{ $d->status }}
                                                </span>
                                            </div>
                                            <p class="text-vellum-muted">{{ $d->description }}</p>
                                            @if($d->expected_correction)
                                                <p class="text-[11px] text-vellum-muted"><strong>Requested Correction:</strong> {{ $d->expected_correction }}</p>
                                            @endif
                                            @if($d->status === 'resolved' && $d->resolution_notes)
                                                <div class="bg-forest/5 border border-forest/10 p-2.5 rounded-xl text-[11px] text-forest">
                                                    <strong>HR Resolution Notes:</strong> {{ $d->resolution_notes }}
                                                </div>
                                            @endif
                                            <span class="text-[9px] text-vellum-faint font-mono block mt-1">Raised on {{ $d->created_at->format('d M Y, g:i A') }}</span>
                                        </div>
                                    @empty
                                        <div class="text-center py-6">
                                            <p class="text-xs text-vellum-faint italic">No disputes raised for this statement.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                    </div>
                </template>

            </main>
        </div>

        <!-- Dispute Modal -->
        <div x-show="openDisputeModal" class="fixed inset-0 overflow-hidden z-50 flex items-center justify-center" x-cloak>
            <div class="absolute inset-0 bg-walnut/40 backdrop-blur-sm" @click="openDisputeModal = false"></div>
            <div class="bg-cream border border-line rounded-2xl p-6 shadow-lift max-w-md w-full relative z-10 space-y-4">
                <h3 class="font-display font-medium text-lg text-vellum">Raise Payout Dispute</h3>
                <p class="text-xs text-vellum-muted leading-relaxed">
                    Explain any attendance overrides, missing leaves, or salary component mismatches. Confirm details to submit to HR/Admin.
                </p>
                <form method="POST" action="{{ route('employee.payroll.dispute') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="record_id" :value="record ? record.record_id : ''">
                    
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Dispute Category</label>
                        <select name="category" required class="w-full text-xs bg-cream border border-line rounded px-3 py-2 text-vellum outline-none">
                            <option value="Attendance">Attendance mismatch</option>
                            <option value="Leave">Approved leave deduction</option>
                            <option value="Salary">Incorrect Base Salary / allowances</option>
                            <option value="Deduction">Other incorrect deduction</option>
                            <option value="Other">Other problem</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Affected Date (Optional)</label>
                        <input type="date" name="affected_date" class="w-full text-xs bg-cream border border-line rounded px-3 py-2 text-vellum outline-none">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Explain the Issue</label>
                        <textarea name="description" rows="3" required placeholder="Provide clear dates and details..."
                                  class="w-full text-xs bg-cream border border-line rounded px-3 py-2 text-vellum outline-none"></textarea>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Expected Correction / Solution</label>
                        <textarea name="expected_correction" rows="2" required placeholder="What needs to be corrected? (e.g. mark June 15 as present)"
                                  class="w-full text-xs bg-cream border border-line rounded px-3 py-2 text-vellum outline-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="openDisputeModal = false" class="px-4 py-2 border border-hairline text-vellum rounded-xl text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-burgundy hover:bg-burgundy-dark text-cream font-bold uppercase tracking-wider rounded-xl text-xs">Submit Dispute</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Alpine App Logic Script -->
    <script>
        function employeePayrollApp() {
            return {
                selectedPeriod: '{{ $period }}',
                allPeriods: @json($allPeriods),
                record: @json($record),
                openDisputeModal: false,
                donutColors: ['#C6941C', '#6B2039', '#1E3D30', '#A85D1E', '#8A7B5C'],

                init() {
                    // console.log("Init Employee Payroll Hub");
                },

                changePeriod() {
                    window.location.href = `/my-payroll?period=${this.selectedPeriod}`;
                },

                ledgerFormulaRows(e) {
                    if (!e) return [];
                    return [
                        { label: 'Base Salary', value: e.baseSalary, explain: 'Authoritative fixed monthly base salary.', calc: 'Base = ₹' + e.baseSalary.toLocaleString('en-IN') },
                        { label: 'Allowances', value: e.allowances, explain: 'Retrieved structural standard allowances.', calc: 'Allowances = ₹' + e.allowances.toLocaleString('en-IN') },
                        { label: 'Daily Rate (Segment)', value: e.dailyRate, explain: 'Derived basic daily rate for segment month (' + e.calendarDays + ' calendar days).', calc: 'Daily = ₹' + e.dailyRate.toLocaleString('en-IN') },
                        { label: 'Hourly Shift Rate', value: e.hourlyRate, explain: 'Derived basic hourly shift rate (daily rate / 8).', calc: 'Hourly = ₹' + e.hourlyRate.toLocaleString('en-IN') },
                        { label: 'Leave Deductions', value: -(e.unpaidLeave * e.dailyRate), tone: 'oxblood', explain: e.unpaidLeave + ' unpaid day(s) deducted, per Unplanned Leave policy.', calc: e.unpaidLeave + ' × ₹' + e.dailyRate.toLocaleString('en-IN') },
                        { label: 'Half Day Deductions', value: -(e.halfDay * Math.round(e.dailyRate / 2)), tone: 'oxblood', explain: e.halfDay + ' half day(s) deducted, per Payroll Mapping.', calc: e.halfDay + ' × ₹' + Math.round(e.dailyRate / 2).toLocaleString('en-IN') },
                        { label: 'Overtime Pay', value: e.overtimeHours * Math.round(e.hourlyRate * 1.5), tone: 'forest', explain: e.overtimeHours + ' overtime hour(s) at 1.5x multiplier.', calc: e.overtimeHours + ' × ₹' + Math.round(e.hourlyRate * 1.5) },
                        { label: 'Bonuses & Adjustments', value: e.bonuses, tone: 'forest', explain: 'Manual corrections or discretionary adjustments applied by admin.', calc: 'Approved corrections' },
                        { label: 'Gross Salary', value: e.gross, emphasis: true },
                        { label: 'Tax (TDS)', value: -e.taxAmt, tone: 'oxblood', explain: 'TDS tax deduction (5% rate).', calc: 'TDS slab applied = –₹' + e.taxAmt.toLocaleString('en-IN') },
                        { label: 'Provident Fund', value: -e.pf, tone: 'oxblood', explain: 'PF contribution (12% of basic up to ceiling).', calc: 'PF rate = –₹' + e.pf.toLocaleString('en-IN') },
                        { label: 'ESI', value: -e.esi, tone: 'oxblood', explain: 'ESI contribution (0.75% of gross).', calc: 'ESI rate = –₹' + e.esi.toLocaleString('en-IN') },
                        { label: 'Professional Tax', value: -e.profTax, tone: 'oxblood', explain: 'Flat professional tax for Uttarakhand.', calc: 'PTAX = –₹' + e.profTax.toLocaleString('en-IN') },
                        { label: 'Net Salary', value: e.net, emphasis: true }
                    ];
                }
            }
        }
    </script>
@endsection
