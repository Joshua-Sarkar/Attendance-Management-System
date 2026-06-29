<x-executive-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Attendance Details</h1>
                <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
                    Ledger profile for {{ $user->name }}
                </div>
            </div>
            <div class="flex gap-2">
                @if(auth()->user()->role === 'admin')
                    <x-secondary-button onclick="window.location.href='{{ route('admin.attendance.logs') }}'">
                        ← Attendance Logs
                    </x-secondary-button>
                @endif
                <x-secondary-button onclick="window.location.href='{{ route('dashboard') }}'">
                    ← Back to Dashboard
                </x-secondary-button>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-forest-bg text-forest border border-forest/30 px-4 py-3 rounded text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-burgundy-bg text-burgundy border border-burgundy/30 px-4 py-3 rounded text-sm font-medium">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block">Employee ID</span>
                    <span class="text-vellum font-semibold font-mono text-base">{{ $user->employee_id }}</span>
                </div>

                <div>
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block">Full Name</span>
                    <span class="text-vellum font-semibold text-base">{{ $user->name }}</span>
                </div>

                <div>
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block">Email Address</span>
                    <span class="text-vellum font-medium text-base select-all">{{ $user->email }}</span>
                </div>

                <div>
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block">Phone Number</span>
                    <span class="text-vellum font-medium text-base">{{ $user->phone ?? 'Not Provided' }}</span>
                </div>

                <div>
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block">Department</span>
                    <span class="text-vellum font-medium text-base">{{ $user->department?->name ?? 'Not Assigned' }}</span>
                </div>

                <div>
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block">Reporting Manager</span>
                    <span class="text-vellum font-medium text-base">{{ $user->manager?->name ?? 'Not Assigned' }}</span>
                </div>

                <div>
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block">Assigned Admin</span>
                    <span class="text-vellum font-medium text-base">{{ $user->admin?->name ?? 'Not Assigned' }}</span>
                </div>

                <div>
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block mb-1">Status</span>
                    <span class="tag {{ $user->status === 'active' ? 'present' : 'absent' }}">
                        {{ $user->status }}
                    </span>
                </div>

                <div>
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider block">Joining Date</span>
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
                <div class="bg-surface-raised p-4 rounded border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider">Present Days</span>
                    <h3 class="text-2xl font-bold text-forest mt-2 font-display">{{ $stats['present'] }}</h3>
                </div>

                <!-- Late Days -->
                <div class="bg-surface-raised p-4 rounded border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider">Late Days</span>
                    <h3 class="text-2xl font-bold text-brass mt-2 font-display">{{ $stats['late'] }}</h3>
                </div>

                <!-- Absent Days -->
                <div class="bg-surface-raised p-4 rounded border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider">Absent Days</span>
                    <h3 class="text-2xl font-bold text-burgundy mt-2 font-display">{{ $stats['absent'] }}</h3>
                </div>

                <!-- Leave Days -->
                <div class="bg-surface-raised p-4 rounded border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider">Leave Days</span>
                    <h3 class="text-2xl font-bold text-slate mt-2 font-display">{{ $stats['on_leave'] }}</h3>
                </div>

                <!-- WFH Days -->
                <div class="bg-surface-raised p-4 rounded border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider">WFH Days</span>
                    <h3 class="text-2xl font-bold text-forest mt-2 font-display">{{ $stats['wfh'] }}</h3>
                </div>

                <!-- Attendance Rate -->
                <div class="bg-surface-raised p-4 rounded border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider">Attendance Rate</span>
                    <h3 class="text-2xl font-bold text-brass mt-2 font-display">{{ $attendancePercentage }}%</h3>
                </div>

                <!-- Total Hours Worked -->
                <div class="bg-surface-raised p-4 rounded border border-hairline flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200 col-span-2 md:col-span-1">
                    <span class="text-vellum-muted text-[10.5px] font-semibold uppercase tracking-wider">Total Hours</span>
                    <h3 class="text-2xl font-bold text-brass mt-2 font-display font-mono">{{ number_format($stats['total_hours'], 1) }}h</h3>
                </div>
            </div>

            <div class="bg-surface-raised p-4 rounded border border-hairline text-sm text-vellum-muted space-y-2">
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
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="bg-surface-raised border-b border-hairline uppercase text-[10px] tracking-wider text-vellum-muted font-semibold">
                        <th class="py-2.5 px-4">Date</th>
                        <th class="py-2.5 px-4">Day of Week</th>
                        <th class="py-2.5 px-4">Check In</th>
                        <th class="py-2.5 px-4">Check Out</th>
                        <th class="py-2.5 px-4">Hours Worked</th>
                        <th class="py-2.5 px-4">Classification</th>
                        <th class="py-2.5 px-4">Status</th>
                        @if(auth()->user()->role === 'admin')
                            <th class="py-2.5 px-4 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-hairline">
                    @foreach($history as $day)
                        @php
                            $status = $day['status'];
                        @endphp
                        <tr class="hover:bg-brass/[0.04] transition duration-150 @if($day['is_weekend']) opacity-60 bg-surface-raised/20 @endif">
                            <td class="py-3 px-4 text-vellum font-semibold">
                                {{ $day['date']->format('M d, Y') }}
                                @if($day['date']->isToday())
                                    <span class="ml-2 bg-brass/[0.13] text-brass text-[9px] uppercase font-bold px-1.5 py-0.5 rounded font-mono">Today</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-vellum-muted">
                                {{ $day['day_of_week'] }}
                            </td>
                            <td class="py-3 px-4 text-vellum font-mono">
                                {{ $day['check_in'] ? $day['check_in']->format('h:i A') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-vellum font-mono">
                                {{ $day['check_out'] ? $day['check_out']->format('h:i A') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-vellum font-mono">
                                {{ $day['hours'] ? number_format($day['hours'], 1) . 'h' : '-' }}
                            </td>
                            <td class="py-3 px-4 text-vellum font-semibold text-vellum-muted">
                                @if($status !== 'weekend')
                                    @if(($day['classification'] ?? 'full_day') === 'half_day')
                                        <span class="text-brass font-semibold">Half Day</span>
                                    @else
                                        <span>Full Day</span>
                                    @endif
                                @else
                                    <span>—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($status === 'weekend')
                                    <span class="px-2 py-0.5 text-[9px] font-mono font-semibold uppercase tracking-wider rounded border border-hairline text-vellum-faint">Weekend</span>
                                @else
                                    <span class="px-2 py-0.5 text-[9px] font-mono font-semibold uppercase tracking-wider rounded {{ $status }}">
                                        @if($status === 'on_leave') On Leave @else {{ str_replace('_', ' ', $status) }} @endif
                                    </span>
                                    @if($day['is_overridden'] ?? false)
                                        <span class="text-[9px] text-brass uppercase font-bold block mt-1 font-mono">Overridden</span>
                                    @endif
                                @endif
                            </td>
                            @if(auth()->user()->role === 'admin')
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('admin.attendance.logs', ['date' => $day['date']->format('Y-m-d'), 'select_employee' => $user->id]) }}#override" 
                                       class="inline-flex items-center justify-center bg-brass/10 hover:bg-brass/25 text-brass font-bold py-1 px-2.5 rounded text-[10px] uppercase tracking-wider transition duration-150">
                                        Override
                                    </a>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


</x-executive-layout>
