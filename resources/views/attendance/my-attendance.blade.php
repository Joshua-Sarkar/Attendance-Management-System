<x-app-layout wide>
    <x-slot name="header">
        <div>
            <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">My Attendance</h1>
            <div class="text-[12.5px] text-vellum-faint mt-1.5 tracking-wide">
                View your personal attendance dashboard, logs, and stats
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="w-full space-y-6">

            <!-- Session Notifications -->
            @if(session('success'))
                <div class="rounded-md bg-forest-bg border border-hairline text-forest px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-md bg-burgundy-bg border border-hairline text-burgundy px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Profile & Today's Attendance Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Personal Profile Card -->
                <div class="panel space-y-4">
                    <h2 class="font-display font-medium text-[16px] text-vellum pb-2 border-b border-hairline">Personal Profile</h2>
                    
                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Employee ID</span>
                            <span class="text-brass font-semibold font-mono text-base">{{ $user->employee_id }}</span>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block mb-1">Status</span>
                            <span class="tag {{ $user->status === 'active' ? 'present' : 'absent' }}">
                                {{ $user->status }}
                            </span>
                        </div>

                        <div class="col-span-2">
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Full Name</span>
                            <span class="text-vellum font-medium text-base">{{ $user->name }}</span>
                        </div>

                        <div class="col-span-2">
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Email Address</span>
                            <span class="text-vellum font-medium text-base select-all">{{ $user->email }}</span>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Phone Number</span>
                            <span class="text-vellum font-medium text-base">{{ $user->phone ?? 'Not Provided' }}</span>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Joining Date</span>
                            <span class="text-vellum font-medium text-base">
                                {{ $user->joining_date?->format('M d, Y') ?? 'Not Provided' }}
                            </span>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Department</span>
                            <span class="text-vellum font-medium text-base">{{ $user->department?->name ?? 'Not Assigned' }}</span>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Reporting Manager</span>
                            <span class="text-vellum font-medium text-base">{{ $user->manager?->name ?? 'Not Assigned' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Today's Attendance & Actions Card -->
                <div class="panel flex flex-col justify-between">
                    <div>
                        <h2 class="font-display font-medium text-[16px] text-vellum mb-4 pb-2 border-b border-hairline">Today's Attendance</h2>
                        
                        @if ($today_attendance)
                            <div class="space-y-4 mb-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-vellum-muted text-sm block">Check In</span>
                                        <p class="text-vellum font-medium text-lg mt-1">
                                            @if ($today_attendance->check_in_time)
                                                {{ $today_attendance->check_in_time->format('h:i A') }}
                                            @else
                                                <span class="text-burgundy">Not checked in</span>
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <span class="text-vellum-muted text-sm block">Check Out</span>
                                        <p class="text-vellum font-medium text-lg mt-1">
                                            @if ($today_attendance->check_out_time)
                                                {{ $today_attendance->check_out_time->format('h:i A') }}
                                            @else
                                                <span class="text-vellum-faint">Pending</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 pt-2">
                                    @if ($hours_today)
                                        <div>
                                            <span class="text-vellum-muted text-sm block">Hours Worked</span>
                                            <p class="text-vellum font-medium text-lg mt-1">{{ number_format($hours_today, 1) }} hours</p>
                                        </div>
                                    @endif
                                    
                                    <div>
                                        <span class="text-vellum-muted text-sm block mb-1">Status</span>
                                        <span class="tag @if($today_attendance->status === 'present') present @elseif($today_attendance->status === 'late') late @elseif($today_attendance->status === 'on_leave') leave @elseif($today_attendance->status === 'wfh') wfh @else absent @endif">
                                            {{ str_replace('_', ' ', $today_attendance->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-vellum-faint mb-6">No attendance record for today yet.</p>
                        @endif
                    </div>

                    <!-- Actions Panel -->
                    <div class="pt-4 border-t border-hairline">
                        <span class="text-vellum-muted text-sm font-semibold block mb-3">Actions</span>
                        <div class="flex flex-col sm:flex-row gap-4">
                            @if (!$is_checked_in)
                                <form method="POST" action="{{ route('attendance.check-in') }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-brass hover:bg-brass/90 text-canvas font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md">
                                        ✓ Check In
                                    </button>
                                </form>
                            @endif
                            
                            @if ($is_checked_in && !$is_checked_out)
                                <form method="POST" action="{{ route('attendance.check-out') }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-burgundy hover:bg-burgundy/90 text-canvas font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md">
                                        ✓ Check Out
                                    </button>
                                </form>
                            @endif
                            
                            @if ($is_checked_in && $is_checked_out)
                                <div class="flex-1 bg-surface-raised text-vellum-muted font-semibold py-3 px-6 rounded-lg text-center border border-hairline">
                                    ✓ Checked in and out for today
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <!-- 30-Day Statistics Widget -->
            <div class="panel space-y-6">
                <h2 class="font-display font-medium text-[16px] text-vellum pb-2 border-b border-hairline">Last 30 Days Statistics</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <!-- Present Days -->
                    <div class="stat-card success">
                        <div class="stat-label">Days Present</div>
                        <div class="stat-value text-forest">{{ $stats['present'] }}</div>
                    </div>

                    <!-- Late Days -->
                    <div class="stat-card warn">
                        <div class="stat-label">Days Late</div>
                        <div class="stat-value text-brass">{{ $stats['late'] }}</div>
                    </div>

                    <!-- Absent Days -->
                    <div class="stat-card danger">
                        <div class="stat-label">Days Absent</div>
                        <div class="stat-value text-burgundy">{{ $stats['absent'] }}</div>
                    </div>

                    <!-- On Leave Days -->
                    <div class="stat-card info">
                        <div class="stat-label">On Leave</div>
                        <div class="stat-value text-slate">{{ $stats['on_leave'] ?? 0 }}</div>
                    </div>

                    <!-- WFH Days -->
                    <div class="stat-card success">
                        <div class="stat-label">WFH Days</div>
                        <div class="stat-value text-forest">{{ $stats['wfh'] ?? 0 }}</div>
                    </div>

                    <!-- Total Hours Worked -->
                    <div class="stat-card success">
                        <div class="stat-label">Total Hours</div>
                        <div class="stat-value text-vellum">{{ number_format($stats['total_hours'], 1) }}<span class="unit">h</span></div>
                    </div>
                </div>

                <div class="bg-surface-raised p-4 rounded border border-hairline text-sm text-vellum-muted space-y-2">
                    <p class="font-medium text-vellum">ℹ️ Statistics Notes:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Metrics are calculated based on calendar weekdays (Monday to Saturday) within the last 30 days.</li>
                        <li>Sundays are automatically excluded from the "Absent" calculation.</li>
                        <li>"Total Hours" includes all completed check-in/out records. For days where only a check-in is logged, hours are computed up to the current moment.</li>
                    </ul>
                </div>
            </div>

            <!-- 30-Day Logs Table -->
            <div class="panel space-y-4">
                <h2 class="font-display font-medium text-[16px] text-vellum pb-2 border-b border-hairline">30-Day Attendance Logs</h2>
                
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
                                <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150 @if($day['is_weekend']) opacity-60 bg-surface-raised/40 @endif">
                                    <td class="py-3 px-4 text-vellum font-medium">
                                        {{ $day['date']->format('M d, Y') }}
                                        @if($day['date']->isToday())
                                            <span class="ml-2 bg-brass/20 text-brass text-[10px] uppercase font-bold px-1.5 py-0.5 rounded">Today</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 {{ $day['is_weekend'] && $day['day_of_week'] === 'Sunday' ? 'text-vellum-faint' : 'text-vellum' }}">
                                        {{ $day['day_of_week'] }}
                                    </td>
                                    <td class="py-3 px-4 text-vellum">
                                        {{ $day['check_in'] ? $day['check_in']->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-vellum">
                                        {{ $day['check_out'] ? $day['check_out']->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-vellum">
                                        {{ $day['hours'] ? number_format($day['hours'], 1) . 'h' : '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="tag @if($status === 'present') present @elseif($status === 'late') late @elseif($status === 'on_leave') leave @elseif($status === 'wfh') wfh @elseif($status === 'weekend') weekend @else absent @endif">
                                            {{ str_replace('_', ' ', $status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
