@props(['user'])

<div x-data="attendanceCalendar({ userId: {{ $user->id }} })" x-init="init()" class="space-y-6">
    <style>
        .tabular { font-variant-numeric: tabular-nums; }
        
        /* perforated ledger-stub edge for the detail panel */
        .stub-edge {
            background-image: radial-gradient(circle, var(--canvas) 3px, transparent 3.5px);
            background-size: 14px 14px;
            background-position: -7px center;
        }

        .day-cell {
            transition: all 0.15s ease-in-out;
        }

        .day-cell:hover {
            transform: translateY(-1px);
        }

        .cell-tooltip { pointer-events: none; }
        .day-cell:hover .cell-tooltip { opacity: 1; transform: translate(-50%, -4px); }

        [x-cloak] { display: none !important; }
    </style>

    <!-- ============================== HEADER & NAVIGATION ============================== -->
    <div class="panel flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-[10px] tracking-[0.15em] uppercase text-brass font-bold mb-1">Workforce Ledger · Attendance Folio</p>
            <h2 class="font-display text-2xl font-semibold text-vellum">Employee Attendance Calendar</h2>
            <p class="text-xs text-vellum-muted mt-1 font-medium">
                <span class="text-vellum font-semibold" x-text="employee.name"></span>
                <span class="mx-1.5 text-hairline-strong">·</span>
                <span x-text="employee.dept"></span>
                <span class="mx-1.5 text-hairline-strong">·</span>
                <span class="font-mono text-[11px]" x-text="employee.id"></span>
            </p>
        </div>

        <!-- Month Navigation Cluster -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center rounded border border-hairline bg-surface-raised overflow-hidden">
                <button @click="prevMonth()" aria-label="Previous month" class="px-2.5 py-2 hover:bg-surface/50 transition-colors text-vellum-muted hover:text-vellum">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <div class="px-3 py-2 border-x border-hairline min-w-[140px] text-center">
                    <span class="font-display text-sm font-medium text-vellum tabular" x-text="monthLabel"></span>
                </div>
                <button @click="nextMonth()" aria-label="Next month" class="px-2.5 py-2 hover:bg-surface/50 transition-colors text-vellum-muted hover:text-vellum">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>

            <button @click="goToday()" class="px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded border border-hairline bg-surface-raised text-forest hover:bg-forest hover:text-white transition-colors">
                Today
            </button>

            <select x-model.number="viewMonth" @change="fetchData()" aria-label="Select month" class="text-xs rounded border border-hairline bg-surface-raised py-2 px-2.5 focus:ring-1 focus:ring-brass focus:outline-none">
                <template x-for="(m,i) in monthNames" :key="i">
                    <option :value="i" x-text="m"></option>
                </template>
            </select>

            <select x-model.number="viewYear" @change="fetchData()" aria-label="Select year" class="text-xs rounded border border-hairline bg-surface-raised py-2 px-2.5 focus:ring-1 focus:ring-brass focus:outline-none">
                <template x-for="y in yearRange" :key="y">
                    <option :value="y" x-text="y"></option>
                </template>
            </select>

            <!-- Month Range Dropdown -->
            <div class="relative">
                <button @click="showMonthRange = !showMonthRange; showDateRange = false" class="px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded border border-hairline bg-surface-raised text-vellum-muted hover:text-vellum transition-colors flex items-center gap-1">
                    <span>Month Range</span>
                    <svg class="w-3 h-3 text-vellum-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div x-show="showMonthRange" x-cloak @click.outside="showMonthRange = false" x-transition
                     class="absolute right-0 mt-2 w-64 bg-surface border border-hairline rounded shadow-lg p-3.5 z-30">
                    <p class="text-[10px] uppercase tracking-wider text-vellum-faint font-bold mb-2">Jump to Month</p>
                    <div class="grid grid-cols-3 gap-1.5">
                        <template x-for="(m,i) in monthNames" :key="i">
                            <button @click="viewMonth = i; fetchData(); showMonthRange = false"
                                    class="text-xs py-1.5 rounded border transition-colors"
                                    :class="i === viewMonth ? 'bg-forest text-white border-forest' : 'border-hairline text-vellum bg-surface-raised hover:border-brass'"
                                    x-text="m.slice(0,3)"></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Custom Date Range Dropdown -->
            <div class="relative">
                <button @click="showDateRange = !showDateRange; showMonthRange = false" class="px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded border border-hairline bg-surface-raised text-vellum-muted hover:text-vellum transition-colors flex items-center gap-1">
                    <span>Custom Range</span>
                    <svg class="w-3 h-3 text-vellum-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div x-show="showDateRange" x-cloak @click.outside="showDateRange = false" x-transition
                     class="absolute right-0 mt-2 w-72 bg-surface border border-hairline rounded shadow-lg p-4.5 z-30 space-y-3">
                    <div>
                        <p class="text-[10px] uppercase tracking-wider text-vellum-faint font-bold mb-1">Custom Date Range Lookup</p>
                        <p class="text-[11px] text-vellum-muted">Select start and end dates to filter metrics & timeline.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="date" x-model="rangeFrom" class="flex-1 text-xs px-2 py-1.5 border border-hairline rounded bg-surface-raised focus:ring-1 focus:ring-brass focus:outline-none">
                        <span class="text-vellum-faint text-xs">to</span>
                        <input type="date" x-model="rangeTo" class="flex-1 text-xs px-2 py-1.5 border border-hairline rounded bg-surface-raised focus:ring-1 focus:ring-brass focus:outline-none">
                    </div>
                    <div class="flex gap-2">
                        <button @click="applyCustomRange()" class="flex-1 text-[11px] font-semibold uppercase tracking-wider py-2 rounded bg-forest text-white hover:bg-forest/90 transition-colors">
                            Apply
                        </button>
                        <button @click="rangeFrom = ''; rangeTo = ''; fetchData(); showDateRange = false" class="text-[11px] font-semibold uppercase tracking-wider px-3 py-2 rounded border border-hairline bg-surface-raised text-vellum-muted hover:text-vellum">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================== FILTER BAR ============================== -->
    <div class="panel space-y-4">
        <!-- Visible Primary Filters -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Status:</span>
                    <select x-model="filters.status" class="text-xs rounded border border-hairline bg-surface-raised py-1.5 px-3 focus:ring-1 focus:ring-brass focus:outline-none min-w-[120px]">
                        <option value="">All Statuses</option>
                        <template x-for="s in statusList" :key="s.key">
                            <option :value="s.key" x-text="s.label"></option>
                        </template>
                    </select>
                </div>

                <button @click="showAdvancedFilters = !showAdvancedFilters" class="text-xs px-3 py-1.5 rounded border border-hairline bg-surface-raised text-brass hover:text-brass-bright font-semibold flex items-center gap-1 transition-colors">
                    <span x-text="showAdvancedFilters ? 'Hide Advanced Filters' : 'Show Advanced Filters'"></span>
                    <svg class="w-3 h-3 transition-transform duration-200" :class="showAdvancedFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
            </div>

            <button @click="resetFilters()" x-show="Object.values(filters).some(x => x !== '' && x !== false)" x-cloak class="text-xs font-semibold text-burgundy hover:underline">
                Reset active filters
            </button>
        </div>

        <!-- Advanced Filters Grouped (Collapsible) -->
        <div x-show="showAdvancedFilters" x-cloak x-transition class="border-t border-hairline pt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Col 1: Classifications & Metadata -->
            <div class="space-y-3">
                <h4 class="text-[10px] uppercase tracking-wider text-vellum-faint font-bold border-b border-hairline/40 pb-1">Classifications</h4>
                <div class="space-y-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] text-vellum-muted">Classification</label>
                        <select x-model="filters.classification" class="text-xs rounded border border-hairline bg-surface-raised py-1.5 px-3 focus:outline-none focus:ring-1 focus:ring-brass">
                            <option value="">All Classifications</option>
                            <option value="Full Day">Full Day</option>
                            <option value="Half Day">Half Day</option>
                            <option value="Non-Working">Non-Working</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] text-vellum-muted">Department</label>
                        <select x-model="filters.department" class="text-xs rounded border border-hairline bg-surface-raised py-1.5 px-3 focus:outline-none focus:ring-1 focus:ring-brass">
                            <option value="">All Departments</option>
                            <template x-for="d in departments" :key="d">
                                <option :value="d" x-text="d"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Col 2: Overrides & Leaves -->
            <div class="space-y-3">
                <h4 class="text-[10px] uppercase tracking-wider text-vellum-faint font-bold border-b border-hairline/40 pb-1">Exceptions & Leaves</h4>
                <div class="space-y-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] text-vellum-muted">Override Status</label>
                        <select x-model="filters.override" class="text-xs rounded border border-hairline bg-surface-raised py-1.5 px-3 focus:outline-none focus:ring-1 focus:ring-brass">
                            <option value="">All Records</option>
                            <option value="yes">Overridden</option>
                            <option value="no">Not Overridden</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] text-vellum-muted">Leave Type</label>
                        <select x-model="filters.leaveType" class="text-xs rounded border border-hairline bg-surface-raised py-1.5 px-3 focus:outline-none focus:ring-1 focus:ring-brass">
                            <option value="">All Leave Types</option>
                            <option value="Planned Leave (Paid)">Planned Leave (Paid)</option>
                            <option value="Planned Leave (Unpaid)">Planned Leave (Unpaid)</option>
                            <option value="Unplanned Leave (Paid)">Unplanned Leave (Paid)</option>
                            <option value="Unplanned Leave (Unpaid)">Unplanned Leave (Unpaid)</option>
                            <option value="Birthday Leave (Paid)">Birthday Leave (Paid)</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                            <option value="Work From Home">Work From Home</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Col 3: Search & Quick Status Toggles -->
            <div class="space-y-3">
                <h4 class="text-[10px] uppercase tracking-wider text-vellum-faint font-bold border-b border-hairline/40 pb-1">Date Search & Quick Toggles</h4>
                <div class="space-y-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] text-vellum-muted">Specific Date</label>
                        <input type="date" x-model="filters.searchDate" class="text-xs rounded border border-hairline bg-surface-raised py-1.5 px-3 focus:outline-none focus:ring-1 focus:ring-brass">
                    </div>
                    <div>
                        <span class="text-[11px] text-vellum-muted block mb-1.5">Quick Filters</span>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="chip in toggleChips" :key="chip.key">
                                <button @click="filters[chip.key] = !filters[chip.key]"
                                        class="text-[10px] font-semibold px-2.5 py-1 rounded-full border transition-colors"
                                        :class="filters[chip.key] ? 'bg-vellum text-canvas border-vellum' : 'border-hairline bg-surface-raised text-vellum-muted hover:border-brass'"
                                        x-text="chip.label"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================== METRICS PANEL ============================== -->
    <div class="panel space-y-4">
        <div>
            <h3 class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">
                <span x-show="rangeFrom && rangeTo" x-cloak>Selected Range Statistics</span>
                <span x-show="!rangeFrom || !rangeTo">Last 30 Days Statistics</span>
            </h3>
        </div>

        <!-- Primary Metrics (Always Visible) -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Attendance Rate -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-brass border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">Attendance %</span>
                <h4 class="text-2xl font-bold mt-2 text-vellum font-display tabular" x-text="metrics.attendanceRate || '0%'"></h4>
            </div>

            <!-- Present Days -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-forest border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">Present Days<span class="hidden">Days Present</span></span>
                <h4 class="text-2xl font-bold mt-2 text-forest font-display tabular" x-text="metrics.presentDays || 0"></h4>
            </div>

            <!-- Late Days -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-cognac border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">Late Days</span>
                <h4 class="text-2xl font-bold mt-2 text-cognac font-display tabular" x-text="metrics.lateDays || 0"></h4>
            </div>

            <!-- Half Days -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-cognac border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">Half Days</span>
                <h4 class="text-2xl font-bold mt-2 text-cognac font-display tabular" x-text="metrics.halfDays || 0"></h4>
            </div>

            <!-- Absent Days -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-burgundy border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">Absent Days</span>
                <h4 class="text-2xl font-bold mt-2 text-burgundy font-display tabular" x-text="metrics.absentDays || 0"></h4>
            </div>

            <!-- Leave Days -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-slate border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">Leave Days</span>
                <h4 class="text-2xl font-bold mt-2 text-slate font-display tabular" x-text="metrics.leaveDays || 0"></h4>
            </div>
        </div>

        <!-- Toggle Secondary Metrics -->
        <div class="flex justify-center border-t border-hairline pt-3">
            <button @click="showSecondary = !showSecondary" class="text-xs font-semibold uppercase tracking-wider text-brass hover:text-brass-bright flex items-center gap-1.5 transition-colors">
                <span x-text="showSecondary ? 'Hide Advanced Metrics' : 'Show Advanced Metrics'"></span>
                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="showSecondary ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
        </div>

        <!-- Secondary Metrics (Collapsed by default) -->
        <div x-show="showSecondary" x-cloak x-transition class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 border-t border-hairline/50 pt-4">
            <!-- Avg Check-In -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Avg Check-In</span>
                <h5 class="text-lg font-bold mt-1 text-vellum font-mono tabular" x-text="metrics.avgCheckIn || '—'"></h5>
            </div>
            <!-- Avg Check-Out -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Avg Check-Out</span>
                <h5 class="text-lg font-bold mt-1 text-vellum font-mono tabular" x-text="metrics.avgCheckOut || '—'"></h5>
            </div>
            <!-- Avg Hours Worked -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Avg Hours Worked</span>
                <h5 class="text-lg font-bold mt-1 text-vellum font-mono tabular" x-text="metrics.avgHoursWorked || '0h'"></h5>
            </div>
            <!-- Total Hours Worked -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Total Hours Worked<span class="hidden">Total Hours</span></span>
                <h5 class="text-lg font-bold mt-1 text-vellum font-mono tabular" x-text="metrics.totalHoursWorked || '0h'"></h5>
            </div>
            <!-- Overtime -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Overtime</span>
                <h5 class="text-lg font-bold mt-1 text-forest font-mono tabular" x-text="metrics.overtime || '0h'"></h5>
            </div>
            <!-- Late Minutes -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Late Minutes</span>
                <h5 class="text-lg font-bold mt-1 text-cognac font-mono tabular" x-text="metrics.lateMinutes || '0m'"></h5>
            </div>
            <!-- Early Exit Minutes -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Early Exit Minutes</span>
                <h5 class="text-lg font-bold mt-1 text-cognac font-mono tabular" x-text="metrics.earlyExitMinutes || '0m'"></h5>
            </div>
            <!-- Override Count -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Override Count</span>
                <h5 class="text-lg font-bold mt-1 text-brass font-mono tabular" x-text="metrics.overrideCount || 0"></h5>
            </div>
            <!-- Payroll Eligible Days -->
            <div class="bg-surface-raised p-3.5 rounded border border-hairline flex flex-col justify-between">
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Payroll Eligible Days</span>
                <h5 class="text-lg font-bold mt-1 text-forest font-mono tabular" x-text="metrics.payrollEligibleDays || 0"></h5>
            </div>
        </div>
    </div>

    <!-- ============================== CALENDAR GRID ============================== -->
    <div class="grid grid-cols-1 xl:grid-cols-[1fr_auto] gap-6 items-start">
        <div class="panel p-0 overflow-hidden w-full">
            <!-- Weekday Header -->
            <div class="grid grid-cols-7 border-b border-hairline bg-surface/50">
                <template x-for="d in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="d">
                    <div class="py-2.5 text-center text-[10.5px] font-bold uppercase tracking-wider text-vellum-faint" x-text="d"></div>
                </template>
            </div>

            <!-- Loading Spinner State -->
            <div x-show="isLoading" class="flex items-center justify-center py-20 bg-surface/10">
                <svg class="animate-spin h-8 w-8 text-brass" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <!-- Day Grid -->
            <div x-show="!isLoading" class="grid grid-cols-7">
                <template x-for="(day, idx) in gridDays" :key="day.iso">
                    <button
                        @click="selectDay(day)"
                        class="day-cell relative min-h-[96px] sm:min-h-[108px] p-2 text-left border-b border-r border-hairline/60 focus:outline-none focus:ring-1 focus:ring-brass transition-all duration-150"
                        :class="[
                            (idx + 1) % 7 === 0 ? 'border-r-0' : '',
                            !day.inRange ? 'bg-surface/30 opacity-40' : 'bg-surface-raised hover:bg-surface/60',
                            matchesFilters(day) ? 'opacity-100' : 'opacity-20 pointer-events-none'
                        ]"
                        :disabled="!day.inRange"
                    >
                        <!-- Override Ribbon Indicator -->
                        <div x-show="day.override" class="absolute top-0 right-0 w-0 h-0 border-t-[14px] border-l-[14px] border-t-brass border-l-transparent" title="Overridden by Administrator"></div>

                        <div class="flex items-start justify-between">
                            <span class="text-xs font-mono font-semibold tabular"
                                  :class="day.inRange ? 'text-vellum' : 'text-vellum-faint/50'">
                                <template x-if="day.isToday">
                                    <span class="inline-flex items-center justify-center w-5.5 h-5.5 rounded-full bg-burgundy text-canvas text-[11px]" x-text="day.num"></span>
                                </template>
                                <template x-if="!day.isToday">
                                    <span x-text="day.num"></span>
                                </template>
                            </span>
                            <span x-show="day.inRange" class="w-2 h-2 rounded-full mt-1.5" :style="`background:${statusColor(day.status)}`"></span>
                        </div>

                        <template x-if="day.inRange">
                            <div class="mt-2.5 space-y-1">
                                <p class="text-[10px] font-semibold leading-tight tracking-wide" :style="`color:${statusColor(day.status)}`" x-text="statusLabel(day.status)"></p>
                                <p class="text-[9.5px] text-vellum-faint font-mono tabular" x-show="day.hours > 0" x-text="day.hours + 'h'"></p>
                            </div>
                        </template>

                        <!-- Cell Hover Tooltip -->
                        <div x-show="day.inRange" class="cell-tooltip absolute left-1/2 bottom-full mb-2 -translate-x-1/2 opacity-0 transition-all duration-150 z-20 w-48 shadow-lg">
                            <div class="bg-vellum text-canvas rounded p-3 text-[10.5px] leading-snug border border-brass-dim/20">
                                <p class="font-display font-semibold text-[11.5px] text-brass-bright" x-text="day.dateLabel"></p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="w-1.5 h-1.5 rounded-full" :style="`background:${statusColor(day.status)}`"></span>
                                    <span class="font-semibold" :style="`color:${statusColor(day.status)}`" x-text="statusLabel(day.status)"></span>
                                </div>
                                <template x-if="day.checkIn">
                                    <p class="text-canvas/80 font-mono tabular mt-1.5" x-text="day.checkIn + ' – ' + (day.checkOut || 'Pending')"></p>
                                </template>
                                <p class="text-canvas/80 font-mono tabular mt-0.5" x-show="day.hours > 0" x-text="day.hours + ' hours worked'"></p>
                                
                                <div class="flex justify-between text-canvas/55 mt-2 pt-2 border-t border-canvas/15 font-mono text-[9px]">
                                    <span x-text="'Late: ' + (day.lateMin > 0 ? day.lateMin + 'm' : 'No')"></span>
                                    <span x-text="'Override: ' + (day.override ? 'Yes' : 'No')"></span>
                                </div>
                                <p class="text-canvas/55 mt-1 text-[9px]" x-text="'Shift: ' + day.classification"></p>
                            </div>
                        </div>
                    </button>
                </template>
            </div>

            <!-- Legend Bar -->
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3.5 border-t border-hairline bg-surface/30 text-[10.5px]">
                <template x-for="s in statusList" :key="s.key">
                    <span class="flex items-center gap-1.5 text-vellum-muted">
                        <span class="w-2 h-2 rounded-full" :style="`background:${s.color}`"></span>
                        <span x-text="s.label"></span>
                    </span>
                </template>
                <span class="flex items-center gap-1.5 text-vellum-muted">
                    <span class="inline-block w-2.5 h-2.5 border-t-[7px] border-l-[7px] border-t-brass border-l-transparent"></span>
                    <span>Admin Override</span>
                </span>
            </div>
        </div>
    </div>

    <!-- ============================== SLIDE-OVER DRAWER ============================== -->
    <div x-show="panelOpen" x-cloak class="fixed inset-0 z-50" aria-modal="true" role="dialog" aria-label="Attendance details drawer">
        <!-- Backdrop -->
        <div x-show="panelOpen" x-transition.opacity @click="closePanel()" class="absolute inset-0 bg-vellum/50 backdrop-blur-xs"></div>

        <!-- Panel -->
        <div x-show="panelOpen"
             x-transition:enter="transition ease-out duration-250" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute right-0 top-0 h-full w-full sm:w-[420px] bg-canvas shadow-2xl overflow-y-auto flex flex-col"
             @keydown.escape.window="closePanel()">

            <template x-if="selected">
                <div class="flex flex-col h-full">
                    <!-- Stub Header -->
                    <div class="bg-forest text-canvas px-6 pt-6 pb-5 relative flex-shrink-0">
                        <button @click="closePanel()" aria-label="Close panel" class="absolute top-4 right-4 text-canvas/70 hover:text-canvas transition-colors">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                        <p class="text-[9px] tracking-[0.18em] uppercase text-canvas/60 font-bold">Attendance Folio Entry</p>
                        <h3 class="font-display text-2xl font-semibold mt-1.5" x-text="selected.dateLabel"></h3>
                        <p class="text-canvas/70 text-xs mt-0.5" x-text="selected.dayName"></p>
                        
                        <span class="inline-flex items-center gap-1.5 mt-3.5 px-3 py-1 rounded-full text-xs font-semibold" :style="`background:${statusColor(selected.status)}22; color:${statusColor(selected.status)}`">
                            <span class="w-1.5 h-1.5 rounded-full" :style="`background:${statusColor(selected.status)}`"></span>
                            <span x-text="statusLabel(selected.status)"></span>
                        </span>
                    </div>
                    <div class="stub-edge h-2.5 bg-forest flex-shrink-0"></div>

                    <!-- Content -->
                    <div class="flex-1 px-6 py-5 space-y-6 overflow-y-auto">
                        <!-- Attendance: Time Blocks -->
                        <div class="space-y-2">
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint">Attendance Timings</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="border border-hairline bg-surface p-3 rounded">
                                    <p class="text-[9px] uppercase tracking-wider text-vellum-muted">Check In</p>
                                    <p class="font-mono text-base font-semibold mt-0.5 text-vellum" x-text="selected.checkIn || '—'"></p>
                                </div>
                                <div class="border border-hairline bg-surface p-3 rounded">
                                    <p class="text-[9px] uppercase tracking-wider text-vellum-muted">Check Out</p>
                                    <p class="font-mono text-base font-semibold mt-0.5 text-vellum" x-text="selected.checkOut || '—'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Data Fields Grouped -->
                        <div class="space-y-4">
                            <!-- Section: Attendance Details -->
                            <div class="space-y-2">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint border-b border-hairline pb-1">Attendance Details</h4>
                                <dl class="divide-y divide-hairline/40 text-xs">
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Hours Worked</dt>
                                        <dd class="font-mono font-semibold text-vellum tabular" x-text="selected.hours ? selected.hours + ' h' : '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Shift Expected Hours</dt>
                                        <dd class="font-mono font-semibold text-vellum tabular" x-text="selected.expectedHours + ' h'"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Classification</dt>
                                        <dd class="font-semibold text-vellum" x-text="selected.classification"></dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Section: Classifications & Rules -->
                            <div class="space-y-2">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint border-b border-hairline pb-1">Classifications & Rules</h4>
                                <dl class="divide-y divide-hairline/40 text-xs">
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Leave Type</dt>
                                        <dd class="font-semibold text-vellum" x-text="selected.leaveType"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Override Status</dt>
                                        <dd class="font-semibold text-vellum" x-text="selected.override ? 'Overridden' : 'Not Overridden'"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Override Reason</dt>
                                        <dd class="font-semibold text-vellum max-w-[200px] text-right truncate" :title="selected.overrideReason" x-text="selected.overrideReason || '—'"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Shift Name</dt>
                                        <dd class="font-semibold text-vellum" x-text="selected.shift"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Grace Period</dt>
                                        <dd class="font-semibold text-vellum" x-text="selected.grace"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Payroll Impact</dt>
                                        <dd class="font-semibold" :class="selected.payrollImpact !== 'None' ? 'text-burgundy font-bold' : 'text-forest'" x-text="selected.payrollImpact"></dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Section: Advanced / Metadata -->
                            <div class="space-y-2">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint border-b border-hairline pb-1">Advanced Terminal Info</h4>
                                <dl class="divide-y divide-hairline/40 text-xs">
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Check-In Device</dt>
                                        <dd class="font-semibold text-vellum text-right" x-text="selected.checkInDevice"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Check-In Location</dt>
                                        <dd class="font-semibold text-vellum text-right" x-text="selected.checkInLocation"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Check-Out Device</dt>
                                        <dd class="font-semibold text-vellum text-right" x-text="selected.checkOutDevice"></dd>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <dt class="text-vellum-muted">Check-Out Location</dt>
                                        <dd class="font-semibold text-vellum text-right" x-text="selected.checkOutLocation"></dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Notes Block -->
                        <div class="space-y-1.5">
                            <h4 class="text-[10px] font-bold uppercase tracking-wider text-vellum-faint">Entry Log Notes</h4>
                            <div class="bg-surface border border-hairline rounded p-3 text-xs leading-relaxed text-vellum-muted" x-text="selected.notes"></div>
                        </div>
                    </div>

                    <!-- Footer: Approval & Admin Actions -->
                    <div class="border-t border-hairline bg-surface p-6 flex flex-col gap-3 flex-shrink-0">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10px]">Verification Signature</span>
                            <span class="font-display font-semibold text-forest text-sm" x-text="selected.approvedBy"></span>
                        </div>

                        @if(auth()->user()->role === 'admin')
                            <div class="mt-2">
                                <a :href="`{{ route('admin.attendance.logs') }}?date=${selected.iso}&select_employee=${userId}#override`" 
                                   class="w-full inline-flex justify-center items-center text-xs font-semibold uppercase tracking-wider py-2.5 rounded bg-brass text-white hover:bg-brass-bright hover:shadow transition-all duration-200">
                                    Administrative Override
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function attendanceCalendar(config) {
    return {
        userId: config.userId,
        isLoading: false,
        viewMonth: new Date().getMonth(),
        viewYear: new Date().getFullYear(),
        monthNames: ['January','February','March','April','May','June','July','August','September','October','November','December'],
        departments: [],
        statusList: [
            { key: 'present', label: 'Present', color: '#234E39' },
            { key: 'late', label: 'Late', color: '#8C4E2D' },
            { key: 'half', label: 'Half Day', color: '#C1652C' },
            { key: 'absent', label: 'Absent', color: '#6E1A24' },
            { key: 'off', label: 'Weekly Off', color: '#6D645A' },
            { key: 'wfh', label: 'Work From Home', color: '#3B5368' },
            { key: 'paid', label: 'Paid Leave', color: '#9C7C38' },
            { key: 'unpaid', label: 'Unplanned Leave', color: '#6E1A24' },
            { key: 'bday', label: 'Birthday Leave', color: '#7C5A9E' },
        ],
        toggleChips: [
            { key: 'onlyLate', label: 'Only Late' },
            { key: 'onlyAbsent', label: 'Only Absent' },
            { key: 'onlyOverride', label: 'Only Overrides' },
            { key: 'onlyLeave', label: 'Only Leave' },
            { key: 'onlyHalf', label: 'Only Half Day' },
            { key: 'onlyWfh', label: 'Only WFH' },
        ],
        employee: { name: '', dept: '', id: '' },
        gridDays: [],
        metrics: {},
        selected: null,
        panelOpen: false,
        showMonthRange: false,
        showDateRange: false,
        showAdvancedFilters: false,
        showSecondary: false,
        rangeFrom: '',
        rangeTo: '',
        filters: {
            department: '',
            status: '',
            classification: '',
            leaveType: '',
            override: '',
            searchDate: '',
            onlyLate: false,
            onlyAbsent: false,
            onlyOverride: false,
            onlyLeave: false,
            onlyHalf: false,
            onlyWfh: false,
        },

        get monthLabel() {
            return `${this.monthNames[this.viewMonth]} ${this.viewYear}`;
        },

        get yearRange() {
            const currentYear = new Date().getFullYear();
            const arr = [];
            for (let y = currentYear - 3; y <= currentYear + 1; y++) {
                arr.push(y);
            }
            return arr;
        },

        init() {
            const today = new Date();
            this.viewMonth = today.getMonth();
            this.viewYear = today.getFullYear();
            this.fetchData();
        },

        fetchData() {
            this.isLoading = true;
            let url = `/attendance/calendar/data?user_id=${this.userId}`;
            if (this.rangeFrom && this.rangeTo) {
                url += `&start_date=${this.rangeFrom}&end_date=${this.rangeTo}`;
            } else {
                url += `&month=${this.viewMonth}&year=${this.viewYear}`;
            }

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        this.isLoading = false;
                        return;
                    }
                    this.gridDays = data.gridDays;
                    this.departments = data.departments || [];
                    this.employee = data.employee;
                    this.metrics = data.metrics;
                    this.isLoading = false;
                })
                .catch(err => {
                    console.error(err);
                    this.isLoading = false;
                });
        },

        prevMonth() {
            this.rangeFrom = '';
            this.rangeTo = '';
            this.viewMonth--;
            if (this.viewMonth < 0) {
                this.viewMonth = 11;
                this.viewYear--;
            }
            this.fetchData();
        },

        nextMonth() {
            this.rangeFrom = '';
            this.rangeTo = '';
            this.viewMonth++;
            if (this.viewMonth > 11) {
                this.viewMonth = 0;
                this.viewYear++;
            }
            this.fetchData();
        },

        goToday() {
            this.rangeFrom = '';
            this.rangeTo = '';
            const today = new Date();
            this.viewMonth = today.getMonth();
            this.viewYear = today.getFullYear();
            this.fetchData();
        },

        applyCustomRange() {
            if (this.rangeFrom && this.rangeTo) {
                this.fetchData();
                this.showDateRange = false;
            }
        },

        statusColor(key) {
            const s = this.statusList.find(s => s.key === key);
            return s ? s.color : '#6D645A';
        },

        statusLabel(key) {
            const s = this.statusList.find(s => s.key === key);
            return s ? s.label : key;
        },

        selectDay(day) {
            if (!day.inRange) return;
            this.selected = day;
            this.panelOpen = true;
        },

        closePanel() {
            this.panelOpen = false;
        },

        resetFilters() {
            this.filters = {
                department: '',
                status: '',
                classification: '',
                leaveType: '',
                override: '',
                searchDate: '',
                onlyLate: false,
                onlyAbsent: false,
                onlyOverride: false,
                onlyLeave: false,
                onlyHalf: false,
                onlyWfh: false,
            };
        },

        matchesFilters(day) {
            if (!day.inRange) return true;
            const f = this.filters;
            if (f.department && day.department !== f.department) return false;
            if (f.status && day.status !== f.status) return false;
            if (f.classification && day.classification !== f.classification) return false;
            if (f.leaveType && day.leaveType !== f.leaveType) return false;
            if (f.override === 'yes' && !day.override) return false;
            if (f.override === 'no' && day.override) return false;
            if (f.searchDate && day.iso !== f.searchDate) return false;
            
            // Toggle chips logic
            if (f.onlyLate && day.status !== 'late') return false;
            if (f.onlyAbsent && day.status !== 'absent') return false;
            if (f.onlyOverride && !day.override) return false;
            if (f.onlyLeave && !['paid', 'unpaid', 'bday'].includes(day.status)) return false;
            if (f.onlyHalf && day.status !== 'half') return false;
            if (f.onlyWfh && day.status !== 'wfh') return false;
            
            return true;
        }
    };
}
</script>
