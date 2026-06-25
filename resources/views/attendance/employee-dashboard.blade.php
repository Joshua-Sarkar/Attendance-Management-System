<x-executive-layout>
    <x-slot name="header">
        <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Employee Dashboard</h1>
        <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
            Welcome back, {{ $user->name }} · ID: <span class="font-mono text-brass font-semibold">{{ $user->employee_id }}</span>
        </div>
    </x-slot>

    <!-- Stats KPI Cards (Briefing Strip Layout) -->
    <div class="space-y-3">
        <h4 class="text-[13px] font-bold text-vellum uppercase tracking-wider">Attendance Briefing</h4>
        <div class="grid grid-cols-1 md:grid-cols-4 border border-hairline bg-surface rounded overflow-hidden">
            <!-- Attendance Rate -->
            <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-vellum-muted uppercase tracking-wider">Attendance Rate (Month)</span>
                <div class="font-display font-bold text-3xl my-2 text-forest">{{ $month_attendance_rate }}%</div>
                <span class="text-xs text-vellum-muted">Monthly average percentage</span>
            </div>

            <!-- Leaves Remaining -->
            <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-vellum-muted uppercase tracking-wider">Leaves Remaining</span>
                <div class="font-display font-bold text-3xl my-2 text-slate">
                    {{ $leaves_remaining }} <span class="text-base text-vellum-muted font-sans font-normal">days</span>
                </div>
                <span class="text-xs text-vellum-muted">Accrued leaves balance</span>
            </div>

            <!-- On-Time Streak -->
            <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-vellum-muted uppercase tracking-wider">On-Time Streak</span>
                <div class="font-display font-bold text-3xl my-2 text-brass">
                    {{ $on_time_streak }} <span class="text-base text-vellum-muted font-sans font-normal">days</span>
                </div>
                <span class="text-xs text-vellum-muted">Consecutive on-time days</span>
            </div>

            <!-- Total Hours Worked -->
            <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-vellum-muted uppercase tracking-wider">Total Hours (Month)</span>
                <div class="font-display font-bold text-3xl my-2 text-vellum">
                    {{ number_format($month_hours, 1) }} <span class="text-base text-vellum-muted font-sans font-normal">h</span>
                </div>
                <span class="text-xs text-vellum-muted">Completed check-in hours</span>
            </div>
        </div>
    </div>

    <!-- Lower Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Today's Status & Details (Col Span 1) -->
        <div class="space-y-6">
            <!-- Today's Attendance -->
            <div class="panel flex flex-col justify-between min-h-[220px]">
                <div>
                    <div class="panel-head mb-4 border-b border-hairline pb-2">
                        <h2>Today's Attendance</h2>
                        <div class="meta">live status</div>
                    </div>
                    
                    @if ($today_attendance)
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider block">Check In</span>
                                    <p class="text-vellum font-mono text-base mt-1 font-medium">
                                        @if ($today_attendance->check_in_time)
                                            {{ $today_attendance->check_in_time->timezone('Asia/Kolkata')->format('h:i A') }}
                                        @else
                                            <span class="text-burgundy">Not checked in</span>
                                        @endif
                                    </p>
                                </div>
                                
                                <div>
                                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider block">Check Out</span>
                                    <p class="text-vellum font-mono text-base mt-1 font-medium">
                                        @if ($today_attendance->check_out_time)
                                            {{ $today_attendance->check_out_time->timezone('Asia/Kolkata')->format('h:i A') }}
                                        @else
                                            <span class="text-vellum-faint">Pending</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                @if ($hours_today)
                                    <div>
                                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider block">Hours Worked</span>
                                        <p class="text-vellum font-mono text-base mt-1 font-medium">{{ number_format($hours_today, 1) }}h</p>
                                    </div>
                                @endif
                                
                                <div>
                                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider block mb-1">Status</span>
                                    <div>
                                        <span class="tag {{ $today_attendance->status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                                            @if($today_attendance->status === 'present') bg-forest-bg text-forest border-transparent
                                            @elseif($today_attendance->status === 'late') bg-cognac-bg text-cognac border-transparent
                                            @elseif($today_attendance->status === 'on_leave' || $today_attendance->status === 'leave') bg-slate-bg text-slate border-transparent
                                            @elseif($today_attendance->status === 'wfh') bg-forest-bg text-forest border-transparent
                                            @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                            {{ str_replace('_', ' ', $today_attendance->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-vellum-muted text-[13px] italic">No attendance record for today yet.</p>
                    @endif
                </div>

                <!-- Actions Quick Access -->
                <div class="pt-4 border-t border-hairline mt-6">
                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider block mb-3">Quick Actions</span>
                    <div class="flex gap-4">
                        @if (!$is_checked_in)
                            <form method="POST" action="{{ route('attendance.check-in') }}" class="flex-1">
                                @csrf
                                <x-primary-button class="w-full justify-center h-[40px]">
                                    ✓ Check In
                                </x-primary-button>
                            </form>
                        @endif
                        
                        @if ($is_checked_in && !$is_checked_out)
                            <form method="POST" action="{{ route('attendance.check-out') }}" class="flex-1">
                                @csrf
                                <x-danger-button class="w-full justify-center h-[40px]">
                                    ✓ Check Out
                                </x-danger-button>
                            </form>
                        @endif
                        
                        @if ($is_checked_in && $is_checked_out)
                            <div class="flex-1 bg-surface-raised text-vellum-muted font-mono text-xs uppercase tracking-wider py-2.5 px-4 rounded text-center border border-hairline">
                                ✓ Checked in and out for today
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Employee Details -->
            <div class="panel">
                <div class="panel-head mb-4 border-b border-hairline pb-2">
                    <h2>Profile Detail</h2>
                    <a href="{{ route('employees.show', $user) }}" class="text-brass hover:underline text-xs font-semibold">Full Profile →</a>
                </div>
                
                <div class="space-y-3.5 text-[13px]">
                    <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                        <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Email</span>
                        <span class="text-vellum select-all truncate max-w-[180px]">{{ $user->email }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                        <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Phone</span>
                        <span class="text-vellum font-mono">{{ $user->phone ?? 'Not Provided' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                        <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Department</span>
                        <span class="text-vellum font-medium">{{ $user->department?->name ?? 'Not Assigned' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                        <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Manager</span>
                        <span class="text-vellum">{{ $user->manager?->name ?? 'Not Assigned' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Joining Date</span>
                        <span class="text-vellum font-mono">{{ $user->joining_date?->format('M d, Y') ?? 'Not Provided' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Recent History Ledger (Col Span 2) -->
        <div class="lg:col-span-2 panel">
            <div class="panel-head mb-4">
                <h2>Recent Attendance (Last 7 Days)</h2>
                <a href="{{ route('attendance.my-attendance') }}" class="text-brass hover:underline text-xs font-semibold">View All Ledger →</a>
            </div>
            
            <div class="ledger flex flex-col">
                @forelse ($recent_history as $record)
                    @php
                        $dateStr = $record->date->format('M d, Y');
                        $checkInStr = $record->check_in_time ? $record->check_in_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                        $checkOutStr = $record->check_out_time ? $record->check_out_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                        
                        $durationStr = '';
                        if ($record->check_in_time && $record->check_out_time) {
                            $hrs = $record->check_in_time->diffInMinutes($record->check_out_time, absolute: true) / 60.0;
                            $durationStr = ' · ' . number_format($hrs, 1) . 'h worked';
                        }
                        
                        $desc = '';
                        if ($record->status === 'present') {
                            $desc = 'Checked in' . $durationStr;
                        } elseif ($record->status === 'late') {
                            $desc = 'Checked in · ' . $record->late_minutes . 'm past grace' . $durationStr;
                        } elseif ($record->status === 'on_leave') {
                            $desc = 'Approved leave';
                        } elseif ($record->status === 'wfh') {
                            $desc = 'Working from home' . $durationStr;
                        } else {
                            $desc = 'No check-in recorded';
                        }
                    @endphp
                    <div class="ledger-row grid grid-cols-[24px_110px_1fr_120px] items-center py-4 px-2 border-b border-hairline last:border-none hover:bg-brass/[0.04] transition duration-150">
                        <span class="seal-indicator {{ $record->status }} w-2 h-2 rounded-full 
                            @if($record->status === 'present' || $record->status === 'wfh') bg-forest
                            @elseif($record->status === 'late') bg-cognac
                            @elseif($record->status === 'on_leave' || $record->status === 'leave') bg-slate
                            @else bg-burgundy @endif"></span>
                        <span class="row-time font-mono text-[13px] text-vellum">{{ $dateStr }}</span>
                        <div class="row-identity flex flex-col gap-0.5">
                            <span class="row-name text-[14.0px] font-semibold text-vellum">
                                @if($record->check_in_time)
                                    {{ $checkInStr }} – {{ $checkOutStr }}
                                @else
                                    Not Checked In
                                @endif
                            </span>
                            <span class="row-desc text-[12px] text-vellum-muted">{{ $desc }}</span>
                        </div>
                        <div class="text-right">
                            <span class="tag {{ $record->status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                                @if($record->status === 'present') bg-forest-bg text-forest border-transparent
                                @elseif($record->status === 'late') bg-cognac-bg text-cognac border-transparent
                                @elseif($record->status === 'on_leave' || $record->status === 'leave') bg-slate-bg text-slate border-transparent
                                @elseif($record->status === 'wfh') bg-forest-bg text-forest border-transparent
                                @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                @if($record->status === 'on_leave') Leave @else {{ str_replace('_', ' ', $record->status) }} @endif
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="empty-cta py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[12px]">
                        No recent attendance records found.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-executive-layout>
