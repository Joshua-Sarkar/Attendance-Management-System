@extends('layouts.payroll')

@section('content')
    <!-- MAIN WORKSPACE -->
    <div x-data="payrollApp()" x-init="init()" class="flex min-h-screen">
        
        <!-- ============ SIDEBAR ============ -->
        <aside class="hidden lg:flex flex-col w-[252px] shrink-0 bg-walnut border-r border-walnut-light sticky top-0 h-screen">
            <div class="px-6 pt-7 pb-6 border-b border-walnut-light">
                <p class="font-display text-[22px] leading-none tracking-tight text-cream">AMS <span class="text-brass">·</span> V1</p>
                <p class="text-[11px] uppercase tracking-[0.14em] text-brass mt-1.5 font-semibold">Payroll Control Center</p>
            </div>
            
            <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
                <a href="{{ route('admin.payroll.dashboard') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'dashboard' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'dashboard' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Dashboard</span>
                </a>
                
                <a href="{{ route('admin.payroll.employees') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'employees' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'employees' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Employee Payroll</span>
                </a>
                
                <a href="{{ route('admin.payroll.ledger') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'ledger' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'ledger' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Salary Ledger</span>
                </a>
                
                <a href="{{ route('admin.payroll.corrections') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'corrections' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'corrections' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Corrections</span>
                </a>
                
                <a href="{{ route('admin.payroll.exceptions') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'exceptions' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'exceptions' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Exceptions</span>
                    <span x-show="exceptionsFlat.filter(e => !e.resolved).length > 0"
                          x-text="exceptionsFlat.filter(e => !e.resolved).length"
                          class="text-[10.5px] px-1.5 py-0.5 rounded-full bg-oxblood text-cream font-bold"></span>
                </a>
                
                <a href="{{ route('admin.payroll.lock') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'lock' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'lock' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Payroll Lock</span>
                </a>
                
                <a href="{{ route('admin.payroll.payslips') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'payslips' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'payslips' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Payslips</span>
                </a>
                
                <a href="{{ route('admin.payroll.audit') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'audit' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'audit' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Audit Trail</span>
                </a>
                
                <a href="{{ route('admin.payroll.reports') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'reports' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'reports' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Reports</span>
                </a>
                
                <a href="{{ route('admin.payroll.settings') }}"
                   class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[13.5px] transition-colors"
                   :class="activeTab === 'settings' ? 'bg-brass text-walnut font-medium shadow-soft' : 'text-cream/65 hover:bg-walnut-light hover:text-cream'">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="activeTab === 'settings' ? 'bg-walnut' : 'bg-brass/60'"></span>
                    <span class="flex-1 text-left">Configuration Center</span>
                </a>
            </nav>
            
            <div class="px-4 py-5 border-t border-walnut-light">
                <div class="rounded-2xl bg-walnut-light/70 border border-brass/25 p-4">
                    <p class="text-[11px] uppercase tracking-wider text-brass font-semibold">Current Cycle</p>
                    <p class="font-display text-[17px] mt-1 text-cream" x-text="cycle.period"></p>
                    <div class="flex items-center gap-1.5 mt-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-forest-light bg-forest"></span>
                        <p class="text-[12px] text-cream/60 capitalize" x-text="cycle.statusLabel"></p>
                    </div>
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
                        <h1 class="font-display text-[19px] truncate text-vellum" x-text="currentNavLabel"></h1>
                        <span class="hidden sm:inline-flex items-center gap-1.5 text-[11.5px] px-2.5 py-1 rounded-full border bg-brass-light/30 text-brass border-brass/30 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-brass"></span>
                            <span x-text="cycle.statusLabel"></span>
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative hidden md:block">
                            <input type="text" placeholder="Search employees, IDs, departments…" x-model="globalSearch"
                                   @keydown.enter="activeTab='employees'; filters.search=globalSearch"
                                   class="w-72 text-[13px] bg-cream border border-line rounded-xl pl-9 pr-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none placeholder:text-ink-faint">
                            <svg class="w-4 h-4 absolute left-3 top-2.5 text-ink-faint" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                            </svg>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-[11px] text-vellum-faint uppercase tracking-wider">Dehradun, UK</span>
                            <button class="w-9 h-9 rounded-full bg-brass-dark text-cream font-display text-[13px] flex items-center justify-center">RS</button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="px-5 lg:px-9 py-7 space-y-8 flex-1">
                
                <!-- Toast Messages -->
                <div x-show="toast" x-transition.opacity class="fixed bottom-6 right-6 bg-walnut text-cream border border-brass/30 px-5 py-3 rounded-xl shadow-lift z-50 flex items-center gap-3" x-cloak>
                    <div class="w-2 h-2 rounded-full bg-brass animate-pulse"></div>
                    <span x-text="toast" class="text-xs font-mono"></span>
                    <button @click="toast = null" class="text-cream/40 hover:text-cream ml-4">&times;</button>
                </div>

                <!-- ================= 1. DASHBOARD ================= -->
                <div x-show="activeTab === 'dashboard'" x-cloak class="fade-in space-y-7">
                    <!-- Hero Header Card -->
                    <div class="rounded-3xl bg-cream border border-line shadow-card p-8 relative overflow-hidden">
                        <div class="absolute -right-16 -top-16 w-72 h-72 rounded-full bg-brass/5"></div>
                        <div class="relative flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.16em] text-brass font-semibold mb-2">Disbursement Overview</p>
                                <h2 class="font-display text-[32px] leading-tight text-vellum" x-text="cycle.period + ' Disbursement Run'"></h2>
                                <p class="text-[13px] text-vellum-muted mt-1.5">Period scheduled disbursement date: <span class="font-mono text-vellum font-semibold">07 {{ \Carbon\Carbon::parse($period)->addMonth()->format('M Y') }}</span></p>
                                
                                <div class="flex items-center gap-3 mt-4">
                                    <span class="inline-flex items-center gap-1.5 text-[12px] px-3.5 py-1.5 rounded-full font-medium"
                                          :class="cycle.status === 'locked' ? 'bg-burgundy/10 text-burgundy border border-burgundy/20' : 'bg-forest/10 text-forest border border-forest/20'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="cycle.status === 'locked' ? 'bg-burgundy' : 'bg-forest'"></span>
                                        <span x-text="cycle.status === 'locked' ? 'Locked & Disbursement Ready' : 'Cycle Open & Editable'"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="w-full lg:w-72">
                                <div class="flex justify-between text-[11px] text-vellum-faint mb-1.5 uppercase font-semibold tracking-wider">
                                    <span>Cycle Progress</span>
                                    <span class="num font-semibold text-vellum" x-text="cycle.status === 'locked' ? '100%' : '78%'"></span>
                                </div>
                                <div class="h-2.5 rounded-full bg-surface overflow-hidden border border-hairline/20">
                                    <div class="h-full bg-gradient-to-r from-brass to-brass-dark rounded-full"
                                         :style="cycle.status === 'locked' ? 'width: 100%' : 'width: 78%'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Pipeline stages -->
                        <div class="relative mt-8 pt-7 border-t border-line">
                            <p class="text-[11px] uppercase tracking-[0.14em] text-vellum-faint font-semibold mb-5">Workflow Run Pipeline</p>
                            <div class="flex items-start overflow-x-auto pb-1 gap-2">
                                <template x-for="(stage, idx) in pipeline" :key="stage.id">
                                    <div class="flex items-start shrink-0">
                                        <div class="tooltip-trigger relative flex flex-col items-center w-28 text-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 relative z-10 text-xs"
                                                :class="stage.status==='done' ? 'bg-forest text-cream border-forest' : stage.status==='current' ? 'bg-brass text-walnut border-brass' : 'bg-cream text-vellum-muted border-line'">
                                                <span x-text="idx + 1" class="font-mono font-bold"></span>
                                            </div>
                                            <p class="text-[11px] font-semibold text-vellum mt-2.5 leading-tight truncate w-full" x-text="stage.label"></p>
                                            <p class="text-[10px] text-vellum-faint font-mono mt-0.5" x-text="stage.date"></p>
                                        </div>
                                        <div x-show="idx < pipeline.length - 1" class="w-8 h-0.5 pipeline-line mt-4 opacity-40"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- KPIs Strips -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <template x-for="kpi in kpis" :key="kpi.label">
                            <div class="panel p-5 bg-surface border border-hairline/20 rounded-2xl hover:border-brass/30 transition-all duration-200">
                                <p class="text-[10.5px] uppercase tracking-wider text-vellum-faint font-semibold" x-text="kpi.label"></p>
                                <p class="font-display text-2xl font-semibold mt-2" 
                                   :class="kpi.tone === 'forest' ? 'text-forest' : (kpi.tone === 'oxblood' ? 'text-burgundy' : 'text-vellum')"
                                   x-text="kpi.value"></p>
                                <p class="text-[11px] text-vellum-muted mt-1" x-text="kpi.sub"></p>
                            </div>
                        </template>
                    </div>

                    <!-- Recalculation Trigger Form -->
                    <div class="flex justify-end pt-2">
                        <form method="POST" action="{{ route('admin.payroll.process') }}">
                            @csrf
                            <input type="hidden" name="period" x-value="cycle.period" :value="cycle.period">
                            <button type="submit" :disabled="cycle.status === 'locked'"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-brass text-cream hover:bg-brass-dark disabled:opacity-30 disabled:pointer-events-none transition shadow-soft">
                                <svg class="w-3.5 h-3.5 animate-spin-slow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Re-calculate Cycle Payroll
                            </button>
                        </form>
                    </div>
                </div>

                <!-- ================= 2. EMPLOYEE LIST ================= -->
                <div x-show="activeTab === 'employees'" x-cloak class="fade-in space-y-6">
                    <!-- Filters Bar -->
                    <div class="flex flex-col md:flex-row gap-4 justify-between items-end bg-surface p-4 rounded-2xl border border-hairline/25">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 flex-1 w-full">
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Search Directory</label>
                                <input type="text" placeholder="Search by name, code..." x-model="filters.search"
                                       class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Filter Department</label>
                                <select x-model="filters.dept" class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none">
                                    <option value="">All Departments</option>
                                    <template x-for="dept in departments" :key="dept">
                                        <option :value="dept" x-text="dept"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Calculation Status</label>
                                <select x-model="filters.status" class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none">
                                    <option value="">All Statuses</option>
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending Review</option>
                                    <option value="correction">Needs Correction</option>
                                </select>
                            </div>
                        </div>
                        <div class="w-full md:w-auto">
                            <select x-model="filters.sort" class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none font-mono">
                                <option value="name">Sort: Name (A-Z)</option>
                                <option value="gross">Sort: Gross (High-Low)</option>
                                <option value="net">Sort: Net (High-Low)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-cream border border-line rounded-2xl overflow-hidden shadow-soft">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-[13px]">
                                <thead>
                                    <tr class="border-b border-line bg-surface/50 font-mono text-[10.5px] uppercase tracking-wider text-vellum-faint">
                                        <th class="py-3.5 px-5">Employee</th>
                                        <th class="py-3.5 px-4 text-center">Worked Days</th>
                                        <th class="py-3.5 px-4 text-center">Unpaid Leaves</th>
                                        <th class="py-3.5 px-4 text-right">Base Salary</th>
                                        <th class="py-3.5 px-4 text-right">Gross Salary</th>
                                        <th class="py-3.5 px-4 text-right">Net Disbursement</th>
                                        <th class="py-3.5 px-4 text-center">Status</th>
                                        <th class="py-3.5 px-5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="emp in filteredEmployees" :key="emp.id">
                                        <tr class="border-b border-line/45 hover:bg-surface/30 transition-colors">
                                            <td class="py-3.5 px-5 flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-brass-light flex items-center justify-center font-display font-medium text-xs text-brass-dark" x-text="emp.initials"></div>
                                                <div>
                                                    <p class="font-semibold text-vellum" x-text="emp.name"></p>
                                                    <p class="text-[11px] text-vellum-faint uppercase tracking-wide" x-text="emp.id + ' · ' + emp.designation"></p>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 text-center num" x-text="emp.present + '/' + emp.workingDays"></td>
                                            <td class="py-3.5 px-4 text-center num text-burgundy font-semibold" x-text="emp.unpaidLeave > 0 ? emp.unpaidLeave : '—'"></td>
                                            <td class="py-3.5 px-4 text-right num font-semibold" x-text="'₹' + emp.baseSalary.toLocaleString('en-IN')"></td>
                                            <td class="py-3.5 px-4 text-right num" x-text="'₹' + emp.gross.toLocaleString('en-IN')"></td>
                                            <td class="py-3.5 px-4 text-right num text-forest font-bold" x-text="'₹' + emp.net.toLocaleString('en-IN')"></td>
                                            <td class="py-3.5 px-4 text-center">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium"
                                                      :class="statusChip(emp.status)">
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(emp.status)"></span>
                                                    <span x-text="statusLabel(emp.status)"></span>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-5 text-right">
                                                <button @click="openDrawer(emp)" class="text-xs font-bold uppercase tracking-wider text-brass hover:text-brass-dark">
                                                    Review Profile
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= 3. SALARY LEDGER ================= -->
                <div x-show="activeTab === 'ledger'" x-cloak class="fade-in space-y-6">
                    <div class="flex justify-between items-center bg-surface p-4 rounded-2xl border border-hairline/25">
                        <div>
                            <h3 class="font-display font-medium text-lg text-vellum">Salary Disbursement Ledger</h3>
                            <p class="text-[11px] text-vellum-faint mt-0.5 uppercase tracking-wide">June 2026 Cycle Summary</p>
                        </div>
                        <a href="{{ route('admin.payroll.ledger.export', ['period' => $period]) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider bg-cream hover:bg-surface text-vellum border border-hairline shadow-soft transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Download Disbursement Ledger (CSV)
                        </a>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left List -->
                        <div class="panel bg-cream border border-line shadow-soft flex flex-col p-0 overflow-hidden lg:col-span-1 rounded-2xl">
                            <div class="p-4 border-b border-line bg-surface/50 font-mono text-[10px] uppercase tracking-wider text-vellum-faint font-bold">
                                Select Employee Profile
                            </div>
                            <div class="divide-y divide-line/45 max-h-[500px] overflow-y-auto">
                                <template x-for="emp in employees" :key="emp.id">
                                    <button @click="ledgerEmpId = emp.id; setLedgerEmp()"
                                            class="w-full text-left p-4 hover:bg-surface/30 transition flex items-center justify-between gap-3 focus:outline-none"
                                            :class="ledgerEmpId === emp.id ? 'bg-surface/60 border-l-4 border-l-brass' : ''">
                                        <div>
                                            <p class="font-semibold text-vellum" x-text="emp.name"></p>
                                            <p class="text-[11px] text-vellum-faint uppercase font-mono tracking-wide" x-text="emp.id + ' · ' + emp.dept"></p>
                                        </div>
                                        <span class="num font-bold text-forest" x-text="'₹' + emp.net.toLocaleString('en-IN')"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Right Ledger Details -->
                        <div class="lg:col-span-2 panel bg-cream border border-line shadow-soft p-6 rounded-2xl">
                            <template x-if="selectedLedgerEmp">
                                <div class="space-y-6">
                                    <div class="flex items-center justify-between pb-4 border-b border-line">
                                        <div>
                                            <h4 class="font-display font-medium text-xl text-vellum" x-text="selectedLedgerEmp.name"></h4>
                                            <p class="text-[12px] text-vellum-faint uppercase tracking-wide" x-text="selectedLedgerEmp.id + ' · ' + selectedLedgerEmp.designation + ' · ' + selectedLedgerEmp.dept"></p>
                                        </div>
                                        <button @click="openDrawer(selectedLedgerEmp)" class="text-xs font-bold uppercase tracking-wider text-brass hover:text-brass-dark">
                                            View Breakdown Dossier
                                        </button>
                                    </div>

                                    <div class="bg-surface/40 p-4 rounded-xl border border-hairline/20 grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint block">Bank Name</span>
                                            <span class="font-medium text-vellum mt-0.5 block text-xs" x-text="selectedLedgerEmp.importSource === 'Manual Entry' ? 'HDFC Bank' : 'State Bank of India'"></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint block">Account Holder</span>
                                            <span class="font-medium text-vellum mt-0.5 block text-xs" x-text="selectedLedgerEmp.name"></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint block">Account Number</span>
                                            <span class="font-mono text-vellum mt-0.5 block text-xs">*********4902</span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint block">IFSC Code</span>
                                            <span class="font-mono text-vellum mt-0.5 block text-xs">SBIN0002130</span>
                                        </div>
                                    </div>

                                    <div class="border border-line rounded-xl overflow-hidden">
                                        <div class="p-3 bg-surface/50 border-b border-line flex justify-between font-mono text-[10px] uppercase tracking-wider text-vellum-faint font-bold">
                                            <span>Calculation Component</span>
                                            <span>Formula & Notes</span>
                                            <span class="text-right">Amount</span>
                                        </div>
                                        <div class="divide-y divide-line/45">
                                            <template x-for="row in ledgerFormulaRows(selectedLedgerEmp)" :key="row.label">
                                                <div class="p-3 flex items-start gap-4 text-xs hover:bg-surface/10 transition">
                                                    <span class="w-40 font-semibold text-vellum" x-text="row.label"></span>
                                                    <span class="flex-1 text-vellum-muted" x-text="row.explain"></span>
                                                    <span class="w-24 text-right font-mono font-bold" 
                                                          :class="row.tone === 'oxblood' ? 'text-burgundy' : (row.tone === 'forest' ? 'text-forest' : 'text-vellum')"
                                                          x-text="row.skipValue ? '—' : (row.value < 0 ? '-' : '') + '₹' + Math.abs(row.value).toLocaleString('en-IN')"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!selectedLedgerEmp">
                                <div class="text-center py-12 text-vellum-faint">
                                    Select an employee from the left panel to review salary calculation formulas.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ================= 4. CORRECTIONS ================= -->
                <div x-show="activeTab === 'corrections'" x-cloak class="fade-in space-y-6">
                    <div class="panel bg-surface p-5 border border-hairline/25 rounded-2xl">
                        <h3 class="font-display font-medium text-lg text-vellum">Administrative Adjustments & Correction Ledger</h3>
                        <p class="text-xs text-vellum-muted mt-1.5 leading-relaxed">
                            Every manual financial adjustment requires double-authorization and a documented business reason, creating an immutable audit trail.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <template x-for="emp in employees.filter(e => e.status === 'correction' || e.correctionStatus === 'resolved')" :key="emp.id">
                            <div class="panel bg-cream border border-line shadow-soft p-6 flex flex-col md:flex-row justify-between gap-6 rounded-2xl">
                                <div class="flex-1 space-y-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-brass-light flex items-center justify-center font-display font-medium text-xs text-brass-dark" x-text="emp.initials"></div>
                                        <div>
                                            <h4 class="font-semibold text-vellum" x-text="emp.name"></h4>
                                            <p class="text-[11px] text-vellum-faint uppercase font-mono tracking-wide" x-text="emp.id + ' · ' + emp.dept"></p>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider font-sans"
                                              :class="emp.correctionStatus === 'resolved' ? 'bg-forest/10 text-forest' : 'bg-burgundy/10 text-burgundy'">
                                            <span class="w-1 h-1 rounded-full" :class="emp.correctionStatus === 'resolved' ? 'bg-forest' : 'bg-burgundy'"></span>
                                            <span x-text="emp.correctionStatus === 'resolved' ? 'Approved & Persisted' : 'Awaiting Approval'"></span>
                                        </span>
                                    </div>

                                    <div class="bg-surface/50 p-3 rounded-lg border border-hairline/15 text-xs space-y-1">
                                        <p class="text-vellum-faint font-semibold uppercase font-mono text-[10px] tracking-wider">Adjustment Reason / Notes</p>
                                        <p class="text-vellum" x-text="emp.correctionReason || 'Slack discussion - approved attendance override regularization.'"></p>
                                    </div>
                                    
                                    <p class="text-[11px] text-vellum-muted" x-text="'System trace: ' + emp.systemExplanation"></p>
                                </div>

                                <div class="flex flex-col justify-between items-end shrink-0">
                                    <div class="text-right">
                                        <span class="text-[10px] uppercase font-bold text-vellum-faint block">Financial Delta</span>
                                        <span class="font-mono font-bold text-lg mt-0.5 block text-forest" x-text="'+₹' + emp.bonuses.toLocaleString('en-IN')"></span>
                                    </div>

                                    <div class="flex gap-3 mt-4" x-show="emp.correctionStatus !== 'resolved'">
                                        <button @click="approveCorrection(emp)" 
                                                class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider bg-forest text-cream hover:bg-forest-dark transition shadow-soft">
                                            Approve Adjustment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- ================= 5. EXCEPTIONS ================= -->
                <div x-show="activeTab === 'exceptions'" x-cloak class="fade-in space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left groups list -->
                        <div class="panel bg-cream border border-line shadow-soft p-0 overflow-hidden md:col-span-1 rounded-2xl">
                            <div class="p-4 border-b border-line bg-surface/50 font-mono text-[10px] uppercase tracking-wider text-vellum-faint font-bold">
                                Exception Categories
                            </div>
                            <div class="divide-y divide-line/45">
                                <template x-for="exc in exceptions" :key="exc.title">
                                    <div class="p-4 flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-vellum" x-text="exc.title"></p>
                                            <p class="text-[11px] text-vellum-faint" x-text="exc.count + ' records flagged'"></p>
                                        </div>
                                        <span class="text-[10.5px] px-2 py-0.5 rounded-full font-bold font-mono" 
                                              :class="exc.count > 0 ? 'bg-burgundy text-cream' : 'bg-forest-light text-forest'">
                                            <span x-text="exc.count"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Right detailed exceptions -->
                        <div class="md:col-span-2 panel bg-cream border border-line shadow-soft p-0 overflow-hidden rounded-2xl">
                            <div class="p-4 border-b border-line bg-surface/50 font-mono text-[10px] uppercase tracking-wider text-vellum-faint font-bold">
                                Flagged System Anomaly Records
                            </div>
                            <div class="divide-y divide-line/45">
                                <template x-for="item in exceptionsFlat" :key="item.id">
                                    <div class="p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-surface/20 transition">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="item.severity === 'Critical' ? 'bg-burgundy' : 'bg-brass'"></span>
                                                <p class="font-semibold text-vellum text-sm" x-text="item.emp"></p>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-burgundy/10 text-burgundy font-bold font-sans uppercase tracking-wider" x-text="item.severity"></span>
                                            </div>
                                            <p class="text-[12px] text-vellum-muted mt-1" x-text="item.type + ' exception resolved: ' + (item.resolved ? 'Yes' : 'No')"></p>
                                            <p class="text-[11px] text-vellum-faint font-mono mt-0.5" x-text="'Assigned Reviewer: ' + item.admin + ' · Raised: ' + item.date"></p>
                                        </div>
                                        <button @click="openDrawer(employees.find(e => e.name === item.emp))" 
                                                class="px-3.5 py-2 border border-hairline hover:bg-surface rounded-xl text-xs font-semibold text-vellum">
                                            Review Discrepancy
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= 6. PAYROLL LOCK ================= -->
                <div x-show="activeTab === 'lock'" x-cloak class="fade-in space-y-6">
                    <div class="panel bg-cream border border-line shadow-card p-8 flex flex-col md:flex-row items-start justify-between gap-8 rounded-2xl">
                        <div class="flex-1 space-y-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-7 h-7 text-brass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <h3 class="font-display font-medium text-2xl text-vellum">Dual Sign-Off & Disbursement Lock</h3>
                            </div>
                            <p class="text-sm text-vellum-muted leading-relaxed">
                                Locking the payroll cycle seals all calculations, logs, and ledger entries for the period, making them completely immutable. Locked cycles will generate payslips automatically.
                            </p>
                            <div class="bg-surface/50 border border-hairline/20 p-4 rounded-xl space-y-3">
                                <p class="text-[10px] uppercase font-bold text-brass tracking-wider">Required Dual Authorization Approvals</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-4 h-4 rounded-full bg-forest text-cream flex items-center justify-center font-bold font-sans">✓</span>
                                        <span class="font-medium text-vellum">Rhea Sarin (Platform Admin)</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-vellum-faint">
                                        <span class="w-4 h-4 rounded-full bg-surface border border-hairline text-vellum flex items-center justify-center font-bold">✓</span>
                                        <span>Aditya Verma (Super Administrator)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="shrink-0 pt-6">
                            <template x-if="cycle.status !== 'locked'">
                                <form method="POST" action="{{ route('admin.payroll.lock') }}">
                                    @csrf
                                    <input type="hidden" name="period" x-value="cycle.period" :value="cycle.period">
                                    <button type="submit" 
                                            class="px-8 py-4 bg-burgundy hover:bg-burgundy-dark text-cream font-bold uppercase tracking-wider rounded-2xl shadow-lift text-xs">
                                        Execute Period Disbursement Lock
                                    </button>
                                </form>
                            </template>
                            <template x-if="cycle.status === 'locked'">
                                <div class="space-y-4">
                                    <div class="p-4 bg-burgundy/15 border border-burgundy/30 text-burgundy rounded-xl font-bold text-center text-xs">
                                        PERIOD IS LOCKED & IMMUTABLE
                                    </div>
                                    <button @click="showLockModal = true"
                                            class="w-full px-4 py-2 border border-burgundy/35 text-burgundy hover:bg-burgundy/5 rounded-xl font-semibold text-xs transition">
                                        Request Period Unlock
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ================= 7. PAYSLIPS ================= -->
                <div x-show="activeTab === 'payslips'" x-cloak class="fade-in space-y-6">
                    <div class="bg-surface p-4 rounded-2xl border border-hairline/25 flex gap-4">
                        <input type="text" placeholder="Search employee payslip..." x-model="payslipFilters.search"
                               class="w-72 text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none">
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left List -->
                        <div class="panel bg-cream border border-line shadow-soft p-0 overflow-hidden lg:col-span-1 rounded-2xl">
                            <div class="p-4 border-b border-line bg-surface/50 font-mono text-[10px] uppercase tracking-wider text-vellum-faint font-bold">
                                Select Employee Payslip
                            </div>
                            <div class="divide-y divide-line/45 max-h-[500px] overflow-y-auto">
                                <template x-for="emp in filteredPayslipEmployees" :key="emp.id">
                                    <button @click="payslipEmp = emp"
                                            class="w-full text-left p-4 hover:bg-surface/30 transition flex items-center justify-between gap-3 focus:outline-none"
                                            :class="payslipEmp && payslipEmp.id === emp.id ? 'bg-surface/60 border-l-4 border-l-brass' : ''">
                                        <div>
                                            <p class="font-semibold text-vellum" x-text="emp.name"></p>
                                            <p class="text-[11px] text-vellum-faint uppercase font-mono tracking-wide" x-text="emp.id + ' · ' + emp.dept"></p>
                                        </div>
                                        <span class="text-[10px] px-2 py-0.5 rounded uppercase font-bold" 
                                              :class="emp.downloadStatus === 'downloaded' ? 'bg-forest/10 text-forest' : 'bg-brass/15 text-brass-dark'"
                                              x-text="emp.downloadStatus"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Right Payslip View -->
                        <div class="lg:col-span-2 panel bg-cream border border-line shadow-card p-8 rounded-2xl font-sans">
                            <template x-if="payslipEmp">
                                <div class="space-y-6">
                                    <div class="flex justify-between items-start border-b border-line pb-6">
                                        <div>
                                            <h3 class="font-display font-bold text-2xl text-vellum">PAYSLIP REPORT</h3>
                                            <p class="text-xs text-vellum-faint uppercase mt-1" x-text="'Cycle: ' + cycle.period"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-vellum text-sm">Venture Request</p>
                                            <p class="text-xs text-vellum-faint">HQ, Dehradun</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs border-b border-line pb-4">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint">Employee Code</span>
                                            <span class="font-mono text-vellum mt-0.5 block" x-text="payslipEmp.id"></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint">Name</span>
                                            <span class="font-medium text-vellum mt-0.5 block" x-text="payslipEmp.name"></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint">Department</span>
                                            <span class="font-medium text-vellum mt-0.5 block" x-text="payslipEmp.dept"></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint">Designation</span>
                                            <span class="font-medium text-vellum mt-0.5 block" x-text="payslipEmp.designation"></span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs">
                                        <div class="space-y-2">
                                            <h5 class="font-mono text-[10px] uppercase font-bold text-brass border-b border-brass/25 pb-1">Earnings</h5>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between"><span>Base Salary</span><span class="num font-semibold text-vellum" x-text="'₹' + payslipEmp.baseSalary.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Allowances</span><span class="num font-semibold text-vellum" x-text="'₹' + payslipEmp.allowances.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between" x-show="payslipEmp.overtimeHours > 0"><span>Overtime Pay</span><span class="num font-semibold text-vellum text-forest" x-text="'₹' + (payslipEmp.overtimeHours * 250).toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between" x-show="payslipEmp.bonuses > 0"><span>Adjustment / Bonus</span><span class="num font-semibold text-vellum text-forest" x-text="'₹' + payslipEmp.bonuses.toLocaleString('en-IN')"></span></div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <h5 class="font-mono text-[10px] uppercase font-bold text-burgundy border-b border-burgundy/25 pb-1">Deductions</h5>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between" x-show="payslipEmp.unpaidLeave > 0"><span>Leave Deductions</span><span class="num font-semibold text-burgundy" x-text="'–₹' + (payslipEmp.unpaidLeave * Math.round(payslipEmp.baseSalary/30)).toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Tax (TDS)</span><span class="num font-semibold text-burgundy" x-text="'–₹' + payslipEmp.taxAmt.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Provident Fund (PF)</span><span class="num font-semibold text-burgundy" x-text="'–₹' + payslipEmp.pf.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>ESI</span><span class="num font-semibold text-burgundy" x-text="'–₹' + payslipEmp.esi.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Professional Tax</span><span class="num font-semibold text-burgundy" x-text="'–₹' + payslipEmp.profTax.toLocaleString('en-IN')"></span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-surface/50 border border-hairline/25 p-4 rounded-xl flex justify-between items-center mt-6">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint">Net Salary Disbursed</span>
                                            <h3 class="font-display font-bold text-2xl text-forest mt-0.5" x-text="'₹' + payslipEmp.net.toLocaleString('en-IN')"></h3>
                                        </div>
                                        <span class="font-mono text-[11px] text-vellum-muted">Rounded to nearest ₹1</span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!payslipEmp">
                                <div class="text-center py-12 text-vellum-faint">
                                    Select an employee from the left panel to review or generate their payslip report.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ================= 8. AUDIT TRAIL ================= -->
                <div x-show="activeTab === 'audit'" x-cloak class="fade-in space-y-6">
                    <div class="bg-cream border border-line rounded-2xl overflow-hidden shadow-soft">
                        <div class="p-4 border-b border-line bg-surface/50 font-mono text-[10px] uppercase tracking-wider text-vellum-faint font-bold">
                            Immutable Audit Ledger
                        </div>
                        <div class="divide-y divide-line/45">
                            <template x-for="log in auditTrail" :key="log.id">
                                <div class="p-4 flex flex-col md:flex-row justify-between gap-4 text-xs">
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="auditDot(log.type)"></span>
                                            <span class="font-semibold text-vellum" x-text="log.action"></span>
                                            <span class="text-[9px] px-1.5 py-0.5 rounded font-bold font-mono uppercase" :class="auditBadge(log.type)" x-text="log.category"></span>
                                        </div>
                                        <p class="text-vellum-muted font-mono text-[11px]" x-text="'Actor: ' + log.actor + ' · Timestamp: ' + log.timestamp"></p>
                                    </div>
                                    <div class="text-right shrink-0" x-show="log.oldValue">
                                        <p class="text-[10px] text-vellum-faint font-mono uppercase tracking-wide">Value Change</p>
                                        <p class="text-vellum font-mono text-[11.5px]" x-text="log.oldValue + ' → ' + log.newValue"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ================= 9. REPORTS ================= -->
                <div x-show="activeTab === 'reports'" x-cloak class="fade-in space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <template x-for="report in reports" :key="report.title">
                            <div class="panel bg-cream border border-line shadow-soft p-6 space-y-4 rounded-2xl">
                                <div>
                                    <h4 class="font-display font-medium text-base text-vellum" x-text="report.title"></h4>
                                    <p class="text-[11px] text-vellum-faint" x-text="report.desc"></p>
                                </div>

                                <template x-if="report.type === 'bar'">
                                    <div class="h-28 flex items-end gap-2 px-2 pb-2 border-b border-line">
                                        <template x-for="item in report.data" :key="item.label">
                                            <div class="flex-1 flex flex-col items-center gap-1 group">
                                                <div class="w-full bg-forest/20 group-hover:bg-forest transition-colors rounded-t" 
                                                     :style="`height: ${(item.value / report.max) * 90}px`" :title="item.value"></div>
                                                <span class="text-[10px] font-mono text-vellum-muted mt-1" x-text="item.label"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="report.type === 'donut'">
                                    <div class="flex items-center gap-8 py-2">
                                        <div class="w-20 h-20 rounded-full relative shrink-0" :style="donutStyle(report.data)">
                                            <div class="absolute inset-4 rounded-full bg-cream"></div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs flex-1">
                                            <template x-for="(item, idx) in report.data" :key="item.label">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="`background-color: ${donutColors[idx % donutColors.length]}`"></span>
                                                    <span class="text-vellum truncate" x-text="item.label"></span>
                                                    <span class="num font-bold ml-auto text-vellum-muted" x-text="item.value"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="report.type === 'line'">
                                    <div class="h-28 flex items-end gap-2 px-2 pb-2 border-b border-line">
                                        <template x-for="item in report.data" :key="item.label">
                                            <div class="flex-1 flex flex-col items-center gap-1 group">
                                                <div class="w-2.5 h-2.5 rounded-full bg-brass shrink-0 mb-1" :style="`margin-bottom: ${(item.value / report.max) * 70}px`"></div>
                                                <span class="text-[10px] font-mono text-vellum-muted" x-text="item.label"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="report.type === 'list'">
                                    <div class="space-y-2">
                                        <template x-for="item in report.data" :key="item.label">
                                            <div class="flex justify-between text-xs py-1 border-b border-line/45 last:border-none">
                                                <span class="text-vellum" x-text="item.label"></span>
                                                <span class="num font-bold text-forest" x-text="'₹' + item.value.toLocaleString('en-IN')"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- ================= 10. SETTINGS ================= -->
                <div x-show="activeTab === 'settings'" x-cloak class="fade-in space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
                        <!-- Left Menu Slabs -->
                        <div class="panel bg-cream border border-line shadow-soft p-2 overflow-hidden lg:col-span-1 sticky top-20 rounded-2xl">
                            <template x-for="group in settingsGroups" :key="group.id">
                                <button @click="settingsTab = group.id"
                                        class="w-full text-left px-4 py-2.5 rounded-xl text-xs font-semibold transition"
                                        :class="settingsTab === group.id ? 'bg-brass text-walnut' : 'text-vellum hover:bg-surface' "
                                        x-text="group.title"></button>
                            </template>
                        </div>

                        <!-- Right Fields Config -->
                        <div class="lg:col-span-3 panel bg-cream border border-line shadow-soft p-6 rounded-2xl">
                            <template x-for="group in settingsGroups" :key="group.id">
                                <div x-show="settingsTab === group.id" class="space-y-6">
                                    <div>
                                        <h4 class="font-display font-medium text-lg text-vellum" x-text="group.title"></h4>
                                        <p class="text-[12px] text-vellum-faint mt-1" x-text="group.desc"></p>
                                    </div>

                                    <form method="POST" action="{{ route('admin.payroll.settings.update') }}" class="space-y-5 border-t border-line pt-5">
                                        @csrf
                                        <input type="hidden" name="group" :value="group.id">

                                        <template x-if="!group.isMappingTable && !group.isOrderList">
                                            <div class="space-y-4">
                                                <template x-for="field in group.fields" :key="field.label">
                                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                                        <div>
                                                            <label class="text-xs font-bold text-vellum uppercase tracking-wide block" x-text="field.label"></label>
                                                            <span class="text-[10px] text-vellum-faint block mt-0.5" x-text="field.hint"></span>
                                                        </div>
                                                        <div class="md:col-span-2">
                                                            <template x-if="field.type === 'text'">
                                                                <input type="text" :value="field.value"
                                                                       class="w-full text-xs bg-surface-raised border border-hairline rounded px-3 py-2 text-vellum focus:ring-1 focus:ring-brass focus:border-brass outline-none">
                                                            </template>
                                                            <template x-if="field.type === 'toggle'">
                                                                <div class="flex items-center">
                                                                    <input type="checkbox" :checked="field.value"
                                                                           class="rounded border-hairline text-brass focus:ring-brass/30 bg-surface-raised w-4 h-4">
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <template x-if="group.isMappingTable">
                                            <div class="border border-line rounded-xl overflow-hidden text-xs">
                                                <div class="p-3 bg-surface/50 border-b border-line font-mono text-[10px] uppercase font-bold text-vellum-faint flex justify-between">
                                                    <span>Attendance State</span>
                                                    <span class="w-48 text-right">Payroll Effect</span>
                                                </div>
                                                <div class="divide-y divide-line/45">
                                                    <template x-for="map in policies.payrollMapping" :key="map.state">
                                                        <div class="p-3 flex justify-between items-center">
                                                            <span class="font-semibold text-vellum" x-text="map.state"></span>
                                                            <span class="font-mono text-vellum-muted" x-text="map.effect"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="group.isOrderList">
                                            <div class="border border-line rounded-xl overflow-hidden text-xs divide-y divide-line/45">
                                                <template x-for="(order, idx) in group.orderItems" :key="order">
                                                    <div class="p-3 flex items-center gap-3">
                                                        <span class="font-mono font-bold text-brass" x-text="idx + 1"></span>
                                                        <span class="font-semibold text-vellum" x-text="order"></span>
                                                        <span class="text-[9px] px-1.5 py-0.5 bg-surface rounded text-vellum-faint font-mono ml-auto">priority tag</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <div class="flex justify-end pt-4 border-t border-line">
                                            <button type="submit" 
                                                    class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-brass text-cream hover:bg-brass-dark transition shadow-soft">
                                                Save Settings Parameters
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </main>
        </div>

        <!-- ============ SPLIT DRAWER SLIDE-OVER PANEL ============ -->
        <div x-show="drawerOpen" class="fixed inset-0 overflow-hidden z-50" x-cloak>
            <div class="absolute inset-0 bg-walnut/40 backdrop-blur-sm" @click="drawerOpen = false"></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex">
                <div class="w-screen max-w-2xl bg-cream border-l border-line shadow-lift flex flex-col drawer-enter"
                     :style="drawerOpen ? 'transform: translateX(0)' : 'transform: translateX(100%)'">
                    
                    <div class="p-6 border-b border-line bg-surface/50 flex justify-between items-center">
                        <div>
                            <h3 class="font-display font-bold text-xl text-vellum" x-text="selectedEmployee ? selectedEmployee.name : ''"></h3>
                            <p class="text-xs text-vellum-faint uppercase font-mono tracking-wide mt-1" 
                               x-text="selectedEmployee ? selectedEmployee.id + ' · ' + selectedEmployee.designation : ''"></p>
                        </div>
                        <button @click="drawerOpen = false" class="text-2xl text-vellum-faint hover:text-vellum font-light">&times;</button>
                    </div>

                    <div class="border-b border-line bg-surface/20 flex gap-2 px-6">
                        <button @click="drawerTab = 'overview'" 
                                class="py-3 px-1 text-xs uppercase font-bold tracking-wider border-b-2"
                                :class="drawerTab === 'overview' ? 'border-brass text-brass' : 'border-transparent text-vellum-faint' ">Overview</button>
                        <button @click="drawerTab = 'attendance'" 
                                class="py-3 px-1 text-xs uppercase font-bold tracking-wider border-b-2"
                                :class="drawerTab === 'attendance' ? 'border-brass text-brass' : 'border-transparent text-vellum-faint' ">Attendance snapshot</button>
                        <button @click="drawerTab = 'corrections'" 
                                class="py-3 px-1 text-xs uppercase font-bold tracking-wider border-b-2"
                                :class="drawerTab === 'corrections' ? 'border-brass text-brass' : 'border-transparent text-vellum-faint' ">Manual overrides</button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <template x-if="selectedEmployee">
                            <div class="space-y-6">
                                <!-- OVERVIEW TAB -->
                                <div x-show="drawerTab === 'overview'" class="space-y-6">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Employment Tenure category:</span>
                                        <span class="text-xs px-2.5 py-1 rounded-full font-bold font-mono" 
                                              :class="selectedEmployee.joiningDate ? ( ( (new Date('2026-06-30') - new Date(selectedEmployee.joiningDate)) / 86400000 ) < 90 ? 'bg-brass-light text-brass-dark' : 'bg-forest-light text-forest' ) : 'bg-forest-light text-forest'"
                                              x-text="selectedEmployee.joiningDate ? ( ( (new Date('2026-06-30') - new Date(selectedEmployee.joiningDate)) / 86400000 ) < 90 ? 'Probation' : 'Permanent' ) : 'Permanent'"></span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-surface/50 border border-hairline/25 p-4 rounded-xl">
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint block">Gross Salary</span>
                                            <span class="font-mono font-bold text-lg text-vellum block mt-1" x-text="'₹' + selectedEmployee.gross.toLocaleString('en-IN')"></span>
                                        </div>
                                        <div class="bg-surface/50 border border-hairline/25 p-4 rounded-xl">
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint block">Net Salary</span>
                                            <span class="font-mono font-bold text-lg text-forest block mt-1" x-text="'₹' + selectedEmployee.net.toLocaleString('en-IN')"></span>
                                        </div>
                                    </div>

                                    <div class="bg-surface/30 p-4 border border-hairline/15 rounded-xl space-y-1">
                                        <span class="text-[10.5px] uppercase font-bold text-brass font-mono block">Payroll Calculation Trace</span>
                                        <p class="text-xs text-vellum leading-relaxed font-mono" x-text="selectedEmployee.systemExplanation"></p>
                                    </div>
                                </div>

                                <!-- ATTENDANCE SNAPSHOT TAB -->
                                <div x-show="drawerTab === 'attendance'" class="space-y-6">
                                    <div class="border border-line rounded-xl overflow-hidden text-xs">
                                        <div class="p-3 bg-surface/50 border-b border-line font-mono text-[10px] uppercase font-bold text-vellum-faint flex justify-between">
                                            <span>Date Period</span>
                                            <span class="text-right">Resolved Status</span>
                                        </div>
                                        <div class="divide-y divide-line/45">
                                            <template x-for="day in selectedEmployee.attendanceSnapshot" :key="day.day">
                                                <div class="p-3 flex justify-between items-center hover:bg-surface/20 transition">
                                                    <span class="font-medium text-vellum" x-text="day.date"></span>
                                                    <span class="text-[10.5px] px-2 py-0.5 font-bold uppercase rounded"
                                                          :class="{
                                                            present: 'bg-forest/10 text-forest',
                                                            late: 'bg-brass/20 text-brass-dark',
                                                            half: 'bg-brass/15 text-brass-dark',
                                                            leave: 'bg-slate/15 text-slate',
                                                            wfh: 'bg-forest/10 text-forest',
                                                            absent: 'bg-burgundy/10 text-burgundy',
                                                            off: 'bg-surface text-vellum-faint border border-line'
                                                          }[day.status]"
                                                          x-text="day.status"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- CORRECTIONS / OVERRIDES EDIT TAB -->
                                <div x-show="drawerTab === 'corrections'" class="space-y-6">
                                    <p class="text-xs text-vellum-muted leading-relaxed">
                                        Apply manual corrections to this employee's payout. Submission will trigger a workflow approval step.
                                    </p>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="text-xs font-bold text-vellum uppercase tracking-wide block mb-1">New Net Salary (₹)</label>
                                            <input type="number" id="correction-amount" :value="selectedEmployee.net"
                                                   class="w-full text-xs bg-surface-raised border border-hairline rounded px-3 py-2 text-vellum outline-none">
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold text-vellum uppercase tracking-wide block mb-1">Business Justification / Reason</label>
                                            <textarea id="correction-reason" rows="3" placeholder="Explain why this manual adjustment is needed..."
                                                      class="w-full text-xs bg-surface-raised border border-hairline rounded px-3 py-2 text-vellum outline-none"></textarea>
                                        </div>
                                        <button @click="submitCorrection(selectedEmployee)"
                                                class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-brass text-cream hover:bg-brass-dark transition shadow-soft">
                                            Submit Correction Adjustment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lock Confirmation Modal -->
        <div x-show="showLockModal" class="fixed inset-0 overflow-hidden z-50 flex items-center justify-center" x-cloak>
            <div class="absolute inset-0 bg-walnut/40 backdrop-blur-sm" @click="showLockModal = false"></div>
            <div class="bg-cream border border-line rounded-2xl p-6 shadow-lift max-w-md w-full relative z-10 space-y-4">
                <h3 class="font-display font-medium text-lg text-vellum">Unlock Payroll Cycle</h3>
                <p class="text-xs text-vellum-muted leading-relaxed">
                    Unlocking this period will restore editing access to employee payroll records and overrides. Please specify the unlock justification below.
                </p>
                <form method="POST" action="{{ route('admin.payroll.unlock') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="period" x-value="cycle.period" :value="cycle.period">
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block mb-1">Justification Reason</label>
                        <textarea name="reason" rows="3" required placeholder="Describe the reason for unlocking..."
                                  class="w-full text-xs bg-surface-raised border border-hairline rounded px-3 py-2 text-vellum outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showLockModal = false" class="px-4 py-2 border border-hairline text-vellum rounded-xl text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-burgundy hover:bg-burgundy-dark text-cream font-bold uppercase tracking-wider rounded-xl text-xs">Unlock Cycle</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Alpine App Logic Script -->
    <script>
        function payrollApp() {
            return {
                activeTab: '{{ $activeTab }}',
                settingsTab: 'general',
                globalSearch: '',
                drawerOpen: false,
                drawerTab: 'overview',
                selectedEmployee: null,
                payslipEmp: null,
                ledgerEmpId: null,
                selectedLedgerEmp: null,
                ledgerOpen: {},
                showLockModal: false,
                cycleLocked: {{ $cycle->status === 'locked' ? 'true' : 'false' }},
                toast: null,
                filters: { search: '', dept: '', status: '', sort: 'name' },
                payslipFilters: { search: '', dept: '', status: '' },

                donutColors: ['#C6941C', '#6B2039', '#1E3D30', '#A85D1E', '#8A7B5C'],

                navItems: [
                    { id: 'dashboard', label: 'Dashboard' },
                    { id: 'employees', label: 'Employee Payroll' },
                    { id: 'ledger', label: 'Salary Ledger' },
                    { id: 'corrections', label: 'Corrections' },
                    { id: 'exceptions', label: 'Exceptions' },
                    { id: 'lock', label: 'Payroll Lock' },
                    { id: 'payslips', label: 'Payslips' },
                    { id: 'audit', label: 'Audit Trail' },
                    { id: 'reports', label: 'Reports' },
                    { id: 'settings', label: 'Configuration Center' },
                ],

                cycle: { 
                    period: '{{ $cycle->period }}', 
                    status: '{{ $cycle->status }}', 
                    statusLabel: '{{ $cycle->status === "locked" ? "Disbursement Ready" : ($cycle->status === "corrections_pending" ? "Corrections Pending" : "Processing") }}' 
                },

                pipeline: [
                    { id: 'imported', label: 'Attendance Imported', status: 'done', date: '28 Jun 2026', tip: 'Attendance records imported' },
                    { id: 'verified', label: 'Attendance Verified', status: 'done', date: '28 Jun 2026', tip: 'All records cross-checked' },
                    { id: 'generated', label: 'Payroll Generated', status: 'done', date: '29 Jun 2026', tip: 'Salary computed using cycle rules' },
                    { id: 'corrections', label: 'Corrections Pending', status: '{{ $cycle->status === "corrections_pending" ? "current" : "done" }}', date: 'In progress', tip: 'Flagged records awaiting HR review' },
                    { id: 'approved', label: 'Payroll Approved', status: '{{ in_array($cycle->status, ["approved", "locked"]) ? "done" : "upcoming" }}', date: '—', tip: 'Requires all corrections cleared' },
                    { id: 'payslips', label: 'Payslips Generated', status: '{{ $cycle->status === "locked" ? "done" : "upcoming" }}', date: '—', tip: 'Generated automatically once payroll is locked' },
                    { id: 'locked', label: 'Payroll Locked', status: '{{ $cycle->status === "locked" ? "done" : "upcoming" }}', date: '—', tip: 'Final state — disbursement-ready and immutable' },
                ],

                kpis: @json($kpis),
                departments: @json($employeesData->pluck('dept')->unique()->values()),
                policies: {
                    lifecycle: {
                        probationDays: {{ \App\Models\PayrollSetting::getValue('lifecycle')['probationDays'] }},
                        autoPromote: {{ \App\Models\PayrollSetting::getValue('lifecycle')['autoPromote'] ? 'true' : 'false' }},
                        probationLeaveBalance: {{ \App\Models\PayrollSetting::getValue('lifecycle')['probationLeaveBalance'] }},
                        probationPayrollCycle: '{{ \App\Models\PayrollSetting::getValue('lifecycle')['probationPayrollCycle'] }}',
                    },
                    workWeek: {
                        workingDays: @json(\App\Models\PayrollSetting::getValue('workWeek')['workingDays']),
                        weeklyOff: @json(\App\Models\PayrollSetting::getValue('workWeek')['weeklyOff']),
                        saturdayIsWorking: {{ \App\Models\PayrollSetting::getValue('workWeek')['saturdayIsWorking'] ? 'true' : 'false' }},
                    },
                    shifts: @json(\App\Models\PayrollSetting::getValue('shifts')),
                    attendance: @json(\App\Models\PayrollSetting::getValue('attendance')),
                    leave: @json(\App\Models\PayrollSetting::getValue('leave')),
                    payroll: @json(\App\Models\PayrollSetting::getValue('payroll')),
                    payrollMapping: @json(\App\Models\PayrollSetting::getValue('payrollMapping')),
                    excelImport: @json(\App\Models\PayrollSetting::getValue('excelImport')),
                    audit: @json(\App\Models\PayrollSetting::getValue('audit')),
                    lock: @json(\App\Models\PayrollSetting::getValue('lockrules') ?? \App\Models\PayrollSetting::getValue('lock')),
                },

                employees: @json($employeesData),
                auditTrail: @json($auditTrail),
                exceptions: @json($exceptionsGrouped),
                exceptionsFlat: @json($exceptionsFlat),
                reports: @json($reports),
                settingsGroups: @json($settingsGroups),
                
                init() {
                    if (this.employees.length > 0) {
                        this.ledgerEmpId = this.employees[0].id;
                        this.setLedgerEmp();
                        this.payslipEmp = this.employees[0];
                    }
                },

                get currentNavLabel() {
                    const item = this.navItems.find(n => n.id === this.activeTab);
                    return item ? item.label : '';
                },

                attPct(e) { return e ? Math.round((e.present / e.workingDays) * 100) : 0 },

                get filteredEmployees() {
                    let list = this.employees.filter(e => {
                        const s = this.filters.search.toLowerCase();
                        const matchesSearch = !s || e.name.toLowerCase().includes(s) || e.id.toLowerCase().includes(s);
                        const matchesDept = !this.filters.dept || e.dept === this.filters.dept;
                        const matchesStatus = !this.filters.status || e.status === this.filters.status;
                        return matchesSearch && matchesDept && matchesStatus;
                    });
                    if (this.filters.sort === 'net') list = list.slice().sort((a, b) => b.net - a.net);
                    if (this.filters.sort === 'gross') list = list.slice().sort((a, b) => b.gross - a.gross);
                    if (this.filters.sort === 'name') list = list.slice().sort((a, b) => a.name.localeCompare(b.name));
                    if (this.filters.sort === 'attendance') list = list.slice().sort((a, b) => this.attPct(b) - this.attPct(a));
                    return list;
                },

                get filteredPayslipEmployees() {
                    return this.employees.filter(e => {
                        const s = this.payslipFilters.search.toLowerCase();
                        const matchesSearch = !s || e.name.toLowerCase().includes(s);
                        return matchesSearch;
                    });
                },

                ledgerFormulaRows(e) {
                    if (!e) return [];
                    const p = this.policies;
                    const dailyRate = Math.round((e.baseSalary + e.allowances) / 30);
                    const hourlyRate = Math.round((e.baseSalary + e.allowances) / 30 / 8);
                    
                    return [
                        { label: 'Employee Category', value: 0, skipValue: true, explain: 'Below ' + p.lifecycle.probationDays + ' days of employment an employee is Probation; auto-promotes to Permanent on day ' + p.lifecycle.probationDays + '.', calc: 'Category: ' + (e.unpaidLeave > 0 ? 'Probation' : 'Permanent'), auditRef: 'Lifecycle Policy' },
                        { label: 'Base Salary', value: e.baseSalary, explain: 'Authoritative fixed monthly base salary.', calc: 'Base = ₹' + e.baseSalary.toLocaleString('en-IN'), auditRef: 'STRUCT-' + e.id },
                        { label: 'Allowances', value: e.allowances, explain: 'Retrieved structural standard allowances.', calc: 'Allowances = ₹' + e.allowances.toLocaleString('en-IN') },
                        { label: 'Daily Rate (Segment)', value: dailyRate, explain: 'Derived basic daily rate for segment month calendar days.', calc: 'Daily = ₹' + dailyRate.toLocaleString('en-IN') },
                        { label: 'Hourly Shift Rate', value: hourlyRate, explain: 'Derived basic hourly shift rate.', calc: 'Hourly = ₹' + hourlyRate.toLocaleString('en-IN') },
                        { label: 'Leave Deductions', value: -(e.unpaidLeave * dailyRate), tone: 'oxblood', explain: e.unpaidLeave + ' unpaid day(s) deducted, per Unplanned Leave policy.', calc: e.unpaidLeave + ' × ₹' + dailyRate.toLocaleString('en-IN') },
                        { label: 'Half Day Deductions', value: -(e.halfDay * Math.round(dailyRate / 2)), tone: 'oxblood', explain: e.halfDay + ' half day(s) deducted, per Payroll Mapping.', calc: e.halfDay + ' × ₹' + Math.round(dailyRate / 2).toLocaleString('en-IN') },
                        { label: 'Overtime Pay', value: e.overtimeHours * Math.round(hourlyRate * 1.5), tone: 'forest', explain: e.overtimeHours + ' overtime hour(s) at 1.5x multiplier.', calc: e.overtimeHours + ' × ₹' + Math.round(hourlyRate * 1.5) },
                        { label: 'Bonuses', value: e.bonuses, tone: 'forest', explain: 'Manual corrections or discretionary adjustments applied.', calc: 'Approved corrections' },
                        { label: 'Gross Salary', value: e.gross, emphasis: true },
                        { label: 'Tax (TDS)', value: -e.taxAmt, tone: 'oxblood', explain: 'TDS tax deduction (5% rate).', calc: 'TDS slab applied = –₹' + e.taxAmt.toLocaleString('en-IN') },
                        { label: 'Provident Fund', value: -e.pf, tone: 'oxblood', explain: 'PF contribution (12% of basic up to ceiling).', calc: 'PF rate = –₹' + e.pf.toLocaleString('en-IN') },
                        { label: 'ESI', value: -e.esi, tone: 'oxblood', explain: 'ESI contribution (0.75% of gross).', calc: 'ESI rate = –₹' + e.esi.toLocaleString('en-IN') },
                        { label: 'Professional Tax', value: -e.profTax, tone: 'oxblood', explain: 'Flat professional tax for Uttarakhand.', calc: 'PTAX = –₹' + e.profTax.toLocaleString('en-IN') },
                        { label: 'Net Salary', value: e.net, emphasis: true }
                    ];
                },

                setLedgerEmp() { this.selectedLedgerEmp = this.employees.find(e => e.id === this.ledgerEmpId) || null },
                openDrawer(emp) { this.selectedEmployee = emp; this.drawerTab = 'overview'; this.drawerOpen = true },
                
                statusChip(status) {
                    return {
                        approved: 'bg-forest/10 text-forest',
                        pending: 'bg-brass/15 text-brass-dark',
                        correction: 'bg-burgundy/10 text-burgundy',
                        locked: 'bg-surface text-vellum-faint border border-line',
                    }[status] || 'bg-surface text-vellum-faint'
                },
                statusDot(status) {
                    return { approved: 'bg-forest', pending: 'bg-brass', correction: 'bg-burgundy', locked: 'bg-vellum-faint' }[status] || 'bg-vellum-faint'
                },
                statusLabel(status) {
                    return { approved: 'Approved', pending: 'Pending', correction: 'Correction', locked: 'Locked' }[status] || status
                },
                auditDot(type) {
                    return { correction: 'bg-burgundy', import: 'bg-brass', approval: 'bg-forest', lock: 'bg-vellum', unlock: 'bg-brass-dark' }[type] || 'bg-vellum-faint'
                },
                auditBadge(type) {
                    return { correction: 'bg-burgundy/10 text-burgundy', import: 'bg-brass/20 text-brass-dark', approval: 'bg-forest/15 text-forest', lock: 'bg-walnut/15 text-cream', unlock: 'bg-brass/15 text-brass-dark' }[type] || 'bg-surface'
                },

                linePoints(data, max) {
                    return data.map((v, i) => (i * (240 / (data.length - 1))) + ',' + (70 - (v.value / max * 64) - 3)).join(' ')
                },
                donutStyle(data) {
                    const total = data.reduce((s, v) => s + v.value, 0)
                    let acc = 0
                    const stops = data.map((v, i) => {
                        const start = acc / total * 360
                        acc += v.value
                        const end = acc / total * 360
                        return this.donutColors[i % this.donutColors.length] + ' ' + start + 'deg ' + end + 'deg'
                    })
                    return 'background: conic-gradient(' + stops.join(',') + ');'
                },

                submitCorrection(emp) {
                    const amount = parseFloat(document.getElementById('correction-amount').value);
                    const reason = document.getElementById('correction-reason').value;

                    if (isNaN(amount) || amount < 0 || !reason || reason.length < 5) {
                        alert('Please enter a valid amount and justification reason (min 5 characters).');
                        return;
                    }

                    this.toast = 'Submitting correction adjustment...';
                    
                    fetch('{{ route("admin.payroll.corrections.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            record_id: emp.record_id,
                            new_net_salary: amount,
                            reason: reason
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Correction submitted successfully!';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                approveCorrection(emp) {
                    this.toast = 'Approving correction adjustment...';
                    
                    fetch(`/admin/payroll/corrections/${emp.record_id}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Correction approved successfully!';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                }
            }
        }
    </script>
@endsection
