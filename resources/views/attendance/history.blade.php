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
        <div class="meta font-mono text-[11px] text-vellum-faint font-medium">attendance log</div>
    </x-slot>

    @php
        $headers = [
            ['label' => 'Date', 'class' => ''],
            ['label' => 'Day', 'class' => ''],
            ['label' => 'Check In', 'class' => ''],
            ['label' => 'Check Out', 'class' => ''],
            ['label' => 'Details', 'class' => ''],
            ['label' => 'Status', 'class' => 'text-right']
        ];
    @endphp

    <x-ledger-table :headers="$headers">
        @forelse ($history as $record)
            @php
                $dateStr = $record->date->format('M d, Y');
                $dayName = $record->date->format('l');
                $checkInStr = $record->check_in_time ? $record->check_in_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                $checkOutStr = $record->check_out_time ? $record->check_out_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                
                $durationStr = '';
                if ($record->check_in_time && $record->check_out_time) {
                    $hrs = $record->check_in_time->diffInMinutes($record->check_out_time, absolute: true) / 60.0;
                    $durationStr = number_format($hrs, 1) . 'h worked';
                }
                
                if ($record->status === 'present') {
                    $details = $durationStr ?: 'Checked in';
                } elseif ($record->status === 'late') {
                    $details = $record->late_minutes . 'm past grace' . ($durationStr ? ' · ' . $durationStr : '');
                } elseif ($record->status === 'on_leave' || $record->status === 'leave') {
                    $details = 'Approved leave';
                } elseif ($record->status === 'paid_leave') {
                    $details = 'Approved paid leave';
                } elseif ($record->status === 'unpaid_leave') {
                    $details = 'Approved unpaid leave';
                } elseif ($record->status === 'weekly_off') {
                    $details = 'Weekly Off · Non-working day';
                } elseif ($record->status === 'wfh') {
                    $details = 'Working from home' . ($durationStr ? ' · ' . $durationStr : '');
                } elseif ($record->status === 'absent') {
                    $details = 'No check-in recorded';
                }
            @endphp
            <tr class="hover:bg-brass/[0.04] transition duration-150 text-[16px]">
                <!-- Date -->
                <td class="py-4 px-4 font-mono text-[16px] text-brass select-all font-medium">
                    {{ $dateStr }}
                </td>

                <!-- Day -->
                <td class="py-4 px-4 text-[18px] font-bold text-vellum">
                    {{ $dayName }}
                </td>

                <!-- Check In -->
                <td class="py-4 px-4 text-[16px] text-vellum-muted font-mono">
                    {{ $checkInStr }}
                </td>

                <!-- Check Out -->
                <td class="py-4 px-4 text-[16px] text-vellum-muted font-mono">
                    {{ $checkOutStr }}
                </td>

                <!-- Details -->
                <td class="py-4 px-4 text-[16px] text-vellum-muted">
                    {{ $details }}
                </td>

                <!-- Status -->
                <td class="py-4 px-4 text-right">
                    @php
                        $displayStatus = \App\Services\AttendanceStateRegistry::getDisplayStatus($record->status);
                        $details = \App\Services\AttendanceStateRegistry::getStates()[$displayStatus] ?? null;
                        $style = $details ? "background-color: {$details['bg_color']}; color: {$details['text_color']}; border-color: transparent;" : "";
                        $label = \App\Services\AttendanceStateRegistry::getLabel($record->status);
                    @endphp
                    <span class="tag {{ $record->status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border" style="{{ $style }}">
                        {{ $label }}
                    </span>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                    No attendance records found.
                </td>
            </tr>
        @endforelse
    </x-ledger-table>
</x-ledger-layout>

<div class="mt-6 max-w-[1180px] mx-auto px-11 pb-8">
    <a href="{{ route('employee.dashboard') }}" class="inline-flex items-center text-brass hover:underline font-semibold text-sm">
        ← Back to Dashboard
    </a>
</div>
