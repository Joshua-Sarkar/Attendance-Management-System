<x-app-layout wide>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">Leave Management</h1>
                <div class="text-[12.5px] text-vellum-faint mt-1.5 tracking-wide">
                    Submit and review leave requests
                </div>
            </div>
            <a href="{{ route('leaves.create') }}" 
               class="inline-flex items-center px-4 py-2.5 bg-brass hover:bg-brass/90 text-canvas font-bold uppercase tracking-widest rounded-md text-xs transition duration-200 shadow-md">
                + Apply for Leave
            </a>
        </div>
    </x-slot>

    <div x-data="{ activeTab: 'my-applications' }" class="py-6 space-y-6">
        <!-- Session Notifications -->
        @if(session('success'))
            <div class="rounded-md bg-forest-bg border border-forest/30 text-forest px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-md bg-burgundy-bg border border-burgundy/30 text-burgundy px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Tab Navigation (Only visible for Manager/Admin since employee only has my-applications) -->
        @if(auth()->user()->role !== 'employee')
            <div class="border-b border-hairline flex gap-6 mb-2">
                <button @click="activeTab = 'my-applications'" 
                        :class="activeTab === 'my-applications' ? 'border-brass text-brass' : 'border-transparent text-vellum-muted hover:text-vellum'"
                        class="pb-3 border-b-2 font-display text-[15px] font-semibold transition focus:outline-none">
                    My Applications
                </button>
                <button @click="activeTab = 'team-approvals'" 
                        :class="activeTab === 'team-approvals' ? 'border-brass text-brass' : 'border-transparent text-vellum-muted hover:text-vellum'"
                        class="pb-3 border-b-2 font-display text-[15px] font-semibold transition focus:outline-none">
                    Team Approvals Queue ({{ $pendingQueue->count() }})
                </button>
                <button @click="activeTab = 'full-history'" 
                        :class="activeTab === 'full-history' ? 'border-brass text-brass' : 'border-transparent text-vellum-muted hover:text-vellum'"
                        class="pb-3 border-b-2 font-display text-[15px] font-semibold transition focus:outline-none">
                    Decision History
                </button>
            </div>
        @endif

        <!-- TAB 1: My Leave Applications -->
        <div x-show="activeTab === 'my-applications'" class="space-y-6" x-transition>
            <!-- Leave Summary Stats -->
            <div class="space-y-3">
                <h4 class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Your Approved Leave Summary (Current Year)</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4">
                    <div class="bg-surface p-4 rounded border border-hairline flex flex-col justify-between">
                        <span class="text-vellum-muted text-xs font-semibold">Casual Leave</span>
                        <h3 class="text-2xl font-bold text-vellum mt-2 font-display">{{ $stats['casual_leave'] }} days</h3>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline flex flex-col justify-between">
                        <span class="text-vellum-muted text-xs font-semibold">Sick Leave</span>
                        <h3 class="text-2xl font-bold text-vellum mt-2 font-display">{{ $stats['sick_leave'] }} days</h3>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline flex flex-col justify-between">
                        <span class="text-vellum-muted text-xs font-semibold">Paid Leave</span>
                        <h3 class="text-2xl font-bold text-vellum mt-2 font-display">{{ $stats['paid_leave'] }} days</h3>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline flex flex-col justify-between">
                        <span class="text-vellum-muted text-xs font-semibold">Unpaid Leave</span>
                        <h3 class="text-2xl font-bold text-vellum mt-2 font-display">{{ $stats['unpaid_leave'] }} days</h3>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline flex flex-col justify-between">
                        <span class="text-vellum-muted text-xs font-semibold">WFH</span>
                        <h3 class="text-2xl font-bold text-vellum mt-2 font-display">{{ $stats['work_from_home'] }} days</h3>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline flex flex-col justify-between">
                        <span class="text-vellum-muted text-xs font-semibold">Emergency</span>
                        <h3 class="text-2xl font-bold text-vellum mt-2 font-display">{{ $stats['emergency_leave'] }} days</h3>
                    </div>
                    <div class="bg-brass/10 p-4 rounded border border-brass/30 flex flex-col justify-between">
                        <span class="text-brass text-xs font-bold">Total Approved</span>
                        <h3 class="text-2xl font-bold text-brass mt-2 font-display">{{ $stats['total_approved'] }} days</h3>
                    </div>
                </div>
            </div>

            <!-- Personal Leave Request History -->
            <div class="panel space-y-4">
                <div class="panel-head flex items-center justify-between mb-4.5">
                    <h2 class="font-display font-medium text-[16px]">Your Leave Applications</h2>
                    <div class="meta font-mono text-[11px] text-vellum-faint">applications history</div>
                </div>

                @if($myLeaves->isEmpty())
                    <p class="text-sm text-vellum-faint py-4 text-center">You have not submitted any leave requests yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-hairline text-vellum-muted font-semibold">
                                    <th class="py-3 px-4">Date Range</th>
                                    <th class="py-3 px-4">Leave Type</th>
                                    <th class="py-3 px-4 text-center">Days</th>
                                    <th class="py-3 px-4">Reason</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4">Notes/Feedback</th>
                                    <th class="py-3 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myLeaves as $req)
                                    <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150">
                                        <td class="py-3 px-4 font-medium text-vellum">
                                            {{ $req->start_date->format('M d, Y') }} - {{ $req->end_date->format('M d, Y') }}
                                        </td>
                                        <td class="py-3 px-4 text-vellum capitalize">
                                            {{ str_replace('_', ' ', $req->leave_type) }}
                                        </td>
                                        <td class="py-3 px-4 text-center text-vellum font-semibold">
                                            {{ $req->total_days }}
                                        </td>
                                        <td class="py-3 px-4 text-vellum-muted max-w-xs truncate" title="{{ $req->reason }}">
                                            {{ $req->reason }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="tag @if($req->status === 'approved') present @elseif($req->status === 'pending') late @elseif($req->status === 'cancelled') leave @else absent @endif">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-xs text-vellum-muted max-w-xs truncate">
                                            @if($req->status === 'approved')
                                                {{ $req->notes ?? '-' }}
                                            @elseif($req->status === 'rejected')
                                                {{ $req->rejection_reason ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center gap-3">
                                                <a href="{{ route('leaves.show', $req) }}" class="text-brass hover:underline font-semibold text-xs">
                                                    View Details
                                                </a>
                                                @if(in_array($req->status, ['pending', 'approved']))
                                                    <form action="{{ route('leaves.cancel', $req) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this leave request?')" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-burgundy hover:underline font-semibold text-xs">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @if(auth()->user()->role !== 'employee')
            <!-- TAB 2: Manager/Admin Approval Queue -->
            <div x-show="activeTab === 'team-approvals'" class="panel space-y-4" x-transition>
                <div class="panel-head flex items-center justify-between mb-4.5">
                    <h2 class="font-display font-medium text-[16px]">Leave Request Approval Queue</h2>
                    <div class="meta font-mono text-[11px] text-vellum-faint font-semibold">review pending requests</div>
                </div>

                @if($pendingQueue->isEmpty())
                    <p class="text-sm text-vellum-faint py-8 text-center">No pending leave requests to review.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-hairline text-vellum-muted font-semibold">
                                    <th class="py-3 px-4">Employee</th>
                                    <th class="py-3 px-4">Leave Type</th>
                                    <th class="py-3 px-4">Date Range</th>
                                    <th class="py-3 px-4 text-center">Total Days</th>
                                    <th class="py-3 px-4">Reason</th>
                                    <th class="py-3 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingQueue as $request)
                                    <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-vellum">{{ $request->user->name }}</div>
                                            <div class="text-xs text-vellum-faint font-mono">{{ $request->user->employee_id }} ({{ ucfirst($request->user->role) }})</div>
                                        </td>
                                        <td class="py-3 px-4 font-medium text-vellum capitalize">
                                            {{ str_replace('_', ' ', $request->leave_type) }}
                                        </td>
                                        <td class="py-3 px-4 text-vellum-muted">
                                            {{ $request->start_date->format('M d, Y') }} - {{ $request->end_date->format('M d, Y') }}
                                        </td>
                                        <td class="py-3 px-4 text-center text-brass font-semibold font-mono">
                                            {{ $request->total_days }}
                                        </td>
                                        <td class="py-3 px-4 text-vellum-muted max-w-xs truncate" title="{{ $request->reason }}">
                                            {{ $request->reason }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center justify-center gap-3">
                                                <a href="{{ route('leaves.show', $request) }}" class="text-brass hover:underline font-semibold text-xs">
                                                    View Details
                                                </a>
                                                <button onclick="openApproveModal({{ $request->id }})" class="bg-forest-bg border border-forest/30 text-forest hover:bg-forest hover:text-canvas font-semibold py-1 px-3 rounded text-xs transition duration-150">
                                                    Approve
                                                </button>
                                                <button onclick="openRejectModal({{ $request->id }})" class="bg-burgundy-bg border border-burgundy/30 text-burgundy hover:bg-burgundy hover:text-canvas font-semibold py-1 px-3 rounded text-xs transition duration-150">
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- TAB 3: Global Decision History -->
            <div x-show="activeTab === 'full-history'" class="panel space-y-4" x-transition>
                <div class="panel-head flex items-center justify-between mb-4.5">
                    <h2 class="font-display font-medium text-[16px]">Leave Decisions History</h2>
                    <div class="meta font-mono text-[11px] text-vellum-faint">completed requests</div>
                </div>

                @if($historyQueue->isEmpty())
                    <p class="text-sm text-vellum-faint py-8 text-center">No leave history recorded.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-hairline text-vellum-muted font-semibold">
                                    <th class="py-3 px-4">Employee</th>
                                    <th class="py-3 px-4">Leave Type</th>
                                    <th class="py-3 px-4">Date Range</th>
                                    <th class="py-3 px-4 text-center">Days</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4">Reviewed By</th>
                                    <th class="py-3 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($historyQueue as $request)
                                    <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-vellum">{{ $request->user->name }}</div>
                                            <div class="text-xs text-vellum-faint font-mono">{{ $request->user->employee_id }}</div>
                                        </td>
                                        <td class="py-3 px-4 capitalize text-vellum">
                                            {{ str_replace('_', ' ', $request->leave_type) }}
                                        </td>
                                        <td class="py-3 px-4 text-vellum-muted">
                                            {{ $request->start_date->format('M d, Y') }} - {{ $request->end_date->format('M d, Y') }}
                                        </td>
                                        <td class="py-3 px-4 text-center text-vellum font-semibold font-mono">
                                            {{ $request->total_days }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="tag @if($request->status === 'approved') present @elseif($request->status === 'cancelled') leave @else absent @endif">
                                                {{ $request->status }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-vellum">
                                            {{ $request->approver?->name ?? 'System' }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center gap-3">
                                                <a href="{{ route('leaves.show', $request) }}" class="text-brass hover:underline font-semibold text-xs">
                                                    View Details
                                                </a>
                                                @if(auth()->user()->role === 'admin' && $request->user_id !== auth()->id())
                                                    <button onclick="openOverrideModal({{ $request->id }}, '{{ $request->status }}')" class="bg-brass hover:bg-brass/90 text-canvas font-semibold py-1 px-2.5 rounded text-xs transition shadow-sm uppercase tracking-wider">
                                                        Override
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center">
        <div class="bg-surface p-6 rounded-lg shadow-xl w-full max-w-md border border-hairline">
            <h3 class="text-lg font-bold text-vellum mb-4 font-display">Approve Leave Request</h3>
            <form id="approveForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-vellum-muted mb-1">Approver Notes (Optional)</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Add optional comments here..."
                                  class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 placeholder-vellum-faint focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('approveModal')" class="bg-surface-raised hover:bg-surface-raised/80 text-vellum font-semibold py-2 px-4 rounded-md transition duration-200 border border-hairline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-forest hover:bg-forest/90 text-canvas font-bold py-2 px-4 rounded-md transition duration-200 shadow-md uppercase tracking-wider text-xs">
                            Confirm Approve
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center">
        <div class="bg-surface p-6 rounded-lg shadow-xl w-full max-w-md border border-hairline">
            <h3 class="text-lg font-bold text-burgundy mb-4 font-display">Reject Leave Request</h3>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="rejection_reason" class="block text-sm font-medium text-vellum-muted mb-1">Rejection Reason (Required)</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" required placeholder="State the reason for rejection..."
                                  class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 placeholder-vellum-faint focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('rejectModal')" class="bg-surface-raised hover:bg-surface-raised/80 text-vellum font-semibold py-2 px-4 rounded-md transition duration-200 border border-hairline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-burgundy hover:bg-burgundy/90 text-vellum font-bold py-2 px-4 rounded-md transition duration-200 shadow-md uppercase tracking-wider text-xs border border-burgundy/20">
                            Confirm Reject
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Override Modal -->
    <div id="overrideModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center">
        <div class="bg-surface p-6 rounded-lg shadow-xl w-full max-w-md border border-hairline">
            <h3 class="text-lg font-bold text-brass mb-4 font-display">Admin Override Decision</h3>
            <form id="overrideForm" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="override_status" class="block text-sm font-medium text-vellum-muted mb-1">Override Status</label>
                        <select name="override_status" id="override_status" required
                                class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                            <option value="approved" class="bg-surface text-vellum">Approved</option>
                            <option value="rejected" class="bg-surface text-vellum">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label for="override_notes" class="block text-sm font-medium text-vellum-muted mb-1">Override Reason / Notes (Required)</label>
                        <textarea name="override_notes" id="override_notes" rows="3" required placeholder="Explain why this decision was overridden..."
                                  class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 placeholder-vellum-faint focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('overrideModal')" class="bg-surface-raised hover:bg-surface-raised/80 text-vellum font-semibold py-2 px-4 rounded-md transition duration-200 border border-hairline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-brass text-canvas font-bold py-2 px-4 rounded-md transition duration-200 shadow-md uppercase tracking-wider text-xs">
                            Apply Override
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApproveModal(id) {
            document.getElementById('approveForm').action = '/leaves/' + id + '/approve';
            document.getElementById('approveModal').classList.remove('hidden');
        }

        function openRejectModal(id) {
            document.getElementById('rejectForm').action = '/leaves/' + id + '/reject';
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function openOverrideModal(id, currentStatus) {
            document.getElementById('overrideForm').action = '/leaves/' + id + '/override';
            const statusSelect = document.getElementById('override_status');
            statusSelect.value = currentStatus === 'approved' ? 'rejected' : 'approved';
            document.getElementById('overrideModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</x-app-layout>
