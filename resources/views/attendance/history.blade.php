<x-ledger-layout>
    <x-slot name="header">
        <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Attendance History</h1>
        <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
            Track check-in history and overall attendance records · Last 30 Days
        </div>
    </x-slot>

    <!-- Top Summary Strip -->
    <div class="grid grid-cols-1 md:grid-cols-3 border border-hairline bg-surface rounded overflow-hidden mb-6">
        <!-- Present -->
        <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
            <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Days Present</span>
            <div class="font-display font-medium text-3xl my-2 text-forest">{{ $present_count }}</div>
            <span class="text-xs text-vellum-muted">Roster check-ins verified</span>
        </div>

        <!-- Absent -->
        <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
            <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Days Absent</span>
            <div class="font-display font-medium text-3xl my-2 text-burgundy">{{ $absent_count }}</div>
            <span class="text-xs text-vellum-muted">Unexcused roster absences</span>
        </div>

        <!-- Late -->
        <div class="p-6 border-r border-hairline last:border-none flex flex-col justify-between">
            <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Days Late</span>
            <div class="font-display font-medium text-3xl my-2 text-cognac">{{ $late_count }}</div>
            <span class="text-xs text-vellum-muted">Arrivals past grace threshold</span>
        </div>
    </div>

    <!-- Ledger Table content inside ledger wrapper -->
    <x-slot name="ledgerHeader">
        <h2>Last 30 Days</h2>
        <div class="meta">attendance log</div>
    </x-slot>

    @forelse ($history as $record)
        @php
            $dateStr = $record->date->format('M d, Y');
            $dayName = $record->date->format('l');
            $checkInStr = $record->check_in_time ? $record->check_in_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
            $checkOutStr = $record->check_out_time ? $record->check_out_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
            
            $durationStr = '';
            if ($record->check_in_time && $record->check_out_time) {
                $hrs = $record->check_in_time->diffInMinutes($record->check_out_time, absolute: true) / 60.0;
                $durationStr = ' · ' . number_format($hrs, 1) . 'h worked';
            }
            
            $desc = '';
            if ($record->status === 'present') {
                $desc = 'Checked in at ' . $checkInStr . ' · Checked out at ' . $checkOutStr . $durationStr;
            } elseif ($record->status === 'late') {
                $desc = 'Checked in late at ' . $checkInStr . ' · ' . $record->late_minutes . 'm past grace' . $durationStr;
            } elseif ($record->status === 'on_leave') {
                $desc = 'Approved leave';
            } elseif ($record->status === 'wfh') {
                $desc = 'Working from home' . $durationStr;
            } else {
                $desc = 'No check-in recorded';
            }
        @endphp
        <div class="ledger-row grid grid-cols-[24px_110px_1fr_120px] items-center py-4 px-2 border-b border-hairline last:border-none hover:bg-brass/[0.04] transition duration-150">
            <span class="seal-indicator {{ $record->status }} w-2 h-2 rounded-full 
                @if($record->status === 'present' || $record->status === 'wfh') bg-forest
                @elseif($record->status === 'late') bg-cognac
                @elseif($record->status === 'on_leave' || $record->status === 'leave') bg-slate
                @else bg-burgundy @endif"></span>
            <span class="row-time font-mono text-[13px] text-vellum">{{ $dateStr }}</span>
            <div class="row-identity flex flex-col gap-0.5">
                <span class="row-name text-[14.0px] font-semibold text-vellum">
                    {{ $dayName }}
                </span>
                <span class="row-desc text-[12px] text-vellum-muted">{{ $desc }}</span>
            </div>
            <div class="text-right">
                <span class="tag {{ $record->status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                    @if($record->status === 'present') bg-forest-bg text-forest border-transparent
                    @elseif($record->status === 'late') bg-cognac-bg text-cognac border-transparent
                    @elseif($record->status === 'on_leave' || $record->status === 'leave') bg-slate-bg text-slate border-transparent
                    @elseif($record->status === 'wfh') bg-forest-bg text-forest border-transparent
                    @else bg-burgundy-bg text-burgundy border-transparent @endif">
                    @if($record->status === 'on_leave') Leave @else {{ str_replace('_', ' ', $record->status) }} @endif
                </span>
            </div>
        </div>
    @empty
        <div class="empty-cta py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[12px]">
            No attendance records found.
        </div>
    @endforelse
</x-ledger-layout>

<div class="mt-6 max-w-[1180px] mx-auto px-11 pb-8">
    <a href="{{ route('employee.dashboard') }}" class="inline-flex items-center text-brass hover:underline font-semibold text-sm">
        ← Back to Dashboard
    </a>
</div>
