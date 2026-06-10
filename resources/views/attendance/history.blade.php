<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface dark:text-on-surface leading-tight">
            {{ __('Attendance History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                
                <!-- Present Count -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-on-surface-variant text-sm">Days Present</p>
                            <p class="text-3xl font-bold text-primary mt-2">{{ $present_count }}</p>
                        </div>
                        <div class="text-primary/20 text-4xl">✓</div>
                    </div>
                </div>

                <!-- Absent Count -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-on-surface-variant text-sm">Days Absent</p>
                            <p class="text-3xl font-bold text-error mt-2">{{ $absent_count }}</p>
                        </div>
                        <div class="text-error/20 text-4xl">✗</div>
                    </div>
                </div>

                <!-- Late Count -->
                <div class="glass-panel p-6 rounded-lg border border-outline-variant/30">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-on-surface-variant text-sm">Days Late</p>
                            <p class="text-3xl font-bold text-tertiary mt-2">{{ $late_count }}</p>
                        </div>
                        <div class="text-tertiary/20 text-4xl">⏱</div>
                    </div>
                </div>

            </div>

            <!-- Attendance Table -->
            <div class="glass-panel p-6 rounded-lg border border-outline-variant/30">
                <h3 class="text-lg font-semibold text-on-surface mb-4">Last 30 Days</h3>
                
                @if ($history->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-outline-variant/30">
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Date</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Day</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Check In</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Check Out</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Hours</th>
                                    <th class="text-left py-3 px-4 text-on-surface-variant font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($history as $record)
                                    <tr class="border-b border-outline-variant/20 hover:bg-surface-container-high/30 transition">
                                        <td class="py-4 px-4 text-on-surface font-medium">
                                            {{ $record->date->format('M d, Y') }}
                                        </td>
                                        <td class="py-4 px-4 text-on-surface-variant">
                                            {{ $record->date->format('l') }}
                                        </td>
                                        <td class="py-4 px-4 text-on-surface">
                                            @if ($record->check_in_time)
                                                <span class="font-medium">{{ $record->check_in_time->format('h:i A') }}</span>
                                            @else
                                                <span class="text-on-surface-variant italic">-</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-on-surface">
                                            @if ($record->check_out_time)
                                                <span class="font-medium">{{ $record->check_out_time->format('h:i A') }}</span>
                                            @else
                                                <span class="text-on-surface-variant italic">-</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-on-surface">
                                            @if ($record->check_in_time && $record->check_out_time)
                                                <span class="font-medium">{{ number_format($record->check_in_time->diffInHours($record->check_out_time), 1) }}h</span>
                                            @else
                                                <span class="text-on-surface-variant italic">-</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold capitalize
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
                    <div class="text-center py-12">
                        <p class="text-on-surface-variant text-lg">No attendance records found.</p>
                    </div>
                @endif
            </div>

            <!-- Back Button -->
            <div class="mt-6">
                <a href="{{ route('employee.dashboard') }}" class="inline-block text-primary hover:text-primary/80 font-medium">
                    ← Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
