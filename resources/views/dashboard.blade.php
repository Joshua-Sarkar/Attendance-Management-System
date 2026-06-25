<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">
            @if(auth()->user()->role === 'manager')
                Manager Attendance Dashboard
            @else
                Workforce Ledger
            @endif
        </h1>
        <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
            Dehradun, Uttarakhand · {{ $stats['total'] }} active members
        </div>
    </x-slot>

    <div class="space-y-9">
        <!-- Dashboard Filters -->
        <div class="panel">
            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <!-- Date Filter -->
                <div>
                    <label for="date" class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider mb-1.5">Date</label>
                    <input type="date" name="date" id="date" value="{{ $date }}"
                           class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                </div>

                <!-- Department Filter -->
                <div>
                    <label for="department_id" class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider mb-1.5">Department</label>
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

                <!-- Search Filter -->
                <div>
                    <label for="search" class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider mb-1.5">Search Employee</label>
                    <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Search by name..."
                           class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-brass hover:bg-brass/90 text-canvas font-bold uppercase tracking-widest rounded focus:outline-none focus:ring-2 focus:ring-brass/30 focus:ring-offset-2 focus:ring-offset-canvas transition ease-in-out duration-150 text-xs h-[38px]">
                        Filter
                    </button>
                    <a href="{{ route('dashboard') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-surface-raised hover:bg-surface-raised/80 text-vellum border border-hairline rounded font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-brass/30 focus:ring-offset-2 focus:ring-offset-canvas disabled:opacity-25 transition ease-in-out duration-150 text-center h-[38px]">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Admin Company Metrics Grid (Briefing Strip Layout) -->
        @if(auth()->user()->role === 'admin' && isset($companyMetrics))
            <div class="space-y-3">
                <h4 class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Workforce Metrics</h4>
                <div class="grid grid-cols-1 md:grid-cols-5 border border-hairline bg-surface rounded overflow-hidden">
                    <!-- People in Company -->
                    <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                        <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">People in Company</span>
                        <div class="font-display font-medium text-3xl my-2">{{ $companyMetrics['people_in_company'] }}</div>
                        <span class="text-xs text-vellum-muted">Total active directory size</span>
                    </div>

                    <!-- Admins count -->
                    <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                        <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Admins</span>
                        <div class="font-display font-medium text-3xl my-2 text-slate-light">{{ $companyMetrics['admins'] }}</div>
                        <span class="text-xs text-vellum-muted">Platform administrators</span>
                    </div>

                    <!-- Managers count -->
                    <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                        <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Managers</span>
                        <div class="font-display font-medium text-3xl my-2 text-slate-light">{{ $companyMetrics['managers'] }}</div>
                        <span class="text-xs text-vellum-muted">Department supervisors</span>
                    </div>

                    <!-- Employees count -->
                    <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                        <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Employees</span>
                        <div class="font-display font-medium text-3xl my-2 text-forest-light">{{ $companyMetrics['employees'] }}</div>
                        <span class="text-xs text-vellum-muted">Workforce members</span>
                    </div>

                    <!-- Correction Requests -->
                    <a href="{{ route('admin.corrections.index') }}" class="p-6 border-r border-hairline last:border-none flex flex-col justify-between hover:bg-brass/[0.04] transition duration-150">
                        <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider text-burgundy-light">Pending Corrections</span>
                        <div class="font-display font-medium text-3xl my-2 text-burgundy-light">{{ $companyMetrics['pending_corrections'] }}</div>
                        <span class="text-xs text-burgundy-light font-semibold">Requires HR review</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- Overview Stats Grid (Briefing Strip Layout) -->
        <div class="space-y-3">
            <h4 class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Today's Punctuality Briefing</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 border border-hairline bg-surface rounded overflow-hidden">
                <!-- Checked In Today -->
                <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                    <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Checked In Today</span>
                    <div class="font-display font-medium text-3xl my-2">
                        {{ $stats['present'] }} <span class="text-base text-vellum-muted font-sans font-normal">/ {{ $stats['total'] }}</span>
                    </div>
                    <span class="text-xs text-forest">
                        @php
                            $percentage = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100) : 0;
                        @endphp
                        {{ $percentage }}% reporting · active roster
                    </span>
                </div>

                <!-- Late Today -->
                <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                    <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider text-cognac-light">Late Arrivals Today</span>
                    <div class="font-display font-medium text-3xl my-2 text-cognac-light">{{ $stats['late'] }}</div>
                    <span class="text-xs text-cognac-light">average {{ $stats['average_late_minutes'] }}m past grace</span>
                </div>

                <!-- On Leave Today -->
                <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                    <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider text-slate-light">On Leave Today</span>
                    <div class="font-display font-medium text-3xl my-2 text-slate-light">
                        {{ $stats['on_leave'] }} <span class="text-base text-vellum-muted font-sans font-normal">+{{ $stats['wfh'] }} WFH</span>
                    </div>
                    <span class="text-xs text-slate-light truncate">
                        @php
                            $leaveNames = collect($stats['exceptions']['on_leave'])->pluck('name')->merge(
                                collect($stats['exceptions']['wfh'])->map(fn($w) => $w['name'] . ' (WFH)')
                            )->join(', ');
                        @endphp
                        {{ $leaveNames ?: 'No leaves on file today' }}
                    </span>
                </div>

                <!-- Absent Today -->
                <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                    <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider text-burgundy-light">Absent, unverified</span>
                    <div class="font-display font-medium text-3xl my-2 text-burgundy-light">{{ $stats['absent'] }}</div>
                    <span class="text-xs text-burgundy-light">no check-in, no leave logs</span>
                </div>
            </div>
        </div>

        <!-- Lower Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Today's Ledger (Col Span 2) -->
            <div class="lg:col-span-2 panel">
                <div class="panel-head">
                    <h2>Today's ledger</h2>
                    <div class="meta">live update</div>
                </div>
                
                <div class="ledger flex flex-col">
                    @forelse($employees as $emp)
                        @php
                            $dateObj = \Carbon\Carbon::parse($date);
                            $att = $emp->today_attendance;
                            $status = $att ? $att->status : ($dateObj->isSunday() ? 'weekend' : 'absent');
                            
                            // Determine entry details
                            $checkInTimeStr = $att?->check_in_time ? $att->check_in_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                            $hoursWorkedStr = '';
                            if ($att && $att->check_in_time) {
                                $endTime = $att->check_out_time ?? ($date === today()->format('Y-m-d') ? now() : null);
                                $hours = $endTime ? $att->check_in_time->diffInMinutes($endTime, absolute: true) / 60.0 : null;
                                $hoursWorkedStr = $hours ? ' · ' . number_format($hours, 1) . 'h worked' : '';
                            }
                            
                            $desc = '';
                            if ($status === 'present') {
                                $desc = 'Checked in' . $hoursWorkedStr . ' · ' . ($emp->department?->name ?? 'N/A');
                            } elseif ($status === 'late') {
                                $desc = 'Checked in · ' . $att->late_minutes . 'm past grace' . $hoursWorkedStr;
                            } elseif ($status === 'on_leave') {
                                $desc = 'Approved leave · ' . ($emp->department?->name ?? 'N/A');
                            } elseif ($status === 'wfh') {
                                $desc = 'Working from home · ' . ($emp->department?->name ?? 'N/A');
                            } elseif ($status === 'weekend') {
                                $desc = 'Weekend · Non-working day';
                            } else {
                                $desc = 'No check-in recorded · flagged for review';
                            }
                        @endphp
                        <div class="ledger-row grid grid-cols-[24px_48px_1.8fr_120px] items-center py-4 px-2 border-b border-hairline last:border-none hover:bg-brass/[0.04] transition duration-150">
                            <span class="seal-indicator {{ $status }} w-2 h-2 rounded-full 
                                @if($status === 'present' || $status === 'wfh') bg-forest
                                @elseif($status === 'late') bg-cognac
                                @elseif($status === 'on_leave' || $status === 'leave') bg-slate
                                @elseif($status === 'weekend') bg-hairline-strong
                                @else bg-burgundy @endif"></span>
                            <span class="row-time font-mono text-[13px] text-vellum-muted">{{ $checkInTimeStr }}</span>
                            <div class="row-identity flex flex-col gap-0.5">
                                <span class="row-name text-[14.5px] font-semibold text-vellum">
                                    <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="hover:text-brass transition-colors">{{ $emp->name }}</a>
                                    <span class="text-[11px] text-vellum-faint font-mono ml-2">({{ $emp->employee_id }})</span>
                                </span>
                                <span class="row-desc text-[12px] text-vellum-muted">{{ $desc }}</span>
                            </div>
                            <div class="text-right">
                                <span class="tag {{ $status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                                    @if($status === 'present') bg-forest-bg text-forest border-transparent
                                    @elseif($status === 'late') bg-cognac-bg text-cognac border-transparent
                                    @elseif($status === 'on_leave' || $status === 'leave') bg-slate-bg text-slate border-transparent
                                    @elseif($status === 'weekend') bg-transparent text-vellum-muted border-hairline-strong
                                    @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                    @if($status === 'on_leave') Leave @elseif($status === 'weekend') Weekend @else {{ str_replace('_', ' ', $status) }} @endif
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-cta py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[12px]">
                            No active workforce members found matching the filters.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right Column: Widgets Stack -->
            <div class="space-y-6">
                <!-- Late Arrivals Widget -->
                <div class="panel">
                    <div class="panel-head">
                        <h2>Late arrivals</h2>
                        <div class="meta">today</div>
                    </div>
                    <div class="flex flex-col">
                        @forelse($stats['late_arrivals'] as $lateEmp)
                            <div class="mini-row flex items-center justify-between py-3 border-b border-hairline last:border-none text-[13px]">
                                <span class="who font-medium text-vellum">{{ $lateEmp['name'] }}</span>
                                <span class="delta font-mono text-[12px] text-brass">+{{ $lateEmp['late_minutes'] }}m</span>
                            </div>
                        @empty
                            <div class="text-[12px] text-vellum-faint text-center py-4">No late arrivals today.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Corrections Widget for Admin -->
                @if(auth()->user()->role === 'admin' && isset($companyMetrics))
                    <div class="panel">
                        <div class="panel-head">
                            <h2>Corrections</h2>
                            <span class="flag-badge inline-flex items-center gap-1.5 font-sans text-[11px] text-burgundy bg-burgundy-bg px-2.5 py-0.5 rounded border border-burgundy/20">
                                {{ $companyMetrics['pending_corrections'] }} pending
                            </span>
                        </div>
                        <div class="text-[12px] text-vellum-muted py-2 leading-relaxed">
                            Review and approve profile modifications submitted by employees.
                        </div>
                        <div class="mt-3.5 pt-3.5 border-t border-hairline">
                            <a href="{{ route('admin.corrections.index') }}" class="text-[12px] font-semibold text-brass hover:underline flex items-center gap-1">
                                Manage Correction Requests
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Recent Activity Feed Widget -->
                <div class="panel">
                    <div class="panel-head">
                        <h2>Recent activity</h2>
                        <div class="meta">live</div>
                    </div>
                    <div class="space-y-3.5 max-h-[320px] overflow-y-auto pr-1">
                        @forelse($recentActivity as $activity)
                            <div class="flex items-start gap-3 text-[13px]">
                                <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 
                                    @if($activity['action'] === 'Checked In') bg-forest @else bg-brass @endif">
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-vellum leading-tight">
                                        {{ $activity['employee_name'] }}
                                    </p>
                                    <p class="text-[11.5px] text-vellum-muted mt-0.5">
                                        {{ $activity['action'] === 'Checked In' ? 'checked in' : ($activity['action'] === 'Checked Out' ? 'checked out' : strtolower($activity['action'])) }} at {{ $activity['timestamp'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-[12px] text-vellum-faint text-center py-4">No check-in/out activity recorded yet today.</p>
                        @endforelse
                    </div>
                    @if(auth()->user()->role === 'admin')
                        <div class="mt-4 pt-4 border-t border-hairline">
                            <a href="{{ route('admin.attendance.logs') }}" class="block text-center bg-brass hover:bg-brass/90 text-canvas font-bold uppercase tracking-widest py-2 px-4 rounded transition duration-200 text-xs">
                                View Full Attendance Logs
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
