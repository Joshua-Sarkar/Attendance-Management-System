<x-executive-layout>
    <x-slot name="header">
        <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">My Attendance</h1>
        <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
            View your personal attendance dashboard, logs, and statistics
        </div>
    </x-slot>

    <!-- Session Notifications -->
    @if(session('success'))
        <div class="rounded bg-forest-bg border border-hairline text-forest px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded bg-burgundy-bg border border-hairline text-burgundy px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Profile & Today's Attendance Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Personal Profile Card -->
        <div class="panel">
            <div class="panel-head mb-4 border-b border-hairline pb-2">
                <h2>Personal Profile</h2>
                <div class="meta">EMP ID: {{ $user->employee_id }}</div>
            </div>
            
            <div class="space-y-3.5 text-[13px]">
                <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                    <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Full Name</span>
                    <span class="text-vellum font-semibold">{{ $user->name }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                    <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Email Address</span>
                    <span class="text-vellum select-all truncate max-w-[200px]">{{ $user->email }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                    <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Phone Number</span>
                    <span class="text-vellum font-mono">{{ $user->phone ?? 'Not Provided' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                    <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Department</span>
                    <span class="text-vellum">{{ $user->department?->name ?? 'Not Assigned' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                    <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Reporting Manager</span>
                    <span class="text-vellum">{{ $user->manager?->name ?? 'Not Assigned' }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-hairline/40">
                    <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Joining Date</span>
                    <span class="text-vellum font-mono">{{ $user->joining_date?->format('M d, Y') ?? 'Not Provided' }}</span>
                </div>
                <div class="flex justify-between items-center py-1">
                    <span class="text-vellum-faint font-semibold uppercase tracking-wider text-[10.5px]">Roster Status</span>
                    <div>
                        <span class="tag {{ $user->status === 'active' ? 'present' : 'absent' }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                            @if($user->status === 'active') bg-forest-bg text-forest border-transparent
                            @else bg-burgundy-bg text-burgundy border-transparent @endif">
                            {{ $user->status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Attendance & Actions Card -->
        <div class="panel flex flex-col justify-between min-h-[280px]">
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
                                        @elseif($today_attendance->status === 'late' || $today_attendance->status === 'half_day') bg-cognac-bg text-cognac border-transparent
                                        @elseif($today_attendance->status === 'on_leave' || $today_attendance->status === 'leave' || $today_attendance->status === 'paid_leave' || $today_attendance->status === 'unpaid_leave') bg-slate-bg text-slate border-transparent
                                        @elseif($today_attendance->status === 'wfh') bg-forest-bg text-forest border-transparent
                                        @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                        @if($today_attendance->status === 'on_leave') Leave @elseif($today_attendance->status === 'paid_leave') Paid Leave @elseif($today_attendance->status === 'unpaid_leave') Unpaid Leave @else {{ str_replace('_', ' ', $today_attendance->status) }} @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-vellum-muted text-[13px] italic">No attendance record for today yet.</p>
                @endif
            </div>

            <!-- Actions Panel -->
            <div class="pt-4 border-t border-hairline mt-6">
                <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider block mb-3">Quick Actions</span>
                <div class="flex gap-4">
                    @if (!$is_checked_in)
                        <form method="POST" action="{{ route('attendance.check-in') }}" class="flex-1">
                            @csrf
                            <x-primary-button class="w-full justify-center h-[40px]">
                                Γ£ô Check In
                            </x-primary-button>
                        </form>
                    @endif
                    
                    @if ($is_checked_in && !$is_checked_out)
                        <form method="POST" action="{{ route('attendance.check-out') }}" class="flex-1">
                            @csrf
                            <x-danger-button class="w-full justify-center h-[40px]">
                                Γ£ô Check Out
                            </x-danger-button>
                        </form>
                    @endif
                    
                    @if ($is_checked_in && $is_checked_out)
                        <div class="flex-1 bg-surface-raised text-vellum-muted font-mono text-xs uppercase tracking-wider py-2.5 px-4 rounded text-center border border-hairline">
                            Γ£ô Checked in and out for today
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 30-Day Statistics Widget (Briefing Strip Layout) -->
    <div class="space-y-3">
        <h4 class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Last 30 Days Statistics</h4>
        <div class="grid grid-cols-2 md:grid-cols-6 border border-hairline bg-surface rounded overflow-hidden">
            <!-- Present Days -->
            <div class="p-5 border-r border-b md:border-b-0 border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[10px] font-semibold text-vellum-faint uppercase tracking-wider">Days Present</span>
                <div class="font-display font-medium text-2xl my-2 text-forest">{{ $stats['present'] }}</div>
                <span class="text-[11px] text-vellum-muted">Roster verified</span>
            </div>

            <!-- Late Days -->
            <div class="p-5 border-r border-b md:border-b-0 border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[10px] font-semibold text-vellum-faint uppercase tracking-wider">Days Late</span>
                <div class="font-display font-medium text-2xl my-2 text-cognac">{{ $stats['late'] }}</div>
                <span class="text-[11px] text-vellum-muted">Past grace</span>
            </div>

            <!-- Absent Days -->
            <div class="p-5 border-r border-b md:border-b-0 border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[10px] font-semibold text-vellum-faint uppercase tracking-wider">Days Absent</span>
                <div class="font-display font-medium text-2xl my-2 text-burgundy">{{ $stats['absent'] }}</div>
                <span class="text-[11px] text-vellum-muted">Unverified</span>
            </div>

            <!-- On Leave Days -->
            <div class="p-5 border-r border-b md:border-b-0 border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[10px] font-semibold text-vellum-faint uppercase tracking-wider">On Leave</span>
                <div class="font-display font-medium text-2xl my-2 text-slate">{{ $stats['on_leave'] ?? 0 }}</div>
                <span class="text-[11px] text-vellum-muted">Approved off</span>
            </div>

            <!-- WFH Days -->
            <div class="p-5 border-r border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[10px] font-semibold text-vellum-faint uppercase tracking-wider">WFH Days</span>
                <div class="font-display font-medium text-2xl my-2 text-forest">{{ $stats['wfh'] ?? 0 }}</div>
                <span class="text-[11px] text-vellum-muted">Remote roster</span>
            </div>

            <!-- Total Hours Worked -->
            <div class="p-5 border-hairline last:border-none flex flex-col justify-between">
                <span class="text-[10px] font-semibold text-vellum-faint uppercase tracking-wider">Total Hours</span>
                <div class="font-display font-medium text-2xl my-2 text-vellum">
                    {{ number_format($stats['total_hours'], 1) }}<span class="text-sm font-sans font-normal text-vellum-muted">h</span>
                </div>
                <span class="text-[11px] text-vellum-muted">Aggregated hours</span>
            </div>
        </div>
    </div>

    <!-- 30-Day Logs Table (Ledger) -->
    <div class="panel">
        <div class="panel-head mb-4 border-b border-hairline pb-2">
            <h2>30-Day Attendance Logs</h2>
            <div class="meta">personal ledger</div>
        </div>
        
        <div class="ledger flex flex-col">
            @foreach($history as $day)
                @php
                    $status = $day['status'];
                    $dateStr = $day['date']->format('M d, Y');
                    $dayName = $day['day_of_week'];
                    $checkInStr = $day['check_in'] ? $day['check_in']->timezone('Asia/Kolkata')->format('h:i A') : 'ΓÇö';
                    $checkOutStr = $day['check_out'] ? $day['check_out']->timezone('Asia/Kolkata')->format('h:i A') : 'ΓÇö';
                    
                    $durationStr = $day['hours'] ? ' ┬╖ ' . number_format($day['hours'], 1) . 'h worked' : '';
                    $classificationStr = ($status !== 'weekend' && isset($day['classification']) && $day['classification'] === 'half_day') ? ' ┬╖ Half Day' : '';
                    
                    $desc = '';
                    if ($status === 'present') {
                        $desc = 'Checked in at ' . $checkInStr . ' ┬╖ Checked out at ' . $checkOutStr . $durationStr . $classificationStr;
                    } elseif ($status === 'late') {
                        $desc = 'Checked in late at ' . $checkInStr . ' ┬╖ ' . (isset($day['late_minutes']) ? $day['late_minutes'] . 'm past grace' : 'past grace') . $durationStr . $classificationStr;
                    } elseif ($status === 'on_leave') {
                        $desc = 'Approved leave' . $classificationStr;
                    } elseif ($status === 'wfh') {
                        $desc = 'Working from home' . $durationStr . $classificationStr;
                    } elseif ($status === 'weekly_off') {
                        $desc = 'Weekly Off ┬╖ Non-working day';
                    } elseif ($status === 'paid_leave') {
                        $desc = 'Approved paid leave' . $classificationStr;
                    } elseif ($status === 'unpaid_leave') {
                        $desc = 'Approved unpaid leave' . $classificationStr;
                    } elseif ($status === 'weekend') {
                        $desc = 'Weekend ┬╖ Non-working day';
                    } else {
                        $desc = 'No check-in recorded' . $classificationStr;
                    }
                @endphp
                <div class="ledger-row grid grid-cols-[24px_110px_1fr_120px] items-center py-4 px-2 border-b border-hairline last:border-none hover:bg-brass/[0.04] transition duration-150 @if($day['is_weekend']) opacity-60 @endif">
                    <span class="seal-indicator {{ $status }} w-2 h-2 rounded-full 
                        @if($status === 'present' || $status === 'wfh') bg-forest
                        @elseif($status === 'late') bg-cognac
                        @elseif($status === 'on_leave' || $status === 'leave' || $status === 'paid_leave' || $status === 'unpaid_leave') bg-slate
                        @elseif($status === 'weekend' || $status === 'weekly_off') bg-hairline-strong
                        @else bg-burgundy @endif"></span>
                    <span class="row-time font-mono text-[13px] text-vellum">
                        {{ $dateStr }}
                        @if($day['date']->isToday())
                            <span class="ml-1.5 bg-brass/10 text-brass text-[9px] uppercase font-bold px-1.5 py-0.5 rounded font-mono">Today</span>
                        @endif
                    </span>
                    <div class="row-identity flex flex-col gap-0.5">
                        <span class="row-name text-[14.0px] font-semibold text-vellum">
                            {{ $dayName }}
                        </span>
                        <span class="row-desc text-[12px] text-vellum-muted">{{ $desc }}</span>
                    </div>
                    <div class="text-right">
                        <span class="tag {{ $status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                            @if($status === 'present') bg-forest-bg text-forest border-transparent
                            @elseif($status === 'late' || $status === 'half_day') bg-cognac-bg text-cognac border-transparent
                            @elseif($status === 'on_leave' || $status === 'leave' || $status === 'paid_leave' || $status === 'unpaid_leave') bg-slate-bg text-slate border-transparent
                            @elseif($status === 'wfh') bg-forest-bg text-forest border-transparent
                            @elseif($status === 'weekly_off' || $status === 'weekend') bg-transparent text-vellum-muted border-hairline-strong
                            @else bg-burgundy-bg text-burgundy border-transparent @endif">
                            @if($status === 'on_leave' || $status === 'leave') Leave @elseif($status === 'paid_leave') Paid Leave @elseif($status === 'unpaid_leave') Unpaid Leave @elseif($status === 'weekly_off') Weekly Off @else {{ str_replace('_', ' ', $status) }} @endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-executive-layout>
