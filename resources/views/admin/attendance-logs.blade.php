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
                    <option value="present" {{ $status === 'present' ? 'selected' : '' }}>Present</option>
                    <option value="late" {{ $status === 'late' ? 'selected' : '' }}>Late</option>
                    <option value="absent" {{ $status === 'absent' ? 'selected' : '' }}>Absent</option>
                    <option value="weekly_off" {{ $status === 'weekly_off' ? 'selected' : '' }}>Weekly Off</option>
                    <option value="paid_leave" {{ $status === 'paid_leave' ? 'selected' : '' }}>Paid Leave</option>
                    <option value="unpaid_leave" {{ $status === 'unpaid_leave' ? 'selected' : '' }}>Unpaid Leave</option>
                    <option value="wfh" {{ $status === 'wfh' ? 'selected' : '' }}>WFH</option>
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
                    ['label' => 'Classification', 'class' => ''],
                    ['label' => 'Status', 'class' => ''],
                    ['label' => 'Actions', 'class' => 'text-right']
                ];
            @endphp

            <x-ledger-table :headers="$headers">
                @forelse($employees as $emp)
                    @php
                        $att = $emp->today_attendance;
                        $isSunday = \Carbon\Carbon::parse($date)->isSunday();
                        $empStatus = $att ? $att->status : ($isSunday ? 'weekly_off' : 'absent');
                        
                        $checkInStr = $att?->check_in_time ? $att->check_in_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                        $checkOutStr = $att?->check_out_time ? $att->check_out_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                        
                        $durationStr = '';
                        if ($att && $att->check_in_time) {
                            $endTime = $att->check_out_time ?? ($date === today()->format('Y-m-d') ? now() : null);
                            $hours = $endTime ? $att->check_in_time->diffInMinutes($endTime, absolute: true) / 60.0 : null;
                            $durationStr = $hours ? number_format($hours, 1) . 'h worked' : '';
                        }
                        
                        $details = '—';
                        if ($empStatus === 'present') {
                            $details = $durationStr ?: 'Checked in';
                        } elseif ($empStatus === 'late') {
                            $details = $att->late_minutes . 'm past grace' . ($durationStr ? ' · ' . $durationStr : '');
                        } elseif ($empStatus === 'on_leave') {
                            $details = 'Approved leave';
                        } elseif ($empStatus === 'paid_leave') {
                            $details = 'Approved paid leave';
                        } elseif ($empStatus === 'unpaid_leave') {
                            $details = 'Approved unpaid leave';
                        } elseif ($empStatus === 'wfh') {
                            $details = 'Working from home' . ($durationStr ? ' · ' . $durationStr : '');
                        } elseif ($empStatus === 'weekly_off') {
                            $details = 'Weekly Off · Non-working day';
                        } else {
                            $details = 'No check-in recorded · flagged for review';
                        }
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
                            {{ $details }}
                        </td>

                        <!-- Classification -->
                        <td class="py-4 px-4 text-[16px] font-medium text-vellum-muted">
                            @if($att && $att->classification === 'half_day')
                                <span class="text-brass font-semibold">Half Day</span>
                                @if($att->is_overridden)
                                    <span class="text-[10px] text-vellum-faint block">Overridden</span>
                                @elseif($att->automatic_classification_reason)
                                    <span class="text-[10px] text-vellum-faint block">
                                        {{ $att->automatic_classification_reason === 'late_arrival' ? 'Late Arrival' : 'Hours' }}
                                    </span>
                                @endif
                            @elseif($att && $att->classification === 'full_day')
                                <span>Full Day</span>
                            @else
                                <span>—</span>
                            @endif
                        </td>

                        <!-- Status -->
                        <td class="py-4 px-4">
                            <span class="tag {{ $empStatus }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border
                                @if($empStatus === 'present') bg-forest-bg text-forest border-transparent
                                @elseif($empStatus === 'late') bg-cognac-bg text-cognac border-transparent
                                @elseif($empStatus === 'on_leave' || $empStatus === 'leave' || $empStatus === 'paid_leave' || $empStatus === 'unpaid_leave') bg-slate-bg text-slate border-transparent
                                @elseif($empStatus === 'wfh') bg-forest-bg text-forest border-transparent
                                @elseif($empStatus === 'weekly_off') bg-transparent text-vellum-muted border-hairline-strong
                                @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                @if($empStatus === 'on_leave') Leave @elseif($empStatus === 'paid_leave') Paid Leave @elseif($empStatus === 'unpaid_leave') Unpaid Leave @elseif($empStatus === 'weekly_off') Weekly Off @else {{ str_replace('_', ' ', $empStatus) }} @endif
                            </span>
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
                        <td colspan="9" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                            No active employees found matching the filters.
                        </td>
                    </tr>
                @endforelse
            </x-ledger-table>
        </div>

        <!-- Tab 2: Attendance Override Management (Inline Redesigned Workspace) -->
        <div x-show="activeTab === 'override'" class="p-6 bg-surface-raised/30 rounded border border-hairline font-sans" x-cloak>
            <form method="POST" action="{{ route('admin.attendance.override.store') }}" class="space-y-6">
                @csrf
                <div class="flex flex-col gap-1.5 mb-4 border-b border-hairline/10 pb-4">
                    <h3 class="text-lg font-medium text-vellum font-display">Attendance Override Workspace</h3>
                    <p class="text-xs text-vellum-muted">Apply attendance status overrides to single employees, multiple employees, departments, or the entire organization.</p>
                </div>

                <div x-data="{
                    selectedDeptIds: [],
                    selectedEmployees: @js($selectEmployeeId ? [(string)$selectEmployeeId] : []),
                    prevDeptIds: [],
                    employeeSearch: '',
                    employees: @js($employees->map(fn($e) => ['id' => (string)$e->id, 'name' => $e->name, 'employee_id' => $e->employee_id, 'dept_id' => (string)$e->department_id])),
                    
                    syncEmployeesFromDepts() {
                        const currentDepts = this.selectedDeptIds;
                        const addedDepts = currentDepts.filter(id => !this.prevDeptIds.includes(id));
                        const removedDepts = this.prevDeptIds.filter(id => !currentDepts.includes(id));
                        
                        addedDepts.forEach(deptId => {
                            this.employees.forEach(emp => {
                                if (emp.dept_id == deptId && !this.selectedEmployees.includes(emp.id)) {
                                    this.selectedEmployees.push(emp.id);
                                }
                            });
                        });
                        
                        removedDepts.forEach(deptId => {
                            this.selectedEmployees = this.selectedEmployees.filter(empId => {
                                const emp = this.employees.find(e => e.id == empId);
                                return !emp || emp.dept_id != deptId;
                            });
                        });
                        
                        this.prevDeptIds = [...currentDepts];
                    },
                    
                    toggleAllDepts() {
                        const allDeptIds = [...new Set(this.employees.map(e => e.dept_id).filter(Boolean))];
                        if (this.selectedDeptIds.length === allDeptIds.length) {
                            this.selectedDeptIds = [];
                        } else {
                            this.selectedDeptIds = allDeptIds;
                        }
                        this.syncEmployeesFromDepts();
                    },
                    
                    selectEntireOrg() {
                        this.selectedEmployees = this.employees.map(e => e.id);
                        this.selectedDeptIds = [...new Set(this.employees.map(e => e.dept_id).filter(Boolean))];
                        this.prevDeptIds = [...this.selectedDeptIds];
                    },
                    
                    clearSelection() {
                        this.selectedEmployees = [];
                        this.selectedDeptIds = [];
                        this.prevDeptIds = [];
                    },
                    
                    matchesSearch(emp) {
                        if (!this.employeeSearch) return true;
                        const query = this.employeeSearch.toLowerCase();
                        return emp.name.toLowerCase().includes(query) || emp.employee_id.toLowerCase().includes(query);
                    }
                }" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- Left: Form Parameters (Col span 5) -->
                    <div class="lg:col-span-5 space-y-4 bg-walnut/[0.05] p-5 rounded border border-hairline/10">
                        <div class="text-sm font-semibold text-vellum-faint uppercase tracking-wider mb-2">Override Parameters</div>
                        
                        <!-- Date Input -->
                        <div>
                            <x-input-label for="bulk_override_date_inline" value="Target Date" />
                            <input type="date" name="date" id="bulk_override_date_inline" value="{{ $date }}" required class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                        </div>

                        <!-- Override Status -->
                        <div>
                            <x-input-label for="bulk_status_inline" value="Override Status" />
                            <select name="status" id="bulk_status_inline" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="weekly_off">Weekly Off</option>
                                <option value="paid_leave">Paid Leave</option>
                                <option value="unpaid_leave">Unpaid Leave</option>
                                <option value="wfh">Work From Home</option>
                            </select>
                        </div>

                        <!-- Override Classification (Optional) -->
                        <div>
                            <x-input-label for="bulk_classification_inline" value="Override Classification (Optional)" />
                            <select name="classification" id="bulk_classification_inline" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                <option value="">Preserve calculated classification (Auto)</option>
                                <option value="full_day">Full Day</option>
                                <option value="half_day">Half Day</option>
                            </select>
                            <p class="text-[10.5px] text-vellum-muted mt-1">If left blank, each employee's automatically computed split is preserved.</p>
                        </div>

                        <!-- Override Reason -->
                        <div>
                            <x-input-label for="bulk_override_reason_inline" value="Override Reason (mandatory)" />
                            <textarea name="override_reason" id="bulk_override_reason_inline" rows="4" required minlength="5" placeholder="Minimum 5 characters describing reason for audit log..." class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                        </div>

                        <div class="pt-4 border-t border-hairline/10 flex justify-end">
                            <x-primary-button type="submit" class="w-full justify-center">
                                Apply Override Configuration
                            </x-primary-button>
                        </div>
                    </div>

                    <!-- Right: Scope / Employees Selection (Col span 7) -->
                    <div class="lg:col-span-7 space-y-4">
                        
                        <!-- Multiple Departments Selection Checkboxes -->
                        <div class="space-y-2 border border-hairline p-4 rounded bg-surface">
                            <div class="flex items-center justify-between border-b border-hairline/10 pb-1.5 mb-2">
                                <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Select Department Scope</span>
                                <button type="button" @click="toggleAllDepts()" class="text-[10px] uppercase font-bold text-brass hover:underline">Toggle All</button>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($departments as $dept)
                                    <label class="flex items-center gap-2.5 text-[13.5px] text-vellum cursor-pointer select-none">
                                        <input type="checkbox" value="{{ $dept->id }}" x-model="selectedDeptIds" @change="syncEmployeesFromDepts()" class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass w-4 h-4">
                                        <span>{{ $dept->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Employees Checklist -->
                        <div class="space-y-2">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <x-input-label value="Target Employees Selection" />
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="selectEntireOrg()" class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-brass/10 hover:bg-brass/25 text-brass rounded transition duration-150">
                                        Entire Org
                                    </button>
                                    <button type="button" @click="clearSelection()" class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-burgundy/10 hover:bg-burgundy/25 text-burgundy rounded transition duration-150">
                                        Clear Selection
                                    </button>
                                </div>
                            </div>

                            <!-- Live Search Input -->
                            <input type="text" x-model="employeeSearch" placeholder="Filter employee list by name or ID..." class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">

                            <!-- Scrollable list of Employees -->
                            <div class="border border-hairline rounded bg-surface overflow-y-auto max-h-[250px] p-3 divide-y divide-hairline/10">
                                <template x-for="emp in employees" :key="emp.id">
                                    <label x-show="matchesSearch(emp)" class="flex items-center gap-3 text-sm text-vellum cursor-pointer py-2 hover:bg-brass/[0.04] px-2.5 rounded transition-colors select-none">
                                        <input type="checkbox" name="user_ids[]" :value="emp.id" x-model="selectedEmployees" class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass w-4 h-4">
                                        <div class="flex-1 min-w-0">
                                            <span class="font-medium truncate block" x-text="emp.name"></span>
                                            <span class="text-[11px] text-vellum-muted font-mono" x-text="emp.employee_id"></span>
                                        </div>
                                    </label>
                                </template>
                            </div>
                            <div class="flex justify-between items-center text-[12px] font-medium mt-1 font-mono text-brass-bright bg-brass/[0.04] border border-brass/10 px-3 py-1.5 rounded">
                                <span>Selection Scope Status:</span>
                                <span x-text="selectedEmployees.length + ' employee(s) selected'"></span>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
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
                                                            @elseif($item->status === 'late') bg-cognac-bg text-cognac border-transparent
                                                            @elseif($item->status === 'on_leave' || $item->status === 'leave' || $item->status === 'paid_leave' || $item->status === 'unpaid_leave') bg-slate-bg text-slate border-transparent
                                                            @elseif($item->status === 'wfh') bg-forest-bg text-forest border-transparent
                                                            @elseif($item->status === 'weekly_off') bg-transparent text-vellum-muted border-hairline-strong
                                                            @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                                            @if($item->status === 'on_leave') Leave @elseif($item->status === 'paid_leave') Paid Leave @elseif($item->status === 'unpaid_leave') Unpaid Leave @elseif($item->status === 'weekly_off') Weekly Off @else {{ str_replace('_', ' ', $item->status) }} @endif
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
