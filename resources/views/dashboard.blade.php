<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface dark:text-on-surface leading-tight">
            {{ __('Manager Attendance Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        <!-- Dashboard Filters -->
        <div class="glass-panel p-6 rounded-lg border border-primary/10">
            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <!-- Date Filter -->
                <div>
                    <label for="date" class="block text-sm font-medium text-on-surface-variant mb-1">Date</label>
                    <input type="date" name="date" id="date" value="{{ $date }}"
                           class="w-full bg-surface-container border border-outline-variant/30 rounded-md text-on-surface px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none">
                </div>

                <!-- Department Filter -->
                <div>
                    <label for="department_id" class="block text-sm font-medium text-on-surface-variant mb-1">Department</label>
                    <select name="department_id" id="department_id"
                            class="w-full bg-surface-container border border-outline-variant/30 rounded-md text-on-surface px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none">
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
                    <label for="search" class="block text-sm font-medium text-on-surface-variant mb-1">Search Employee</label>
                    <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Search by name..."
                           class="w-full bg-surface-container border border-outline-variant/30 rounded-md text-on-surface px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary focus:outline-none">
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary/95 text-on-primary font-semibold py-2 px-4 rounded-md transition duration-200 shadow-md">
                        Filter
                    </button>
                    <a href="{{ route('dashboard') }}" class="bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold py-2 px-4 rounded-md transition duration-200 text-center border border-outline-variant/30">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Admin Company Metrics Grid -->
        @if(auth()->user()->role === 'admin' && isset($companyMetrics))
            <div class="space-y-2">
                <h4 class="text-lg font-semibold text-on-surface">Workforce Metrics</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- People in Company -->
                    <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex items-center justify-between">
                        <div>
                            <span class="text-on-surface-variant text-sm font-medium">People in Company</span>
                            <h3 class="text-3xl font-bold text-on-surface mt-1">{{ $companyMetrics['people_in_company'] }}</h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Admins count -->
                    <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex items-center justify-between">
                        <div>
                            <span class="text-on-surface-variant text-sm font-medium">Admins</span>
                            <h3 class="text-3xl font-bold text-on-surface mt-1">{{ $companyMetrics['admins'] }}</h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center text-purple-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Managers count -->
                    <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex items-center justify-between">
                        <div>
                            <span class="text-on-surface-variant text-sm font-medium">Managers</span>
                            <h3 class="text-3xl font-bold text-on-surface mt-1">{{ $companyMetrics['managers'] }}</h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center text-blue-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.048M9 9h6m-8 4h16" />
                            </svg>
                        </div>
                    </div>

                    <!-- Employees count -->
                    <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex items-center justify-between">
                        <div>
                            <span class="text-on-surface-variant text-sm font-medium">Employees</span>
                            <h3 class="text-3xl font-bold text-on-surface mt-1">{{ $companyMetrics['employees'] }}</h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center text-green-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Overview Stats Grid -->
        <div class="space-y-2">
            <h4 class="text-lg font-semibold text-on-surface">Today's Attendance Status</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Users Monitored -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex items-center justify-between">
                    <div>
                        <span class="text-on-surface-variant text-sm font-medium">
                            @if(auth()->user()->role === 'admin')
                                Total Active Users
                            @else
                                Assigned Employees
                            @endif
                        </span>
                        <h3 class="text-3xl font-bold text-on-surface mt-1">{{ $stats['total'] }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Present Today -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex items-center justify-between">
                    <div>
                        <span class="text-on-surface-variant text-sm font-medium">Present Today</span>
                        <h3 class="text-3xl font-bold text-secondary mt-1">{{ $stats['present'] }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center text-secondary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Late Today -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex items-center justify-between">
                    <div>
                        <span class="text-on-surface-variant text-sm font-medium">Late Today</span>
                        <h3 class="text-3xl font-bold text-tertiary mt-1">{{ $stats['late'] }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-tertiary/10 flex items-center justify-center text-tertiary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Absent Today -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 flex items-center justify-between">
                    <div>
                        <span class="text-on-surface-variant text-sm font-medium">Absent Today</span>
                        <h3 class="text-3xl font-bold text-error mt-1">{{ $stats['absent'] }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center text-error">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Employee Attendance Table Section (Col Span 2) -->
            <div class="lg:col-span-2 glass-panel p-6 rounded-lg border border-outline-variant/30 space-y-4">
                <h4 class="text-lg font-semibold text-on-surface">Workforce Attendance Details</h4>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/30 text-on-surface-variant font-semibold">
                                <th class="py-3 px-4">Employee ID</th>
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4">Check In</th>
                                <th class="py-3 px-4">Check Out</th>
                                <th class="py-3 px-4">Hours</th>
                                <th class="py-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $emp)
                                @php
                                    $att = $emp->today_attendance;
                                    $status = $att ? $att->status : 'absent';
                                @endphp
                                <tr class="border-b border-outline-variant/20 hover:bg-surface-container-high/30 transition duration-150">
                                    <!-- Employee ID (Clickable) -->
                                    <td class="py-3 px-4">
                                        <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="text-primary hover:underline font-mono font-medium">
                                            {{ $emp->employee_id }}
                                        </a>
                                    </td>
                                    <!-- Employee Name (Clickable) -->
                                    <td class="py-3 px-4">
                                        <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="text-on-surface hover:text-primary transition font-medium">
                                            {{ $emp->name }}
                                        </a>
                                    </td>
                                    <!-- Role badge -->
                                    <td class="py-3 px-4 font-medium capitalize">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold
                                            @if($emp->role === 'admin')
                                                bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                                            @elseif($emp->role === 'manager')
                                                bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                            @else
                                                bg-gray-100 text-gray-800 dark:bg-gray-700/30 dark:text-gray-300
                                            @endif
                                        ">
                                            {{ $emp->role }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-on-surface-variant">
                                        {{ $emp->department?->name ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-on-surface">
                                        {{ $att?->check_in_time ? $att->check_in_time->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-on-surface">
                                        {{ $att?->check_out_time ? $att->check_out_time->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-on-surface">
                                        @if ($att && $att->check_in_time)
                                            @php
                                                $endTime = $att->check_out_time ?? ($date === today()->format('Y-m-d') ? now() : null);
                                                $hours = $endTime ? $att->check_in_time->diffInMinutes($endTime, absolute: true) / 60.0 : null;
                                            @endphp
                                            {{ $hours ? number_format($hours, 1) . 'h' : '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold capitalize
                                            @if($status === 'present')
                                                bg-primary/20 text-primary
                                            @elseif($status === 'late')
                                                bg-tertiary/20 text-tertiary
                                            @else
                                                bg-error/20 text-error
                                            @endif
                                        ">
                                            {{ $status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 px-4 text-center text-on-surface-variant">
                                        No active workforce members found matching the filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Attendance Activity Feed Widget (Col Span 1) -->
            <div class="glass-panel p-6 rounded-lg border border-outline-variant/30 space-y-4 flex flex-col justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-on-surface mb-4">Recent Attendance Activity</h4>
                    
                    <div class="space-y-4">
                        @forelse($recentActivity as $activity)
                            <div class="flex items-start gap-3 border-l-2 border-outline-variant/50 pl-4 relative">
                                <!-- Marker Bullet -->
                                <div class="absolute w-2 h-2 rounded-full left-[-5px] top-1.5 
                                    @if($activity['action'] === 'Checked In') bg-secondary shadow-[0_0_8px_#00ffcc] @else bg-primary shadow-[0_0_8px_#ff2d78] @endif">
                                </div>
                                <div class="flex-1 space-y-1">
                                    <p class="text-sm text-on-surface font-medium leading-tight">
                                        <span class="font-semibold">{{ $activity['employee_name'] }}</span> 
                                        <span class="text-on-surface-variant font-normal">
                                            @if($activity['action'] === 'Checked In') checked in @else checked out @endif
                                        </span>
                                    </p>
                                    <p class="text-xs text-on-surface-variant font-mono">
                                        ID: {{ $activity['employee_id'] }} | {{ $activity['timestamp'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-on-surface-variant text-center py-6">
                                No check-in/out activity recorded yet today.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
