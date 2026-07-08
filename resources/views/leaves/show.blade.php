@php
    $resolvedType = $leaveRequest->leave_type ?? ($leaveRequest->leave_credit_id ? 'complimentary' : null);
@endphp
<x-dossier-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vellum leading-tight font-display">
            {{ __('Leave Application Details') }}
        </h2>
    </x-slot>

    <!-- Back Link & Request ID -->
    <div class="flex justify-between items-center border-b border-hairline pb-4 mb-6">
        <a href="{{ route('leaves.index') }}" class="text-brass hover:underline transition text-sm flex items-center gap-1 font-semibold">
            ← Back to Leave Management
        </a>
        <span class="text-xs text-vellum-faint font-mono font-medium">Request ID: #{{ $leaveRequest->id }}</span>
    </div>

    <!-- Details Card -->
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-hairline/40 pb-4 gap-4">
            <div>
                <h3 class="text-xl font-bold text-brass font-display capitalize">
                    {{ $leaveRequest->leave_type_label }}
                </h3>
                <p class="text-xs text-vellum-muted mt-1 font-medium">
                    Submitted by: <span class="font-semibold text-vellum">{{ $leaveRequest->user->name }}</span> (ID: {{ $leaveRequest->user->employee_id }})
                </p>
            </div>
            <div>
                <span class="tag {{ $leaveRequest->status }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-1 rounded border
                    @if($leaveRequest->status === 'approved') bg-forest-bg text-forest border-transparent
                    @elseif($leaveRequest->status === 'pending') bg-cognac-bg text-cognac border-transparent
                    @elseif($leaveRequest->status === 'cancelled') bg-transparent text-vellum-muted border-hairline-strong
                    @else bg-burgundy-bg text-burgundy border-transparent @endif">
                    @if($leaveRequest->status === 'approved' && $resolvedType === 'complimentary')
                        Auto Approved
                    @else
                        {{ $leaveRequest->status }}
                    @endif
                </span>
            </div>
        </div>

        <!-- 200px Dossier details alignment -->
        <div class="flex flex-col text-sm">
            <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline/40 items-center">
                <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Start Date</span>
                <span class="text-sm font-medium text-vellum font-mono">{{ $leaveRequest->start_date->format('M d, Y') }}</span>
            </div>
            <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline/40 items-center">
                <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">End Date</span>
                <span class="text-sm font-medium text-vellum font-mono">{{ $leaveRequest->end_date->format('M d, Y') }}</span>
            </div>
            <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline/40 items-center">
                <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Total Duration</span>
                <span class="text-sm font-bold text-brass font-mono">{{ $leaveRequest->total_days }} {{ $leaveRequest->total_days === 1 ? 'Day' : 'Days' }}</span>
            </div>
        </div>

        <!-- Reason -->
        <div class="bg-surface-raised p-4 rounded border border-hairline">
            <span class="text-vellum-faint uppercase tracking-wider text-[10.5px] font-semibold block mb-2">Reason for Request</span>
            <p class="text-vellum text-sm leading-relaxed whitespace-pre-wrap font-medium">{{ $leaveRequest->reason }}</p>
        </div>

        <!-- Notes / Feedback -->
        @if($leaveRequest->status === 'approved' && $leaveRequest->notes)
            <div class="bg-forest-bg p-4 rounded border border-forest/15">
                <span class="text-forest uppercase tracking-wider text-[10.5px] font-semibold block mb-2">Approval Notes</span>
                <p class="text-vellum text-sm whitespace-pre-wrap font-medium">{{ $leaveRequest->notes }}</p>
            </div>
        @endif

        @if($leaveRequest->status === 'rejected' && $leaveRequest->rejection_reason)
            <div class="bg-burgundy-bg p-4 rounded border border-burgundy/15">
                <span class="text-burgundy uppercase tracking-wider text-[10.5px] font-semibold block mb-2">Rejection Feedback</span>
                <p class="text-vellum text-sm whitespace-pre-wrap font-medium">{{ $leaveRequest->rejection_reason }}</p>
            </div>
        @endif
    </div>

    <!-- Audit Trail Timeline -->
    <div class="mt-8 pt-8 border-t border-hairline">
        <h3 class="text-base font-bold text-brass pb-3 flex items-center gap-2 font-display">
            <svg class="w-5 h-5 text-brass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Audit Trail Timeline
        </h3>

        <div class="relative pl-8 mt-4 space-y-6 before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-hairline-strong">
            @foreach($logs as $log)
                @php
                    $dotColor = 'bg-brass';
                    if ($log->action === 'applied') {
                        $dotColor = 'bg-brass';
                    } elseif ($log->action === 'approved') {
                        $dotColor = 'bg-forest';
                    } elseif ($log->action === 'rejected') {
                        $dotColor = 'bg-burgundy';
                    } elseif ($log->action === 'cancelled') {
                        $dotColor = 'bg-hairline-strong';
                    } elseif ($log->action === 'overridden') {
                        $dotColor = 'bg-brass';
                    }
                @endphp
                <div class="relative flex flex-col gap-2 bg-surface-raised p-4 rounded border border-hairline">
                    <!-- Marker Dot -->
                    <span class="absolute -left-8 top-5 w-2 h-2 rounded-full border border-surface flex items-center justify-center {{ $dotColor }} ring-4 ring-brass/10"></span>

                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1 border-b border-hairline/40 pb-2">
                        <span class="text-[13px] font-bold text-vellum capitalize">
                            Action: {{ $log->action }}
                        </span>
                        <span class="text-xs text-vellum-faint font-mono">
                            {{ $log->created_at->timezone('Asia/Kolkata')->format('M d, Y @ h:i A') }}
                        </span>
                    </div>

                    <div class="text-xs text-vellum-muted space-y-1">
                        <p>
                            Acting User: <span class="font-semibold text-vellum">{{ $log->user->name }}</span> ({{ ucfirst($log->user->role) }})
                        </p>
                        @if($log->from_status || $log->to_status)
                            <p>
                                Transition: 
                                <span class="font-mono bg-surface px-1.5 py-0.5 rounded capitalize">{{ $log->from_status ?? 'None' }}</span>
                                → 
                                <span class="font-mono bg-surface px-1.5 py-0.5 rounded capitalize font-semibold text-brass">{{ $log->to_status }}</span>
                            </p>
                        @endif
                    </div>

                    @if($log->notes)
                        <div class="text-xs italic text-vellum bg-surface/40 p-2.5 rounded border-l-2 border-brass mt-2 leading-relaxed">
                            "{{ $log->notes }}"
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-dossier-layout>
