@props(['user'])

<div x-data="attendanceCalendar({ userId: {{ $user->id }} })" x-init="init()" class="space-y-6 relative">
    <style>
        .tabular { font-variant-numeric: tabular-nums; }
        
        /* perforated ledger-stub edge for the detail panel */
        .stub-edge {
            background-image: radial-gradient(circle, var(--canvas) 3px, transparent 3.5px);
            background-size: 14px 14px;
            background-position: -7px center;
        }

        .day-cell {
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .day-cell:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(30, 22, 17, 0.12);
            border-color: var(--brass) !important;
            background-color: var(--surface-raised) !important;
            z-index: 40; /* Sit above neighboring cells */
        }
    </style>

    <!-- ============================== QUICK FILTERS PANEL ============================== -->
    <div class="panel space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-display font-semibold text-lg text-vellum">Attendance Ledger</h3>
                <p class="text-xs text-vellum-muted mt-0.5">Filter records and statistics by parameters</p>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="showAdvancedFilters = !showAdvancedFilters" 
                        class="text-xs font-semibold uppercase tracking-wider px-3.5 py-2 bg-surface-raised border border-hairline rounded text-vellum hover:border-brass hover:text-brass transition-all duration-150 flex items-center gap-1.5 focus:outline-none focus:ring-1 focus:ring-brass">
                    <span>Advanced Filters</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="showAdvancedFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <button @click="resetFilters()" 
                        class="text-xs font-semibold uppercase tracking-wider px-3.5 py-2 bg-[#F3E6E8] border border-[#6E1A24]/20 rounded text-[#6E1A24] hover:bg-[#6E1A24] hover:text-white transition-all duration-150 focus:outline-none focus:ring-1 focus:ring-[#6E1A24]">
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Advanced Filters Drawer/Content -->
        <div x-show="showAdvancedFilters" x-cloak x-transition class="border-t border-hairline/60 pt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Col 1: Date Range -->
            <div class="space-y-3">
                <h4 class="text-[10px] uppercase tracking-wider text-vellum-faint font-bold border-b border-hairline/40 pb-1">Date Boundary Selection</h4>
                <div class="space-y-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] text-vellum-muted">Start Date</label>
                        <input type="date" x-model="rangeFrom" @change="applyCustomRange()" class="text-xs rounded border border-hairline bg-surface-raised py-1.5 px-3 focus:outline-none focus:ring-1 focus:ring-brass">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] text-vellum-muted">End Date</label>
                        <input type="date" x-model="rangeTo" @change="applyCustomRange()" class="text-xs rounded border border-hairline bg-surface-raised py-1.5 px-3 focus:outline-none focus:ring-1 focus:ring-brass">
                    </div>
                </div>
            </div>

            <!-- Col 2: Status & Overrides -->
            <div class="space-y-3">
                <h4 class="text-[10px] uppercase tracking-wider text-vellum-faint font-bold border-b border-hairline/40 pb-1">Status & Parameters</h4>
                <div class="space-y-3">
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
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <!-- Attendance Rate -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-brass border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">Attendance Rate</span>
                <h4 class="text-2xl font-bold mt-2 text-vellum font-display tabular" x-text="metrics.attendanceRate || '0%'"></h4>
            </div>

            <!-- Present Days -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-forest border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">Present Days <span class="hidden">Days Present</span></span>
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

            <!-- WFH Days -->
            <div class="bg-surface-raised p-4 rounded border-l-4 border-l-slate border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-all duration-200">
                <span class="text-vellum-muted text-[10px] font-semibold uppercase tracking-wider">WFH Days</span>
                <h4 class="text-2xl font-bold mt-2 text-slate font-display tabular" x-text="metrics.wfhDays || 0"></h4>
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
                <span class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-wider">Total Hours Worked</span>
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
        <!-- Calendar Card -->
        <div class="panel p-0 w-full relative" id="attendance-calendar-card">
            <!-- Relocated Sticky Legend Bar -->
            <div class="sticky top-0 bg-[#F4EFE6] z-20 border-b border-hairline py-3 px-5 flex flex-wrap items-center gap-x-4 gap-y-2.5 rounded-t-lg">
                <span class="text-[10px] uppercase tracking-wider text-vellum-faint font-bold mr-1.5">Quick Filters:</span>
                <template x-for="s in statusList" :key="s.key">
                    <button 
                        @click="legendFilter = (legendFilter === s.key ? null : s.key)"
                        @mouseenter="legendHover = s.key"
                        @mouseleave="legendHover = null"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded border transition-all duration-150 text-[10.5px] cursor-pointer focus:outline-none focus:ring-1 focus:ring-brass"
                        :class="[
                            legendFilter === s.key ? 'bg-surface border-brass ring-1 ring-brass font-bold text-vellum' : 'bg-surface-raised border-hairline text-vellum-muted hover:border-brass hover:text-vellum'
                        ]"
                        :style="legendFilter !== null && legendFilter !== s.key ? 'opacity: 0.4;' : ''"
                    >
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="`background:${s.color}; box-shadow: 0 0 4px 1px ${s.color}40`"></span>
                        <span x-text="s.label"></span>
                    </button>
                </template>
                <!-- Clear Legend Filter Badge -->
                <button 
                    x-show="legendFilter !== null" 
                    @click="legendFilter = null"
                    class="text-[9.5px] text-burgundy font-mono uppercase tracking-wider hover:underline ml-auto"
                >
                    Clear Filter
                </button>
            </div>

            <!-- Responsive scroll wrapper for mobile grid support -->
            <div class="overflow-x-auto md:overflow-visible scroll-hide">
                <div class="min-w-[720px] md:min-w-0">
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
                                @mouseenter="showTooltip($event, day, idx)"
                                @mouseleave="hideTooltip()"
                                class="day-cell relative p-3 text-left border-b border-r border-hairline/60 focus:outline-none focus:ring-2 focus:ring-brass transition-all duration-180 flex flex-col justify-between"
                                :class="[
                                    (idx + 1) % 7 === 0 ? 'border-r-0' : '',
                                    !day.inRange ? 'bg-surface/10 opacity-35 pointer-events-none' : 'bg-surface-raised',
                                    matchesFilters(day) ? '' : 'opacity-20 pointer-events-none',
                                    selected && selected.iso === day.iso ? 'ring-2 ring-inset ring-brass bg-[#FAF9F5] z-30 shadow-lg scale-[1.01]' : '',
                                    
                                    /* Legend Filter highlight or dim */
                                    legendFilter !== null && day.status !== legendFilter ? 'opacity-[0.25]' : '',
                                    legendFilter !== null && day.status === legendFilter ? 'ring-2 ring-inset ring-brass/80 bg-surface z-10 scale-[1.01]' : '',
                                    
                                    /* Legend Hover dim or scale */
                                    legendHover !== null && day.status !== legendHover && legendFilter === null ? 'opacity-[0.45]' : '',
                                    legendHover !== null && day.status === legendHover && legendFilter === null ? 'scale-[1.02] border-brass z-10 bg-surface shadow-md' : ''
                                ]"
                                :disabled="!day.inRange"
                                :style="`height: 128px;`"
                            >
                                <!-- Override Ribbon Indicator -->
                                <div x-show="day.override" class="absolute top-0 right-0 w-0 h-0 border-t-[14px] border-l-[14px] border-t-brass border-l-transparent" title="Overridden by Administrator"></div>

                                <div class="w-full h-full flex flex-col justify-between select-none">
                                    <!-- Top: Date & optional WFH badge -->
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm sm:text-base font-mono font-semibold tabular-nums"
                                              :class="day.inRange ? 'text-vellum' : 'text-vellum-faint/40'">
                                            <template x-if="day.isToday">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-burgundy text-canvas text-xs font-bold" x-text="day.num"></span>
                                            </template>
                                            <template x-if="!day.isToday">
                                                <span x-text="day.num"></span>
                                            </template>
                                        </span>
                                        <!-- WFH Badge -->
                                        <span x-show="day.inRange && day.status === 'wfh'" class="text-[8.5px] font-mono font-bold uppercase tracking-wider text-[#3B5368] px-1.5 py-0.5 bg-[#E7EDF1]/80 rounded border border-[#3B5368]/20">WFH</span>
                                    </div>

                                    <!-- Middle: Pill Status Badge -->
                                    <div class="flex-grow flex items-center mt-1.5 mb-1.5">
                                        <template x-if="day.inRange">
                                            <div class="w-full flex items-center gap-1.5 px-2 py-1 rounded text-[10px] font-bold tracking-wide border transition-all duration-150"
                                                 :style="statusBadgeStyle(day.status)">
                                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                                      :style="statusDotStyle(day.status)"></span>
                                                <span x-text="statusLabel(day.status)"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Bottom: Hours / Time Range -->
                                    <div class="flex items-center justify-between text-[9px] text-vellum-faint font-mono tabular-nums leading-none">
                                        <template x-if="day.inRange">
                                            <div class="flex justify-between items-center w-full">
                                                <!-- Hours Worked -->
                                                <span x-show="['present', 'late', 'half', 'wfh'].includes(day.status) && day.hours > 0" 
                                                      class="font-semibold text-vellum-muted"
                                                      x-text="day.hours + ' h'"></span>
                                                
                                                <!-- Timings range -->
                                                <span class="text-[8.5px] opacity-75 font-medium ml-auto" 
                                                      x-show="day.checkIn" 
                                                      x-text="formatTimeRange(day.checkIn, day.checkOut)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================== CENTERED MODAL DIALOG (Teleported to body) ============================== -->
    <template x-teleport="body">
        <div x-show="panelOpen" x-cloak class="fixed inset-0 z-[9990]">
            <!-- Backdrop -->
            <div x-show="panelOpen" 
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="transition ease-in duration-150" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0"
                 @click="closePanel()" 
                 class="fixed inset-0 bg-[#1B1917]/55 backdrop-blur-xs"></div>

            <!-- Viewport-centered Wrapper (independent of page layout constraints) -->
            <div class="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full p-4 sm:p-0 sm:w-[90vw] md:max-w-[900px] xl:max-w-[1050px] z-10 pointer-events-none flex items-center justify-center">
                <!-- Modal Card Container -->
                <div x-show="panelOpen"
                     id="attendance-modal-panel"
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 scale-95" 
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="opacity-100 scale-100" 
                     x-transition:leave-end="opacity-0 scale-95"
                     class="w-full max-h-[85vh] bg-canvas border border-hairline-strong shadow-2xl flex flex-col rounded-xl overflow-hidden pointer-events-auto"
                     @keydown.escape.window="closePanel()"
                     @keydown.tab="handleTrap($event)">

                    <template x-if="selected">
                        <div class="flex flex-col max-h-[85vh] overflow-hidden">
                            <!-- Header -->
                            <div class="bg-forest text-canvas px-6 py-4 relative flex-shrink-0 flex items-center justify-between border-b border-hairline">
                                <div class="space-y-0.5">
                                    <p class="text-[9px] tracking-[0.18em] uppercase text-canvas/60 font-bold font-mono">Attendance Folio Entry</p>
                                    <div class="flex items-baseline gap-2">
                                        <h3 class="font-display text-xl sm:text-2xl font-semibold text-canvas leading-tight" x-text="selected.dateLabel"></h3>
                                        <span class="text-canvas/70 text-xs font-medium font-sans" x-text="selected.dayName"></span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-3">
                                    <!-- Status Badge -->
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border transition-all duration-150" :style="statusBadgeStyle(selected.status)">
                                        <span class="w-1.5 h-1.5 rounded-full" :style="statusDotStyle(selected.status)"></span>
                                        <span x-text="statusLabel(selected.status)"></span>
                                    </span>
                                    
                                    <!-- Close Button -->
                                    <button @click="closePanel()" aria-label="Close modal" class="text-canvas/70 hover:text-canvas hover:scale-105 transition-all focus:outline-none p-1 rounded-full hover:bg-canvas/10">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Scrollable Modal Body -->
                            <div class="flex-1 px-6 py-6 overflow-y-auto space-y-6 bg-canvas">
                                <!-- Content Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    
                                    <!-- Section: Attendance Timings -->
                                    <div class="border border-hairline bg-surface p-5 rounded-lg space-y-4 hover:border-hairline-strong transition-colors duration-150">
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-brass border-b border-hairline/60 pb-1.5 block">Attendance Timings</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Check In</span>
                                                <span class="font-mono text-base font-semibold text-vellum mt-0.5 block" x-text="selected.checkIn || '—'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Check Out</span>
                                                <span class="font-mono text-base font-semibold text-vellum mt-0.5 block" x-text="selected.checkOut || '—'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section: Shift Information -->
                                    <div class="border border-hairline bg-surface p-5 rounded-lg space-y-4 hover:border-hairline-strong transition-colors duration-150">
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-brass border-b border-hairline/60 pb-1.5 block">Shift Information</h4>
                                        <div class="grid grid-cols-3 gap-4">
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Shift Name</span>
                                                <span class="text-xs sm:text-sm font-semibold text-vellum mt-0.5 block" x-text="selected.shift || '—'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Grace Period</span>
                                                <span class="text-xs sm:text-sm font-semibold text-vellum mt-0.5 block" x-text="selected.grace || '—'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Late Threshold</span>
                                                <span class="font-mono text-xs sm:text-sm font-semibold text-vellum mt-0.5 block" x-text="selected.lateThreshold || '—'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section: Attendance Summary -->
                                    <div class="border border-hairline bg-surface p-5 rounded-lg space-y-4 hover:border-hairline-strong transition-colors duration-150">
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-brass border-b border-hairline/60 pb-1.5 block">Attendance Summary</h4>
                                        <div class="grid grid-cols-3 gap-4">
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Hours Worked</span>
                                                <span class="font-mono text-base font-semibold text-vellum mt-0.5 block" x-text="selected.hours ? selected.hours + ' h' : '0 h'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Expected Hours</span>
                                                <span class="font-mono text-base font-semibold text-vellum mt-0.5 block" x-text="selected.expectedHours ? selected.expectedHours + ' h' : '—'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Classification</span>
                                                <span class="text-xs sm:text-sm font-semibold text-vellum mt-0.5 block" x-text="selected.classification || '—'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section: Payroll Information -->
                                    <div class="border border-hairline bg-surface p-5 rounded-lg space-y-4 hover:border-hairline-strong transition-colors duration-150">
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-brass border-b border-hairline/60 pb-1.5 block">Payroll Information</h4>
                                        <div class="grid grid-cols-3 gap-4">
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Payroll Eligible</span>
                                                <span class="text-xs sm:text-sm font-bold mt-0.5 block" 
                                                      :class="selected.payrollImpact === 'None' ? 'text-forest' : 'text-burgundy'"
                                                      x-text="selected.payrollImpact === 'None' ? 'Yes' : 'No'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Half-Day Deduction</span>
                                                <span class="text-xs sm:text-sm font-semibold text-vellum mt-0.5 block" 
                                                      x-text="selected.status === 'half' ? 'Yes' : 'No'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Overtime Eligible</span>
                                                <span class="text-xs sm:text-sm font-semibold text-vellum mt-0.5 block" 
                                                      x-text="selected.hours > selected.expectedHours ? 'Yes' : 'No'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section: Leave & Override -->
                                    <div class="border border-hairline bg-surface p-5 rounded-lg space-y-4 hover:border-hairline-strong transition-colors duration-150">
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-brass border-b border-hairline/60 pb-1.5 block">Leave & Override</h4>
                                        <div class="grid grid-cols-3 gap-4">
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Leave Type</span>
                                                <span class="text-xs sm:text-sm font-semibold text-vellum mt-0.5 block" x-text="selected.leaveType || '—'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Override Status</span>
                                                <span class="text-xs sm:text-sm font-semibold text-vellum mt-0.5 block" x-text="selected.override ? 'Overridden' : 'Not Overridden'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Override Reason</span>
                                                <span class="text-xs sm:text-sm font-semibold text-vellum mt-0.5 block break-words" x-text="selected.overrideReason || '—'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section: Device & Verification -->
                                    <div class="border border-hairline bg-surface p-5 rounded-lg space-y-4 hover:border-hairline-strong transition-colors duration-150">
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-brass border-b border-hairline/60 pb-1.5 block">Device & Verification</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Check-In Terminal</span>
                                                <span class="text-xs font-semibold text-vellum mt-0.5 block truncate" x-text="selected.checkInDevice || '—'"></span>
                                                <span class="text-[9.5px] text-vellum-faint mt-0.5 block truncate" x-text="selected.checkInLocation || '—'"></span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-vellum-faint uppercase tracking-wider block font-semibold">Check-Out Terminal</span>
                                                <span class="text-xs font-semibold text-vellum mt-0.5 block truncate" x-text="selected.checkOutDevice || '—'"></span>
                                                <span class="text-[9.5px] text-vellum-faint mt-0.5 block truncate" x-text="selected.checkOutLocation || '—'"></span>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Notes Block -->
                                <div class="border border-hairline bg-surface p-5 rounded-lg space-y-2 hover:border-hairline-strong transition-colors duration-150">
                                    <h4 class="text-[11px] font-bold uppercase tracking-wider text-brass border-b border-hairline/60 pb-1.5 block">Entry Log Notes</h4>
                                    <div class="text-xs leading-relaxed text-vellum-muted" x-text="selected.notes"></div>
                                </div>
                            </div>

                            <!-- Footer: Approval & Admin Actions -->
                            <div class="border-t border-hairline bg-surface px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 flex-shrink-0">
                                <div class="flex flex-col">
                                    <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10px]">Verification Signature</span>
                                    <span class="font-display font-semibold text-forest text-base mt-0.5" x-text="selected.approvedBy"></span>
                                </div>

                                @if(auth()->user()->role === 'admin')
                                    <div>
                                        <a :href="`{{ route('admin.attendance.logs') }}?date=${selected.iso}&select_employee=${userId}#override`" 
                                           class="inline-flex justify-center items-center text-xs font-semibold uppercase tracking-wider px-5 py-3 rounded bg-brass text-white hover:bg-brass-bright hover:shadow transition-all duration-200">
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
    </template>
    <!-- Global Floating Tooltip Portal (Teleported to document.body) -->
    <template x-teleport="body">
        <div x-show="activeTooltipDay !== null"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1.5"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-1.5"
             class="fixed pointer-events-none shadow-2xl z-[9999]"
             :style="`left: ${tooltipX}px; top: ${tooltipY}px;`"
             role="tooltip">
            <template x-if="activeTooltipDay">
                <div class="bg-surface border border-hairline-strong rounded-lg p-5 text-[11px] leading-relaxed space-y-3.5 font-sans shadow-xl w-80 opacity-100" style="opacity: 1 !important;">
                    <!-- Section 1: Header (Date + Status) -->
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <h4 class="font-display font-semibold text-[15.5px] text-brass tracking-wide leading-tight" x-text="activeTooltipDay.dateLabel"></h4>
                            <p class="text-[9.5px] text-vellum-faint uppercase tracking-wider mt-0.5" x-text="activeTooltipDay.dayName"></p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[9.5px] font-bold tracking-wide border transition-all duration-150"
                              :style="statusBadgeStyle(activeTooltipDay.status)">
                            <span class="w-1 h-1 rounded-full flex-shrink-0" :style="statusDotStyle(activeTooltipDay.status)"></span>
                            <span x-text="statusLabel(activeTooltipDay.status)"></span>
                        </span>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-hairline/60"></div>

                    <!-- Section 2: Attendance Timings -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold">Check In</span>
                            <span class="font-mono text-vellum font-semibold" x-text="activeTooltipDay.checkIn || '—'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold">Check Out</span>
                            <span class="font-mono text-vellum font-semibold" x-text="activeTooltipDay.checkOut || '—'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold">Shift</span>
                            <span class="text-vellum font-semibold text-right max-w-[180px] truncate" :title="activeTooltipDay.shift" x-text="activeTooltipDay.shift || '—'"></span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-hairline/60"></div>

                    <!-- Section 3: Attendance Summary -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold">Hours Worked</span>
                            <span class="font-mono text-vellum font-semibold" x-text="(activeTooltipDay.hours || 0) + 'h / ' + (activeTooltipDay.expectedHours || 8.5) + 'h'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold">Classification</span>
                            <span class="text-vellum font-semibold" x-text="activeTooltipDay.classification || '—'"></span>
                        </div>
                        <div class="flex justify-between items-center" x-show="activeTooltipDay.leaveType && activeTooltipDay.leaveType !== '—'">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold">Leave Type</span>
                            <span class="text-vellum font-semibold" x-text="activeTooltipDay.leaveType"></span>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-hairline/60"></div>

                    <!-- Section 4: Verification & Audit -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold">Late Status</span>
                            <span class="text-xs font-semibold" 
                                  :class="activeTooltipDay.lateMin > 0 ? 'text-burgundy font-bold' : 'text-vellum font-semibold'"
                                  x-text="activeTooltipDay.lateMin > 0 ? 'Yes (' + activeTooltipDay.lateMin + 'm)' : 'No'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold">Overridden</span>
                            <span class="text-vellum font-semibold" x-text="activeTooltipDay.override ? 'Yes' : 'No'"></span>
                        </div>
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider font-semibold flex-shrink-0">Approved By</span>
                            <span class="text-vellum font-semibold text-right break-words max-w-[150px]" x-text="activeTooltipDay.approvedBy || 'System'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>
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
        legendFilter: null,
        legendHover: null,
        activeTooltipDay: null,
        activeTooltipElement: null,
        activeTooltipIndex: null,
        tooltipX: 0,
        tooltipY: 0,
        tooltipTransform: '',
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
            
            // Watch panelOpen to toggle body scroll locking
            this.$watch('panelOpen', value => {
                if (value) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
            
            // Dynamic position updates on window scroll and resize
            window.addEventListener('scroll', () => this.updateTooltipPosition(), { passive: true });
            window.addEventListener('resize', () => this.updateTooltipPosition(), { passive: true });
            
            // Local scroll wrapper event binding after DOM layout completes
            this.$nextTick(() => {
                const scrollWrapper = this.$el.querySelector('.overflow-x-auto');
                if (scrollWrapper) {
                    scrollWrapper.addEventListener('scroll', () => this.updateTooltipPosition(), { passive: true });
                }
            });
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

        tooltipStatusColor(key) {
            const colors = {
                present: '#52B788',
                wfh: '#88C0D0',
                late: '#F4A261',
                half: '#F4A261',
                absent: '#E76F51',
                unpaid: '#E76F51',
                paid: '#D4AF37',
                bday: '#B392AC',
                off: '#9C9180'
            };
            return colors[key] || '#9C9180';
        },

        statusLabel(key) {
            const s = this.statusList.find(s => s.key === key);
            return s ? s.label : key;
        },

        statusBadgeStyle(status) {
            const styles = {
                present: { bg: '#D1E7DD', text: '#0A3622' },
                wfh: { bg: '#CFE2FF', text: '#052C65' },
                late: { bg: '#FFF3CD', text: '#664D03' },
                half: { bg: '#FFE8CC', text: '#803D00' },
                absent: { bg: '#F8D7DA', text: '#58151C' },
                unpaid: { bg: '#F8D7DA', text: '#58151C' },
                paid: { bg: '#FEF3C7', text: '#78350F' },
                bday: { bg: '#F3E8FF', text: '#4C1D95' },
                off: { bg: '#E2E3E5', text: '#212529' }
            };
            const s = styles[status] || { bg: '#FAF8F5', text: '#6D645A' };
            return `background-color: ${s.bg}; color: ${s.text}; border: 1px solid rgba(156, 124, 56, 0.25);`;
        },

        statusDotStyle(status) {
            const dots = {
                present: { color: '#198754', glow: '0 0 6px 2px rgba(25, 135, 84, 0.4)' },
                wfh: { color: '#0D6EFD', glow: '0 0 6px 2px rgba(13, 110, 253, 0.4)' },
                late: { color: '#FFC107', glow: '0 0 6px 2px rgba(255, 193, 7, 0.45)' },
                half: { color: '#FD7E14', glow: '0 0 6px 2px rgba(253, 126, 20, 0.45)' },
                absent: { color: '#DC3545', glow: '0 0 6px 2px rgba(220, 53, 69, 0.45)' },
                unpaid: { color: '#DC3545', glow: '0 0 6px 2px rgba(220, 53, 69, 0.45)' },
                paid: { color: '#D97706', glow: '0 0 6px 2px rgba(217, 119, 6, 0.4)' },
                bday: { color: '#8B5CF6', glow: '0 0 6px 2px rgba(139, 92, 246, 0.4)' },
                off: { color: '#6C757D', glow: 'none' }
            };
            const d = dots[status] || { color: '#6C757D', glow: 'none' };
            return `background-color: ${d.color}; box-shadow: ${d.glow};`;
        },

        handleTrap(e) {
            const focusable = e.currentTarget.querySelectorAll('button, [href], input, select, textarea, [tabindex="0"]');
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            
            if (e.shiftKey) {
                if (document.activeElement === first) {
                    last.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === last) {
                    first.focus();
                    e.preventDefault();
                }
            }
        },

        formatTimeRange(inTime, outTime) {
            if (!inTime) return '';
            const clean = (t) => t.replace(' AM', 'a').replace(' PM', 'p').replace(/^0/, '');
            return outTime ? `${clean(inTime)}–${clean(outTime)}` : `${clean(inTime)}–...`;
        },

        selectDay(day) {
            if (!day.inRange) return;
            this.selected = day;
            this.panelOpen = true;
            this.$nextTick(() => {
                const modalBody = document.querySelector(
                    '#attendance-modal-panel .overflow-y-auto'
                );

                if (modalBody) {
                    modalBody.scrollTop = 0;
                }
            });
        },

        closePanel() {
            this.panelOpen = false;
            this.selected = null; // Clear selected state when the drawer is closed!
            document.body.style.overflow = '';
        },

        showTooltip(event, day, idx) {
            this.activeTooltipDay = day;
            this.activeTooltipElement = event.currentTarget;
            this.activeTooltipIndex = idx;
            this.updateTooltipPosition();
        },

        hideTooltip() {
            this.activeTooltipDay = null;
            this.activeTooltipElement = null;
            this.activeTooltipIndex = null;
        },

        updateTooltipPosition() {
            if (!this.activeTooltipDay || !this.activeTooltipElement) return;
            
            this.$nextTick(() => {
                const tooltipEl = document.querySelector('[role="tooltip"]');
                if (!tooltipEl) return;
                
                const rect = this.activeTooltipElement.getBoundingClientRect();
                const tooltipWidth = tooltipEl.offsetWidth || 320;
                const tooltipHeight = tooltipEl.offsetHeight || 250;
                const spacing = 8;
                
                // Centered horizontally above the element by default
                let top = rect.top - tooltipHeight - spacing;
                let left = rect.left + (rect.width - tooltipWidth) / 2;
                
                // Flip vertically if it overflows the top of the viewport
                if (top < spacing) {
                    top = rect.bottom + spacing;
                }
                
                // Clamp horizontally to prevent overflowing viewport edges
                const minLeft = spacing;
                const maxLeft = window.innerWidth - tooltipWidth - spacing;
                left = Math.max(minLeft, Math.min(maxLeft, left));
                
                this.tooltipX = left;
                this.tooltipY = top;
            });
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
