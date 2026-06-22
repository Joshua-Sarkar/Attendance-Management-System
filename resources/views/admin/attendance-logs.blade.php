<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">Attendance Logs</h1>
                <div class="text-[12.5px] text-vellum-faint mt-1.5 tracking-wide">
                    Daily audit record system
                </div>
            </div>
            <!-- Export CSV Placeholder -->
            <button type="button" disabled class="inline-flex items-center bg-surface-raised text-vellum-faint font-semibold py-2 px-4 rounded-md text-sm border border-hairline opacity-50 cursor-not-allowed shadow-sm" title="Export CSV feature coming soon">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </button>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <!-- Logs Filters Panel -->
        <div class="panel">
            <form method="GET" action="{{ route('admin.attendance.logs') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <!-- Date Filter -->
                <div>
                    <label for="date" class="block text-xs font-medium text-vellum-muted uppercase tracking-wider mb-1.5">Date</label>
                    <input type="date" name="date" id="date" value="{{ $date }}"
                           class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                </div>

                <!-- Department Filter -->
                <div>
                    <label for="department_id" class="block text-xs font-medium text-vellum-muted uppercase tracking-wider mb-1.5">Department</label>
                    <select name="department_id" id="department_id"
                            class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-xs font-medium text-vellum-muted uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" id="status"
                            class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                        <option value="">All Statuses</option>
                        <option value="present" {{ $status === 'present' ? 'selected' : '' }}>Present</option>
                        <option value="late" {{ $status === 'late' ? 'selected' : '' }}>Late</option>
                        <option value="absent" {{ $status === 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="on_leave" {{ $status === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                        <option value="wfh" {{ $status === 'wfh' ? 'selected' : '' }}>WFH</option>
                    </select>
                </div>

                <!-- Search Filter (Name or ID) -->
                <div>
                    <label for="search" class="block text-xs font-medium text-vellum-muted uppercase tracking-wider mb-1.5">Search Employee</label>
                    <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Search by name or ID..."
                           class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-brass hover:bg-brass/90 text-canvas font-semibold py-2 px-4 rounded-md transition duration-200 text-sm shadow-md">
                        Filter
                    </button>
                    <a href="{{ route('admin.attendance.logs') }}" class="bg-surface-raised hover:bg-surface-raised/80 text-vellum font-semibold py-2 px-4 rounded-md transition duration-200 text-center border border-hairline text-sm">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Attendance Logs Table Card -->
        <div class="panel space-y-4">
            <div class="panel-head flex items-center justify-between mb-4.5">
                <h2 class="font-display font-medium text-[16px]">Daily Attendance Log History</h2>
                <div class="meta font-mono text-[11px] text-vellum-faint">audit view</div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-hairline text-vellum-muted font-semibold">
                            <th class="py-3 px-4">Employee ID</th>
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Department</th>
                            <th class="py-3 px-4">Check In</th>
                            <th class="py-3 px-4">Check Out</th>
                            <th class="py-3 px-4">Hours Worked</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $att = $emp->today_attendance;
                                $isSunday = \Carbon\Carbon::parse($date)->isSunday();
                                $empStatus = $att ? $att->status : ($isSunday ? 'weekend' : 'absent');
                            @endphp
                            <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150">
                                <!-- Employee ID (Clickable) -->
                                <td class="py-3 px-4">
                                    <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="text-brass hover:underline font-mono font-medium">
                                        {{ $emp->employee_id }}
                                    </a>
                                </td>
                                <!-- Employee Name (Clickable) -->
                                <td class="py-3 px-4">
                                    <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="text-vellum hover:text-brass transition font-medium">
                                        {{ $emp->name }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-vellum-muted">
                                    {{ $emp->department?->name ?? 'N/A' }}
                                </td>
                                <td class="py-3 px-4 text-vellum">
                                    {{ $att?->check_in_time ? $att->check_in_time->timezone('Asia/Kolkata')->format('h:i A') : '-' }}
                                </td>
                                <td class="py-3 px-4 text-vellum">
                                    {{ $att?->check_out_time ? $att->check_out_time->timezone('Asia/Kolkata')->format('h:i A') : '-' }}
                                </td>
                                <td class="py-3 px-4 text-vellum font-mono">
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
                                    @if($empStatus === 'weekend')
                                        <span class="tag text-vellum-faint border border-hairline uppercase">Weekend</span>
                                    @else
                                        <span class="tag {{ $empStatus }}">
                                            @if($empStatus === 'on_leave') On Leave @else {{ str_replace('_', ' ', $empStatus) }} @endif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="inline-flex items-center text-xs text-brass hover:underline font-semibold gap-1">
                                        View Details
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 px-4 text-center text-vellum-faint border border-dashed border-hairline-strong rounded-lg mt-1 text-[12px]">
                                    No active employees found matching the filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
