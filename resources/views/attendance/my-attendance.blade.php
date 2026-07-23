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
                                    @php
                                        $displayStatus = \App\Services\AttendanceStateRegistry::getDisplayStatus($today_attendance->status);
                                        $details = \App\Services\AttendanceStateRegistry::getStates()[$displayStatus] ?? null;
                                        $style = $details ? "background-color: {$details['bg_color']}; color: {$details['text_color']}; border-color: transparent;" : "";
                                        $label = \App\Services\AttendanceStateRegistry::getLabel($today_attendance->status);
                                    @endphp
                                    <span class="tag {{ $today_attendance->status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border" style="{{ $style }}">
                                        {{ $label }}
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
    </div>

    <!-- Employee Attendance Calendar Integration -->
    <x-attendance-calendar :user="$user" />
</x-executive-layout>
