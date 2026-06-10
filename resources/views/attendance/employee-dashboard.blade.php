<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface dark:text-on-surface leading-tight">
            {{ __('Employee Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Welcome Card -->
            <div class="glass-panel p-6 rounded-lg border border-primary/10">
                <h3 class="text-2xl font-semibold text-on-surface mb-2">
                    Welcome, {{ $user->name }}!
                </h3>
                <p class="text-on-surface-variant">
                    Employee ID: <span class="font-medium">{{ $user->employee_id }}</span>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Employee Details Card -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30">
                    <h4 class="text-lg font-semibold text-on-surface mb-4">Employee Details</h4>
                    
                    <div class="space-y-3">
                        <div>
                            <span class="text-on-surface-variant text-sm">Email</span>
                            <p class="text-on-surface font-medium">{{ $user->email }}</p>
                        </div>
                        
                        <div>
                            <span class="text-on-surface-variant text-sm">Department</span>
                            <p class="text-on-surface font-medium">
                                {{ $user->department?->name ?? 'Not Assigned' }}
                            </p>
                        </div>
                        
                        <div>
                            <span class="text-on-surface-variant text-sm">Manager</span>
                            <p class="text-on-surface font-medium">
                                {{ $user->manager?->name ?? 'Not Assigned' }}
                            </p>
                        </div>
                        
                        <div>
                            <span class="text-on-surface-variant text-sm">Role</span>
                            <p class="text-on-surface font-medium capitalize">{{ $user->role }}</p>
                        </div>
                        
                        <div>
                            <span class="text-on-surface-variant text-sm">Status</span>
                            <p class="text-on-surface font-medium capitalize">
                                <span class="inline-block px-3 py-1 rounded-full text-sm @if($user->status === 'active') bg-primary/20 text-primary @else bg-error/20 text-error @endif">
                                    {{ $user->status }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Today's Attendance Status Card -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30">
                    <h4 class="text-lg font-semibold text-on-surface mb-4">Today's Attendance</h4>
                    
                    @if ($today_attendance)
                        <div class="space-y-4">
                            <!-- Check-in Status -->
                            <div>
                                <span class="text-on-surface-variant text-sm">Check In</span>
                                <p class="text-on-surface font-medium">
                                    @if ($today_attendance->check_in_time)
                                        {{ $today_attendance->check_in_time->format('h:i A') }}
                                    @else
                                        <span class="text-error">Not checked in</span>
                                    @endif
                                </p>
                            </div>
                            
                            <!-- Check-out Status -->
                            <div>
                                <span class="text-on-surface-variant text-sm">Check Out</span>
                                <p class="text-on-surface font-medium">
                                    @if ($today_attendance->check_out_time)
                                        {{ $today_attendance->check_out_time->format('h:i A') }}
                                    @else
                                        <span class="text-on-surface-variant">Pending</span>
                                    @endif
                                </p>
                            </div>
                            
                            <!-- Hours Worked -->
                            @if ($hours_today)
                                <div>
                                    <span class="text-on-surface-variant text-sm">Hours Worked</span>
                                    <p class="text-on-surface font-medium">{{ number_format($hours_today, 1) }} hours</p>
                                </div>
                            @endif
                            
                            <!-- Status Badge -->
                            <div>
                                <span class="text-on-surface-variant text-sm">Status</span>
                                <p class="text-on-surface font-medium capitalize">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm
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
                                </p>
                            </div>
                        </div>
                    @else
                        <p class="text-on-surface-variant">No attendance record for today yet.</p>
                    @endif
                </div>

            </div>

            <!-- Action Buttons -->
            <div class="glass-panel p-6 rounded-lg border border-primary/10">
                <h4 class="text-lg font-semibold text-on-surface mb-4">Actions</h4>
                
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
                        <div class="flex-1 bg-surface-container-high/50 text-on-surface font-semibold py-3 px-6 rounded-lg text-center">
                            ✓ Checked in and out
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent History Table -->
            <div class="glass-panel p-6 rounded-lg border border-outline-variant/30">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-on-surface">Recent Attendance (Last 7 Days)</h4>
                    <a href="{{ route('attendance.history') }}" class="text-primary hover:text-primary/80 text-sm font-medium">View All →</a>
                </div>
                
                @if ($recent_history->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-outline-variant/30">
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Date</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Check In</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Check Out</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Hours</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recent_history as $record)
                                    <tr class="border-b border-outline-variant/20 hover:bg-surface-container-high/30 transition">
                                        <td class="py-3 px-4 text-on-surface">{{ $record->date->format('M d, Y') }}</td>
                                        <td class="py-3 px-4 text-on-surface">
                                            @if ($record->check_in_time)
                                                {{ $record->check_in_time->format('h:i A') }}
                                            @else
                                                <span class="text-on-surface-variant">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-on-surface">
                                            @if ($record->check_out_time)
                                                {{ $record->check_out_time->format('h:i A') }}
                                            @else
                                                <span class="text-on-surface-variant">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-on-surface">
                                            @if ($record->check_in_time && $record->check_out_time)
                                                {{ number_format($record->check_in_time->diffInHours($record->check_out_time), 1) }}h
                                            @else
                                                <span class="text-on-surface-variant">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium capitalize
                                                @if($record->status === 'present')
                                                    bg-primary/20 text-primary
                                                @elseif($record->status === 'late')
                                                    bg-tertiary/20 text-tertiary
                                                @else
                                                    bg-error/20 text-error
                                                @endif
                                            ">
                                                {{ $record->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-on-surface-variant text-center py-6">No attendance records yet.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
