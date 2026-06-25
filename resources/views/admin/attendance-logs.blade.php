<x-ledger-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Attendance Logs</h1>
                <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
                    Daily audit record system · Active roster registry
                </div>
            </div>
            <!-- Export CSV Placeholder -->
            <button type="button" disabled class="inline-flex items-center bg-surface-raised text-vellum-faint font-semibold py-2 px-4 rounded text-xs uppercase tracking-wider border border-hairline opacity-50 cursor-not-allowed shadow-sm" title="Export CSV feature coming soon">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </button>
        </div>
    </x-slot>

    <x-slot name="filters">
        <form method="GET" action="{{ route('admin.attendance.logs') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <!-- Date Filter -->
            <div>
                <x-input-label for="date" value="Date" />
                <input type="date" name="date" id="date" value="{{ $date }}"
                       class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
            </div>

            <!-- Department Filter -->
            <div>
                <x-input-label for="department_id" value="Department" />
                <select name="department_id" id="department_id"
                        class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
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
                <x-input-label for="status" value="Status" />
                <select name="status" id="status"
                        class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
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
                <x-input-label for="search" value="Search Employee" />
                <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Search by name or ID..."
                       class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-2">
                <x-primary-button type="submit" class="flex-1 justify-center h-[38px] text-xs">
                    Filter
                </x-primary-button>
                <x-secondary-button href="{{ route('admin.attendance.logs') }}" class="flex-1 justify-center h-[38px] text-xs" onclick="window.location.href='{{ route('admin.attendance.logs') }}'">
                    Clear
                </x-secondary-button>
            </div>
        </form>
    </x-slot>

    <x-slot name="ledgerHeader">
        <h2>Daily Attendance Log History</h2>
        <div class="meta">audit view</div>
    </x-slot>

    @forelse($employees as $emp)
        @php
            $att = $emp->today_attendance;
            $isSunday = \Carbon\Carbon::parse($date)->isSunday();
            $empStatus = $att ? $att->status : ($isSunday ? 'weekend' : 'absent');
            
            $checkInStr = $att?->check_in_time ? $att->check_in_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
            $checkOutStr = $att?->check_out_time ? $att->check_out_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
            
            $hoursWorkedStr = '';
            if ($att && $att->check_in_time) {
                $endTime = $att->check_out_time ?? ($date === today()->format('Y-m-d') ? now() : null);
                $hours = $endTime ? $att->check_in_time->diffInMinutes($endTime, absolute: true) / 60.0 : null;
                $hoursWorkedStr = $hours ? ' · ' . number_format($hours, 1) . 'h worked' : '';
            }
            
            $desc = '';
            if ($empStatus === 'present') {
                $desc = 'Checked in at ' . $checkInStr . ' · Checked out at ' . $checkOutStr . $hoursWorkedStr;
            } elseif ($empStatus === 'late') {
                $desc = 'Checked in late at ' . $checkInStr . ' · ' . $att->late_minutes . 'm past grace' . $hoursWorkedStr;
            } elseif ($empStatus === 'on_leave') {
                $desc = 'Approved leave';
            } elseif ($empStatus === 'wfh') {
                $desc = 'Working from home' . $hoursWorkedStr;
            } elseif ($empStatus === 'weekend') {
                $desc = 'Weekend · Non-working day';
            } else {
                $desc = 'No check-in recorded · flagged for review';
            }
        @endphp
        <div class="ledger-row grid grid-cols-[24px_110px_1fr_120px] items-center py-4 px-2 border-b border-hairline last:border-none hover:bg-brass/[0.04] transition duration-150">
            <span class="seal-indicator {{ $empStatus }} w-2 h-2 rounded-full 
                @if($empStatus === 'present' || $empStatus === 'wfh') bg-forest
                @elseif($empStatus === 'late') bg-cognac
                @elseif($empStatus === 'on_leave' || $empStatus === 'leave') bg-slate
                @elseif($empStatus === 'weekend') bg-hairline-strong
                @else bg-burgundy @endif"></span>
            <span class="row-time font-mono text-[13px] text-vellum">
                <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="text-brass hover:underline font-mono">
                    {{ $emp->employee_id }}
                </a>
            </span>
            <div class="row-identity flex flex-col gap-0.5">
                <span class="row-name text-[14.5px] font-semibold text-vellum">
                    <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="hover:text-brass transition-colors">{{ $emp->name }}</a>
                    <span class="text-[11.5px] text-vellum-faint font-normal font-sans ml-2">({{ $emp->department?->name ?? 'No Department' }})</span>
                </span>
                <span class="row-desc text-[12px] text-vellum-muted">{{ $desc }}</span>
            </div>
            <div class="text-right">
                <span class="tag {{ $empStatus }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                    @if($empStatus === 'present') bg-forest-bg text-forest border-transparent
                    @elseif($empStatus === 'late') bg-cognac-bg text-cognac border-transparent
                    @elseif($empStatus === 'on_leave' || $empStatus === 'leave') bg-slate-bg text-slate border-transparent
                    @elseif($empStatus === 'wfh') bg-forest-bg text-forest border-transparent
                    @elseif($empStatus === 'weekend') bg-transparent text-vellum-muted border-hairline-strong
                    @else bg-burgundy-bg text-burgundy border-transparent @endif">
                    @if($empStatus === 'on_leave') Leave @elseif($empStatus === 'weekend') Weekend @else {{ str_replace('_', ' ', $empStatus) }} @endif
                </span>
            </div>
        </div>
    @empty
        <div class="empty-cta py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[12px]">
            No active employees found matching the filters.
        </div>
    @endforelse
</x-ledger-layout>
