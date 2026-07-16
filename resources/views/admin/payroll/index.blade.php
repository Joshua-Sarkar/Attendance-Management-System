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
                        <form method="GET" action="" class="hidden sm:inline-flex items-center gap-1.5">
                            <span class="text-[11px] font-bold text-brass uppercase">Payroll Period:</span>
                            <select name="period" onchange="this.form.submit()" 
                                    class="text-[11.5px] bg-cream border border-brass/30 rounded-full px-3 py-1 focus:border-brass outline-none text-brass font-bold uppercase font-mono">
                                @foreach(\App\Models\PayrollCycle::orderBy('id', 'desc')->get() as $c)
                                    <option value="{{ $c->period }}" {{ $c->period === $period ? 'selected' : '' }}>{{ $c->period }}</option>
                                @endforeach
                            </select>
                            <span class="text-[11.5px] px-2.5 py-1 rounded-full border bg-brass-light/30 text-brass border-brass/30 font-medium capitalize" x-text="cycle.statusLabel"></span>
                        </form>
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
                            <p class="text-[11px] uppercase tracking-[0.14em] text-vellum-faint font-semibold mb-5 font-mono">Workflow Run Pipeline Status</p>
                            <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-10 gap-3">
                                <template x-for="(stage, idx) in pipeline" :key="stage.id">
                                    <div class="flex flex-col items-center p-3 rounded-xl border bg-surface/30 transition text-center relative"
                                         :class="{
                                            'border-forest/30 bg-forest/5': stage.status === 'done',
                                            'border-brass bg-brass-light/20': stage.status === 'current',
                                            'border-burgundy/30 bg-burgundy/5': stage.status === 'blocked',
                                            'border-line bg-surface/20': stage.status === 'upcoming'
                                         }">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-mono font-bold"
                                             :class="{
                                                'bg-forest text-cream border-forest': stage.status === 'done',
                                                'bg-brass text-walnut border-brass': stage.status === 'current',
                                                'bg-burgundy text-cream border-burgundy': stage.status === 'blocked',
                                                'bg-cream text-vellum-muted border border-line': stage.status === 'upcoming'
                                             }">
                                            <template x-if="stage.status === 'done'"><span>✓</span></template>
                                            <template x-if="stage.status !== 'done'"><span x-text="idx + 1"></span></template>
                                        </div>
                                        
                                        <p class="text-[10px] font-bold text-vellum mt-2 leading-tight" x-text="stage.label"></p>
                                        
                                        <!-- Completion details -->
                                        <p class="text-[10px] text-vellum-faint mt-1 font-mono" x-text="stage.completed + ' / ' + stage.total"></p>
                                        <p class="text-[9px] text-vellum-muted font-mono" x-text="stage.pct + '%'"></p>
                                        
                                        <!-- Blocked / Warnings -->
                                        <template x-if="stage.status === 'blocked' && stage.reason">
                                            <div class="mt-2 text-[9px] font-semibold text-burgundy bg-burgundy/10 border border-burgundy/20 px-1.5 py-0.5 rounded leading-tight" x-text="stage.reason"></div>
                                        </template>
                                        <template x-if="stage.status !== 'blocked' && stage.reason">
                                            <div class="mt-2 text-[9px] font-medium text-vellum-muted" x-text="stage.reason"></div>
                                        </template>
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
                    <div class="flex flex-col gap-4 bg-surface p-4 rounded-2xl border border-hairline/25">
                        <div class="flex flex-col md:flex-row gap-4 justify-between items-end w-full">
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
                        
                        <!-- Row 2: Extended Auditable Filters -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full border-t border-line/40 pt-3">
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Employment Category</label>
                                <select x-model="filters.category" class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass outline-none">
                                    <option value="">All Categories</option>
                                    <option value="Permanent">Permanent</option>
                                    <option value="Probation">Probation</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Employee Review State</label>
                                <select x-model="filters.employeeReview" class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass outline-none">
                                    <option value="">All States</option>
                                    <option value="approved">Approved</option>
                                    <option value="disputed">Disputed</option>
                                    <option value="stale">Stale</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Admin Review State</label>
                                <select x-model="filters.adminApproved" class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass outline-none">
                                    <option value="">All States</option>
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Lock State</label>
                                <select x-model="filters.lockState" class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass outline-none">
                                    <option value="">All States</option>
                                    <option value="locked">Locked</option>
                                    <option value="open">Open</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-cream border border-line rounded-2xl overflow-hidden shadow-soft">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-[13px]">
                                <thead>
                                    <tr class="border-b border-line bg-surface/50 font-mono text-[10.5px] uppercase tracking-wider text-vellum-faint">
                                        <th class="py-3.5 px-5">Employee</th>
                                        <th class="py-3.5 px-4 text-center" title="Paid Equivalent Days / Eligible Working Days">Paid / Eligible Days</th>
                                        <th class="py-3.5 px-4 text-right">Deductions</th>
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
                                            <td class="py-3.5 px-4 text-right num text-burgundy font-semibold" x-text="'₹' + emp.deductions.toLocaleString('en-IN')"></td>
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
                                            <h4 class="font-display font-medium text-xl text-vellum flex items-center gap-2">
                                                <span x-text="selectedLedgerEmp.name"></span>
                                                <span class="text-[9px] px-2 py-0.5 rounded font-mono font-bold uppercase border"
                                                      :class="selectedLedgerEmp.employment_category === 'Probation' ? 'bg-brass-light text-brass-dark border-brass/20' : 'bg-forest-light text-forest border-forest/20'"
                                                      x-text="selectedLedgerEmp.employment_category"></span>
                                            </h4>
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
                    <!-- Employee Disputes Workspace -->
                    <div class="panel bg-cream border border-line shadow-card p-6 rounded-2xl space-y-4">
                        <div>
                            <h3 class="font-display font-medium text-lg text-vellum">Employee Disputes Workspace</h3>
                            <p class="text-xs text-vellum-muted mt-0.5">Review, approve, or reject pay-dispute claims raised by employees through self-service review.</p>
                        </div>

                        <div class="space-y-4">
                            <!-- Helper to gather all disputes from employees -->
                            @php
                                $allDisputes = \App\Models\PayrollDispute::with('user.department', 'payrollRecord')->orderBy('created_at', 'desc')->get();
                            @endphp
                            @forelse($allDisputes as $d)
                                <div class="border border-line rounded-xl p-4 text-xs space-y-3 bg-surface/30">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-brass-light flex items-center justify-center font-display font-medium text-[10px] text-brass-dark">
                                                {{ strtoupper(substr($d->user->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-vellum">{{ $d->user->name }}</h4>
                                                <p class="text-[10px] text-vellum-faint uppercase font-mono tracking-wide">
                                                    {{ $d->user->employee_id ?? 'EMP-'.$d->user->id }} · {{ $d->user->department->name ?? 'Unassigned' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded text-[9px] font-bold font-sans uppercase tracking-wider border
                                                         {{ $d->status === 'open' ? 'bg-burgundy/10 text-burgundy border-burgundy/20' : 'bg-forest/10 text-forest border-forest/20' }}">
                                                {{ $d->status }}
                                            </span>
                                            <span class="text-[10px] font-mono text-vellum-faint">{{ $d->created_at->format('d M Y, g:i A') }}</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-cream p-3 rounded-lg border border-hairline/25">
                                        <div>
                                            <span class="text-[9px] uppercase font-bold text-vellum-faint block">Disputed Category & Date</span>
                                            <span class="font-semibold text-vellum mt-0.5 block">{{ $d->category }} @if($d->affected_date) (Date: {{ $d->affected_date->format('d M Y') }}) @endif</span>
                                            
                                            <span class="text-[9px] uppercase font-bold text-vellum-faint block mt-2">Dispute Description</span>
                                            <p class="text-vellum mt-0.5">{{ $d->description }}</p>
                                        </div>
                                        <div>
                                            <span class="text-[9px] uppercase font-bold text-vellum-faint block">Expected Correction</span>
                                            <p class="text-vellum mt-0.5 font-medium italic">{{ $d->expected_correction }}</p>

                                            @if($d->status === 'resolved' && $d->resolution_notes)
                                                <span class="text-[9px] uppercase font-bold text-forest block mt-2">Resolution Note</span>
                                                <p class="text-forest mt-0.5">{{ $d->resolution_notes }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    @if($d->status === 'open')
                                        <div class="flex items-center gap-2 justify-end pt-1">
                                            <input type="text" id="dispute-note-{{ $d->id }}" placeholder="Resolution/Rejection comments..." 
                                                   class="text-[11.5px] bg-cream border border-line rounded-lg px-3 py-1.5 w-64 outline-none focus:border-brass">
                                            <button @click="resolveDisputeAdmin({{ $d->id }}, 'resolved')" 
                                                    class="px-3 py-1.5 bg-forest text-cream text-[10px] font-bold uppercase tracking-wider rounded transition">
                                                Resolve (Pending Re-approve)
                                            </button>
                                            <button @click="resolveDisputeAdmin({{ $d->id }}, 'rejected')" 
                                                    class="px-3 py-1.5 bg-burgundy text-cream text-[10px] font-bold uppercase tracking-wider rounded transition">
                                                Reject Dispute
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-vellum-faint italic py-4">No active disputes pending review.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Administrative Adjustments (Manual corrections) -->
                    <div class="panel bg-cream border border-line shadow-card p-6 rounded-2xl space-y-4">
                        <div>
                            <h3 class="font-display font-medium text-lg text-vellum">Administrative Adjustments & Correction Ledger</h3>
                            <p class="text-xs text-vellum-muted mt-0.5">Every manual financial adjustment requires double-authorization and a documented business reason, creating an immutable audit trail.</p>
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
                    <div class="panel bg-cream border border-line shadow-card p-6 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between border-b border-line pb-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-7 h-7 text-brass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <div>
                                    <h3 class="font-display font-medium text-lg text-vellum">Per-Employee Locking Workspace</h3>
                                    <p class="text-xs text-vellum-muted mt-0.5">Authorize approvals, review employee review status, resolve blocks, and seal calculations into immutable snapshots.</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-mono font-bold bg-surface/50 border border-hairline/25 px-3 py-1 rounded text-vellum-faint uppercase" x-text="'Period: ' + cycle.period"></span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-line font-mono text-[10px] uppercase font-bold text-vellum-faint bg-surface/20">
                                        <th class="p-3">Employee</th>
                                        <th class="p-3 text-right">Net Payout</th>
                                        <th class="p-3 text-center">Version & Fingerprint</th>
                                        <th class="p-3 text-center">Employee Review</th>
                                        <th class="p-3 text-center">Admin Status</th>
                                        <th class="p-3 text-center">Detailed Status</th>
                                        <th class="p-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/45">
                                    <template x-for="emp in filteredEmployees" :key="emp.id">
                                        <tr class="hover:bg-surface/10 transition">
                                            <td class="p-3">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full bg-brass-light flex items-center justify-center font-display font-medium text-[10px] text-brass-dark" x-text="emp.initials"></div>
                                                    <div>
                                                        <p class="font-semibold text-vellum cursor-pointer hover:text-brass" @click="openDrawer(emp)" x-text="emp.name"></p>
                                                        <p class="text-[10px] text-vellum-faint uppercase font-mono tracking-wide" x-text="emp.id + ' · ' + emp.dept"></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-3 text-right font-mono font-bold text-vellum" x-text="'₹' + emp.net.toLocaleString('en-IN')"></td>
                                            <td class="p-3 text-center font-mono">
                                                <span class="block font-bold text-walnut" x-text="'v' + emp.calculation_version"></span>
                                                <span class="block text-[9px] text-vellum-faint" x-text="emp.fingerprint ? emp.fingerprint.substring(0,8) : '—'"></span>
                                            </td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider font-sans border"
                                                      :class="{
                                                        approved: 'bg-forest/10 text-forest border-forest/20',
                                                        disputed: 'bg-burgundy/10 text-burgundy border-burgundy/20',
                                                        stale: 'bg-cognac/10 text-cognac border-cognac/20',
                                                        pending: 'bg-brass/10 text-brass border-brass/20'
                                                      }[emp.employee_review_status]"
                                                      x-text="emp.employee_review_status"></span>
                                                <span class="block text-[8px] text-vellum-faint mt-0.5" x-text="emp.employee_approved_at || '—'"></span>
                                            </td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider font-sans border"
                                                      :class="emp.admin_approved_at ? 'bg-forest/10 text-forest border-forest/20' : 'bg-brass/10 text-brass border-brass/20'"
                                                      x-text="emp.admin_approved_at ? 'APPROVED' : 'PENDING'"></span>
                                                <span class="block text-[8px] text-vellum-faint mt-0.5" x-text="emp.admin_approved_at || '—'"></span>
                                            </td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider font-sans border"
                                                      :class="resolveRecordStatus(emp).class"
                                                      x-text="resolveRecordStatus(emp).label"></span>
                                                <span class="block text-[8px] text-vellum-faint mt-0.5" x-show="emp.locked" x-text="emp.locked_at || '—'"></span>
                                            </td>
                                            <td class="p-3 text-right">
                                                <div class="flex justify-end items-center gap-1.5">
                                                    <!-- Unlocked + no employee approval -->
                                                    <button x-show="!emp.locked && emp.employee_review_status !== 'approved'"
                                                            @click="openDrawer(emp)"
                                                            class="px-2 py-1 bg-cream border border-brass text-brass hover:bg-brass/5 text-[10px] font-bold uppercase tracking-wider rounded transition">
                                                        View Calculation
                                                    </button>
                                                    
                                                    <!-- Employee approved + admin pending -->
                                                    <button x-show="!emp.locked && emp.employee_review_status === 'approved' && !emp.admin_approved_at"
                                                            @click="approveRecordAdmin(emp)"
                                                            class="px-2 py-1 bg-forest hover:bg-forest-dark text-cream text-[10px] font-bold uppercase tracking-wider rounded transition shadow-soft">
                                                        Review & Approve
                                                    </button>
                                                    
                                                    <!-- Both approved + current calculation matches -->
                                                    <button x-show="!emp.locked && emp.employee_review_status === 'approved' && emp.admin_approved_at"
                                                            @click="lockRecordAdmin(emp)"
                                                            class="px-2 py-1 bg-burgundy hover:bg-burgundy-dark text-cream text-[10px] font-bold uppercase tracking-wider rounded transition shadow-soft">
                                                        Lock Payroll
                                                    </button>
                                                    
                                                    <!-- Locked: View Finalised -->
                                                    <button x-show="emp.locked"
                                                            @click="openDrawer(emp)"
                                                            class="px-2 py-1 bg-cream border border-brass text-brass hover:bg-brass/5 text-[10px] font-bold uppercase tracking-wider rounded transition">
                                                        View Finalised
                                                    </button>
                                                    
                                                    <!-- Locked: Reopen -->
                                                    <button x-show="emp.locked"
                                                            @click="reopenRecordAdminPrompt(emp)"
                                                            class="px-2 py-1 bg-cream border border-burgundy text-burgundy hover:bg-burgundy/5 text-[10px] font-bold uppercase tracking-wider rounded transition">
                                                        Reopen Payroll
                                                    </button>
                                                    
                                                    <!-- Warning/Dispute indicators -->
                                                    <span x-show="emp.employee_review_status === 'disputed'" class="text-[10px] font-bold text-burgundy px-1 block font-sans" title="Employee raised dispute!">⚠ DISPUTE</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Custom Reopen Modal -->
                <div x-show="reopenPromptOpen" class="fixed inset-0 overflow-hidden z-50 flex items-center justify-center" x-cloak>
                    <div class="absolute inset-0 bg-walnut/40 backdrop-blur-sm" @click="reopenPromptOpen = false"></div>
                    <div class="bg-cream border border-line rounded-2xl p-6 shadow-lift max-w-md w-full relative z-10 space-y-4">
                        <h3 class="font-display font-medium text-lg text-vellum">Reopen Payroll Record</h3>
                        <p class="text-xs text-burgundy font-semibold">
                            Warning: Reopening this record will remove the active payroll lock, invalidate existing approvals, and revoke any generated/published payslips.
                        </p>
                        
                        <div class="space-y-2 text-xs border border-line p-3 rounded-xl bg-surface/30">
                            <p class="text-vellum"><strong>Employee Name:</strong> <span class="font-semibold text-walnut" x-text="selectedRecordToReopen ? selectedRecordToReopen.name : ''"></span></p>
                            <p class="text-vellum"><strong>Payroll Cycle:</strong> <span class="font-semibold text-walnut" x-text="cycle.period"></span></p>
                            <p class="text-vellum"><strong>Calculation Version:</strong> <span class="font-semibold text-walnut" x-text="selectedRecordToReopen ? 'v' + selectedRecordToReopen.calculation_version : ''"></span></p>
                            <p class="text-vellum"><strong>Fingerprint:</strong> <span class="font-mono text-[10px] text-walnut" x-text="selectedRecordToReopen && selectedRecordToReopen.fingerprint ? selectedRecordToReopen.fingerprint : 'N/A'"></span></p>
                        </div>
                        
                        <div class="space-y-3">
                            <label class="text-[10px] font-bold uppercase tracking-wide text-vellum-faint block">Mandatory Reopen Reason</label>
                            <textarea x-model="reopenReason" rows="3" required placeholder="Provide justification reason for reopening (min 5 characters)..."
                                      class="w-full text-xs bg-cream border border-line rounded px-3 py-2 text-vellum outline-none"></textarea>
                        </div>
                        
                        <p class="text-[10.5px] text-vellum-muted leading-relaxed">
                            Once reopened, the calculation will be rerun against current attendance/salary parameters. Both employee and administrator review will be required before this record can be locked again.
                        </p>
                        
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="reopenPromptOpen = false" class="px-4 py-2 border border-hairline text-vellum rounded-xl text-xs">Cancel</button>
                            <button type="button" @click="submitReopenRecord()" class="px-4 py-2 bg-burgundy hover:bg-burgundy-dark text-cream font-bold uppercase tracking-wider rounded-xl text-xs">Execute Reopen</button>
                        </div>
                    </div>
                </div>

                <!-- ================= 7. PAYSLIPS ================= -->
                <div x-show="activeTab === 'payslips'" x-cloak class="fade-in space-y-6">
                    <div class="bg-surface p-4 rounded-2xl border border-hairline/25 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
                        <input type="text" placeholder="Search employee payslip..." x-model="payslipFilters.search"
                               class="w-72 text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none">
                        
                        <div class="flex gap-2">
                            <button @click="bulkGeneratePayslips()" 
                                    class="px-4 py-2 border border-brass text-brass hover:bg-brass/5 rounded-xl text-xs font-bold uppercase tracking-wider transition">
                                Bulk Generate
                            </button>
                            <button @click="bulkPublishPayslips()" 
                                    class="px-4 py-2 bg-brass text-cream hover:bg-brass-dark rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-soft">
                                Bulk Publish
                            </button>
                        </div>
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
                                        <div class="flex flex-col items-end gap-1">
                                            <span class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase border" 
                                                  :class="{
                                                    published: 'bg-forest/10 text-forest border-forest/20',
                                                    generated: 'bg-brass/10 text-brass border-brass/20',
                                                    pending: 'bg-surface border border-line text-vellum-faint'
                                                  }[emp.payslip_status || 'pending']"
                                                  x-text="emp.payslip_status || 'pending'"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Right Payslip View -->
                        <div class="lg:col-span-2 panel bg-cream border border-line shadow-card p-8 rounded-2xl font-sans">
                            <template x-if="payslipEmp">
                                <div class="space-y-6">
                                    <!-- Payslip Status Metadata Ribbon -->
                                    <div class="bg-surface/75 border border-hairline/20 p-4 rounded-xl flex flex-wrap justify-between items-center gap-4 text-xs">
                                        <div class="space-y-1">
                                            <p class="text-vellum"><strong>Payslip Status:</strong> 
                                                <span class="px-2 py-0.5 rounded font-bold uppercase"
                                                      :class="{
                                                        published: 'bg-forest/10 text-forest',
                                                        generated: 'bg-brass/10 text-brass',
                                                        pending: 'bg-surface text-vellum-faint border border-line'
                                                      }[payslipEmp.payslip_status]"
                                                      x-text="payslipEmp.payslip_status"></span>
                                            </p>
                                            <p class="text-vellum-muted text-[11px]">
                                                Generated: <span x-text="payslipEmp.payslip_generated_at || '—'"></span> · 
                                                Published: <span x-text="payslipEmp.payslip_published_at || '—'"></span>
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <template x-if="!payslipEmp.locked">
                                                <span class="text-xs text-burgundy italic">Record must be Locked to generate payslips.</span>
                                            </template>
                                            <template x-if="payslipEmp.locked">
                                                <div class="flex gap-2">
                                                    <button @click="generateSinglePayslip(payslipEmp)"
                                                            class="px-3.5 py-2 border border-brass text-brass rounded-xl text-xs font-bold uppercase tracking-wider transition hover:bg-brass/5">
                                                        Generate
                                                    </button>
                                                    <button x-show="payslipEmp.payslip_status !== 'published'"
                                                            @click="publishSinglePayslip(payslipEmp)"
                                                            class="px-3.5 py-2 bg-brass text-cream rounded-xl text-xs font-bold uppercase tracking-wider transition hover:bg-brass-dark shadow-soft">
                                                        Publish
                                                    </button>
                                                    <a :href="'/my-payslip/' + payslipEmp.record_id + '/download'"
                                                       class="px-3.5 py-2 bg-forest text-cream rounded-xl text-xs font-bold uppercase tracking-wider transition hover:bg-forest-dark shadow-soft inline-flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                        </svg>
                                                        Download PDF
                                                    </a>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

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
                                                <div class="flex justify-between" x-show="payslipEmp.overtimeHours > 0"><span>Overtime Pay</span><span class="num font-semibold text-vellum text-forest" x-text="'₹' + (payslipEmp.overtimeHours * Math.round(payslipEmp.baseSalary/240)).toLocaleString('en-IN')"></span></div>
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
                    <!-- Audit Filters Bar -->
                    <div class="flex flex-col md:flex-row gap-4 justify-between items-end bg-surface p-4 rounded-2xl border border-hairline/25">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 flex-1 w-full">
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Search Audit Logs</label>
                                <input type="text" placeholder="Search actor, action, details..." x-model="auditFilters.search"
                                       class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint block mb-1">Filter Category</label>
                                <select x-model="auditFilters.category" class="w-full text-xs bg-cream border border-line rounded-lg px-3 py-2 focus:border-brass focus:ring-1 focus:ring-brass/40 outline-none">
                                    <option value="">All Categories</option>
                                    <option value="Calculation">Calculation</option>
                                    <option value="Approval">Approval</option>
                                    <option value="Correction">Correction</option>
                                    <option value="Configuration">Configuration</option>
                                    <option value="Lock">Lock</option>
                                    <option value="Unlock">Unlock</option>
                                    <option value="Dispute">Dispute</option>
                                    <option value="Payslip">Payslip</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-cream border border-line rounded-2xl overflow-hidden shadow-soft">
                        <div class="p-4 border-b border-line bg-surface/50 font-mono text-[10px] uppercase tracking-wider text-vellum-faint font-bold">
                            Immutable Audit Ledger
                        </div>
                        <div class="divide-y divide-line/45">
                            <template x-for="log in filteredAuditLogs" :key="log.id">
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
                            <template x-if="filteredAuditLogs.length === 0">
                                <div class="p-8 text-center text-xs text-vellum-faint">
                                    No audit entries found matching search or category selection.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ================= 9. REPORTS ================= -->
                <div x-show="activeTab === 'reports'" x-cloak class="fade-in space-y-7" x-data="{ localReportFilter: '{{ $reportFilter }}' }">
                    
                    <!-- Shared Report Date and Period Filters -->
                    <div class="panel bg-cream border border-line shadow-soft p-6 rounded-3xl space-y-4">
                        <form method="GET" action="{{ route('admin.payroll.reports') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div>
                                <label class="text-[10px] font-bold text-vellum uppercase tracking-wider block mb-1.5 font-mono">Period Filter</label>
                                <select name="report_filter" x-model="localReportFilter" @change="if ($el.value !== 'custom') $el.form.submit()"
                                        class="w-full text-xs bg-cream border border-line rounded-xl px-3.5 py-2.5 outline-none focus:border-brass text-vellum font-semibold">
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="this_week">This Week</option>
                                    <option value="prev_week">Previous Week</option>
                                    <option value="this_month">This Month</option>
                                    <option value="prev_month">Previous Month</option>
                                    <option value="current_cycle">Current Payroll Cycle</option>
                                    <option value="prev_cycle">Previous Payroll Cycle</option>
                                    <option value="specific_cycle">Specific Payroll Cycle</option>
                                    <option value="custom">Custom Date Range</option>
                                </select>
                            </div>

                            <div x-show="localReportFilter === 'specific_cycle'" class="md:col-span-1">
                                <label class="text-[10px] font-bold text-vellum uppercase tracking-wider block mb-1.5 font-mono">Select Cycle</label>
                                <select name="report_cycle" @change="$el.form.submit()"
                                        class="w-full text-xs bg-cream border border-line rounded-xl px-3.5 py-2.5 outline-none focus:border-brass text-vellum font-semibold">
                                    @foreach($allPeriods as $p)
                                        <option value="{{ $p }}" {{ $reportCycle === $p ? 'selected' : '' }}>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="localReportFilter === 'custom'" class="grid grid-cols-2 gap-2 md:col-span-2">
                                <div>
                                    <label class="text-[10px] font-bold text-vellum uppercase tracking-wider block mb-1.5 font-mono">Start Date</label>
                                    <input type="date" name="start_date" value="{{ $reportStartDate }}"
                                           class="w-full text-xs bg-cream border border-line rounded-xl px-3 py-2 outline-none focus:border-brass text-vellum font-semibold">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-vellum uppercase tracking-wider block mb-1.5 font-mono">End Date</label>
                                    <input type="date" name="end_date" value="{{ $reportEndDate }}"
                                           class="w-full text-xs bg-cream border border-line rounded-xl px-3 py-2 outline-none focus:border-brass text-vellum font-semibold">
                                </div>
                            </div>

                            <div x-show="localReportFilter === 'custom'">
                                <button type="submit" class="w-full text-xs font-semibold bg-brass text-walnut px-4 py-2.5 rounded-xl transition hover:bg-brass-dark font-mono uppercase tracking-wider">
                                    Apply Filter
                                </button>
                            </div>
                        </form>

                        <div class="flex items-center gap-2 pt-3 border-t border-line/40">
                            <span class="text-xs font-semibold text-vellum-faint">Active Resolved Range:</span>
                            <span class="text-xs font-mono font-bold text-brass uppercase tracking-wider">{{ $resolvedRangeLabel }}</span>
                        </div>
                    </div>

                    <!-- Reports & Exports Reconciliation Center -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
                        <!-- Left Panel: Report Categories -->
                        <div class="panel bg-cream border border-line shadow-soft p-2.5 rounded-3xl lg:col-span-1">
                            <p class="text-[10px] font-bold text-vellum uppercase tracking-wider px-3.5 py-2.5 text-vellum-faint font-mono">Report Categories</p>
                            <div class="space-y-0.5">
                                <template x-for="cat in [
                                    { id: 'payroll_summary', label: 'Payroll Summary' },
                                    { id: 'attendance_export', label: 'Attendance Export' },
                                    { id: 'monthly_attendance', label: 'Monthly Attendance' },
                                    { id: 'leave_report', label: 'Leave Report' },
                                    { id: 'deduction_report', label: 'Deduction Report' },
                                    { id: 'salary_report', label: 'Salary Report' },
                                    { id: 'payroll_reconciliation', label: 'Payroll Reconciliation' },
                                    { id: 'employee_payroll_detail', label: 'Employee Payroll Detail' },
                                    { id: 'department_payroll', label: 'Department Payroll' },
                                    { id: 'overtime_report', label: 'Overtime Report' },
                                    { id: 'disbursement_register', label: 'Disbursement Register' }
                                ]" :key="cat.id">
                                    <button @click="selectedReport = cat.id"
                                            class="w-full text-left px-3.5 py-2.5 rounded-xl text-xs font-semibold transition"
                                            :class="selectedReport === cat.id ? 'bg-brass text-walnut font-bold shadow-soft' : 'text-walnut/85 hover:bg-brass/10 hover:text-walnut' "
                                            x-text="cat.label"></button>
                                </template>
                            </div>
                        </div>

                        <!-- Right Panel: Export Details and Action -->
                        <div class="panel bg-cream border border-line shadow-soft p-6 rounded-3xl lg:col-span-3 space-y-6">
                            <div>
                                <h4 class="font-display font-medium text-lg text-vellum" x-text="getReportTitle()"></h4>
                                <p class="text-[12px] text-vellum-faint mt-1" x-text="getReportDescription()"></p>
                            </div>

                            <form method="POST" action="{{ route('admin.payroll.reports.export') }}" class="border-t border-line pt-6 space-y-5">
                                @csrf
                                <input type="hidden" name="category" :value="selectedReport">
                                <input type="hidden" name="report_filter" value="{{ $reportFilter }}">
                                <input type="hidden" name="start_date" value="{{ $reportStartDate }}">
                                <input type="hidden" name="end_date" value="{{ $reportEndDate }}">
                                <input type="hidden" name="report_cycle" value="{{ $reportCycle }}">

                                <div class="bg-surface/50 border border-line p-4 rounded-2xl space-y-2">
                                    <p class="text-xs font-semibold text-vellum">Exporting constraints:</p>
                                    <ul class="text-[11px] text-vellum-muted list-disc list-inside space-y-1">
                                        <li>Period Range: <span class="font-mono font-bold text-brass">{{ $resolvedRangeLabel }}</span></li>
                                        <li>File Type: <span class="font-bold">Real Excel Workbook (.xlsx)</span></li>
                                        <li>Contains fully reconciled and synchronised canonical figures.</li>
                                    </ul>
                                </div>

                                <button type="submit" class="inline-flex items-center gap-2.5 text-xs font-semibold bg-forest hover:bg-forest-dark text-cream px-5 py-3.5 rounded-xl transition shadow-soft uppercase tracking-wider font-mono">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Generate & Download Excel Report
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Visual Charts Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                        <template x-for="report in reports" :key="report.title">
                            <div class="panel bg-cream border border-line shadow-soft p-6 space-y-4 rounded-3xl">
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
                                                                <input type="text" x-model="field.value"
                                                                       class="w-full text-xs bg-surface-raised border border-hairline rounded px-3 py-2 text-vellum focus:ring-1 focus:ring-brass focus:border-brass outline-none">
                                                            </template>
                                                            <template x-if="field.type === 'number'">
                                                                <input type="number" :step="field.step || '1'" x-model.number="field.value"
                                                                       class="w-full text-xs bg-surface-raised border border-hairline rounded px-3 py-2 text-vellum focus:ring-1 focus:ring-brass focus:border-brass outline-none">
                                                            </template>
                                                            <template x-if="field.type === 'select'">
                                                                <select x-model="field.value"
                                                                        class="w-full text-xs bg-surface-raised border border-hairline rounded px-3 py-2 text-vellum focus:ring-1 focus:ring-brass focus:border-brass outline-none font-semibold">
                                                                    <template x-for="opt in field.options" :key="opt.value">
                                                                        <option :value="opt.value" x-text="opt.label" :selected="opt.value == field.value"></option>
                                                                    </template>
                                                                </select>
                                                            </template>
                                                            <template x-if="field.type === 'toggle'">
                                                                <div class="flex items-center">
                                                                    <input type="checkbox" x-model="field.value"
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

                                        <div class="flex flex-col gap-4 pt-4 border-t border-line" x-data="{ previewData: null, previewing: false }">
                                            <div x-show="previewData" class="p-4 bg-brass-light/20 border border-brass/30 rounded-xl text-xs space-y-2" x-cloak>
                                                <h5 class="font-bold text-brass uppercase">Simulation Impact Summary</h5>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                                    <div>
                                                        <span class="text-[9px] uppercase font-bold text-vellum-faint block">Affected Employees</span>
                                                        <span class="font-semibold text-vellum mt-0.5 block" x-text="previewData ? previewData.affected_records + ' employee(s)' : ''"></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[9px] uppercase font-bold text-vellum-faint block">Gross Delta Sum</span>
                                                        <span class="font-semibold text-vellum mt-0.5 block font-mono" x-text="previewData ? (previewData.gross_delta >= 0 ? '+' : '') + '₹' + previewData.gross_delta.toLocaleString('en-IN') : ''"></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[9px] uppercase font-bold text-vellum-faint block">Net Delta Sum</span>
                                                        <span class="font-semibold text-vellum mt-0.5 block font-mono" :class="previewData && previewData.net_delta < 0 ? 'text-burgundy' : 'text-forest'" x-text="previewData ? (previewData.net_delta >= 0 ? '+' : '') + '₹' + previewData.net_delta.toLocaleString('en-IN') : ''"></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-[9px] uppercase font-bold text-vellum-faint block">Stale Approvals Reset</span>
                                                        <span class="font-semibold text-vellum mt-0.5 block" x-text="previewData ? previewData.stale_approvals + ' approvals' : ''"></span>
                                                    </div>
                                                </div>
                                                <p class="text-[11px] text-vellum-muted mt-1 leading-relaxed">
                                                    ⚠ Saving these policy changes will force recalculation of all open employee records, incrementing calculation version and invalidating signed-off approvals.
                                                </p>
                                            </div>
                                            
                                            <div class="flex justify-end gap-3">
                                                <button type="button" @click="previewConfigImpact(group.id, $data)" :disabled="previewing"
                                                        class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider border border-brass text-brass hover:bg-brass/5 transition">
                                                    <span x-text="previewing ? 'Simulating...' : 'Preview Configuration Impact'"></span>
                                                </button>
                                                <button type="button" @click="saveAndForceRecalculate(group.id)"
                                                        class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider bg-brass text-cream hover:bg-brass-dark transition shadow-soft">
                                                    Save & Recalculate Policy
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Actual Payroll Cycle Management Section for Cycle Tab -->
                                    <template x-if="group.id === 'cycle'">
                                        <div class="mt-8 border-t border-line pt-6 space-y-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h5 class="font-display font-medium text-base text-vellum">Actual Payroll Cycle Instances</h5>
                                                    <p class="text-xs text-vellum-faint mt-0.5">Lifecycle history of processed payroll periods.</p>
                                                </div>
                                                <button type="button" @click="openNextCyclePreviewModal()"
                                                        class="px-4 py-2 bg-forest hover:bg-forest-dark text-cream text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-soft">
                                                    Create / Open Next Cycle
                                                </button>
                                            </div>

                                            <div class="border border-line rounded-xl overflow-hidden text-xs bg-cream">
                                                <div class="p-3 bg-surface/50 border-b border-line font-mono text-[10px] uppercase font-bold text-vellum-faint grid grid-cols-12 gap-2">
                                                    <span class="col-span-2">Cycle Name</span>
                                                    <span class="col-span-2">Start Date</span>
                                                    <span class="col-span-2">End Date</span>
                                                    <span class="col-span-2">Payment Date</span>
                                                    <span class="col-span-2 text-center">Status</span>
                                                    <span class="col-span-1 text-center">Eligible</span>
                                                    <span class="col-span-1 text-right">Locked</span>
                                                </div>
                                                <div class="divide-y divide-line/45">
                                                    <template x-for="ci in cycleInstances" :key="ci.period">
                                                        <div class="p-3 grid grid-cols-12 gap-2 items-center hover:bg-surface/10 transition"
                                                             :class="ci.period === cycle.period ? 'bg-brass/5' : ''">
                                                            <div class="col-span-2 font-bold text-vellum flex items-center gap-1.5">
                                                                <span x-text="ci.period"></span>
                                                                <template x-if="ci.period === cycle.period">
                                                                    <span class="px-1.5 py-0.5 bg-forest/10 text-forest text-[9px] uppercase tracking-wide font-bold rounded">Active</span>
                                                                </template>
                                                            </div>
                                                            <span class="col-span-2 font-mono text-vellum-muted" x-text="ci.start_date"></span>
                                                            <span class="col-span-2 font-mono text-vellum-muted" x-text="ci.end_date"></span>
                                                            <span class="col-span-2 font-mono text-vellum-muted" x-text="ci.payment_date"></span>
                                                            <div class="col-span-2 text-center">
                                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded border"
                                                                      :class="statusChip(ci.status)"
                                                                      x-text="statusLabel(ci.status)"></span>
                                                            </div>
                                                            <span class="col-span-1 text-center font-mono" x-text="ci.eligible_count"></span>
                                                            <span class="col-span-1 text-right font-mono" x-text="ci.locked_count + '/' + ci.record_count"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
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
                                    <!-- Profile Summary -->
                                    <div class="grid grid-cols-2 gap-4 text-xs bg-surface/40 p-4 rounded-xl border border-hairline/25">
                                        <div>
                                            <span class="text-[9px] uppercase font-bold text-vellum-faint block">Joining Date</span>
                                            <span class="font-medium text-vellum mt-0.5 block" x-text="selectedEmployee.joiningDate || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-[9px] uppercase font-bold text-vellum-faint block">Category / Tenure</span>
                                            <span class="font-bold text-vellum mt-0.5 block uppercase" x-text="selectedEmployee.employment_category"></span>
                                        </div>
                                        <div>
                                            <span class="text-[9px] uppercase font-bold text-vellum-faint block">Selected Payroll Period</span>
                                            <span class="font-mono text-vellum mt-0.5 block" x-text="cycle.period"></span>
                                        </div>
                                        <div>
                                            <span class="text-[9px] uppercase font-bold text-vellum-faint block">Cycle Rule Type</span>
                                            <span class="font-mono text-vellum mt-0.5 block" x-text="selectedEmployee.importSource === 'Manual Entry' ? 'Custom' : 'Standard Monthly'"></span>
                                        </div>
                                    </div>

                                    <!-- Earnings & Deductions Hierarchy Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                                        <!-- Earnings Card -->
                                        <div class="border border-line rounded-xl p-4 bg-surface/20 space-y-3">
                                            <h5 class="font-mono text-[10px] uppercase font-bold text-brass border-b border-brass/25 pb-1">Earnings Component</h5>
                                            <div class="space-y-2">
                                                <div class="flex justify-between"><span>Base Salary</span><span class="num font-semibold text-vellum" x-text="'₹' + selectedEmployee.baseSalary.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Allowances</span><span class="num font-semibold text-vellum" x-text="'₹' + selectedEmployee.allowances.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Overtime Pay</span><span class="num font-semibold text-forest" x-text="'₹' + selectedEmployee.overtimePay.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Manual Adjustments</span><span class="num font-semibold text-forest" x-text="'₹' + selectedEmployee.bonuses.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between border-t border-line/45 pt-1.5 font-bold"><span>Gross Salary</span><span class="num text-vellum" x-text="'₹' + selectedEmployee.gross.toLocaleString('en-IN')"></span></div>
                                            </div>
                                        </div>

                                        <!-- Deductions Card -->
                                        <div class="border border-line rounded-xl p-4 bg-surface/20 space-y-3">
                                            <h5 class="font-mono text-[10px] uppercase font-bold text-burgundy border-b border-burgundy/25 pb-1">Deductions Component</h5>
                                            <div class="space-y-2">
                                                <div class="flex justify-between"><span>Attendance Deductions</span><span class="num font-semibold text-burgundy" x-text="'–₹' + selectedEmployee.attendanceDeductions.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Leave Deductions</span><span class="num font-semibold text-burgundy" x-text="'–₹' + selectedEmployee.leaveDeductions.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Provident Fund (PF)</span><span class="num font-semibold text-burgundy" x-text="'–₹' + selectedEmployee.pf.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>ESI</span><span class="num font-semibold text-burgundy" x-text="'–₹' + selectedEmployee.esi.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Professional Tax</span><span class="num font-semibold text-burgundy" x-text="'–₹' + selectedEmployee.profTax.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between"><span>Tax (TDS)</span><span class="num font-semibold text-burgundy" x-text="'–₹' + selectedEmployee.taxAmt.toLocaleString('en-IN')"></span></div>
                                                <div class="flex justify-between border-t border-line/45 pt-1.5 font-bold"><span>Total Deductions</span><span class="num text-burgundy" x-text="'–₹' + selectedEmployee.deductions.toLocaleString('en-IN')"></span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Summary Rates & States -->
                                    <div class="bg-surface/50 border border-hairline/25 p-4 rounded-xl text-xs space-y-2.5">
                                        <h5 class="font-mono text-[10px] uppercase font-bold text-vellum-faint border-b border-line/45 pb-1">Operational Metrics</h5>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                            <div>
                                                <span class="text-[9px] text-vellum-muted block font-sans">Daily Salary Rate</span>
                                                <span class="font-mono font-bold text-vellum mt-0.5 block" x-text="'₹' + selectedEmployee.dailyRate.toLocaleString('en-IN')"></span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] text-vellum-muted block font-sans">Hourly Rate</span>
                                                <span class="font-mono font-bold text-vellum mt-0.5 block" x-text="'₹' + selectedEmployee.hourlyRate.toLocaleString('en-IN')"></span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] text-vellum-muted block font-sans">Calendar Days</span>
                                                <span class="font-mono font-bold text-vellum mt-0.5 block" x-text="selectedEmployee.calendarDays + ' days'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] text-vellum-muted block font-sans">Eligible Working Days</span>
                                                <span class="font-mono font-bold text-vellum mt-0.5 block" x-text="selectedEmployee.workingDays + ' days'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Disbursement Summary & Approvals -->
                                    <div class="bg-surface-raised border border-brass/30 p-5 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-vellum-faint">Net Salary Disbursed</span>
                                            <h3 class="font-display font-bold text-2xl text-forest mt-0.5" x-text="'₹' + selectedEmployee.net.toLocaleString('en-IN')"></h3>
                                            <p class="text-[10px] text-vellum-muted mt-0.5">Calculation version: <span class="font-mono font-bold" x-text="'v' + selectedEmployee.calculation_version"></span></p>
                                        </div>
                                        <div class="text-xs space-y-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-vellum">Employee Review:</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
                                                      :class="{
                                                        approved: 'bg-forest/10 text-forest border-forest/20',
                                                        disputed: 'bg-burgundy/10 text-burgundy border-burgundy/20',
                                                        stale: 'bg-cognac/10 text-cognac border-cognac/20',
                                                        pending: 'bg-brass/10 text-brass border-brass/20'
                                                      }[selectedEmployee.employee_review_status]"
                                                      x-text="selectedEmployee.employee_review_status"></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-vellum">Admin Approval:</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
                                                      :class="selectedEmployee.admin_approved_at ? 'bg-forest/10 text-forest border-forest/20' : 'bg-brass/10 text-brass border-brass/20'"
                                                      x-text="selectedEmployee.admin_approved_at ? 'Approved' : 'Pending'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ATTENDANCE SNAPSHOT TAB -->
                                <div x-show="drawerTab === 'attendance'" class="space-y-6">
                                    <div class="border border-line rounded-xl overflow-hidden text-xs bg-cream">
                                        <div class="p-3 bg-surface/50 border-b border-line font-mono text-[10px] uppercase font-bold text-vellum-faint grid grid-cols-12 gap-2">
                                            <span class="col-span-2">Date</span>
                                            <span class="col-span-2">Check In/Out</span>
                                            <span class="col-span-1 text-center">Hours</span>
                                            <span class="col-span-2 text-center">Original</span>
                                            <span class="col-span-2 text-center">Resolved</span>
                                            <span class="col-span-1 text-center">Override</span>
                                            <span class="col-span-2 text-right">Deduction</span>
                                        </div>
                                        <div class="divide-y divide-line/45 max-h-[350px] overflow-y-auto">
                                            <template x-for="day in selectedEmployee.attendanceSnapshot" :key="day.date">
                                                <div class="p-3 grid grid-cols-12 gap-2 items-center hover:bg-surface/10 transition">
                                                    <div class="col-span-2">
                                                        <span class="font-bold text-vellum block" x-text="day.date"></span>
                                                        <span class="text-[9px] text-vellum-faint block uppercase font-sans" x-text="day.dayOfWeek"></span>
                                                    </div>
                                                    <div class="col-span-2 font-mono text-[10.5px]">
                                                        <span class="text-vellum block" x-text="day.check_in ? day.check_in.split(' ')[1] || day.check_in : '—'"></span>
                                                        <span class="text-vellum-muted block" x-text="day.check_out ? day.check_out.split(' ')[1] || day.check_out : '—'"></span>
                                                    </div>
                                                    <span class="col-span-1 text-center font-mono" x-text="day.hours_worked ? Number(day.hours_worked).toFixed(1) + 'h' : '—'"></span>
                                                    <div class="col-span-2 text-center">
                                                        <span class="text-[9.5px] px-1.5 py-0.5 rounded uppercase font-mono bg-surface/50 border border-line text-vellum-faint" x-text="day.original_status || '—'"></span>
                                                    </div>
                                                    <div class="col-span-2 text-center">
                                                        <span class="text-[10px] px-2 py-0.5 font-bold uppercase rounded"
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
                                                    <div class="col-span-1 text-center font-bold">
                                                        <span x-show="day.is_override" class="text-brass" title="Manual override applied">★</span>
                                                        <span x-show="!day.is_override" class="text-vellum-muted/20">—</span>
                                                    </div>
                                                    <span class="col-span-2 text-right font-mono font-bold text-burgundy" x-text="day.deducted_amount > 0 ? '–₹' + day.deducted_amount.toLocaleString('en-IN') : '₹0'"></span>
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

        <!-- Next Cycle Preview Modal -->
        <div x-show="showNextCycleModal" class="fixed inset-0 overflow-hidden z-50 flex items-center justify-center" x-cloak>
            <div class="absolute inset-0 bg-walnut/40 backdrop-blur-sm" @click="showNextCycleModal = false"></div>
            <div class="bg-cream border border-line rounded-3xl p-6 shadow-lift max-w-lg w-full relative z-10 space-y-5">
                <div class="flex justify-between items-center border-b border-line pb-3">
                    <h3 class="font-display font-medium text-lg text-vellum">Create / Open Next Payroll Cycle</h3>
                    <button @click="showNextCycleModal = false" class="text-2xl text-vellum-faint hover:text-vellum font-light">&times;</button>
                </div>
                
                <template x-if="nextCyclePreview">
                    <div class="space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-4 bg-surface/40 p-4 rounded-xl border border-hairline/25">
                            <div>
                                <span class="text-[9px] uppercase font-bold text-vellum-faint block">Cycle Period</span>
                                <span class="font-bold text-vellum mt-0.5 block font-mono text-sm" x-text="nextCyclePreview.period"></span>
                            </div>
                            <div>
                                <span class="text-[9px] uppercase font-bold text-vellum-faint block">Resolved Payment Date</span>
                                <span class="font-bold text-vellum mt-0.5 block font-mono text-sm" x-text="nextCyclePreview.payment_date"></span>
                            </div>
                            <div>
                                <span class="text-[9px] uppercase font-bold text-vellum-faint block">Resolved Start Date</span>
                                <span class="font-semibold text-vellum mt-0.5 block font-mono" x-text="nextCyclePreview.start_date"></span>
                            </div>
                            <div>
                                <span class="text-[9px] uppercase font-bold text-vellum-faint block">Resolved End Date</span>
                                <span class="font-semibold text-vellum mt-0.5 block font-mono" x-text="nextCyclePreview.end_date"></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-b border-line/45 pb-1">
                            <span class="font-bold text-vellum uppercase tracking-wide text-[10px]">Newly Entering Employees (<span x-text="nextCyclePreview.newly_entering.length"></span>)</span>
                        </div>
                        <div class="max-h-24 overflow-y-auto divide-y divide-line/45 border border-line rounded-lg bg-surface/10 p-2">
                            <template x-for="emp in nextCyclePreview.newly_entering" :key="emp.employee_id">
                                <div class="py-1 flex justify-between">
                                    <span class="font-semibold text-vellum" x-text="emp.name"></span>
                                    <span class="font-mono text-vellum-muted" x-text="emp.employee_id + ' (Joined: ' + emp.joining_date + ')'"></span>
                                </div>
                            </template>
                            <template x-if="nextCyclePreview.newly_entering.length === 0">
                                <p class="text-vellum-muted italic py-1">None</p>
                            </template>
                        </div>

                        <div class="flex items-center justify-between border-b border-line/45 pb-1">
                            <span class="font-bold text-vellum uppercase tracking-wide text-[10px]">Excluded Employees (<span x-text="nextCyclePreview.excluded.length"></span>)</span>
                        </div>
                        <div class="max-h-24 overflow-y-auto divide-y divide-line/45 border border-line rounded-lg bg-surface/10 p-2">
                            <template x-for="emp in nextCyclePreview.excluded" :key="emp.employee_id">
                                <div class="py-1 flex justify-between">
                                    <span class="font-semibold text-vellum" x-text="emp.name"></span>
                                    <span class="text-burgundy" x-text="emp.reason"></span>
                                </div>
                            </template>
                            <template x-if="nextCyclePreview.excluded.length === 0">
                                <p class="text-vellum-muted italic py-1">None</p>
                            </template>
                        </div>

                        <div class="bg-forest/10 border border-forest/20 p-3 rounded-xl flex gap-2">
                            <span class="text-forest">✓</span>
                            <p class="text-[11px] text-forest/90 leading-normal">
                                Opening this cycle will automatically instantiate payroll records for all <strong x-text="nextCyclePreview.eligible_count"></strong> eligible employees for <span x-text="nextCyclePreview.period"></span>.
                            </p>
                        </div>
                    </div>
                </template>
                
                <div class="flex justify-end gap-3 border-t border-line pt-4">
                    <button type="button" @click="showNextCycleModal = false" class="px-4 py-2 border border-hairline text-vellum rounded-xl text-xs font-semibold">Cancel</button>
                    <button type="button" @click="confirmCreateNextCycle()" 
                            class="px-4 py-2 bg-forest hover:bg-forest-dark text-cream font-bold uppercase tracking-wider rounded-xl text-xs transition shadow-soft">
                        Confirm & Open Next Cycle
                    </button>
                </div>
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
                showNextCycleModal: false,
                nextCyclePreview: null,
                cycleInstances: @json($cycleInstances),
                cycleLocked: {{ $cycle->status === 'locked' ? 'true' : 'false' }},
                toast: null,
                unlockPromptOpen: false,
                unlockReason: '',
                selectedRecordToUnlock: null,
                reopenPromptOpen: false,
                reopenReason: '',
                selectedRecordToReopen: null,
                filters: { search: '', dept: '', status: '', sort: 'name', category: '', employeeReview: '', adminApproved: '', lockState: '' },
                payslipFilters: { search: '', dept: '', status: '' },
                auditFilters: { search: '', category: '' },

                donutColors: ['#C6941C', '#6B2039', '#1E3D30', '#A85D1E', '#8A7B5C'],

                navItems: [
                    { id: 'dashboard', label: 'Dashboard' },
                    { id: 'employees', label: 'Employee Payroll' },
                    { id: 'ledger', label: 'Salary Ledger' },
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

                pipeline: @json($pipeline),

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
                selectedReport: 'payroll_summary',
                getReportTitle() {
                    const titles = {
                        payroll_summary: 'Payroll Summary Report',
                        attendance_export: 'Detailed Attendance Export',
                        monthly_attendance: 'Monthly Attendance Regularity Report',
                        leave_report: 'Detailed Leave Report',
                        deduction_report: 'Detailed Deduction Report',
                        salary_report: 'Salary Master / Salary Report',
                        payroll_reconciliation: 'Payroll Reconciliation Export (Redundancy)',
                        employee_payroll_detail: 'Employee Payroll Detail Report',
                        department_payroll: 'Department Payroll Cost Report',
                        overtime_report: 'Overtime Report',
                        disbursement_register: 'Disbursement Register & Payslip Status',
                    };
                    return titles[this.selectedReport] || 'Report';
                },
                getReportDescription() {
                    const descs = {
                        payroll_summary: 'A high-level summary of gross salaries, total deductions, and net disbursements for all employees.',
                        attendance_export: 'Employee-day level attendance evidence for the selected date range. Includes shifts, check-in/out times, late minutes, overrides, and leaves.',
                        monthly_attendance: 'Aggregates employee regularity: present days, absent days, leave counts, overtime hours, punctuality and absenteeism rates.',
                        leave_report: 'A detailed breakdown of leaves: types, opening balances, leaves taken, approval states, and payroll impacts.',
                        deduction_report: 'Lists all detailed deduction components separately: Attendance, Unpaid Leave, PF, ESI, Professional Tax, TDS, and other statutory/manual deductions.',
                        salary_report: 'Contains the canonical salary structures, base salaries, allowances, and statutory settings effective during the period.',
                        payroll_reconciliation: 'The comprehensive redundancy report with enough raw data (identity, period, salary basis, attendance basis, earnings, deductions, approvals, locks) to manually reconstruct payroll.',
                        employee_payroll_detail: 'Exposes employee-by-employee detailed line items for earnings and deductions.',
                        department_payroll: 'Summarizes payroll expenditures and cost comparison across departments.',
                        overtime_report: 'Detailed overtime breakdown: hours worked, hourly rates, multipliers, and calculated overtime pay.',
                        disbursement_register: 'Tracks bank disbursement parameters: account details, IFSC, net salaries, approval timestamps, and lock status.',
                    };
                    return descs[this.selectedReport] || '';
                },
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
                        const matchesCategory = !this.filters.category || e.employment_category === this.filters.category;
                        const matchesEmployeeReview = !this.filters.employeeReview || e.employee_review_status === this.filters.employeeReview;
                        const matchesAdminApproved = !this.filters.adminApproved || (this.filters.adminApproved === 'approved' ? !!e.admin_approved_at : !e.admin_approved_at);
                        const matchesLockState = !this.filters.lockState || (this.filters.lockState === 'locked' ? !!e.locked : !e.locked);
                        return matchesSearch && matchesDept && matchesStatus && matchesCategory && matchesEmployeeReview && matchesAdminApproved && matchesLockState;
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

                get filteredAuditLogs() {
                    return this.auditTrail.filter(log => {
                        const s = this.auditFilters.search.toLowerCase();
                        const matchesSearch = !s || 
                            log.action.toLowerCase().includes(s) || 
                            log.actor.toLowerCase().includes(s) || 
                            log.category.toLowerCase().includes(s) ||
                            (log.oldValue && String(log.oldValue).toLowerCase().includes(s)) ||
                            (log.newValue && String(log.newValue).toLowerCase().includes(s));
                        const matchesCategory = !this.auditFilters.category || log.category === this.auditFilters.category;
                        return matchesSearch && matchesCategory;
                    });
                },

                ledgerFormulaRows(e) {
                    if (!e) return [];
                    const p = this.policies;
                    const dailyRate = Math.round(e.baseSalary / e.calendarDays);
                    
                    const rows = [
                        { label: 'Base Salary', value: e.baseSalary, explain: 'Authoritative fixed monthly base salary.', calc: 'Base = ₹' + e.baseSalary.toLocaleString('en-IN'), auditRef: 'STRUCT-' + e.id },
                        { label: 'Allowances', value: e.allowances, explain: 'Retrieved structural standard allowances.', calc: 'Allowances = ₹' + e.allowances.toLocaleString('en-IN') },
                        { label: 'Daily Rate (Segment)', value: dailyRate, explain: 'Derived daily rate = Monthly Base / ' + e.calendarDays + ' calendar days.', calc: 'Daily = ₹' + dailyRate.toLocaleString('en-IN') },
                        { label: 'Hourly Shift Rate', value: e.hourlyRate, explain: 'Derived basic hourly shift rate.', calc: 'Hourly = ₹' + e.hourlyRate.toLocaleString('en-IN') }
                    ];

                    // Dynamically listing each individual attendance / leave deduction date
                    if (e.attendanceSnapshot && e.attendanceSnapshot.length > 0) {
                        e.attendanceSnapshot.forEach(day => {
                            if (day.deducted_amount > 0) {
                                const statusLabel = day.status === 'absent' ? 'Unpaid Absence' : 
                                                    (day.status === 'half' ? 'Half Day Deduction' : 'Unpaid Leave');
                                rows.push({
                                    label: day.date + ' ' + statusLabel,
                                    value: -day.deducted_amount,
                                    tone: 'oxblood',
                                    explain: 'Resolved state: ' + day.status.toUpperCase() + ' (Original: ' + day.original_status + ')',
                                    calc: 'Rate ₹' + dailyRate.toLocaleString('en-IN') + ' × factor'
                                });
                            }
                        });
                    }

                    // Overtime
                    if (e.overtimeHours > 0) {
                        rows.push({
                            label: 'Overtime Pay',
                            value: e.overtimePay,
                            tone: 'forest',
                            explain: e.overtimeHours + ' overtime hour(s) worked.',
                            calc: e.overtimeHours + ' hrs × ₹' + e.hourlyRate.toLocaleString('en-IN') + ' × multiplier'
                        });
                    }

                    // Bonuses / Corrections
                    if (e.bonuses > 0) {
                        rows.push({
                            label: 'Discretionary Adjustment',
                            value: e.bonuses,
                            tone: 'forest',
                            explain: 'Approved administrative adjustment delta.',
                            calc: 'Manual override correction'
                        });
                    }

                    // Gross
                    rows.push({ label: 'Gross Salary', value: e.gross, emphasis: true });

                    // Net
                    rows.push({ label: 'Net Salary', value: e.net, emphasis: true });

                    return rows;
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
                    return { 
                        calculation: 'bg-brass', 
                        approval: 'bg-forest', 
                        correction: 'bg-burgundy', 
                        configuration: 'bg-brass-dark', 
                        lock: 'bg-vellum', 
                        unlock: 'bg-cognac', 
                        dispute: 'bg-burgundy', 
                        payslip: 'bg-forest' 
                    }[type] || 'bg-vellum-faint'
                },
                auditBadge(type) {
                    return { 
                        calculation: 'bg-brass/10 text-brass-dark', 
                        approval: 'bg-forest/10 text-forest', 
                        correction: 'bg-burgundy/10 text-burgundy', 
                        configuration: 'bg-brass-dark/10 text-brass-dark', 
                        lock: 'bg-surface text-vellum border border-line', 
                        unlock: 'bg-cognac/10 text-cognac', 
                        dispute: 'bg-burgundy/10 text-burgundy', 
                        payslip: 'bg-forest/10 text-forest' 
                    }[type] || 'bg-surface'
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
                },

                approveRecordAdmin(emp) {
                    this.toast = 'Signing off Admin approval...';
                    fetch(`/admin/payroll/records/${emp.record_id}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = data.message;
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                lockRecordAdmin(emp) {
                    this.toast = 'Sealing record & creating snapshot...';
                    fetch(`/admin/payroll/records/${emp.record_id}/lock`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = data.message;
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                unlockRecordAdminPrompt(emp) {
                    this.selectedRecordToUnlock = emp;
                    this.unlockReason = '';
                    this.unlockPromptOpen = true;
                },

                submitUnlockRecord() {
                    if (!this.unlockReason || this.unlockReason.length < 5) {
                        alert('Please enter a valid reason (min 5 characters) for override unlock.');
                        return;
                    }
                    this.unlockPromptOpen = false;
                    this.toast = 'Unlocking employee payroll record...';
                    
                    fetch(`/admin/payroll/records/${this.selectedRecordToUnlock.record_id}/unlock`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            reason: this.unlockReason
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Record unlocked successfully.';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                reopenRecordAdminPrompt(emp) {
                    this.selectedRecordToReopen = emp;
                    this.reopenReason = '';
                    this.reopenPromptOpen = true;
                },

                submitReopenRecord() {
                    if (!this.reopenReason || this.reopenReason.length < 5) {
                        alert('Please enter a valid reason (min 5 characters) for reopening.');
                        return;
                    }
                    this.reopenPromptOpen = false;
                    this.toast = 'Reopening employee payroll record...';
                    
                    fetch(`/admin/payroll/records/${this.selectedRecordToReopen.record_id}/reopen`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            reason: this.reopenReason
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Record reopened and recalculated successfully.';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                resolveRecordStatus(emp) {
                    if (emp.locked) {
                        if (emp.payslip_status === 'published') {
                            return { label: 'Payslip Available', class: 'bg-forest/15 text-forest border-forest/30' };
                        } else if (emp.payslip_status === 'generated') {
                            return { label: 'Payslip Generated (Pending Pub)', class: 'bg-brass/15 text-brass-dark border-brass/30' };
                        } else {
                            return { label: 'Locked / Finalised', class: 'bg-burgundy/15 text-burgundy border-burgundy/30' };
                        }
                    }
                    
                    if (emp.payslip_status === 'revoked') {
                        return { label: 'Reopened', class: 'bg-cognac/15 text-cognac border-cognac/30' };
                    }
                    
                    if (emp.employee_review_status === 'approved' && emp.admin_approved_at) {
                        return { label: 'Ready to Lock', class: 'bg-forest/15 text-forest border-forest/30' };
                    }
                    
                    if (emp.employee_review_status === 'approved' && !emp.admin_approved_at) {
                        return { label: 'Awaiting Admin Approval', class: 'bg-brass/15 text-brass-dark border-brass/30' };
                    }
                    
                    if (emp.employee_review_status === 'disputed') {
                        return { label: 'Disputed', class: 'bg-burgundy/15 text-burgundy border-burgundy/30' };
                    }
                    
                    if (emp.employee_review_status === 'stale') {
                        return { label: 'Stale (Recalc Pending)', class: 'bg-cognac/15 text-cognac border-cognac/30' };
                    }
                    
                    return { label: 'Awaiting Employee Review', class: 'bg-brass/10 text-brass-dark border-brass/20' };
                },

                resolveDisputeAdmin(id, status) {
                    const note = document.getElementById(`dispute-note-${id}`).value;
                    if (!note || note.length < 5) {
                        alert('Please enter resolution/rejection comments (min 5 characters).');
                        return;
                    }
                    this.toast = 'Submitting dispute resolution...';
                    
                    fetch(`/admin/payroll/disputes/${id}/resolve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            status: status,
                            notes: note
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Dispute resolved successfully!';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                generateSinglePayslip(emp) {
                    this.toast = 'Generating employee payslip snapshot...';
                    fetch(`/admin/payroll/records/${emp.record_id}/payslip/generate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Payslip generated successfully!';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                publishSinglePayslip(emp) {
                    this.toast = 'Publishing payslip to employee dashboard...';
                    fetch(`/admin/payroll/records/${emp.record_id}/payslip/publish`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Payslip published successfully!';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                bulkGeneratePayslips() {
                    this.toast = 'Bulk generating payslips...';
                    fetch('/admin/payroll/payslips/bulk-generate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            period: this.cycle.period
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Bulk generated successfully!';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                bulkPublishPayslips() {
                    this.toast = 'Bulk publishing payslips...';
                    fetch('/admin/payroll/payslips/bulk-publish', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            period: this.cycle.period
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Bulk published successfully!';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                previewConfigImpact(groupId, alpineData) {
                    alpineData.previewing = true;
                    this.toast = 'Simulating policy updates on active records...';

                    // Gather inputs for this group form dynamically
                    const fields = {};
                    const group = this.settingsGroups.find(g => g.id === groupId);
                    if (group && group.fields) {
                        group.fields.forEach(f => {
                            if (f.key) {
                                fields[f.key] = f.value;
                            }
                        });
                    }

                    fetch('/admin/payroll/settings/preview', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            group: groupId,
                            fields: fields,
                            period: this.cycle.period
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        alpineData.previewing = false;
                        if (data.success) {
                            alpineData.previewData = data;
                            this.toast = 'Simulation complete!';
                            setTimeout(() => { this.toast = null }, 2000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                saveAndForceRecalculate(groupId) {
                    this.toast = 'Saving settings and force recalculating...';
                    
                    const fields = {};
                    const group = this.settingsGroups.find(g => g.id === groupId);
                    if (group && group.fields) {
                        group.fields.forEach(f => {
                            if (f.key) {
                                fields[f.key] = f.value;
                            }
                        });
                    }

                    fetch('/admin/payroll/settings/save-recalculate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            group: groupId,
                            fields: fields,
                            period: this.cycle.period
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = 'Settings saved & cycle recalculated successfully!';
                            setTimeout(() => { window.location.reload() }, 1000);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                },

                openNextCyclePreviewModal() {
                    this.toast = 'Loading next cycle preview...';
                    fetch('{{ route("admin.payroll.cycles.next-preview") }}')
                    .then(r => r.json())
                    .then(data => {
                        this.toast = null;
                        if (data.success) {
                            this.nextCyclePreview = data;
                            this.showNextCycleModal = true;
                        } else {
                            alert('Error loading preview: ' + data.message);
                        }
                    });
                },

                confirmCreateNextCycle() {
                    this.showNextCycleModal = false;
                    this.toast = 'Opening next payroll cycle...';
                    fetch('{{ route("admin.payroll.cycles.create") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.toast = data.message;
                            setTimeout(() => { window.location.href = `/admin/payroll?period=${data.period}` }, 1200);
                        } else {
                            this.toast = 'Error: ' + data.message;
                        }
                    });
                }
            }
        }
    </script>
@endsection
