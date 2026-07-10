<x-ledger-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center gap-4">
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Attendance Logs</h1>
                <!-- Export CSV Placeholder -->
                <button type="button" disabled class="inline-flex items-center justify-center bg-surface-raised text-vellum-faint font-bold py-2 px-4 rounded text-xs uppercase tracking-wider border border-hairline opacity-50 cursor-not-allowed shadow-sm h-[38px]" title="Export CSV feature coming soon">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
                <button type="button" x-data @click="window.location.hash = 'override'" class="inline-flex items-center justify-center bg-brass text-vellum font-bold py-2 px-4 rounded text-xs uppercase tracking-wider hover:bg-brass/90 transition duration-150 h-[38px] shadow-sm">
                    Override Management
                </button>
            </div>
            <div class="text-[13px] text-vellum-muted tracking-wide">
                Daily audit record system · Active roster registry
            </div>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-forest-bg text-forest border border-forest/30 px-4 py-3 rounded text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 bg-burgundy-bg text-burgundy border border-burgundy/30 px-4 py-3 rounded text-sm font-medium">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-slot name="filters">
        <form method="GET" action="{{ route('admin.attendance.logs') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <!-- Date Filter -->
            <div>
                <x-input-label for="date" value="Date" />
                <input type="date" name="date" id="date" value="{{ $date }}"
                       class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
            </div>

            <!-- Department Filter -->
            <div>
                <x-input-label for="department_id" value="Department" />
                <select name="department_id" id="department_id"
                        class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <x-input-label for="status" value="Status" />
                <select name="status" id="status"
                        class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                    <option value="">All Statuses</option>
                    @foreach(\App\Services\AttendanceStateRegistry::getStates() as $key => $details)
                        <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>
                            {{ $details['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Filter (Name or ID) -->
            <div>
                <x-input-label for="search" value="Search Employee" />
                <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Search by name or ID..."
                       class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-2">
                <x-primary-button type="submit" class="flex-1 justify-center h-[38px] text-xs">
                    Filter
                </x-primary-button>
                <x-secondary-button href="{{ route('admin.attendance.logs') }}" class="flex-1 justify-center h-[38px] text-xs" onclick="window.location.href='{{ route('admin.attendance.logs') }}'">
                    Clear
                </x-secondary-button>
            </div>
        </form>
    </x-slot>

    <x-slot name="ledgerHeader">
        <div class="flex flex-wrap items-center gap-6 border-b border-hairline/20 w-full pb-2"
             x-data="{ activeTab: window.location.hash ? window.location.hash.substring(1) : 'roster' }"
             @hashchange.window="activeTab = window.location.hash ? window.location.hash.substring(1) : 'roster'">
            <a href="#roster" 
               :class="activeTab === 'roster' ? 'border-b-2 border-brass text-brass-bright font-semibold' : 'text-vellum-muted hover:text-vellum'"
               class="pb-1.5 text-[14px] uppercase tracking-wider transition-colors duration-150">
                Daily Attendance Roster
            </a>
            <a href="#override" 
               :class="activeTab === 'override' ? 'border-b-2 border-brass text-brass-bright font-semibold' : 'text-vellum-muted hover:text-vellum'"
               class="pb-1.5 text-[14px] uppercase tracking-wider transition-colors duration-150">
                Attendance Override Management
            </a>
            <a href="#audit" 
               :class="activeTab === 'audit' ? 'border-b-2 border-brass text-brass-bright font-semibold' : 'text-vellum-muted hover:text-vellum'"
               class="pb-1.5 text-[14px] uppercase tracking-wider transition-colors duration-150">
                Override Audit Trail
            </a>
        </div>
    </x-slot>

    <div x-data="{ activeTab: window.location.hash ? window.location.hash.substring(1) : 'roster' }"
         @hashchange.window="activeTab = window.location.hash ? window.location.hash.substring(1) : 'roster'">
         
        <!-- Tab 1: Daily Attendance Roster -->
        <div x-show="activeTab === 'roster'">
            @php
                $headers = [
                    ['label' => 'Employee ID', 'class' => ''],
                    ['label' => 'Employee Name', 'class' => ''],
                    ['label' => 'Department', 'class' => ''],
                    ['label' => 'Check In', 'class' => ''],
                    ['label' => 'Check Out', 'class' => ''],
                    ['label' => 'Details', 'class' => ''],
                    ['label' => 'Status', 'class' => ''],
                    ['label' => 'Actions', 'class' => 'text-right']
                ];
            @endphp

            <x-ledger-table :headers="$headers">
                @forelse($employees as $emp)
                    @php
                        $state = $emp->resolved_state;
                        $empStatus = $state['status'];
                        
                        $checkInStr = $state['check_in_time'] ? $state['check_in_time']->timezone('Asia/Kolkata')->format('h:i A') : '—';
                        $checkOutStr = $state['check_out_time'] ? $state['check_out_time']->timezone('Asia/Kolkata')->format('h:i A') : '—';
                        
                        $displayStatus = \App\Services\AttendanceStateRegistry::getDisplayStatus($empStatus);
                        $displayLabel = \App\Services\AttendanceStateRegistry::getLabel($empStatus);
                    @endphp
                    <tr class="hover:bg-brass/[0.04] transition duration-150 text-[16px]">
                        <!-- Employee ID -->
                        <td class="py-4 px-4 font-mono text-[16px] text-brass select-all font-medium">
                            <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="hover:underline">
                                {{ $emp->employee_id }}
                            </a>
                        </td>

                        <!-- Employee Name -->
                        <td class="py-4 px-4 text-[18px] font-bold text-vellum">
                            <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="hover:text-brass transition-colors">
                                {{ $emp->name }}
                            </a>
                        </td>

                        <!-- Department -->
                        <td class="py-4 px-4 text-[16px] text-vellum font-medium">
                            {{ $emp->department?->name ?? 'None' }}
                        </td>

                        <!-- Check In -->
                        <td class="py-4 px-4 text-[16px] text-vellum-muted font-mono">
                            {{ $checkInStr }}
                        </td>

                        <!-- Check Out -->
                        <td class="py-4 px-4 text-[16px] text-vellum-muted font-mono">
                            {{ $checkOutStr }}
                        </td>

                        <!-- Details -->
                        <td class="py-4 px-4 text-[16px] text-vellum-muted">
                            <x-attendance-subtitle :employee="$emp" :date="$date" :attendance="$emp->today_attendance" />
                        </td>

                        <!-- Status -->
                        <td class="py-4 px-4">
                            <span class="tag {{ $displayStatus }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border
                                @if($displayStatus === 'present') bg-forest-bg text-forest border-transparent
                                @elseif($displayStatus === 'absent' || $displayStatus === 'upr' || $displayStatus === 'hd_upr') bg-burgundy-bg text-burgundy border-transparent
                                @elseif($displayStatus === 'half') bg-cognac-bg text-cognac border-transparent
                                @elseif($displayStatus === 'off') bg-transparent text-vellum-muted border-hairline-strong
                                @elseif($displayStatus === 'future') bg-transparent text-vellum-faint border-hairline
                                @else bg-slate-bg text-slate border-transparent @endif">
                                {{ $displayLabel }}
                            </span>
                            @php $att = $emp->today_attendance; @endphp
                            @if($att && $att->is_overridden)
                                <span class="text-[9px] text-brass uppercase font-bold block mt-1 font-mono">Overridden</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.attendance.logs', ['date' => $date, 'select_employee' => $emp->id]) }}#override" 
                               class="inline-flex items-center justify-center bg-brass/10 hover:bg-brass/25 text-brass font-bold py-1.5 px-3 rounded text-[11px] uppercase tracking-wider transition duration-150">
                                Override
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                            No active employees found matching the filters.
                        </td>
                    </tr>
                @endforelse
            </x-ledger-table>
        </div>

        <!-- Tab 2: Attendance Override Management (Inline Redesigned Workspace) -->
        <div x-show="activeTab === 'override'" class="p-6 bg-surface-raised/30 rounded border border-hairline font-sans" x-cloak>
            <div x-data="{
                step: 1,
                scopeType: 'employee',
                selectedEmployeeIds: @js($selectEmployeeId ? [(string)$selectEmployeeId] : []),
                selectedDeptIds: [],
                employeeSearch: '',
                employees: [],
                dateMode: 'single',
                singleDate: @js($date),
                startDate: @js($date),
                endDate: @js($date),
                workingDaysOnly: true,
                includeSundays: false,
                skipLeaves: false,
                skipOverrides: false,
                multipleDates: [],
                newMultipleDate: '',
                status: 'present',
                classification: 'automatic',
                overrideReason: '',
                conflictHandling: 'cancel',
                loadingPreview: false,
                previewError: null,
                previewData: {},
                errorText: null,

                init() {
                    fetch('{{ route('admin.attendance.override.employees') }}')
                        .then(res => res.json())
                        .then(data => {
                            this.employees = data;
                        });

                    this.$watch('status', value => {
                        if (value === 'half_day') {
                            this.classification = 'half_day';
                        } else {
                            this.classification = 'full_day';
                        }
                    });
                },

                toggleEmployeeSelection(id) {
                    id = String(id);
                    if (this.selectedEmployeeIds.includes(id)) {
                        this.selectedEmployeeIds = this.selectedEmployeeIds.filter(x => x !== id);
                    } else {
                        this.selectedEmployeeIds.push(id);
                    }
                },

                toggleAllDepts() {
                    const allDeptIds = [...new Set(this.employees.map(e => e.dept_id).filter(Boolean))].map(String);
                    if (this.selectedDeptIds.length === allDeptIds.length) {
                        this.selectedDeptIds = [];
                    } else {
                        this.selectedDeptIds = allDeptIds;
                    }
                },

                matchesSearch(emp) {
                    if (!this.employeeSearch) return false;
                    const q = this.employeeSearch.toLowerCase();
                    return emp.name.toLowerCase().includes(q) || emp.employee_id.toLowerCase().includes(q);
                },

                getEmployeeCount() {
                    if (this.scopeType === 'all') return this.employees.length;
                    if (this.scopeType === 'department') {
                        const deptIds = this.selectedDeptIds.map(String);
                        return this.employees.filter(e => deptIds.includes(String(e.dept_id))).length;
                    }
                    return this.selectedEmployeeIds.length;
                },

                addMultipleDate() {
                    if (this.newMultipleDate && !this.multipleDates.includes(this.newMultipleDate)) {
                        this.multipleDates.push(this.newMultipleDate);
                        this.newMultipleDate = '';
                    }
                },

                removeMultipleDate(idx) {
                    this.multipleDates.splice(idx, 1);
                },

                nextStep() {
                    this.errorText = null;
                    if (this.step === 1) {
                        if (this.scopeType === 'employee' && this.selectedEmployeeIds.length === 0) {
                            this.errorText = 'Please select at least one employee.';
                            return;
                        }
                        if (this.scopeType === 'department' && this.selectedDeptIds.length === 0) {
                            this.errorText = 'Please select at least one department.';
                            return;
                        }
                    } else if (this.step === 2) {
                        if (this.dateMode === 'single' && !this.singleDate) {
                            this.errorText = 'Please select a date.';
                            return;
                        }
                        if (this.dateMode === 'range' && (!this.startDate || !this.endDate)) {
                            this.errorText = 'Please select start and end dates.';
                            return;
                        }
                        if (this.dateMode === 'multiple' && this.multipleDates.length === 0) {
                            this.errorText = 'Please add at least one date.';
                            return;
                        }
                    } else if (this.step === 3) {
                        if (this.overrideReason.trim().length < 5) {
                            this.errorText = 'Please provide an override reason (minimum 5 characters).';
                            return;
                        }
                    }
                    this.step++;
                    if (this.step === 4) {
                        this.loadPreview();
                    }
                },

                prevStep() {
                    this.errorText = null;
                    this.step--;
                },

                loadPreview() {
                    this.loadingPreview = true;
                    this.previewError = null;

                    const payload = {
                        _token: '{{ csrf_token() }}',
                        scope_type: this.scopeType,
                        employee_ids: this.selectedEmployeeIds,
                        department_ids: this.selectedDeptIds,
                        date_mode: this.dateMode,
                        date: this.singleDate,
                        start_date: this.startDate,
                        end_date: this.endDate,
                        dates: this.multipleDates,
                        working_days_only: this.workingDaysOnly,
                        include_sundays: this.includeSundays,
                        skip_leaves: this.skipLeaves,
                        skip_overrides: this.skipOverrides,
                        status: this.status,
                        classification: this.classification,
                        override_reason: this.overrideReason,
                        conflict_handling: this.conflictHandling
                    };

                    fetch('{{ route('admin.attendance.override.preview') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => {
                        if (!res.ok) {
                            return res.json().then(err => { throw new Error(err.error || err.message || 'Validation failed'); });
                        }
                        return res.json();
                    })
                    .then(data => {
                        this.previewData = data;
                        this.loadingPreview = false;
                    })
                    .catch(err => {
                        this.previewError = err.message;
                        this.loadingPreview = false;
                    });
                }
            }">
                <form method="POST" action="{{ route('admin.attendance.override.store') }}" class="space-y-6">
                    @csrf

                    <!-- Hidden Inputs for Form Submission -->
                    <input type="hidden" name="scope_type" :value="scopeType">
                    <template x-for="empId in selectedEmployeeIds" :key="'emp-' + empId">
                        <input type="hidden" name="employee_ids[]" :value="empId">
                    </template>
                    <template x-for="deptId in selectedDeptIds" :key="'dept-' + deptId">
                        <input type="hidden" name="department_ids[]" :value="deptId">
                    </template>
                    <input type="hidden" name="date_mode" :value="dateMode">
                    <input type="hidden" name="date" :value="singleDate">
                    <input type="hidden" name="start_date" :value="startDate">
                    <input type="hidden" name="end_date" :value="endDate">
                    <template x-for="d in multipleDates" :key="'date-' + d">
                        <input type="hidden" name="dates[]" :value="d">
                    </template>
                    <input type="hidden" name="working_days_only" :value="workingDaysOnly ? 1 : 0">
                    <input type="hidden" name="include_sundays" :value="includeSundays ? 1 : 0">
                    <input type="hidden" name="skip_leaves" :value="skipLeaves ? 1 : 0">
                    <input type="hidden" name="skip_overrides" :value="skipOverrides ? 1 : 0">
                    <input type="hidden" name="status" :value="status">
                    <input type="hidden" name="classification" :value="classification">
                    <input type="hidden" name="override_reason" :value="overrideReason">
                    <input type="hidden" name="conflict_handling" :value="conflictHandling">

                    <div class="flex flex-col gap-1.5 mb-4 border-b border-hairline/10 pb-4">
                        <h3 class="text-lg font-medium text-vellum font-display">Attendance Override Workspace</h3>
                        <p class="text-xs text-vellum-muted">Apply attendance status overrides to single employees, multiple employees, departments, or the entire organization.</p>
                    </div>

                    <!-- Steps Progress Tracker -->
                    <div class="flex items-center justify-between border-b border-hairline/10 pb-4 mb-6">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm transition" :class="step === 1 ? 'bg-brass text-canvas font-bold' : 'bg-surface-raised text-vellum-muted border border-hairline'">1</span>
                            <span class="text-sm font-semibold" :class="step === 1 ? 'text-brass-bright' : 'text-vellum-muted'">Scope</span>
                        </div>
                        <div class="h-px bg-hairline/20 flex-1 mx-4"></div>
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm transition" :class="step === 2 ? 'bg-brass text-canvas font-bold' : 'bg-surface-raised text-vellum-muted border border-hairline'">2</span>
                            <span class="text-sm font-semibold" :class="step === 2 ? 'text-brass-bright' : 'text-vellum-muted'">When</span>
                        </div>
                        <div class="h-px bg-hairline/20 flex-1 mx-4"></div>
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm transition" :class="step === 3 ? 'bg-brass text-canvas font-bold' : 'bg-surface-raised text-vellum-muted border border-hairline'">3</span>
                            <span class="text-sm font-semibold" :class="step === 3 ? 'text-brass-bright' : 'text-vellum-muted'">What</span>
                        </div>
                        <div class="h-px bg-hairline/20 flex-1 mx-4"></div>
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm transition" :class="step === 4 ? 'bg-brass text-canvas font-bold' : 'bg-surface-raised text-vellum-muted border border-hairline'">4</span>
                            <span class="text-sm font-semibold" :class="step === 4 ? 'text-brass-bright' : 'text-vellum-muted'">Review</span>
                        </div>
                    </div>

                    <!-- STEP 1: Scope (Who) -->
                    <div x-show="step === 1" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-vellum block mb-1">1. Select Target Scope (Who)</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="flex flex-col p-4 bg-surface rounded border hover:border-brass/50 cursor-pointer transition relative" :class="scopeType === 'employee' ? 'border-brass bg-brass/[0.03]' : 'border-hairline'">
                                    <input type="radio" value="employee" x-model="scopeType" class="sr-only">
                                    <span class="font-bold text-vellum text-sm">Employees Selection</span>
                                    <span class="text-[11px] text-vellum-muted mt-1 leading-relaxed">Search and select one or multiple individual employees.</span>
                                </label>
                                <label class="flex flex-col p-4 bg-surface rounded border hover:border-brass/50 cursor-pointer transition relative" :class="scopeType === 'department' ? 'border-brass bg-brass/[0.03]' : 'border-hairline'">
                                    <input type="radio" value="department" x-model="scopeType" class="sr-only">
                                    <span class="font-bold text-vellum text-sm">Department Scope</span>
                                    <span class="text-[11px] text-vellum-muted mt-1 leading-relaxed">Target all employees belonging to selected departments.</span>
                                </label>
                                <label class="flex flex-col p-4 bg-surface rounded border hover:border-brass/50 cursor-pointer transition relative" :class="scopeType === 'all' ? 'border-brass bg-brass/[0.03]' : 'border-hairline'">
                                    <input type="radio" value="all" x-model="scopeType" class="sr-only">
                                    <span class="font-bold text-vellum text-sm">Entire Organization</span>
                                    <span class="text-[11px] text-vellum-muted mt-1 leading-relaxed">Override attendance for all active employees organization-wide.</span>
                                </label>
                            </div>
                        </div>

                        <!-- Employee Search (Who: Employees selection) -->
                        <div x-show="scopeType === 'employee'" class="space-y-4" x-cloak>
                            <div class="space-y-2">
                                <x-input-label value="Search Employee Names / IDs" />
                                <div class="relative" x-data="{ openDropdown: false }" @click.outside="openDropdown = false">
                                    <input type="text" x-model="employeeSearch" @focus="openDropdown = true" placeholder="Type name or ID to filter..." class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">

                                    <div x-show="openDropdown && employeeSearch.trim().length > 0" class="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto bg-surface-raised border border-hairline rounded shadow-lg divide-y divide-hairline/10" x-cloak>
                                        <template x-for="emp in employees.filter(e => matchesSearch(e))" :key="emp.id">
                                            <button type="button" @click="toggleEmployeeSelection(emp.id); employeeSearch = ''; openDropdown = false" class="w-full text-left px-4 py-2.5 text-sm hover:bg-brass/[0.06] transition flex justify-between items-center text-vellum">
                                                <div>
                                                    <span class="font-semibold block" x-text="emp.name"></span>
                                                    <span class="text-xs font-mono text-vellum-muted" x-text="emp.employee_id"></span>
                                                </div>
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-brass" x-text="selectedEmployeeIds.includes(emp.id) ? 'Selected' : 'Select'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-xs font-medium border border-hairline rounded bg-surface p-3 font-mono text-brass-bright bg-brass/[0.03] border-brass/10">
                                <span>Selection Scope Summary:</span>
                                <span class="font-bold" x-text="selectedEmployeeIds.length + ' Employee(s) Selected'"></span>
                            </div>

                            <!-- Selected List Details collapsible tag container -->
                            <div x-show="selectedEmployeeIds.length > 0" x-data="{ showList: false }">
                                <button type="button" @click="showList = !showList" class="text-[11px] text-brass uppercase font-bold hover:underline mb-2 flex items-center gap-1 font-mono">
                                    <span x-text="showList ? '[-] Hide selection details' : '[+] View selection details'"></span>
                                </button>
                                <div x-show="showList" class="flex flex-wrap gap-1.5 p-3 bg-surface rounded border border-hairline/10 max-h-40 overflow-y-auto font-sans">
                                    <template x-for="empId in selectedEmployeeIds" :key="empId">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-[11px] font-semibold bg-brass/10 text-brass border border-brass/20">
                                            <span x-text="employees.find(e => e.id == empId)?.name || 'Unknown'"></span>
                                            <button type="button" @click="toggleEmployeeSelection(empId)" class="hover:text-burgundy font-bold text-xs select-none">&times;</button>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Department checkboxes (Who: Departments scope) -->
                        <div x-show="scopeType === 'department'" class="space-y-4" x-cloak>
                            <div class="space-y-2 border border-hairline p-4 rounded bg-surface font-sans">
                                <div class="flex items-center justify-between border-b border-hairline/10 pb-1.5 mb-2 font-mono">
                                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Select Target Departments</span>
                                    <button type="button" @click="toggleAllDepts()" class="text-[10px] uppercase font-bold text-brass hover:underline">Toggle All</button>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    @foreach($departments as $dept)
                                        <label class="flex items-center gap-2.5 text-[13.5px] text-vellum cursor-pointer select-none">
                                            <input type="checkbox" value="{{ $dept->id }}" x-model="selectedDeptIds" class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass w-4 h-4">
                                            <span>{{ $dept->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-xs font-medium border border-hairline rounded bg-surface p-3 font-mono text-brass-bright bg-brass/[0.03] border-brass/10">
                                <span>Selection Scope Summary:</span>
                                <span class="font-bold" x-text="getEmployeeCount() + ' Employee(s) Selected (in ' + selectedDeptIds.length + ' Department(s))'"></span>
                            </div>
                        </div>

                        <!-- Organization scope summary -->
                        <div x-show="scopeType === 'all'" class="space-y-4" x-cloak>
                            <div class="flex justify-between items-center text-xs font-medium border border-hairline rounded bg-surface p-3 font-mono text-brass-bright bg-brass/[0.03] border-brass/10">
                                <span>Selection Scope Summary:</span>
                                <span class="font-bold" x-text="getEmployeeCount() + ' Employee(s) Selected (All Active Organization)'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Date Selection (When) -->
                    <div x-show="step === 2" class="space-y-6" x-cloak>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-vellum block mb-1">2. Select Date Option (When)</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="flex flex-col p-4 bg-surface rounded border hover:border-brass/50 cursor-pointer transition" :class="dateMode === 'single' ? 'border-brass bg-brass/[0.03]' : 'border-hairline'">
                                    <input type="radio" value="single" x-model="dateMode" class="sr-only">
                                    <span class="font-bold text-vellum text-sm">Single Date</span>
                                    <span class="text-[11px] text-vellum-muted mt-1 leading-relaxed">Override attendance parameters for a single day.</span>
                                </label>
                                <label class="flex flex-col p-4 bg-surface rounded border hover:border-brass/50 cursor-pointer transition" :class="dateMode === 'range' ? 'border-brass bg-brass/[0.03]' : 'border-hairline'">
                                    <input type="radio" value="range" x-model="dateMode" class="sr-only">
                                    <span class="font-bold text-vellum text-sm">Date Range</span>
                                    <span class="text-[11px] text-vellum-muted mt-1 leading-relaxed">Override parameters across a continuous range of dates.</span>
                                </label>
                                <label class="flex flex-col p-4 bg-surface rounded border hover:border-brass/50 cursor-pointer transition" :class="dateMode === 'multiple' ? 'border-brass bg-brass/[0.03]' : 'border-hairline'">
                                    <input type="radio" value="multiple" x-model="dateMode" class="sr-only">
                                    <span class="font-bold text-vellum text-sm">Multiple Individual Dates</span>
                                    <span class="text-[11px] text-vellum-muted mt-1 leading-relaxed">Override parameters for separate distinct days.</span>
                                </label>
                            </div>
                        </div>

                        <!-- Single Date Input -->
                        <div x-show="dateMode === 'single'" class="space-y-4" x-cloak>
                            <div>
                                <x-input-label for="single_date" value="Target Override Date" />
                                <input type="date" x-model="singleDate" id="single_date" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                            </div>
                        </div>

                        <!-- Date Range Inputs -->
                        <div x-show="dateMode === 'range'" class="space-y-4" x-cloak>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="start_date" value="Start Date" />
                                    <input type="date" x-model="startDate" id="start_date" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                </div>
                                <div>
                                    <x-input-label for="end_date" value="End Date" />
                                    <input type="date" x-model="endDate" id="end_date" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                </div>
                            </div>

                            <!-- Options Checklist for Date Range -->
                            <div class="space-y-2 border border-hairline p-4 rounded bg-surface">
                                <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider block mb-2">Override Options & Rules</span>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 font-sans">
                                    <label class="flex items-center gap-2.5 text-[13px] text-vellum cursor-pointer select-none">
                                        <input type="checkbox" x-model="workingDaysOnly" class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass w-4 h-4">
                                        <span>Working Days Only</span>
                                    </label>
                                    <label class="flex items-center gap-2.5 text-[13px] text-vellum cursor-pointer select-none">
                                        <input type="checkbox" x-model="includeSundays" class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass w-4 h-4">
                                        <span>Include Sundays</span>
                                    </label>
                                    <label class="flex items-center gap-2.5 text-[13px] text-vellum cursor-pointer select-none">
                                        <input type="checkbox" x-model="skipLeaves" class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass w-4 h-4">
                                        <span>Skip Existing Leave Records</span>
                                    </label>
                                    <label class="flex items-center gap-2.5 text-[13px] text-vellum cursor-pointer select-none">
                                        <input type="checkbox" x-model="skipOverrides" class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass w-4 h-4">
                                        <span>Skip Existing Overrides</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Multiple Dates List Workflow -->
                        <div x-show="dateMode === 'multiple'" class="space-y-4" x-cloak>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1">
                                    <x-input-label for="new_multiple_date" value="Select & Add Date" />
                                    <input type="date" x-model="newMultipleDate" id="new_multiple_date" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                </div>
                                <button type="button" @click="addMultipleDate()" class="bg-brass text-vellum font-bold py-2 px-4 rounded text-xs uppercase tracking-wider hover:bg-brass/90 transition duration-150 h-[38px] shadow-sm select-none">
                                    + Add Date
                                </button>
                            </div>

                            <div x-show="multipleDates.length > 0" class="space-y-2">
                                <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider block">Selected Dates List</span>
                                <div class="flex flex-wrap gap-1.5 p-3 bg-surface rounded border border-hairline/10 max-h-40 overflow-y-auto">
                                    <template x-for="(d, idx) in multipleDates" :key="d">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-xs font-semibold bg-brass/10 text-brass border border-brass/20">
                                            <span x-text="d"></span>
                                            <button type="button" @click="removeMultipleDate(idx)" class="hover:text-burgundy font-bold text-xs select-none">&times;</button>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Override Action (What) & Reason -->
                    <div x-show="step === 3" class="space-y-6" x-cloak>
                        <label class="text-sm font-semibold text-vellum block mb-1">3. Select Action & Reason (What)</label>
                        <div class="space-y-4">
                            <!-- Override Action -->
                            <div>
                                <x-input-label for="override_status" value="Override Action" />
                                <select x-model="status" id="override_status" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                    @foreach(\App\Services\AttendanceStateRegistry::getStates() as $key => $details)
                                        <option value="{{ $key }}">{{ $details['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Conflict Handling -->
                        <div>
                            <x-input-label for="conflict_handling_select" value="Conflict Handling Strategy" />
                            <select x-model="conflictHandling" id="conflict_handling_select" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                <option value="skip">Skip conflicting records (Do not write over pre-existing leaves/overrides)</option>
                                <option value="replace">Replace existing overrides (Overwrite pre-existing records)</option>
                                <option value="cancel">Cancel operation (Prevent override if any conflict exists)</option>
                            </select>
                        </div>

                        <!-- Override Reason -->
                        <div>
                            <x-input-label for="override_reason_textarea" value="Override Reason (mandatory)" />
                            <textarea x-model="overrideReason" id="override_reason_textarea" rows="3" minlength="5" placeholder="Minimum 5 characters describing reason for audit trail log..." class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                        </div>
                    </div>

                    <!-- STEP 4: Preview Before Apply -->
                    <div x-show="step === 4" class="space-y-6" x-cloak>
                        <label class="text-sm font-semibold text-vellum block mb-1">4. Review Preview & Apply</label>

                        <!-- Loading Indicator -->
                        <div x-show="loadingPreview" class="flex flex-col items-center justify-center py-12 space-y-3" x-cloak>
                            <svg class="animate-spin h-8 w-8 text-brass" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-vellum-muted font-mono">Evaluating configuration and scanning conflicts...</span>
                        </div>

                        <!-- Preview Error Alert -->
                        <div x-show="!loadingPreview && previewError" class="bg-burgundy-bg text-burgundy border border-burgundy/30 px-4 py-3 rounded text-sm font-medium" x-cloak>
                            <span x-text="previewError"></span>
                        </div>

                        <!-- Preview Success / Metrics -->
                        <div x-show="!loadingPreview && !previewError" class="space-y-6" x-cloak>
                            <!-- Metrics Cards Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="bg-surface-raised/40 p-4 rounded border border-hairline text-center">
                                    <span class="text-[10px] font-bold text-vellum-muted uppercase tracking-wider block">Employees Selected</span>
                                    <span class="text-3xl font-display font-medium text-brass-bright mt-1 block" x-text="previewData.employees_selected"></span>
                                </div>
                                <div class="bg-surface-raised/40 p-4 rounded border border-hairline text-center">
                                    <span class="text-[10px] font-bold text-vellum-muted uppercase tracking-wider block">Dates Selected</span>
                                    <span class="text-3xl font-display font-medium text-brass-bright mt-1 block" x-text="previewData.dates_selected"></span>
                                </div>
                                <div class="bg-surface-raised/40 p-4 rounded border border-hairline text-center">
                                    <span class="text-[10px] font-bold text-vellum-muted uppercase tracking-wider block">Attendance Records Affected</span>
                                    <span class="text-3xl font-display font-medium text-brass-bright mt-1 block" x-text="previewData.attendance_records_affected"></span>
                                </div>
                                <div class="bg-surface-raised/40 p-4 rounded border border-hairline text-center">
                                    <span class="text-[10px] font-bold text-vellum-muted uppercase tracking-wider block">Existing Overrides</span>
                                    <span class="text-3xl font-display font-medium text-brass mt-1 block" x-text="previewData.existing_overrides"></span>
                                </div>
                                <div class="bg-surface-raised/40 p-4 rounded border border-hairline text-center">
                                    <span class="text-[10px] font-bold text-vellum-muted uppercase tracking-wider block">Existing Leave Records</span>
                                    <span class="text-3xl font-display font-medium text-brass mt-1 block" x-text="previewData.existing_leave_records"></span>
                                </div>
                                <div class="bg-surface-raised/40 p-4 rounded border border-hairline text-center font-sans">
                                    <span class="text-[10px] font-bold text-vellum-muted uppercase tracking-wider block">Records That Will Change</span>
                                    <span class="text-3xl font-display font-medium text-forest mt-1 block font-bold" x-text="previewData.records_that_will_change"></span>
                                </div>
                            </div>

                            <!-- Conflict Message warning/error -->
                            <div x-show="previewData.conflict_message" class="px-4 py-3.5 rounded border text-sm font-medium font-mono" :class="previewData.conflict_message.startsWith('Error') ? 'bg-burgundy-bg text-burgundy border-burgundy/30' : 'bg-cognac-bg text-cognac border-cognac/30'" x-cloak>
                                <span x-text="previewData.conflict_message"></span>
                            </div>

                            <!-- Confirmation parameters summary box -->
                            <div class="bg-walnut/[0.03] p-4 rounded border border-hairline/25 text-xs text-vellum-muted space-y-1">
                                <span class="font-bold text-vellum block mb-1 text-[11px] uppercase tracking-wider">Configuration Summary</span>
                                <div>Override Action: <span class="font-mono text-brass-bright font-semibold" x-text="status === 'half_day' ? 'HALF DAY' : (status === 'present' ? 'PRESENT' : (status === 'paid_leave' ? 'PLANNED LEAVE (PAID)' : (status === 'unpaid_leave' ? 'UNPLANNED LEAVE (UNPAID)' : 'WEEKLY OFF')))"></span></div>
                                <div>Conflict Strategy: <span class="font-mono text-brass-bright font-semibold" x-text="conflictHandling.toUpperCase()"></span></div>
                                <div class="pt-1.5 mt-1 border-t border-hairline/10">Reason: <span class="italic text-vellum font-semibold" x-text="'&ldquo;' + overrideReason + '&rdquo;'"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Step Validation Error Alert -->
                    <div x-show="errorText" class="mt-4 bg-burgundy-bg text-burgundy border border-burgundy/30 px-4 py-3 rounded text-sm font-medium" x-cloak>
                        <span x-text="errorText"></span>
                    </div>

                    <!-- Step Navigation Control Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t border-hairline/10 mt-6 select-none">
                        <button type="button" x-show="step > 1" @click="prevStep()" class="inline-flex items-center justify-center bg-surface-raised text-vellum hover:bg-surface-raised/85 font-bold py-2 px-4 rounded text-xs uppercase tracking-wider border border-hairline shadow-sm h-[38px] transition duration-150">
                            Back
                        </button>
                        <div x-show="step === 1" class="w-1"></div> <!-- Flex spacer -->

                        <button type="button" x-show="step < 4" @click="nextStep()" class="inline-flex items-center justify-center bg-brass text-vellum font-bold py-2 px-4 rounded text-xs uppercase tracking-wider hover:bg-brass/90 transition duration-150 h-[38px] shadow-sm">
                            Next
                        </button>

                        <button type="submit" x-show="step === 4" :disabled="loadingPreview || !!previewError || (previewData && previewData.conflict_message && previewData.conflict_message.startsWith('Error'))" class="inline-flex items-center justify-center bg-forest text-vellum font-bold py-2 px-6 rounded text-xs uppercase tracking-wider hover:bg-forest/90 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 h-[38px] shadow-sm select-none">
                            Apply Override Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab 3: Override Audit Trail (Redesigned as Action Timeline) -->
        <div x-show="activeTab === 'audit'" x-cloak>
            @php
                $auditHeaders = [
                    ['label' => 'Timestamp', 'class' => ''],
                    ['label' => 'Administrator', 'class' => ''],
                    ['label' => 'Action Performed', 'class' => ''],
                    ['label' => 'Scope', 'class' => ''],
                    ['label' => 'Employees Affected', 'class' => ''],
                    ['label' => 'Reason', 'class' => ''],
                    ['label' => '', 'class' => 'text-right']
                ];
            @endphp

            <x-ledger-table :headers="$auditHeaders">
                @forelse($groupedOverrides as $override)
                    @php
                        $timestampStr = $override['timestamp'] ? $override['timestamp']->timezone('Asia/Kolkata')->format('Y-m-d h:i A') : '—';
                    @endphp
                    <tbody x-data="{ open: false }" class="border-b border-hairline/20 last:border-none">
                        <!-- Primary Event Row -->
                        <tr class="hover:bg-brass/[0.04] transition duration-150 text-[14px]">
                            <!-- Timestamp -->
                            <td class="py-4 px-4 font-mono text-[13.5px] text-vellum font-medium whitespace-nowrap">
                                {{ $timestampStr }}
                            </td>

                            <!-- Administrator -->
                            <td class="py-4 px-4 text-[14px] font-bold text-vellum whitespace-nowrap">
                                {{ $override['administrator'] }}
                            </td>

                            <!-- Action Performed -->
                            <td class="py-4 px-4 text-[14px] text-vellum font-medium">
                                {{ $override['action'] }}
                            </td>

                            <!-- Scope -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-[10px] font-mono uppercase tracking-[0.8px] rounded border
                                    @if($override['scope'] === 'Department') bg-slate-bg text-slate border-transparent
                                    @elseif($override['scope'] === 'Multiple Employees') bg-cognac-bg text-cognac border-transparent
                                    @elseif($override['scope'] === 'Organization') bg-forest-bg text-forest border-transparent
                                    @else bg-surface-raised text-vellum-muted border-hairline @endif">
                                    {{ $override['scope'] }}
                                </span>
                            </td>

                            <!-- Employees Affected -->
                            <td class="py-4 px-4 font-mono text-[13.5px] text-brass-bright font-bold text-center">
                                {{ $override['affected_count'] }}
                            </td>

                            <!-- Reason -->
                            <td class="py-4 px-4 text-[13.5px] text-vellum-muted max-w-[200px] truncate" title="{{ $override['reason'] }}">
                                {{ $override['reason'] }}
                            </td>

                            <!-- Actions (Expand) -->
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <button type="button" @click="open = !open" 
                                        class="inline-flex items-center gap-1.5 justify-center bg-brass/10 hover:bg-brass/25 text-brass font-bold py-1 px-3 rounded text-[10px] uppercase tracking-wider transition duration-150">
                                    <span x-text="open ? 'Hide' : 'Expand'"></span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3 h-3 transition-transform duration-200" :class="{'rotate-180': open}">
                                        <path d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <!-- Collapsible Affected Employees Detail Roster -->
                        <tr x-show="open" x-cloak class="bg-surface-raised/40">
                            <td colspan="7" class="py-4 px-6">
                                <div class="text-[11px] font-bold text-vellum-faint uppercase tracking-wider mb-2.5">Affected Employees Registry Detail</div>
                                
                                <div class="flex flex-wrap gap-4 text-[12px] bg-walnut/15 border border-hairline/20 p-3 rounded mb-3 text-vellum-muted">
                                    <div><span class="font-bold text-brass uppercase tracking-wider text-[10.5px]">Dates Affected:</span> <span class="text-vellum font-mono">{{ $override['dates_affected'] }}</span></div>
                                    <div><span class="font-bold text-brass uppercase tracking-wider text-[10.5px]">Conflict Strategy:</span> <span class="text-vellum font-mono uppercase">{{ $override['conflict_strategy'] }}</span></div>
                                    <div><span class="font-bold text-brass uppercase tracking-wider text-[10.5px]">Records Modified:</span> <span class="text-vellum font-mono font-bold">{{ $override['records_modified'] }}</span></div>
                                </div>

                                <div class="overflow-hidden rounded border border-hairline bg-surface">
                                    <table class="w-full text-left border-collapse text-[13.5px]">
                                        <thead>
                                            <tr class="bg-walnut/10 border-b border-hairline text-[10.5px] font-mono uppercase tracking-[0.8px] text-vellum-muted">
                                                <th class="py-2.5 px-4">Employee ID</th>
                                                <th class="py-2.5 px-4">Employee Name</th>
                                                <th class="py-2.5 px-4">Department</th>
                                                <th class="py-2.5 px-4">Original Status</th>
                                                <th class="py-2.5 px-4">Final Status</th>
                                                <th class="py-2.5 px-4">Original Classification</th>
                                                <th class="py-2.5 px-4">Final Classification</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-hairline/10">
                                            @foreach($override['items'] as $item)
                                                <tr class="hover:bg-brass/[0.02] transition-colors duration-100">
                                                    <!-- ID -->
                                                    <td class="py-2 px-4 font-mono text-[13.5px] text-brass select-all font-medium">
                                                        {{ $item->user->employee_id }}
                                                    </td>
                                                    
                                                    <!-- Name -->
                                                    <td class="py-2 px-4 font-bold text-vellum">
                                                        {{ $item->user->name }}
                                                    </td>

                                                    <!-- Department -->
                                                    <td class="py-2 px-4 text-vellum-muted">
                                                        {{ $item->user->department?->name ?? 'None' }}
                                                    </td>

                                                    <!-- Original Status -->
                                                    <td class="py-2 px-4 font-mono text-[12.5px] text-vellum-faint uppercase">
                                                        {{ str_replace('_', ' ', $item->automatic_status ?? '—') }}
                                                    </td>

                                                    <!-- Final Status -->
                                                    <td class="py-2 px-4">
                                                        <span class="tag {{ $item->status }} text-[9.5px] font-mono uppercase px-2 py-0.5 rounded border
                                                            @if($item->status === 'present') bg-forest-bg text-forest border-transparent
                                                            @elseif($item->status === 'late' || $item->status === 'half_day') bg-cognac-bg text-cognac border-transparent
                                                            @elseif($item->status === 'on_leave' || $item->status === 'leave' || $item->status === 'paid_leave' || $item->status === 'unpaid_leave') bg-slate-bg text-slate border-transparent
                                                            @elseif($item->status === 'wfh') bg-forest-bg text-forest border-transparent
                                                            @elseif($item->status === 'weekly_off') bg-transparent text-vellum-muted border-hairline-strong
                                                            @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                                            @if($item->status === 'on_leave') Leave @elseif($item->status === 'paid_leave') Planned Leave (Paid) @elseif($item->status === 'unpaid_leave') Unplanned Leave (Unpaid) @elseif($item->status === 'weekly_off') Weekly Off @else {{ str_replace('_', ' ', $item->status) }} @endif
                                                        </span>
                                                    </td>

                                                    <!-- Original Classification -->
                                                    <td class="py-2 px-4 text-[13px] text-vellum-faint">
                                                        {{ $item->automatic_classification === 'half_day' ? 'Half Day' : ($item->automatic_classification === 'full_day' ? 'Full Day' : $item->automatic_classification ?? '—') }}
                                                    </td>

                                                    <!-- Final Classification -->
                                                    <td class="py-2 px-4 text-[13px] text-vellum-muted font-bold">
                                                        {{ $item->classification === 'half_day' ? 'Half Day' : ($item->classification === 'full_day' ? 'Full Day' : '—') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                                No override logs found matching the filters.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </x-ledger-table>
        </div>
    </div>
</x-ledger-layout>
