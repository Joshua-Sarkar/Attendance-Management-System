<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">Attendance Details</h1>
                <div class="text-[12.5px] text-vellum-faint mt-1.5 tracking-wide">
                    Ledger profile for {{ $user->name }}
                </div>
            </div>
            <div class="flex gap-2">
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.attendance.logs') }}" class="inline-flex items-center bg-surface-raised hover:bg-surface-raised/80 text-vellum font-semibold py-2 px-4 rounded-md transition duration-200 text-sm border border-hairline shadow-sm">
                        ← Attendance Logs
                    </a>
                @endif
                <a href="{{ route('dashboard') }}" class="inline-flex items-center bg-surface-raised hover:bg-surface-raised/80 text-vellum font-semibold py-2 px-4 rounded-md transition duration-200 text-sm border border-hairline shadow-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <!-- Profile & 30-Day Stats Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Employee Profile Card -->
            <div class="panel space-y-4">
                <div class="panel-head border-b border-hairline pb-2 mb-4">
                    <h2 class="font-display font-medium text-[16px]">Employee Profile</h2>
                    <span class="meta font-mono text-[11px] text-vellum-faint">directory</span>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block">Employee ID</span>
                        <span class="text-vellum font-semibold font-mono text-base">{{ $user->employee_id }}</span>
                    </div>

                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block">Full Name</span>
                        <span class="text-vellum font-medium text-base">{{ $user->name }}</span>
                    </div>

                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block">Email Address</span>
                        <span class="text-vellum font-medium text-base select-all">{{ $user->email }}</span>
                    </div>

                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block">Phone Number</span>
                        <span class="text-vellum font-medium text-base">{{ $user->phone ?? 'Not Provided' }}</span>
                    </div>

                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block">Department</span>
                        <span class="text-vellum font-medium text-base">{{ $user->department?->name ?? 'Not Assigned' }}</span>
                    </div>

                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block">Reporting Manager</span>
                        <span class="text-vellum font-medium text-base">{{ $user->manager?->name ?? 'Not Assigned' }}</span>
                    </div>

                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block">Assigned Admin</span>
                        <span class="text-vellum font-medium text-base">{{ $user->admin?->name ?? 'Not Assigned' }}</span>
                    </div>

                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block font-semibold mb-1">Status</span>
                        <span class="tag {{ $user->status === 'active' ? 'present' : 'absent' }}">
                            {{ $user->status }}
                        </span>
                    </div>

                    <div>
                        <span class="text-vellum-muted text-xs uppercase tracking-wider block">Joining Date</span>
                        <span class="text-vellum font-medium text-base">
                            {{ $user->joining_date?->format('M d, Y') ?? 'Not Provided' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 30-Day Statistics Widget (Col Span 2) -->
            <div class="lg:col-span-2 panel space-y-6">
                <div class="panel-head border-b border-hairline pb-2 mb-4">
                    <h2 class="font-display font-medium text-[16px]">Last 30 Days Statistics</h2>
                    <span class="meta font-mono text-[11px] text-vellum-faint">summary</span>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    <!-- Present Days -->
                    <div class="bg-surface-raised p-4 rounded-lg border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                        <span class="text-vellum-muted text-xs font-medium uppercase tracking-wider">Present Days</span>
                        <h3 class="text-2xl font-bold text-forest mt-2 font-display">{{ $stats['present'] }}</h3>
                    </div>

                    <!-- Late Days -->
                    <div class="bg-surface-raised p-4 rounded-lg border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                        <span class="text-vellum-muted text-xs font-medium uppercase tracking-wider">Late Days</span>
                        <h3 class="text-2xl font-bold text-brass mt-2 font-display">{{ $stats['late'] }}</h3>
                    </div>

                    <!-- Absent Days -->
                    <div class="bg-surface-raised p-4 rounded-lg border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                        <span class="text-vellum-muted text-xs font-medium uppercase tracking-wider">Absent Days</span>
                        <h3 class="text-2xl font-bold text-burgundy mt-2 font-display">{{ $stats['absent'] }}</h3>
                    </div>

                    <!-- Leave Days -->
                    <div class="bg-surface-raised p-4 rounded-lg border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                        <span class="text-vellum-muted text-xs font-medium uppercase tracking-wider">Leave Days</span>
                        <h3 class="text-2xl font-bold text-slate mt-2 font-display">{{ $stats['on_leave'] }}</h3>
                    </div>

                    <!-- WFH Days -->
                    <div class="bg-surface-raised p-4 rounded-lg border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                        <span class="text-vellum-muted text-xs font-medium uppercase tracking-wider">WFH Days</span>
                        <h3 class="text-2xl font-bold text-forest mt-2 font-display">{{ $stats['wfh'] }}</h3>
                    </div>

                    <!-- Attendance Rate -->
                    <div class="bg-surface-raised p-4 rounded-lg border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                        <span class="text-vellum-muted text-xs font-medium uppercase tracking-wider">Attendance Rate</span>
                        <h3 class="text-2xl font-bold text-brass mt-2 font-display">{{ $attendancePercentage }}%</h3>
                    </div>

                    <!-- Total Hours Worked -->
                    <div class="bg-surface-raised p-4 rounded-lg border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 col-span-2 md:col-span-1 font-mono">
                        <span class="text-vellum-muted text-xs font-medium uppercase tracking-wider">Total Hours</span>
                        <h3 class="text-2xl font-bold text-brass mt-2 font-display">{{ number_format($stats['total_hours'], 1) }}h</h3>
                    </div>
                </div>

                <div class="bg-surface-raised p-4 rounded-lg border border-hairline text-sm text-vellum-muted space-y-2">
                    <p class="font-medium text-vellum flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-brass" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Statistics Notes:
                    </p>
                    <ul class="list-disc pl-5 space-y-1 text-xs">
                        <li>Metrics are calculated based on calendar weekdays (Monday to Friday) within the last 30 days.</li>
                        <li>Weekends are automatically excluded from the "Absent" calculation.</li>
                        <li>"Total Hours" includes all completed check-in/out records. For days where only a check-in is logged, hours are computed up to the current moment.</li>
                        <li>Attendance Rate is the percentage of present days (including late arrivals) out of total active weekdays.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 30-Day Logs Table -->
        <div class="panel space-y-4">
            <div class="panel-head flex items-center justify-between mb-4.5">
                <h2 class="font-display font-medium text-[16px]">30-Day Attendance Logs</h2>
                <div class="meta font-mono text-[11px] text-vellum-faint">ledger feed</div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-hairline text-vellum-muted font-semibold">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Day of Week</th>
                            <th class="py-3 px-4">Check In</th>
                            <th class="py-3 px-4">Check Out</th>
                            <th class="py-3 px-4">Hours Worked</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $day)
                            @php
                                $status = $day['status'];
                            @endphp
                            <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150
                                @if($day['is_weekend']) opacity-60 bg-surface-raised/20 @endif">
                                <td class="py-3 px-4 text-vellum font-medium">
                                    {{ $day['date']->format('M d, Y') }}
                                    @if($day['date']->isToday())
                                        <span class="ml-2 bg-brass/[0.13] text-brass text-[10px] uppercase font-bold px-1.5 py-0.5 rounded">Today</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-vellum-muted">
                                    {{ $day['day_of_week'] }}
                                </td>
                                <td class="py-3 px-4 text-vellum">
                                    {{ $day['check_in'] ? $day['check_in']->format('h:i A') : '-' }}
                                </td>
                                <td class="py-3 px-4 text-vellum">
                                    {{ $day['check_out'] ? $day['check_out']->format('h:i A') : '-' }}
                                </td>
                                <td class="py-3 px-4 text-vellum font-mono">
                                    {{ $day['hours'] ? number_format($day['hours'], 1) . 'h' : '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    @if($status === 'weekend')
                                        <span class="tag text-vellum-faint border border-hairline uppercase">Weekend</span>
                                    @else
                                        <span class="tag {{ $status }}">
                                            @if($status === 'on_leave') On Leave @else {{ str_replace('_', ' ', $status) }} @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
