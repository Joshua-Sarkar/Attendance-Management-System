<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">Employee Dashboard</h1>
            <div class="text-[12.5px] text-vellum-faint mt-1.5 tracking-wide">
                Track your attendance status, leaves, and performance metrics
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Welcome Card -->
            <div class="panel">
                <h3 class="font-display font-medium text-[22px] text-vellum mb-1">
                    Welcome back, {{ $user->name }}!
                </h3>
                <p class="text-vellum-faint text-[12.5px]">
                    Employee ID: <span class="font-mono text-brass font-semibold">{{ $user->employee_id }}</span>
                </p>
            </div>

            <!-- Stats KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Month's Attendance Rate -->
                <div class="stat-card success">
                    <div class="stat-label">Attendance Rate (Month)</div>
                    <div class="stat-value text-forest">
                        {{ $month_attendance_rate }}<span class="unit">%</span>
                    </div>
                    <div class="stat-foot">monthly average percentage</div>
                </div>

                <!-- Leaves Remaining -->
                <div class="stat-card info">
                    <div class="stat-label">Leaves Remaining</div>
                    <div class="stat-value text-slate">
                        {{ $leaves_remaining }}<span class="unit">days</span>
                    </div>
                    <div class="stat-foot">accrued leaves balance</div>
                </div>

                <!-- On-Time Streak -->
                <div class="stat-card warn">
                    <div class="stat-label">On-Time Streak</div>
                    <div class="stat-value text-brass">
                        {{ $on_time_streak }}<span class="unit">days</span>
                    </div>
                    <div class="stat-foot">consecutive on-time days</div>
                </div>

                <!-- Total Hours Worked -->
                <div class="stat-card success">
                    <div class="stat-label">Total Hours (Month)</div>
                    <div class="stat-value text-vellum">
                        {{ number_format($month_hours, 1) }}<span class="unit">h</span>
                    </div>
                    <div class="stat-foot">completed check-in hours</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Employee Details Card -->
                <div class="panel">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b border-hairline">
                        <h4 class="font-display font-medium text-[16px] text-vellum">Employee Details</h4>
                        <a href="{{ route('employees.show', $user) }}" class="text-brass hover:underline text-xs font-semibold">View Full Profile →</a>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 pt-2 text-sm">
                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Employee ID</span>
                            <p class="text-brass font-mono mt-1 font-semibold">{{ $user->employee_id }}</p>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Email</span>
                            <p class="text-vellum mt-1 select-all truncate">{{ $user->email }}</p>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Phone</span>
                            <p class="text-vellum mt-1">{{ $user->phone ?? 'Not Provided' }}</p>
                        </div>
                        
                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Department</span>
                            <p class="text-vellum mt-1">{{ $user->department?->name ?? 'Not Assigned' }}</p>
                        </div>
                        
                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Manager</span>
                            <p class="text-vellum mt-1">{{ $user->manager?->name ?? 'Not Assigned' }}</p>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Role</span>
                            <p class="text-vellum mt-1 capitalize">{{ $user->role }}</p>
                        </div>
                        
                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block mb-1">Status</span>
                            <div>
                                <span class="tag {{ $user->status === 'active' ? 'present' : 'absent' }}">
                                    {{ $user->status }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="text-vellum-faint text-xs uppercase tracking-wider block">Joining Date</span>
                            <p class="text-vellum mt-1">
                                {{ $user->joining_date?->format('M d, Y') ?? 'Not Provided' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Today's Attendance Status Card -->
                <div class="panel flex flex-col justify-between">
                    <div>
                        <h4 class="font-display font-medium text-[16px] text-vellum mb-4 pb-2 border-b border-hairline">Today's Attendance</h4>
                        
                        @if ($today_attendance)
                            <div class="space-y-4">
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
                            <p class="text-vellum-faint">No attendance record for today yet.</p>
                        @endif
                    </div>

                    <!-- Actions Quick Access -->
                    <div class="pt-4 border-t border-hairline mt-4">
                        <span class="text-vellum-muted text-sm font-semibold block mb-3">Quick Actions</span>
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

            <!-- Recent History Table -->
            <div class="panel space-y-4">
                <div class="flex justify-between items-center mb-4 pb-2 border-b border-hairline">
                    <h4 class="font-display font-medium text-[16px] text-vellum">Recent Attendance (Last 7 Days)</h4>
                    <a href="{{ route('attendance.my-attendance') }}" class="text-brass hover:underline text-xs font-semibold">View All →</a>
                </div>
                
                @if ($recent_history->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-hairline text-vellum-muted font-semibold">
                                    <th class="py-3 px-4">Date</th>
                                    <th class="py-3 px-4">Check In</th>
                                    <th class="py-3 px-4">Check Out</th>
                                    <th class="py-3 px-4">Hours</th>
                                    <th class="py-3 px-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent_history as $record)
                                    <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150">
                                        <td class="py-3 px-4 text-vellum font-medium">{{ $record->date->format('M d, Y') }}</td>
                                        <td class="py-3 px-4 text-vellum">
                                            @if ($record->check_in_time)
                                                {{ $record->check_in_time->format('h:i A') }}
                                            @else
                                                <span class="text-vellum-faint">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-vellum">
                                            @if ($record->check_out_time)
                                                {{ $record->check_out_time->format('h:i A') }}
                                            @else
                                                <span class="text-vellum-faint">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-vellum">
                                            @if ($record->check_in_time && $record->check_out_time)
                                                {{ number_format($record->check_in_time->diffInHours($record->check_out_time), 1) }}h
                                            @else
                                                <span class="text-vellum-faint">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="tag @if($record->status === 'present') present @elseif($record->status === 'late') late @elseif($record->status === 'on_leave') leave @elseif($record->status === 'wfh') wfh @else absent @endif">
                                                {{ $record->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-vellum-faint text-center py-6">No attendance records yet.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
