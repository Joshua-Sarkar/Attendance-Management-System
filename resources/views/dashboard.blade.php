<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">
            @if(auth()->user()->role === 'manager')
                Manager Attendance Dashboard
            @else
                Workforce Ledger
            @endif
        </h1>
        <div class="text-[12.5px] text-vellum-faint mt-1.5 tracking-wide">
            Dehradun, Uttarakhand · {{ $stats['total'] }} active members
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Dashboard Filters -->
        <div class="panel">
            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <!-- Date Filter -->
                <div>
                    <label for="date" class="block text-xs font-medium text-vellum-muted uppercase tracking-wider mb-1.5">Date</label>
                    <input type="date" name="date" id="date" value="{{ $date }}"
                           class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                </div>

                <!-- Department Filter -->
                <div>
                    <label for="department_id" class="block text-xs font-medium text-vellum-muted uppercase tracking-wider mb-1.5">Department</label>
                    <select name="department_id" id="department_id"
                            class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
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
                    <label for="search" class="block text-xs font-medium text-vellum-muted uppercase tracking-wider mb-1.5">Search Employee</label>
                    <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Search by name..."
                           class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-brass hover:bg-brass/90 text-canvas font-semibold py-2 px-4 rounded-md transition duration-200 text-sm shadow-md">
                        Filter
                    </button>
                    <a href="{{ route('dashboard') }}" class="bg-surface-raised hover:bg-surface-raised/80 text-vellum font-semibold py-2 px-4 rounded-md transition duration-200 text-center border border-hairline text-sm">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Admin Company Metrics Grid -->
        @if(auth()->user()->role === 'admin' && isset($companyMetrics))
            <div class="space-y-2">
                <h4 class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Workforce Metrics</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- People in Company -->
                    <div class="stat-card">
                        <div class="stat-label">People in Company</div>
                        <div class="stat-value">{{ $companyMetrics['people_in_company'] }}</div>
                        <div class="stat-foot">Total active directory size</div>
                    </div>

                    <!-- Admins count -->
                    <div class="stat-card info">
                        <div class="stat-label">Admins</div>
                        <div class="stat-value">{{ $companyMetrics['admins'] }}</div>
                        <div class="stat-foot">Platform administrator roles</div>
                    </div>

                    <!-- Managers count -->
                    <div class="stat-card info">
                        <div class="stat-label">Managers</div>
                        <div class="stat-value">{{ $companyMetrics['managers'] }}</div>
                        <div class="stat-foot">Department supervisor roles</div>
                    </div>

                    <!-- Employees count -->
                    <div class="stat-card success">
                        <div class="stat-label">Employees</div>
                        <div class="stat-value">{{ $companyMetrics['employees'] }}</div>
                        <div class="stat-foot">Workforce members</div>
                    </div>

                    <!-- Correction Requests -->
                    <a href="{{ route('admin.corrections.index') }}" class="stat-card warn block hover:scale-[1.01] transition-transform">
                        <div class="stat-label">Pending Corrections</div>
                        <div class="stat-value text-brass">{{ $companyMetrics['pending_corrections'] }}</div>
                        <div class="stat-foot text-brass font-semibold">Requires HR review</div>
                    </a>
                </div>
            </div>
        @endif

        <!-- Overview Stats Grid -->
        <div class="space-y-2">
            <h4 class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Today's Punctuality Dashboard</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Checked In Today -->
                <div class="stat-card">
                    <div class="stat-label">Checked In Today</div>
                    <div class="stat-value">{{ $stats['present'] }} <span class="unit">/ {{ $stats['total'] }}</span></div>
                    <div class="stat-foot up">
                        @php
                            $percentage = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100) : 0;
                        @endphp
                        {{ $percentage }}% reporting · active workforce
                    </div>
                </div>

                <!-- Late Today -->
                <div class="stat-card warn">
                    <div class="stat-label">Late Arrivals Today</div>
                    <div class="stat-value">{{ $stats['late'] }}</div>
                    <div class="stat-foot">average {{ $stats['average_late_minutes'] }} minutes past grace</div>
                </div>

                <!-- On Leave Today -->
                <div class="stat-card info">
                    <div class="stat-label">On Leave Today</div>
                    <div class="stat-value">{{ $stats['on_leave'] }} <span class="unit">+{{ $stats['wfh'] }} WFH</span></div>
                    <div class="stat-foot truncate">
                        @php
                            $leaveNames = collect($stats['exceptions']['on_leave'])->pluck('name')->merge(
                                collect($stats['exceptions']['wfh'])->map(fn($w) => $w['name'] . ' (WFH)')
                            )->join(', ');
                        @endphp
                        {{ $leaveNames ?: 'No leaves on file today' }}
                    </div>
                </div>

                <!-- Absent Today -->
                <div class="stat-card danger">
                    <div class="stat-label">Absent, unverified</div>
                    <div class="stat-value">{{ $stats['absent'] }}</div>
                    <div class="stat-foot flag">no check-in, no leave on file</div>
                </div>
            </div>
        </div>

        <!-- Lower Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Left Column: Today's Ledger (Col Span 2) -->
            <div class="lg:col-span-2 panel">
                <div class="panel-head flex items-center justify-between mb-4.5">
                    <h2 class="font-display font-medium text-[16px]">Today's ledger</h2>
                    <div class="meta font-mono text-[11px] text-vellum-faint">live</div>
                </div>
                
                <div class="ledger">
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
                        <div class="entry flex items-start gap-3.5 py-3 border-b border-hairline last:border-none relative">
                            <div class="seal {{ $status }} absolute left-[-26px] top-[16px] w-[13px] h-[13px] rounded-full border-[1.5px] bg-canvas
                                @if($status === 'present') bg-forest border-forest
                                @elseif($status === 'late') border-brass shadow-[inset_0_0_0_6px_#C9A24B] rounded-[50%_50%_50%_0]
                                @elseif($status === 'on_leave') border-slate
                                @elseif($status === 'wfh') border-forest
                                @elseif($status === 'weekend') border-hairline-strong
                                @else bg-burgundy border-burgundy rounded-[3px] @endif">
                            </div>
                            <div class="entry-time font-mono text-[12px] text-vellum-faint w-[70px] shrink-0 pt-0.5">
                                {{ $checkInTimeStr }}
                            </div>
                            <div class="entry-body flex-1 min-w-0">
                                <div class="entry-name text-[13.5px] font-medium text-vellum">
                                    <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="hover:text-brass transition-colors">
                                        {{ $emp->name }}
                                    </a>
                                    <span class="text-[11px] text-vellum-faint font-mono ml-2">({{ $emp->employee_id }})</span>
                                </div>
                                <div class="entry-desc text-[12px] text-vellum-muted mt-0.5 truncate">
                                    {{ $desc }}
                                </div>
                            </div>
                            <span class="tag {{ $status }} text-[10.5px] tracking-[0.6px] uppercase px-2.5 py-0.5 rounded-full font-medium shrink-0
                                @if($status === 'present') bg-forest-bg text-forest
                                @elseif($status === 'late') bg-brass/[0.13] text-brass
                                @elseif($status === 'on_leave') bg-slate-bg text-slate
                                @elseif($status === 'wfh') bg-forest-bg text-forest
                                @elseif($status === 'weekend') text-vellum-faint border border-hairline
                                @else bg-burgundy-bg text-burgundy @endif">
                                @if($status === 'on_leave') On Leave @elseif($status === 'weekend') Weekend @else {{ str_replace('_', ' ', $status) }} @endif
                            </span>
                        </div>
                    @empty
                        <div class="empty-cta py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded-lg mt-1 text-[12px]">
                            No active workforce members found matching the filters.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right Column: Widgets Stack -->
            <div class="space-y-5">
                <!-- Late Arrivals Widget -->
                <div class="panel">
                    <div class="panel-head flex items-center justify-between mb-4.5">
                        <h2 class="font-display font-medium text-[16px]">Late arrivals</h2>
                        <div class="meta font-mono text-[11px] text-vellum-faint">today</div>
                    </div>
                    <div class="space-y-[2px]">
                        @forelse($stats['late_arrivals'] as $lateEmp)
                            <div class="mini-row flex items-center justify-between py-2 border-b border-hairline last:border-none text-[12.5px]">
                                <span class="who font-medium text-vellum">{{ $lateEmp['name'] }}</span>
                                <span class="delta font-mono text-[11.5px] text-brass">+{{ $lateEmp['late_minutes'] }}m</span>
                            </div>
                        @empty
                            <div class="text-[12px] text-vellum-faint text-center py-4">No late arrivals today.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Corrections Widget for Admin -->
                @if(auth()->user()->role === 'admin' && isset($companyMetrics))
                    <div class="panel">
                        <div class="panel-head flex items-center justify-between mb-4.5">
                            <h2 class="font-display font-medium text-[16px]">Corrections</h2>
                            <span class="flag-badge inline-flex items-center gap-1.5 font-sans text-[11px] text-burgundy bg-burgundy-bg px-2.5 py-0.5 rounded-full">
                                {{ $companyMetrics['pending_corrections'] }} pending
                            </span>
                        </div>
                        <div class="text-[12px] text-vellum-faint py-2">
                            Review and approve profile modifications submitted by employees.
                        </div>
                        <div class="mt-2.5 pt-2.5 border-t border-hairline">
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
                    <div class="panel-head flex items-center justify-between mb-4.5">
                        <h2 class="font-display font-medium text-[16px]">Recent activity</h2>
                        <div class="meta font-mono text-[11px] text-vellum-faint">live</div>
                    </div>
                    <div class="space-y-3.5 max-h-[320px] overflow-y-auto pr-1">
                        @forelse($recentActivity as $activity)
                            <div class="flex items-start gap-3 text-[12.5px]">
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
                            <a href="{{ route('admin.attendance.logs') }}" class="block text-center bg-brass hover:bg-brass/90 text-canvas font-semibold py-2 px-4 rounded-md transition duration-200 text-xs">
                                View Full Attendance Logs
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
