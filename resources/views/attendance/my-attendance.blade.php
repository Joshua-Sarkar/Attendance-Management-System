<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface dark:text-on-surface leading-tight">
            {{ __('My Attendance') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Session Notifications -->
            @if(session('success'))
                <div class="rounded-md bg-green-100 border border-green-300 text-green-700 px-4 py-3 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-md bg-red-100 border border-red-300 text-red-700 px-4 py-3 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Profile & Today's Attendance Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Personal Profile Card -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 space-y-4">
                    <h4 class="text-lg font-semibold text-on-surface pb-2 border-b border-outline-variant/30">Personal Profile</h4>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Employee ID</span>
                            <span class="text-on-surface font-semibold font-mono text-base">{{ $user->employee_id }}</span>
                        </div>

                        <div>
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block font-semibold mb-1">Status</span>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold capitalize
                                @if($user->status === 'active') bg-primary/20 text-primary @else bg-error/20 text-error @endif">
                                {{ $user->status }}
                            </span>
                        </div>

                        <div class="col-span-2">
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Full Name</span>
                            <span class="text-on-surface font-medium text-base">{{ $user->name }}</span>
                        </div>

                        <div class="col-span-2">
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Email Address</span>
                            <span class="text-on-surface font-medium text-base select-all">{{ $user->email }}</span>
                        </div>

                        <div>
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Phone Number</span>
                            <span class="text-on-surface font-medium text-base">{{ $user->phone ?? 'Not Provided' }}</span>
                        </div>

                        <div>
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Joining Date</span>
                            <span class="text-on-surface font-medium text-base">
                                {{ $user->joining_date?->format('M d, Y') ?? 'Not Provided' }}
                            </span>
                        </div>

                        <div>
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Department</span>
                            <span class="text-on-surface font-medium text-base">{{ $user->department?->name ?? 'Not Assigned' }}</span>
                        </div>

                        <div>
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Reporting Manager</span>
                            <span class="text-on-surface font-medium text-base">{{ $user->manager?->name ?? 'Not Assigned' }}</span>
                        </div>

                        <div>
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Assigned Admin</span>
                            <span class="text-on-surface font-medium text-base">{{ $user->admin?->name ?? 'Not Assigned' }}</span>
                        </div>

                        <div>
                            <span class="text-on-surface-variant text-xs uppercase tracking-wider block">Role</span>
                            <span class="text-on-surface font-medium text-base capitalize">{{ $user->role }}</span>
                        </div>
                    </div>
                </div>

                <!-- Today's Attendance & Actions Card -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex flex-col justify-between">
                    <div>
                        <h4 class="text-lg font-semibold text-on-surface mb-4 pb-2 border-b border-outline-variant/30">Today's Attendance</h4>
                        
                        @if ($today_attendance)
                            <div class="space-y-4 mb-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-on-surface-variant text-sm">Check In</span>
                                        <p class="text-on-surface font-medium text-lg">
                                            @if ($today_attendance->check_in_time)
                                                {{ $today_attendance->check_in_time->format('h:i A') }}
                                            @else
                                                <span class="text-error">Not checked in</span>
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <span class="text-on-surface-variant text-sm">Check Out</span>
                                        <p class="text-on-surface font-medium text-lg">
                                            @if ($today_attendance->check_out_time)
                                                {{ $today_attendance->check_out_time->format('h:i A') }}
                                            @else
                                                <span class="text-on-surface-variant">Pending</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    @if ($hours_today)
                                        <div>
                                            <span class="text-on-surface-variant text-sm">Hours Worked</span>
                                            <p class="text-on-surface font-medium text-lg">{{ number_format($hours_today, 1) }} hours</p>
                                        </div>
                                    @endif
                                    
                                    <div>
                                        <span class="text-on-surface-variant text-sm">Status</span>
                                        <div>
                                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold capitalize
                                                @if($today_attendance->status === 'present')
                                                    bg-primary/20 text-primary
                                                @elseif($today_attendance->status === 'late')
                                                    bg-tertiary/20 text-tertiary
                                                @else
                                                    bg-error/20 text-error
                                                @endif
                                            ">
                                                {{ $today_attendance->status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-on-surface-variant mb-6">No attendance record for today yet.</p>
                        @endif
                    </div>

                    <!-- Actions Panel -->
                    <div class="pt-4 border-t border-outline-variant/20">
                        <span class="text-on-surface-variant text-sm font-semibold block mb-3">Actions</span>
                        <div class="flex flex-col sm:flex-row gap-4">
                            @if (!$is_checked_in)
                                <form method="POST" action="{{ route('attendance.check-in') }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-on-primary font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-lg hover:shadow-xl">
                                        ✓ Check In
                                    </button>
                                </form>
                            @endif
                            
                            @if ($is_checked_in && !$is_checked_out)
                                <form method="POST" action="{{ route('attendance.check-out') }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-secondary hover:bg-secondary/90 text-on-secondary font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-lg hover:shadow-xl">
                                        ✓ Check Out
                                    </button>
                                </form>
                            @endif
                            
                            @if ($is_checked_in && $is_checked_out)
                                <div class="flex-1 bg-surface-container-high/50 text-on-surface font-semibold py-3 px-6 rounded-lg text-center border border-outline-variant/30">
                                    ✓ Checked in and out for today
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <!-- 30-Day Statistics Widget -->
            <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 space-y-6">
                <h4 class="text-lg font-semibold text-on-surface pb-2 border-b border-outline-variant/30">Last 30 Days Statistics</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Present Days -->
                    <div class="bg-surface-container p-5 rounded-lg border border-outline-variant/20 flex flex-col justify-between">
                        <span class="text-on-surface-variant text-sm font-medium">Days Present</span>
                        <h3 class="text-4xl font-bold text-secondary mt-2">{{ $stats['present'] }}</h3>
                    </div>

                    <!-- Late Days -->
                    <div class="bg-surface-container p-5 rounded-lg border border-outline-variant/20 flex flex-col justify-between">
                        <span class="text-on-surface-variant text-sm font-medium">Days Late</span>
                        <h3 class="text-4xl font-bold text-tertiary mt-2">{{ $stats['late'] }}</h3>
                    </div>

                    <!-- Absent Days -->
                    <div class="bg-surface-container p-5 rounded-lg border border-outline-variant/20 flex flex-col justify-between">
                        <span class="text-on-surface-variant text-sm font-medium">Days Absent</span>
                        <h3 class="text-4xl font-bold text-error mt-2">{{ $stats['absent'] }}</h3>
                    </div>

                    <!-- Total Hours Worked -->
                    <div class="bg-surface-container p-5 rounded-lg border border-outline-variant/20 flex flex-col justify-between">
                        <span class="text-on-surface-variant text-sm font-medium">Total Hours</span>
                        <h3 class="text-4xl font-bold text-primary mt-2">{{ number_format($stats['total_hours'], 1) }}h</h3>
                    </div>
                </div>

                <div class="bg-surface-container/50 p-4 rounded-lg border border-outline-variant/20 text-sm text-on-surface-variant space-y-2">
                    <p class="font-medium text-on-surface">ℹ️ Statistics Notes:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Metrics are calculated based on calendar weekdays (Monday to Friday) within the last 30 days.</li>
                        <li>Weekends are automatically excluded from the "Absent" calculation.</li>
                        <li>"Total Hours" includes all completed check-in/out records. For days where only a check-in is logged, hours are computed up to the current moment.</li>
                    </ul>
                </div>
            </div>

            <!-- 30-Day Logs Table -->
            <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 space-y-4">
                <h4 class="text-lg font-semibold text-on-surface">30-Day Attendance Logs</h4>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/30 text-on-surface-variant font-semibold">
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
                                <tr class="border-b border-outline-variant/20 hover:bg-surface-container-high/30 transition duration-150
                                    @if($day['is_weekend']) opacity-60 bg-surface-container-low/20 @endif">
                                    <td class="py-3 px-4 text-on-surface font-medium">
                                        {{ $day['date']->format('M d, Y') }}
                                        @if($day['date']->isToday())
                                            <span class="ml-2 bg-primary/20 text-primary text-[10px] uppercase font-bold px-1.5 py-0.5 rounded">Today</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-on-surface-variant">
                                        {{ $day['day_of_week'] }}
                                    </td>
                                    <td class="py-3 px-4 text-on-surface">
                                        {{ $day['check_in'] ? $day['check_in']->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-on-surface">
                                        {{ $day['check_out'] ? $day['check_out']->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-on-surface">
                                        {{ $day['hours'] ? number_format($day['hours'], 1) . 'h' : '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold capitalize
                                            @if($status === 'present')
                                                bg-primary/20 text-primary
                                            @elseif($status === 'late')
                                                bg-tertiary/20 text-tertiary
                                            @elseif($status === 'weekend')
                                                bg-surface-container-high/55 text-on-surface-variant
                                            @else
                                                bg-error/20 text-error
                                            @endif
                                        ">
                                            {{ $status }}
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
