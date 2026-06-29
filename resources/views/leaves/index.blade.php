<x-ledger-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center gap-4">
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Leave Management</h1>
                <x-primary-button onclick="window.location.href='{{ route('leaves.create') }}'">
                    + Apply for Leave
                </x-primary-button>
            </div>
            <div class="text-[13px] text-vellum-muted tracking-wide">
                Submit and review organization leave requests
            </div>
        </div>
    </x-slot>

    <!-- Session Notifications -->
    @if(session('success'))
        <div class="rounded bg-forest-bg border border-hairline text-forest px-4 py-3 text-sm mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded bg-burgundy-bg border border-hairline text-burgundy px-4 py-3 text-sm mb-6">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded bg-burgundy-bg border border-hairline text-burgundy px-4 py-3 text-sm mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="{ activeTab: 'my-applications' }" class="space-y-6">
        <!-- Tab Navigation (Only visible for Manager/Admin since employee only has my-applications) -->
        @if(auth()->user()->role !== 'employee')
            <div class="border-b border-hairline flex gap-6 pb-0.5">
                <button @click="activeTab = 'my-applications'" 
                        :class="activeTab === 'my-applications' ? 'border-brass text-brass font-medium' : 'border-transparent text-vellum-muted hover:text-vellum'"
                        class="pb-2.5 border-b-2 font-display text-[15px] font-semibold transition focus:outline-none">
                    My Applications
                </button>
                <button @click="activeTab = 'team-approvals'" 
                        :class="activeTab === 'team-approvals' ? 'border-brass text-brass font-medium' : 'border-transparent text-vellum-muted hover:text-vellum'"
                        class="pb-2.5 border-b-2 font-display text-[15px] font-semibold transition focus:outline-none">
                    Team Approvals ({{ $pendingQueue->count() }})
                </button>
                <button @click="activeTab = 'full-history'" 
                        :class="activeTab === 'full-history' ? 'border-brass text-brass font-medium' : 'border-transparent text-vellum-muted hover:text-vellum'"
                        class="pb-2.5 border-b-2 font-display text-[15px] font-semibold transition focus:outline-none">
                    Decision History
                </button>
            </div>
        @endif

        <!-- TAB 1: My Leave Applications -->
        <div x-show="activeTab === 'my-applications'" class="space-y-6" x-transition>
            <!-- Leave Summary Stats (Briefing Strip Layout) -->
            <div class="space-y-3">
                <h4 class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Approved Leave Summary (Current Year)</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 border border-hairline bg-surface rounded overflow-hidden">
                    <div class="p-5 border-r border-hairline last:border-none flex flex-col justify-between">
                        <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Planned Leave</span>
                        <div class="font-display font-medium text-2xl my-2 text-vellum">{{ $stats['planned'] }} <span class="text-sm font-sans font-normal text-vellum-muted">days</span></div>
                        <span class="text-[11px] text-vellum-muted">Pre-approved roster days</span>
                    </div>
                    <div class="p-5 border-r border-hairline last:border-none flex flex-col justify-between">
                        <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Unplanned Leave</span>
                        <div class="font-display font-medium text-2xl my-2 text-vellum">{{ $stats['unplanned'] }} <span class="text-sm font-sans font-normal text-vellum-muted">days</span></div>
                        <span class="text-[11px] text-vellum-muted">Emergency absences</span>
                    </div>
                    <div class="p-5 border-r border-hairline last:border-none flex flex-col justify-between">
                        <span class="text-[10.5px] font-semibold text-vellum-faint uppercase tracking-wider">Birthday Leave</span>
                        <div class="font-display font-medium text-2xl my-2 text-vellum">{{ $stats['complimentary'] }} <span class="text-sm font-sans font-normal text-vellum-muted">days</span></div>
                        <span class="text-[11px] text-vellum-muted">Complimentary credit</span>
                    </div>
                    <div class="p-5 bg-brass/[0.04] last:border-none flex flex-col justify-between">
                        <span class="text-[10.5px] font-semibold text-brass uppercase tracking-wider">Total Approved</span>
                        <div class="font-display font-medium text-2xl my-2 text-brass">{{ $stats['total_approved'] }} <span class="text-sm font-sans font-normal text-brass-bright">days</span></div>
                        <span class="text-[11px] text-brass font-medium">Aggregate approved</span>
                    </div>
                </div>
            </div>

            <!-- Personal Leave Request History Ledger -->
            <div class="panel">
                <div class="panel-head mb-4 border-b border-hairline pb-2">
                    <h2>Your Leave Applications</h2>
                    <div class="meta">applications history</div>
                </div>

                @php
                    $myApplicationsHeaders = [
                        ['label' => 'Status', 'class' => ''],
                        ['label' => 'Date Range', 'class' => ''],
                        ['label' => 'Leave Type', 'class' => ''],
                        ['label' => 'Reason / Details', 'class' => ''],
                        ['label' => 'Total Days', 'class' => ''],
                        ['label' => 'Actions', 'class' => 'text-right']
                    ];
                @endphp

                <x-ledger-table :headers="$myApplicationsHeaders">
                    @forelse($myLeaves as $req)
                        @php
                            $status = $req->status;
                            $dateRangeStr = $req->start_date->format('M d, Y') . ' – ' . $req->end_date->format('M d, Y');
                            $resolvedType = $req->leave_type;
                            if (!$resolvedType && $req->leave_credit_id) {
                                $resolvedType = 'complimentary';
                            }
                            if (in_array($resolvedType, ['planned', 'casual_leave', 'paid_leave'])) {
                                $typeStr = 'Planned Leave';
                            } elseif (in_array($resolvedType, ['unplanned', 'sick_leave', 'emergency_leave', 'unpaid_leave'])) {
                                $typeStr = 'Unplanned Leave';
                            } elseif ($resolvedType === 'complimentary') {
                                $typeStr = 'Birthday Leave';
                            } elseif ($resolvedType === 'work_from_home') {
                                $typeStr = 'Work From Home';
                            } else {
                                $typeStr = 'Planned Leave';
                            }
                            
                            $feedback = '';
                            if ($status === 'approved' && $req->notes) {
                                $feedback = ' · Note: "' . $req->notes . '"';
                            } elseif ($status === 'rejected' && $req->rejection_reason) {
                                $feedback = ' · Rejection: "' . $req->rejection_reason . '"';
                            }
                            
                            $desc = ucfirst($typeStr) . ' · ' . $req->reason . $feedback;
                        @endphp
                        <tr class="hover:bg-brass/[0.04] transition duration-150 text-[16px]">
                            <!-- Status -->
                            <td class="py-4 px-4">
                                <span class="tag {{ $status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                                    @if($status === 'approved') bg-forest-bg text-forest border-transparent
                                    @elseif($status === 'pending') bg-cognac-bg text-cognac border-transparent
                                    @elseif($status === 'cancelled') bg-transparent text-vellum-muted border-hairline-strong
                                    @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                    @if($status === 'approved' && $resolvedType === 'complimentary')
                                        Auto Approved
                                    @else
                                        {{ $status }}
                                    @endif
                                </span>
                            </td>

                            <!-- Date Range -->
                            <td class="py-4 px-4 font-mono text-[16px] text-vellum">
                                {{ $dateRangeStr }}
                            </td>

                            <!-- Leave Type -->
                            <td class="py-4 px-4 text-[18px] font-bold text-vellum">
                                {{ ucfirst($typeStr) }}
                            </td>

                            <!-- Reason / Details -->
                            <td class="py-4 px-4 text-[16px] text-vellum-muted truncate max-w-[320px]" title="{{ $req->reason }}">
                                {{ $req->reason }}
                            </td>

                            <!-- Total Days -->
                            <td class="py-4 px-4 font-mono text-[16px] text-vellum font-semibold">
                                {{ $req->total_days }} days
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('leaves.show', $req) }}" class="text-brass hover:underline font-semibold text-xs">
                                        Details
                                    </a>
                                    @if(in_array($status, ['pending', 'approved']))
                                        <form action="{{ route('leaves.cancel', $req) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this leave request?')" class="inline">
                                            @csrf
                                            <button type="submit" class="text-burgundy-light hover:underline font-semibold text-xs">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                                You have not submitted any leave requests yet.
                            </td>
                        </tr>
                    @endforelse
                </x-ledger-table>
            </div>
        </div>

        @if(auth()->user()->role !== 'employee')
            <!-- TAB 2: Manager/Admin Approval Queue Ledger -->
            <div x-show="activeTab === 'team-approvals'" class="panel" x-transition style="display: none;">
                <div class="panel-head mb-4 border-b border-hairline pb-2">
                    <h2>Leave Request Approval Queue</h2>
                    <div class="meta font-mono text-[11px] text-vellum-faint">review pending requests</div>
                </div>

                @php
                    $teamApprovalsHeaders = [
                        ['label' => 'Employee', 'class' => ''],
                        ['label' => 'Date Range', 'class' => ''],
                        ['label' => 'Leave Type', 'class' => ''],
                        ['label' => 'Reason / Details', 'class' => ''],
                        ['label' => 'Total Days', 'class' => ''],
                        ['label' => 'Actions', 'class' => 'text-right']
                    ];
                @endphp

                <x-ledger-table :headers="$teamApprovalsHeaders">
                    @forelse($pendingQueue as $request)
                        @php
                            $dateRangeStr = $request->start_date->format('M d, Y') . ' – ' . $request->end_date->format('M d, Y');
                            $resolvedType = $request->leave_type;
                            if (!$resolvedType && $request->leave_credit_id) {
                                $resolvedType = 'complimentary';
                            }
                            if (in_array($resolvedType, ['planned', 'casual_leave', 'paid_leave'])) {
                                $typeStr = 'Planned Leave';
                            } elseif (in_array($resolvedType, ['unplanned', 'sick_leave', 'emergency_leave', 'unpaid_leave'])) {
                                $typeStr = 'Unplanned Leave';
                            } elseif ($resolvedType === 'complimentary') {
                                $typeStr = 'Birthday Leave';
                            } elseif ($resolvedType === 'work_from_home') {
                                $typeStr = 'Work From Home';
                            } else {
                                $typeStr = 'Planned Leave';
                            }
                        @endphp
                        <tr class="hover:bg-brass/[0.04] transition duration-150 text-[16px]">
                            <!-- Employee -->
                            <td class="py-4 px-4">
                                <div class="text-[18px] font-bold text-vellum">{{ $request->user->name }}</div>
                                <div class="text-[13px] text-vellum-faint font-mono mt-0.5">{{ $request->user->employee_id }} · {{ ucfirst($request->user->role) }}</div>
                            </td>

                            <!-- Date Range -->
                            <td class="py-4 px-4 font-mono text-[16px] text-vellum">
                                {{ $dateRangeStr }}
                            </td>

                            <!-- Leave Type -->
                            <td class="py-4 px-4 text-[16px] text-vellum font-semibold capitalize">
                                {{ $typeStr }}
                            </td>

                            <!-- Reason / Details -->
                            <td class="py-4 px-4 text-[16px] text-vellum-muted truncate max-w-[280px]" title="{{ $request->reason }}">
                                {{ $request->reason }}
                            </td>

                            <!-- Total Days -->
                            <td class="py-4 px-4 font-mono text-[16px] text-brass font-semibold">
                                {{ $request->total_days }} days
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end gap-2.5">
                                    <a href="{{ route('leaves.show', $request) }}" class="text-brass hover:underline font-semibold text-xs pr-1">
                                        Details
                                    </a>
                                    <button onclick="openApproveModal({{ $request->id }})" class="bg-forest-bg border border-forest/20 text-forest hover:bg-forest hover:text-canvas font-semibold py-1 px-3 rounded text-[11px] uppercase tracking-wider transition duration-150">
                                        Approve
                                    </button>
                                    <button onclick="openRejectModal({{ $request->id }})" class="bg-burgundy-bg border border-burgundy/20 text-burgundy hover:bg-burgundy hover:text-canvas font-semibold py-1 px-3 rounded text-[11px] uppercase tracking-wider transition duration-150">
                                        Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                                No pending leave requests to review.
                            </td>
                        </tr>
                    @endforelse
                </x-ledger-table>
            </div>

            <!-- TAB 3: Global Decision History Ledger -->
            <div x-show="activeTab === 'full-history'" class="panel" x-transition style="display: none;">
                <div class="panel-head mb-4 border-b border-hairline pb-2">
                    <h2>Leave Decisions History</h2>
                    <div class="meta font-mono text-[11px] text-vellum-faint">completed requests</div>
                </div>

                @php
                    $historyHeaders = [
                        ['label' => 'Status', 'class' => ''],
                        ['label' => 'Employee', 'class' => ''],
                        ['label' => 'Date Range', 'class' => ''],
                        ['label' => 'Leave Type', 'class' => ''],
                        ['label' => 'Reviewed By', 'class' => ''],
                        ['label' => 'Total Days', 'class' => ''],
                        ['label' => 'Actions', 'class' => 'text-right']
                    ];
                @endphp

                <x-ledger-table :headers="$historyHeaders">
                    @forelse($historyQueue as $request)
                        @php
                            $status = $request->status;
                            $dateRangeStr = $request->start_date->format('M d, Y') . ' – ' . $request->end_date->format('M d, Y');
                            $resolvedType = $request->leave_type;
                            if (!$resolvedType && $request->leave_credit_id) {
                                $resolvedType = 'complimentary';
                            }
                            if (in_array($resolvedType, ['planned', 'casual_leave', 'paid_leave'])) {
                                $typeStr = 'Planned Leave';
                            } elseif (in_array($resolvedType, ['unplanned', 'sick_leave', 'emergency_leave', 'unpaid_leave'])) {
                                $typeStr = 'Unplanned Leave';
                            } elseif ($resolvedType === 'complimentary') {
                                $typeStr = 'Birthday Leave';
                            } elseif ($resolvedType === 'work_from_home') {
                                $typeStr = 'Work From Home';
                            } else {
                                $typeStr = 'Planned Leave';
                            }
                        @endphp
                        <tr class="hover:bg-brass/[0.04] transition duration-150 text-[16px]">
                            <!-- Status tag -->
                            <td class="py-4 px-4">
                                <span class="tag {{ $status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                                    @if($status === 'approved') bg-forest-bg text-forest border-transparent
                                    @elseif($status === 'cancelled') bg-transparent text-vellum-muted border-hairline-strong
                                    @else bg-burgundy-bg text-burgundy border-transparent @endif">
                                    @if($status === 'approved' && $resolvedType === 'complimentary')
                                        Auto Approved
                                    @else
                                        {{ $status }}
                                    @endif
                                </span>
                            </td>

                            <!-- Employee -->
                            <td class="py-4 px-4">
                                <div class="text-[18px] font-bold text-vellum">{{ $request->user->name }}</div>
                                <div class="text-[13px] text-vellum-faint font-mono mt-0.5">{{ $request->user->employee_id }}</div>
                            </td>

                            <!-- Date Range -->
                            <td class="py-4 px-4 font-mono text-[16px] text-vellum">
                                {{ $dateRangeStr }}
                            </td>

                            <!-- Leave Type -->
                            <td class="py-4 px-4 text-[16px] text-vellum font-semibold capitalize">
                                {{ $typeStr }}
                            </td>

                            <!-- Reviewed By -->
                            <td class="py-4 px-4 text-[16px] text-vellum-muted">
                                {{ $request->approver?->name ?? 'System' }}
                            </td>

                            <!-- Total Days -->
                            <td class="py-4 px-4 font-mono text-[16px] text-vellum font-semibold">
                                {{ $request->total_days }} days
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('leaves.show', $request) }}" class="text-brass hover:underline font-semibold text-xs">
                                        Details
                                    </a>
                                    @if(auth()->user()->role === 'admin' && $request->user_id !== auth()->id())
                                        <button onclick="openOverrideModal({{ $request->id }}, '{{ $status }}', '{{ $request->leave_type }}')" class="bg-brass hover:bg-brass/90 text-canvas font-semibold py-1 px-2.5 rounded text-[11px] uppercase tracking-wider transition duration-150 shadow-sm">
                                            Override
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                                No leave history recorded.
                            </td>
                        </tr>
                    @endforelse
                </x-ledger-table>
            </div>
        @endif
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center">
        <div class="bg-surface p-6 rounded shadow-xl w-full max-w-md border border-hairline">
            <h3 class="text-lg font-bold text-vellum mb-4 font-display">Approve Leave Request</h3>
            <form id="approveForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <x-input-label for="notes" value="Approver Notes (Optional)" />
                        <textarea name="notes" id="notes" rows="3" placeholder="Add optional comments here..."
                                  class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm placeholder-vellum-faint focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <x-secondary-button type="button" onclick="closeModal('approveModal')">
                            Cancel
                        </x-secondary-button>
                        <x-primary-button type="submit" class="bg-forest border-forest hover:bg-forest/90">
                            Confirm Approve
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center">
        <div class="bg-surface p-6 rounded shadow-xl w-full max-w-md border border-hairline">
            <h3 class="text-lg font-bold text-burgundy mb-4 font-display">Reject Leave Request</h3>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <x-input-label for="rejection_reason" value="Rejection Reason (Required)" />
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" required placeholder="State the reason for rejection..."
                                  class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm placeholder-vellum-faint focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <x-secondary-button type="button" onclick="closeModal('rejectModal')">
                            Cancel
                        </x-secondary-button>
                        <x-danger-button type="submit">
                            Confirm Reject
                        </x-danger-button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Override Modal -->
    <div id="overrideModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center">
        <div class="bg-surface p-6 rounded shadow-xl w-full max-w-md border border-hairline">
            <h3 class="text-lg font-bold text-brass mb-4 font-display">Admin Override Decision</h3>
            <form id="overrideForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <x-input-label for="override_status" value="Override Status" />
                        <select name="override_status" id="override_status" required
                                class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                            <option value="approved" class="bg-surface text-vellum">Approved</option>
                            <option value="rejected" class="bg-surface text-vellum">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="override_notes" value="Override Reason / Notes (Required)" />
                        <textarea name="override_notes" id="override_notes" rows="3" required placeholder="Explain why this decision was overridden..."
                                  class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm placeholder-vellum-faint focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <x-secondary-button type="button" onclick="closeModal('overrideModal')">
                            Cancel
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            Apply Override
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openApproveModal(id) {
            document.getElementById('approveForm').action = "{{ route('leaves.index') }}/" + id + "/approve";
            document.getElementById('approveModal').classList.remove('hidden');
        }

        function openRejectModal(id) {
            document.getElementById('rejectForm').action = "{{ route('leaves.index') }}/" + id + "/reject";
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function openOverrideModal(id, currentStatus, leaveType) {
            document.getElementById('overrideForm').action = "{{ route('leaves.index') }}/" + id + "/override";
            const statusSelect = document.getElementById('override_status');
            if (currentStatus === 'approved') {
                statusSelect.value = 'rejected';
            } else {
                statusSelect.value = 'approved';
            }
            document.getElementById('overrideModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</x-ledger-layout>
