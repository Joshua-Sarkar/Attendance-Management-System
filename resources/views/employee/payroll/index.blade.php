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
                    <p class="font-bold text-brass uppercase">Need Assistance?</p>
                    <p>Contact Payroll Administration if you notice discrepancies in your statement.</p>
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
                            <span>Self-Service Portal</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <select x-model="selectedPeriod" @change="changePeriod()"
                                class="text-[12.5px] bg-cream border border-line rounded-xl px-3.5 py-2 outline-none focus:border-brass font-mono font-semibold text-walnut">
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

            <main class="px-5 lg:px-9 py-7 space-y-7 flex-1">
                
                @if (session('success'))
                    <div class="p-4 bg-forest/10 border border-forest/20 text-forest rounded-2xl text-xs font-mono flex items-center gap-2">
                        <span>✓</span> <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="p-4 bg-burgundy/10 border border-burgundy/20 text-burgundy rounded-2xl text-xs font-mono flex items-center gap-2">
                        <span>⚠</span> <span>{{ session('error') }}</span>
                    </div>
                @endif

                <template x-if="!record">
                    <div class="text-center py-16 panel bg-cream border border-line rounded-3xl space-y-2 shadow-soft">
                        <p class="text-vellum-faint italic font-display text-lg">No payroll statement generated for <span x-text="selectedPeriod"></span>.</p>
                        <p class="text-xs text-vellum-muted max-w-md mx-auto">Calculations will appear here once HR/Admin processes the month's attendance logs.</p>
                    </div>
                </template>

                <template x-if="record">
                    <div class="space-y-7">
                        
                        <!-- ================= 1. PAYROLL HERO SUMMARY ================= -->
                        <div class="panel bg-cream border border-line p-7 lg:p-8 rounded-3xl relative overflow-hidden shadow-soft">
                            <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-brass/5"></div>
                            
                            <div class="relative flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                                <div class="space-y-2.5">
                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-brass font-mono bg-brass-light/20 border border-brass/25 px-3 py-1 rounded-full">
                                            Payroll Statement
                                        </span>
                                        <span class="px-3 py-1 rounded-full text-[10px] font-mono font-bold border inline-flex items-center gap-1.5"
                                              :class="record.locked ? 'bg-burgundy/10 text-burgundy border-burgundy/20' : (record.employee_review_status === 'approved' ? 'bg-forest/10 text-forest border-forest/20' : 'bg-brass/10 text-brass-dark border-brass/25')">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="record.locked ? 'bg-burgundy' : (record.employee_review_status === 'approved' ? 'bg-forest' : 'bg-brass-dark')"></span>
                                            <span x-text="record.locked ? 'LOCKED & FINALISED' : (record.employee_review_status === 'approved' ? 'EMPLOYEE APPROVED' : 'REVIEW PENDING')"></span>
                                        </span>
                                    </div>
                                    <h2 class="font-display text-3xl font-bold text-walnut" x-text="selectedPeriod"></h2>
                                    <p class="text-xs text-vellum-faint">
                                        Employee: <span class="font-semibold text-walnut" x-text="record.name"></span>
                                        <span class="mx-1 text-vellum-faint">·</span>
                                        ID: <span class="font-mono text-vellum" x-text="record.id"></span>
                                        <span class="mx-1 text-vellum-faint">·</span>
                                        Dept: <span class="font-semibold text-walnut" x-text="record.dept"></span>
                                    </p>
                                </div>

                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 lg:text-right">
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-bold text-vellum-faint uppercase tracking-wider font-mono">Net Disbursement Payout</p>
                                        <p class="font-display text-4xl font-black text-forest tracking-tight" x-text="'₹' + record.net.toLocaleString('en-IN')"></p>
                                        <p class="text-[11px] text-vellum-muted">Scheduled Disbursement: <span class="font-semibold text-walnut">07th of next month</span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Pills Bar -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6 pt-6 border-t border-line/60 text-xs">
                                <div class="bg-surface/50 border border-line p-3 rounded-2xl">
                                    <span class="block text-[9.5px] font-mono uppercase font-bold text-vellum-faint">Base Salary</span>
                                    <span class="font-mono font-bold text-walnut text-sm mt-0.5 block" x-text="'₹' + record.baseSalary.toLocaleString('en-IN')"></span>
                                </div>
                                <div class="bg-surface/50 border border-line p-3 rounded-2xl">
                                    <span class="block text-[9.5px] font-mono uppercase font-bold text-vellum-faint">Total Deductions</span>
                                    <span class="font-mono font-bold text-burgundy text-sm mt-0.5 block" x-text="'-₹' + record.deductions.toLocaleString('en-IN')"></span>
                                </div>
                                <div class="bg-surface/50 border border-line p-3 rounded-2xl">
                                    <span class="block text-[9.5px] font-mono uppercase font-bold text-vellum-faint">Statement Version</span>
                                    <span class="font-mono font-bold text-brass-dark text-sm mt-0.5 block" x-text="'v' + record.calculation_version"></span>
                                </div>
                            </div>
                        </div>

                        <!-- ================= 2. WORKFLOW STEPPER & APPROVAL CARD ================= -->
                        <div class="panel bg-cream border border-line p-6 lg:p-7 rounded-3xl space-y-6 shadow-soft">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-line pb-4">
                                <div>
                                    <h3 class="font-display font-bold text-base text-walnut">Payroll Approval & Sign-Off Lifecycle</h3>
                                    <p class="text-[11px] text-vellum-muted">Track stage progression from attendance resolution to final disbursement lock.</p>
                                </div>
                                <span class="px-3 py-1 rounded-xl text-[10px] font-bold font-mono uppercase tracking-wider border"
                                      :class="{
                                          approved: 'bg-forest/10 border-forest/20 text-forest',
                                          pending: 'bg-brass/10 border-brass/20 text-brass-dark',
                                          stale: 'bg-burgundy/10 border-burgundy/20 text-burgundy',
                                          disputed: 'bg-burgundy/10 border-burgundy/20 text-burgundy'
                                      }[record.employee_review_status]"
                                      x-text="'Review State: ' + record.employee_review_status"></span>
                            </div>

                            <!-- 4-Stage Horizontal Stepper -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Stage 1 -->
                                <div class="p-4 bg-surface/40 border border-line rounded-2xl space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-mono font-bold text-vellum-faint">STAGE 1</span>
                                        <span class="text-forest text-[11px] font-bold">✓ Ready</span>
                                    </div>
                                    <p class="text-xs font-bold text-walnut">Attendance Basis</p>
                                    <p class="text-[10px] text-vellum-muted">Shifts, check-ins, and leave requests resolved.</p>
                                </div>

                                <!-- Stage 2 -->
                                <div class="p-4 bg-surface/40 border border-line rounded-2xl space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-mono font-bold text-vellum-faint">STAGE 2</span>
                                        <span class="text-forest text-[11px] font-bold">✓ Generated</span>
                                    </div>
                                    <p class="text-xs font-bold text-walnut">Payroll Calculation</p>
                                    <p class="text-[10px] text-vellum-muted">Base salary and attendance deductions mapped.</p>
                                </div>

                                <!-- Stage 3 -->
                                <div class="p-4 border rounded-2xl space-y-1.5"
                                     :class="record.employee_review_status === 'approved' ? 'bg-forest/5 border-forest/25' : (record.employee_review_status === 'disputed' ? 'bg-burgundy/5 border-burgundy/25' : 'bg-brass/5 border-brass/25')">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-mono font-bold text-vellum-faint">STAGE 3</span>
                                        <span class="text-[11px] font-bold"
                                              :class="record.employee_review_status === 'approved' ? 'text-forest' : (record.employee_review_status === 'disputed' ? 'text-burgundy' : 'text-brass-dark')"
                                              x-text="record.employee_review_status === 'approved' ? '✓ Approved' : (record.employee_review_status === 'disputed' ? '⚠ Disputed' : '• Action Required')"></span>
                                    </div>
                                    <p class="text-xs font-bold text-walnut">Employee Sign-off</p>
                                    <p class="text-[10px] text-vellum-muted"
                                       x-text="record.employee_approved_at ? 'Signed off on ' + record.employee_approved_at : (record.employee_review_status === 'disputed' ? 'Dispute raised for HR review' : 'Please review and confirm calculation')"></p>
                                </div>

                                <!-- Stage 4 -->
                                <div class="p-4 border rounded-2xl space-y-1.5"
                                     :class="record.locked ? 'bg-forest/5 border-forest/25' : 'bg-surface/30 border-line'">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-mono font-bold text-vellum-faint">STAGE 4</span>
                                        <span class="text-[11px] font-bold" :class="record.locked ? 'text-forest' : 'text-vellum-faint'" x-text="record.locked ? '🔒 Locked' : 'Pending Lock'"></span>
                                    </div>
                                    <p class="text-xs font-bold text-walnut">Admin Lock & Release</p>
                                    <p class="text-[10px] text-vellum-muted" x-text="record.locked ? 'Finalized for disbursement' : 'Awaiting HR/Admin cycle lock'"></p>
                                </div>
                            </div>

                            <!-- Stepper Actions Box -->
                            <template x-if="record.employee_review_status !== 'approved' && !record.locked">
                                <div class="bg-surface/60 border border-brass/30 p-5 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="space-y-1 max-w-lg">
                                        <p class="text-xs font-bold text-walnut">Employee Sign-Off Required</p>
                                        <p class="text-[11.5px] text-vellum-muted leading-relaxed">
                                            Review the earnings breakdown, attendance basis metrics, and daily log snapshot below. Click <span class="font-bold text-forest">Confirm & Approve</span> if accurate, or <span class="font-bold text-burgundy">Raise Dispute</span> if there is an error.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto">
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
                                <div class="bg-forest/5 border border-forest/20 p-4.5 rounded-2xl text-[12px] text-forest flex items-start gap-3">
                                    <span class="text-lg">✓</span>
                                    <div>
                                        <p class="font-bold">You approved this payroll statement on <span x-text="record.employee_approved_at"></span>.</p>
                                        <p class="text-[11px] text-forest/80 mt-0.5">Your sign-off has been registered. The statement is now awaiting final administrative lock by HR before payslip release.</p>
                                    </div>
                                </div>
                            </template>

                            <template x-if="record.locked">
                                <div class="bg-surface/75 border border-line p-4.5 rounded-2xl text-[12px] text-vellum-muted flex items-start gap-3">
                                    <span class="text-lg">🔒</span>
                                    <div>
                                        <p class="font-bold text-walnut">Payroll Statement Locked & Immutable.</p>
                                        <p class="text-[11px] text-vellum-muted mt-0.5">This statement has been sealed by Payroll Administration. Calculations are frozen for financial disbursement.</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- ================= 3. PAYSLIP RELEASE CARD ================= -->
                        <div class="panel bg-cream border border-line p-6 rounded-3xl shadow-soft flex flex-col sm:flex-row justify-between items-start sm:items-center gap-5">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-display font-bold text-base text-walnut">Official Payslip Document</h3>
                                    <span class="inline-flex items-center gap-1.5 text-[9.5px] px-2.5 py-0.5 rounded-full border font-bold uppercase tracking-wider font-mono"
                                          :class="record.locked ? 'bg-forest/10 text-forest border-forest/20' : 'bg-brass/10 text-brass-dark border-brass/25'"
                                          x-text="record.locked ? 'PAYSLIP RELEASED' : 'PENDING CYCLE LOCK'">
                                    </span>
                                </div>
                                <p class="text-[11.5px] text-vellum-muted"
                                   x-text="record.locked ? 'Your official signed payslip PDF is ready for download.' : 'Payslips become available for download immediately after HR/Admin locks the payroll cycle.'"></p>
                            </div>
                            
                            <div class="shrink-0">
                                <template x-if="record.locked">
                                    <a :href="'/my-payslip/' + record.record_id + '/download'" 
                                       class="inline-flex items-center gap-2.5 text-xs font-bold uppercase tracking-wider font-mono bg-forest hover:bg-forest-dark text-cream px-6 py-3 rounded-xl transition shadow-soft">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download Payslip PDF
                                    </a>
                                </template>
                                <template x-if="!record.locked">
                                    <button disabled
                                            class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider font-mono bg-surface text-vellum-faint border border-line px-5 py-3 rounded-xl cursor-not-allowed opacity-75">
                                        <svg class="w-4 h-4 text-vellum-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Payslip Locked — Pending Admin Lock
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- ================= 4. EARNINGS & DEDUCTIONS BREAKDOWN ================= -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- SALARY SUMMARY CARD -->
                            <div class="panel bg-cream border border-line p-6 rounded-3xl shadow-soft flex flex-col justify-between space-y-5">
                                <div>
                                    <div class="flex justify-between items-center border-b border-line pb-3">
                                        <h3 class="font-display font-bold text-base text-walnut">Salary Summary</h3>
                                        <span class="text-[10px] font-mono font-bold text-forest uppercase tracking-wider bg-forest/10 px-2.5 py-0.5 rounded">Statement Overview</span>
                                    </div>
                                    <div class="mt-4 space-y-3 text-xs">
                                        <div class="flex justify-between items-center py-1 border-b border-line/40">
                                            <div>
                                                <span class="text-vellum-muted font-semibold block">Base Salary</span>
                                                <span class="text-[10px] text-vellum-faint">Fixed contractual base component</span>
                                            </div>
                                            <span class="font-mono font-bold text-walnut text-sm" x-text="'₹' + record.baseSalary.toLocaleString('en-IN')"></span>
                                        </div>
                                        <div class="flex justify-between items-center py-1 border-b border-line/40">
                                            <div>
                                                <span class="text-vellum-muted font-semibold block">Attendance Deductions</span>
                                                <span class="text-[10px] text-vellum-faint">Deductions resolved from attendance ledger</span>
                                            </div>
                                            <span class="font-mono font-bold text-burgundy text-sm" x-text="'-₹' + record.deductions.toLocaleString('en-IN')"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-line/70 flex justify-between items-center font-bold text-walnut text-sm">
                                    <span>NET DISBURSEMENT</span>
                                    <span class="font-mono text-forest text-base" x-text="'₹' + record.net.toLocaleString('en-IN')"></span>
                                </div>
                            </div>

                            <!-- ATTENDANCE DEDUCTIONS BREAKDOWN CARD -->
                            <div class="panel bg-cream border border-line p-6 rounded-3xl shadow-soft flex flex-col justify-between space-y-5">
                                <div>
                                    <div class="flex justify-between items-center border-b border-line pb-3">
                                        <h3 class="font-display font-bold text-base text-walnut">Attendance Deductions</h3>
                                        <span class="text-[10px] font-mono font-bold text-burgundy uppercase tracking-wider bg-burgundy/10 px-2.5 py-0.5 rounded">Absence & Penalty Components</span>
                                    </div>
                                    <div class="mt-4 space-y-3 text-xs" x-if="record.deductionBreakdown">
                                        <!-- Half Days -->
                                        <div class="flex justify-between items-center py-1 border-b border-line/40">
                                            <div>
                                                <span class="text-vellum-muted font-semibold block">Half Days</span>
                                                <span class="text-[10px] text-vellum-faint" x-text="record.deductionBreakdown.half_days.quantity + ' day(s) @ ₹' + record.deductionBreakdown.half_days.rate.toLocaleString('en-IN') + '/day'"></span>
                                            </div>
                                            <span class="font-mono font-bold text-burgundy text-sm" x-text="record.deductionBreakdown.half_days.amount > 0 ? '-₹' + record.deductionBreakdown.half_days.amount.toLocaleString('en-IN') : '₹0.00'"></span>
                                        </div>

                                        <!-- Unpaid Leave Days -->
                                        <div class="flex justify-between items-center py-1 border-b border-line/40">
                                            <div>
                                                <span class="text-vellum-muted font-semibold block">Unpaid Leave Days</span>
                                                <span class="text-[10px] text-vellum-faint" x-text="record.deductionBreakdown.unpaid_leaves.quantity + ' day(s) @ ₹' + record.deductionBreakdown.unpaid_leaves.rate.toLocaleString('en-IN') + '/day'"></span>
                                            </div>
                                            <span class="font-mono font-bold text-burgundy text-sm" x-text="record.deductionBreakdown.unpaid_leaves.amount > 0 ? '-₹' + record.deductionBreakdown.unpaid_leaves.amount.toLocaleString('en-IN') : '₹0.00'"></span>
                                        </div>

                                        <!-- Late Penalties -->
                                        <div class="flex justify-between items-center py-1 border-b border-line/40">
                                            <div>
                                                <span class="text-vellum-muted font-semibold block">Late Penalties</span>
                                                <span class="text-[10px] text-vellum-faint" x-text="record.deductionBreakdown.late_penalties.quantity + ' late arrival instance(s)'"></span>
                                            </div>
                                            <span class="font-mono font-bold text-burgundy text-sm" x-text="record.deductionBreakdown.late_penalties.amount > 0 ? '-₹' + record.deductionBreakdown.late_penalties.amount.toLocaleString('en-IN') : '₹0.00'"></span>
                                        </div>

                                        <!-- Override Adjustments -->
                                        <div class="flex justify-between items-center py-1 border-b border-line/40">
                                            <div>
                                                <span class="text-vellum-muted font-semibold block">Override Adjustments</span>
                                                <span class="text-[10px] text-vellum-faint" x-text="record.deductionBreakdown.override_adjustments.quantity + ' attendance ledger override(s)'"></span>
                                            </div>
                                            <span class="font-mono font-bold text-burgundy text-sm" x-text="record.deductionBreakdown.override_adjustments.amount > 0 ? '-₹' + record.deductionBreakdown.override_adjustments.amount.toLocaleString('en-IN') : '₹0.00'"></span>
                                        </div>

                                        <!-- Manual Adjustments -->
                                        <template x-if="record.deductionBreakdown.manual_adjustments.amount > 0">
                                            <div class="flex justify-between items-center py-1 border-b border-line/40">
                                                <div>
                                                    <span class="text-vellum-muted font-semibold block">Manual Payroll Adjustments</span>
                                                    <span class="text-[10px] text-vellum-faint">Admin approved payroll correction</span>
                                                </div>
                                                <span class="font-mono font-bold text-burgundy text-sm" x-text="'-₹' + record.deductionBreakdown.manual_adjustments.amount.toLocaleString('en-IN')"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Itemized Dates Accordion / Summary -->
                                <template x-if="record.deductionBreakdown && record.deductionBreakdown.itemized_dates.length > 0">
                                    <div class="pt-2 border-t border-line/40">
                                        <p class="text-[10px] font-bold text-vellum-faint uppercase font-mono mb-1.5">Deduction Dates Log:</p>
                                        <div class="max-h-24 overflow-y-auto space-y-1 pr-1">
                                            <template x-for="item in record.deductionBreakdown.itemized_dates" :key="item.date + item.type">
                                                <div class="flex justify-between items-center text-[10.5px] bg-surface/50 px-2.5 py-1 rounded border border-line/30">
                                                    <span class="font-mono font-semibold text-walnut" x-text="item.date"></span>
                                                    <span class="text-vellum-muted" x-text="item.type"></span>
                                                    <span class="font-mono font-bold text-burgundy" x-text="'-₹' + item.amount.toLocaleString('en-IN')"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <div class="pt-3 border-t border-line/70 flex justify-between items-center font-bold text-burgundy text-sm">
                                    <span>TOTAL ATTENDANCE DEDUCTIONS</span>
                                    <span class="font-mono text-base" x-text="'-₹' + record.deductions.toLocaleString('en-IN')"></span>
                                </div>
                            </div>
                        </div>

                        <!-- ================= 5. ATTENDANCE BASIS & IMPACT SUMMARY ================= -->
                        <div class="panel bg-cream border border-line p-6 lg:p-7 rounded-3xl space-y-6 shadow-soft">
                            <div class="border-b border-line pb-3">
                                <h3 class="font-display font-bold text-base text-walnut">Attendance Basis Metrics</h3>
                                <p class="text-[11px] text-vellum-muted">Summary of shift counts and attendance metrics resolved by the engine for this cycle.</p>
                            </div>
                            
                             <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                                <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                    <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Working Days</span>
                                    <span class="block font-mono text-lg font-bold text-walnut" x-text="record.workingDays + ' Days'"></span>
                                </div>
                                <div class="bg-surface/50 border border-line p-3.5 rounded-2xl text-center space-y-1">
                                    <span class="block text-[9px] font-bold text-vellum-faint uppercase font-mono tracking-wider">Present Days</span>
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
                            </div>

                            <!-- Daily Attendance Snapshot Grid -->
                            <div class="pt-4 border-t border-line/60 space-y-4">
                                <div>
                                    <h4 class="font-display font-bold text-sm text-walnut">Daily Attendance & Deduction Breakdown</h4>
                                    <p class="text-[11px] text-vellum-muted">Day-by-day shift states and direct financial deduction impacts.</p>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                                    <template x-for="day in record.attendanceSnapshot" :key="day.day">
                                        <div class="p-3 border border-line rounded-2xl text-center bg-surface/35 hover:border-brass/35 transition flex flex-col justify-between min-h-[105px]">
                                            <div>
                                                 <div class="flex justify-between items-center text-[9px] font-bold text-vellum-faint uppercase font-mono">
                                                     <span x-text="day.date"></span>
                                                 </div>
                                                 <span class="block text-[11px] font-semibold text-walnut mt-0.5" x-text="'Day ' + day.day"></span>
                                            </div>
                                            
                                             <div class="mt-2">
                                                 <span class="inline-block text-[8.5px] px-2 py-0.5 font-bold uppercase rounded-lg font-mono border"
                                                       :class="{
                                                          present: 'bg-forest/10 text-forest border-forest/15',
                                                          wfh: 'bg-forest/10 text-forest border-forest/15',
                                                          bday: 'bg-forest/10 text-forest border-forest/15',
                                                          planned: 'bg-forest/10 text-forest border-forest/15',
                                                          late: 'bg-brass/25 text-brass-dark border border-brass/35',
                                                          half: 'bg-brass/20 text-brass-dark border border-brass/30',
                                                          hdp: 'bg-brass/20 text-brass-dark border border-brass/30',
                                                          hd_upa: 'bg-burgundy/10 text-burgundy border-burgundy/15',
                                                          hd_upr: 'bg-burgundy/10 text-burgundy border-burgundy/15',
                                                          absent: 'bg-burgundy/10 text-burgundy border-burgundy/15',
                                                          upr: 'bg-burgundy/10 text-burgundy border-burgundy/15',
                                                          upa: 'bg-burgundy/10 text-burgundy border-burgundy/15',
                                                          off: 'bg-surface text-vellum-faint border border-line'
                                                       }[day.status]"
                                                       x-text="day.status"></span>
                                                 
                                                 <span class="block text-[9.5px] mt-1 font-mono"
                                                       :class="day.deducted_amount > 0 ? 'text-burgundy font-bold' : 'text-vellum-faint'"
                                                       x-text="day.deducted_amount > 0 ? '–₹' + day.deducted_amount.toLocaleString('en-IN') : (day.status === 'off' ? 'Off Day' : 'Paid')">
                                                 </span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- ================= 6. DISPUTE RESOLUTION HISTORY ================= -->
                        <div class="panel bg-cream border border-line p-6 rounded-3xl space-y-4 shadow-soft">
                            <h3 class="font-display font-bold text-base text-walnut border-b border-line pb-3">Dispute Resolution History</h3>
                            <div class="space-y-3">
                                @forelse($disputes as $d)
                                    <div class="border border-line rounded-2xl p-4 text-xs space-y-2 bg-surface/30">
                                        <div class="flex justify-between items-center">
                                            <span class="font-bold text-walnut text-sm">Category: {{ $d->category }}</span>
                                            <span class="px-2.5 py-0.5 rounded-lg text-[9px] font-bold font-mono uppercase tracking-wider
                                                         {{ $d->status === 'open' ? 'bg-burgundy/10 border border-burgundy/15 text-burgundy' : 'bg-forest/10 border border-forest/15 text-forest' }}">
                                                {{ $d->status }}
                                            </span>
                                        </div>
                                        <p class="text-vellum-muted leading-relaxed">{{ $d->description }}</p>
                                        @if($d->expected_correction)
                                            <p class="text-[11px] text-vellum-muted"><strong>Requested Solution:</strong> {{ $d->expected_correction }}</p>
                                        @endif
                                        @if($d->status === 'resolved' && $d->resolution_notes)
                                            <div class="bg-forest/5 border border-forest/15 p-3 rounded-xl text-[11px] text-forest space-y-0.5">
                                                <strong class="block font-bold">HR Resolution Notes:</strong>
                                                <p>{{ $d->resolution_notes }}</p>
                                            </div>
                                        @endif
                                        <span class="text-[9.5px] text-vellum-faint font-mono block pt-1">Submitted on {{ $d->created_at->format('d M Y, g:i A') }}</span>
                                    </div>
                                @empty
                                    <div class="text-center py-6">
                                        <p class="text-xs text-vellum-faint italic">No disputes submitted for this payroll period.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </template>

            </main>
        </div>

        <!-- ============ DISPUTE MODAL ============ -->
        <div x-show="openDisputeModal" class="fixed inset-0 overflow-hidden z-50 flex items-center justify-center p-4" x-cloak>
            <div class="absolute inset-0 bg-walnut/40 backdrop-blur-sm" @click="openDisputeModal = false"></div>
            <div class="bg-cream border border-line rounded-3xl p-6 sm:p-7 shadow-lift max-w-md w-full relative z-10 space-y-4">
                <div>
                    <h3 class="font-display font-bold text-lg text-walnut">Raise Payroll Dispute</h3>
                    <p class="text-xs text-vellum-muted leading-relaxed mt-0.5">
                        Submit any attendance mismatches, missing leave credits, or calculation issues to HR/Admin.
                    </p>
                </div>
                <form method="POST" action="{{ route('employee.payroll.dispute') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="record_id" :value="record ? record.record_id : ''">
                    
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Dispute Category</label>
                        <select name="category" required class="w-full text-xs bg-cream border border-line rounded-xl px-3.5 py-2.5 text-vellum outline-none focus:border-brass">
                            <option value="Attendance">Attendance Mismatch</option>
                            <option value="Leave">Approved Leave Deduction</option>
                            <option value="Salary">Incorrect Base Salary / Allowances</option>
                            <option value="Deduction">Other Incorrect Deduction</option>
                            <option value="Other">Other Issues</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Affected Date (Optional)</label>
                        <input type="date" name="affected_date" class="w-full text-xs bg-cream border border-line rounded-xl px-3.5 py-2 text-vellum outline-none focus:border-brass">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Explain the Issue</label>
                        <textarea name="description" rows="3" required placeholder="Provide clear dates and details..."
                                  class="w-full text-xs bg-cream border border-line rounded-xl px-3.5 py-2 text-vellum outline-none focus:border-brass"></textarea>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Expected Correction / Solution</label>
                        <textarea name="expected_correction" rows="2" required placeholder="What needs to be corrected? (e.g., mark June 15 as present)"
                                  class="w-full text-xs bg-cream border border-line rounded-xl px-3.5 py-2 text-vellum outline-none focus:border-brass"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="openDisputeModal = false" class="px-4 py-2 border border-hairline text-vellum rounded-xl text-xs font-semibold">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 bg-burgundy hover:bg-burgundy-dark text-cream font-bold uppercase tracking-wider rounded-xl text-xs shadow-soft">Submit Dispute</button>
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

                init() {
                    // Init Employee Payroll Hub
                },

                changePeriod() {
                    window.location.href = `/my-payroll?period=${this.selectedPeriod}`;
                }
            }
        }
    </script>
@endsection
