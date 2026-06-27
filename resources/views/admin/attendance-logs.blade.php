<x-ledger-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center gap-4">
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Attendance Logs</h1>
                <!-- Export CSV Placeholder -->
                <button type="button" disabled class="inline-flex items-center justify-center bg-surface-raised text-vellum-faint font-bold py-2 px-4 rounded text-xs uppercase tracking-wider border border-hairline opacity-50 cursor-not-allowed shadow-sm h-[38px]" title="Export CSV feature coming soon">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
                <button type="button" @click="$dispatch('open-modal', 'bulk-override-modal'); $nextTick(() => { document.getElementById('bulk_override_date').value = '{{ $date }}'; })" class="inline-flex items-center justify-center bg-brass text-vellum font-bold py-2 px-4 rounded text-xs uppercase tracking-wider hover:bg-brass/90 transition duration-150 h-[38px] shadow-sm">
                    Bulk Override
                </button>
            </div>
            <div class="text-[13px] text-vellum-muted tracking-wide">
                Daily audit record system · Active roster registry
            </div>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-forest-bg text-forest border border-forest/30 px-4 py-3 rounded text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 bg-burgundy-bg text-burgundy border border-burgundy/30 px-4 py-3 rounded text-sm font-medium">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
        <div class="meta font-mono text-[11px] text-vellum-faint font-medium">audit view</div>
    </x-slot>

    @php
        $headers = [
            ['label' => 'Employee ID', 'class' => ''],
            ['label' => 'Employee Name', 'class' => ''],
            ['label' => 'Department', 'class' => ''],
            ['label' => 'Check In', 'class' => ''],
            ['label' => 'Check Out', 'class' => ''],
            ['label' => 'Details', 'class' => ''],
            ['label' => 'Classification', 'class' => ''],
            ['label' => 'Status', 'class' => ''],
            ['label' => 'Actions', 'class' => 'text-right']
        ];
    @endphp

    <x-ledger-table :headers="$headers">
        @forelse($employees as $emp)
            @php
                $att = $emp->today_attendance;
                $isSunday = \Carbon\Carbon::parse($date)->isSunday();
                $empStatus = $att ? $att->status : ($isSunday ? 'weekend' : 'absent');
                
                $checkInStr = $att?->check_in_time ? $att->check_in_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                $checkOutStr = $att?->check_out_time ? $att->check_out_time->timezone('Asia/Kolkata')->format('h:i A') : '—';
                
                $durationStr = '';
                if ($att && $att->check_in_time) {
                    $endTime = $att->check_out_time ?? ($date === today()->format('Y-m-d') ? now() : null);
                    $hours = $endTime ? $att->check_in_time->diffInMinutes($endTime, absolute: true) / 60.0 : null;
                    $durationStr = $hours ? number_format($hours, 1) . 'h worked' : '';
                }
                
                $details = '—';
                if ($empStatus === 'present') {
                    $details = $durationStr ?: 'Checked in';
                } elseif ($empStatus === 'late') {
                    $details = $att->late_minutes . 'm past grace' . ($durationStr ? ' · ' . $durationStr : '');
                } elseif ($empStatus === 'on_leave') {
                    $details = 'Approved leave';
                } elseif ($empStatus === 'wfh') {
                    $details = 'Working from home' . ($durationStr ? ' · ' . $durationStr : '');
                } elseif ($empStatus === 'weekend') {
                    $details = 'Weekend · Non-working day';
                } else {
                    $details = 'No check-in recorded · flagged for review';
                }
            @endphp
            <tr class="hover:bg-brass/[0.04] transition duration-150 text-[16px]">
                <!-- Employee ID -->
                <td class="py-4 px-4 font-mono text-[16px] text-brass select-all font-medium">
                    <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="hover:underline">
                        {{ $emp->employee_id }}
                    </a>
                </td>

                <!-- Employee Name -->
                <td class="py-4 px-4 text-[18px] font-bold text-vellum">
                    <a href="{{ route('admin.attendance.employee.show', $emp) }}" class="hover:text-brass transition-colors">
                        {{ $emp->name }}
                    </a>
                </td>

                <!-- Department -->
                <td class="py-4 px-4 text-[16px] text-vellum font-medium">
                    {{ $emp->department?->name ?? 'None' }}
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

                <!-- Classification -->
                <td class="py-4 px-4 text-[16px] font-medium text-vellum-muted">
                    @if($att && $att->classification === 'half_day')
                        <span class="text-brass font-semibold">Half Day</span>
                        @if($att->is_overridden)
                            <span class="text-[10px] text-vellum-faint block">Overridden</span>
                        @elseif($att->automatic_classification_reason)
                            <span class="text-[10px] text-vellum-faint block">
                                {{ $att->automatic_classification_reason === 'late_arrival' ? 'Late Arrival' : 'Hours' }}
                            </span>
                        @endif
                    @elseif($att && $att->classification === 'full_day')
                        <span>Full Day</span>
                    @else
                        <span>—</span>
                    @endif
                </td>

                <!-- Status -->
                <td class="py-4 px-4">
                    <span class="tag {{ $empStatus }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border
                        @if($empStatus === 'present') bg-forest-bg text-forest border-transparent
                        @elseif($empStatus === 'late') bg-cognac-bg text-cognac border-transparent
                        @elseif($empStatus === 'on_leave' || $empStatus === 'leave') bg-slate-bg text-slate border-transparent
                        @elseif($empStatus === 'wfh') bg-forest-bg text-forest border-transparent
                        @elseif($empStatus === 'weekend') bg-transparent text-vellum-muted border-hairline-strong
                        @else bg-burgundy-bg text-burgundy border-transparent @endif">
                        @if($empStatus === 'on_leave') Leave @elseif($empStatus === 'weekend') Weekend @else {{ str_replace('_', ' ', $empStatus) }} @endif
                    </span>
                    @if($att && $att->is_overridden)
                        <span class="text-[9px] text-brass uppercase font-bold block mt-1 font-mono">Overridden</span>
                    @endif
                </td>

                <!-- Actions -->
                <td class="py-4 px-4 text-right whitespace-nowrap">
                    @if($empStatus !== 'weekend')
                        <button type="button" 
                                @click="$dispatch('open-modal', 'override-modal'); 
                                        $nextTick(() => { 
                                            document.getElementById('override_user_id').value = '{{ $emp->id }}'; 
                                            document.getElementById('override_date').value = '{{ $date }}'; 
                                            document.getElementById('override_employee_name').innerText = '{{ addslashes($emp->name) }}';
                                            document.getElementById('override_status').value = '{{ $att ? $att->status : 'absent' }}';
                                            document.getElementById('override_classification').value = '{{ $att ? $att->classification : 'full_day' }}';
                                            document.getElementById('override_reason').value = '{{ $att ? addslashes($att->override_reason) : '' }}';
                                        })" 
                                class="inline-flex items-center justify-center bg-brass/10 hover:bg-brass/25 text-brass font-bold py-1.5 px-3 rounded text-[11px] uppercase tracking-wider transition duration-150">
                            Override
                        </button>
                    @else
                        <span class="text-vellum-faint font-mono text-[11px]">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                    No active employees found matching the filters.
                </td>
            </tr>
        @endforelse
    </x-ledger-table>

    <!-- Individual Override Modal -->
    <x-modal name="override-modal" maxWidth="lg">
        <form method="POST" action="{{ route('admin.attendance.override.store') }}" class="p-6">
            @csrf
            <h3 class="text-lg font-medium text-vellum font-display mb-4">
                Override Attendance for <span id="override_employee_name" class="text-brass"></span>
            </h3>

            <input type="hidden" name="user_id" id="override_user_id">
            <input type="hidden" name="date" id="override_date">

            <div class="space-y-4">
                <div>
                    <x-input-label for="override_status" value="Attendance Status" />
                    <select name="status" id="override_status" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="absent">Absent</option>
                        <option value="on_leave">On Leave</option>
                        <option value="wfh">WFH</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="override_classification" value="Classification" />
                    <select name="classification" id="override_classification" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                        <option value="full_day">Full Day</option>
                        <option value="half_day">Half Day</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="override_reason" value="Override Reason" />
                    <textarea name="override_reason" id="override_reason" rows="3" required minlength="5" placeholder="Minimum 5 characters describing reason for audit log..." class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>
                <x-primary-button type="submit">
                    Apply Override
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Bulk Override Modal -->
    <x-modal name="bulk-override-modal" maxWidth="lg">
        <form method="POST" action="{{ route('admin.attendance.override.store') }}" class="p-6">
            @csrf
            <h3 class="text-lg font-medium text-vellum font-display mb-4">
                Bulk Attendance Override
            </h3>

            <div class="space-y-4">
                <div>
                    <x-input-label for="bulk_override_date" value="Date" />
                    <input type="date" name="date" id="bulk_override_date" required class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                </div>

                <div>
                    <x-input-label for="bulk_user_ids" value="Select Employees" />
                    <select name="user_ids[]" id="bulk_user_ids" multiple required size="6" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_id }})</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-vellum-muted mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple employees.</p>
                </div>

                <div>
                    <x-input-label for="bulk_status" value="Attendance Status" />
                    <select name="status" id="bulk_status" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="absent">Absent</option>
                        <option value="on_leave">On Leave</option>
                        <option value="wfh">WFH</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="bulk_classification" value="Classification" />
                    <select name="classification" id="bulk_classification" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                        <option value="full_day">Full Day</option>
                        <option value="half_day">Half Day</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="bulk_override_reason" value="Override Reason" />
                    <textarea name="override_reason" id="bulk_override_reason" rows="3" required minlength="5" placeholder="Minimum 5 characters describing reason for audit log..." class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>
                <x-primary-button type="submit">
                    Apply Bulk Override
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-ledger-layout>
