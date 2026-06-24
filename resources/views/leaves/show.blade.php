<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vellum leading-tight font-display">
            {{ __('Leave Application Details') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-6">
        <!-- Back Link -->
        <div class="flex justify-between items-center">
            <a href="{{ route('leaves.index') }}" class="text-vellum-muted hover:text-brass transition text-sm flex items-center gap-1 font-medium">
                ← Back to Leave Management
            </a>
            <span class="text-xs text-vellum-faint font-mono">Request ID: #{{ $leaveRequest->id }}</span>
        </div>

        <!-- Details Card -->
        <div class="glass-panel p-6 rounded-lg border border-hairline space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-hairline pb-4 gap-4">
                <div>
                    <h3 class="text-xl font-bold text-brass font-display capitalize">
                        {{ $leaveRequest->leave_type === 'complimentary' ? 'Birthday Leave' : ($leaveRequest->leave_type ? str_replace('_', ' ', $leaveRequest->leave_type) : 'Pending Classification') }}
                    </h3>
                    <p class="text-sm text-vellum-muted mt-1">
                        Submitted by: <span class="font-semibold text-vellum">{{ $leaveRequest->user->name }}</span> ({{ $leaveRequest->user->employee_id }})
                    </p>
                </div>
                <div>
                    <span class="tag @if($leaveRequest->status === 'approved') present @elseif($leaveRequest->status === 'pending') late @elseif($leaveRequest->status === 'cancelled') leave @else absent @endif">
                        {{ $leaveRequest->status }}
                    </span>
                </div>
            </div>

            <!-- Meta Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <span class="text-vellum-muted block uppercase tracking-wider text-xs font-semibold">Start Date</span>
                    <span class="text-vellum font-medium text-base mt-1 block font-mono">{{ $leaveRequest->start_date->format('M d, Y') }}</span>
                </div>
                <div>
                    <span class="text-vellum-muted block uppercase tracking-wider text-xs font-semibold">End Date</span>
                    <span class="text-vellum font-medium text-base mt-1 block font-mono">{{ $leaveRequest->end_date->format('M d, Y') }}</span>
                </div>
                <div>
                    <span class="text-vellum-muted block uppercase tracking-wider text-xs font-semibold">Total Days</span>
                    <span class="text-brass font-bold text-base mt-1 block font-mono">{{ $leaveRequest->total_days }} {{ $leaveRequest->total_days === 1 ? 'Day' : 'Days' }}</span>
                </div>
            </div>

            <!-- Reason Block -->
            <div class="bg-surface p-4 rounded-lg border border-hairline">
                <span class="text-vellum-muted uppercase tracking-wider text-xs font-semibold block mb-2">Reason for Request</span>
                <p class="text-vellum text-sm leading-relaxed whitespace-pre-wrap">{{ $leaveRequest->reason }}</p>
            </div>

            <!-- Notes or Rejection Reason -->
            @if($leaveRequest->status === 'approved' && $leaveRequest->notes)
                <div class="bg-forest-bg p-4 rounded-lg border border-forest/20">
                    <span class="text-forest-light uppercase tracking-wider text-xs font-semibold block mb-2">Approval Notes</span>
                    <p class="text-vellum text-sm whitespace-pre-wrap">{{ $leaveRequest->notes }}</p>
                </div>
            @endif

            @if($leaveRequest->status === 'rejected' && $leaveRequest->rejection_reason)
                <div class="bg-burgundy-bg p-4 rounded-lg border border-burgundy/20">
                    <span class="text-burgundy-light uppercase tracking-wider text-xs font-semibold block mb-2">Rejection Feedback</span>
                    <p class="text-vellum text-sm whitespace-pre-wrap">{{ $leaveRequest->rejection_reason }}</p>
                </div>
            @endif
        </div>

        <!-- Audit Trail Timeline -->
        <div class="glass-panel p-6 rounded-lg border border-hairline space-y-6">
            <h3 class="text-lg font-bold text-brass pb-2 border-b border-hairline flex items-center gap-2 font-display">
                <svg class="w-5 h-5 text-brass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Audit Trail Timeline
            </h3>

            <div class="relative pl-8 space-y-8 before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-hairline-strong">
                @foreach($logs as $log)
                    <div class="relative flex flex-col sm:flex-row sm:items-start gap-4">
                        <!-- Timeline Marker Dot -->
                        <span class="absolute -left-8 top-1.5 w-3 h-3 rounded-full border-2 border-surface flex items-center justify-center
                            @if($log->action === 'applied')
                                bg-brass ring-4 ring-brass/10
                            @elseif($log->action === 'approved')
                                bg-forest-light ring-4 ring-forest-light/10
                            @elseif($log->action === 'rejected')
                                bg-burgundy-light ring-4 ring-burgundy-light/10
                            @elseif($log->action === 'cancelled')
                                bg-slate-light ring-4 ring-slate-light/10
                            @elseif($log->action === 'overridden')
                                bg-brass ring-4 ring-brass/10
                            @endif
                        "></span>

                        <!-- Log Details -->
                        <div class="flex-1 bg-surface-raised p-4 rounded-lg border border-hairline space-y-2">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 border-b border-hairline pb-2">
                                <span class="text-sm font-bold text-vellum capitalize">
                                    Action: {{ $log->action }}
                                </span>
                                <span class="text-xs text-vellum-faint font-mono">
                                    {{ $log->created_at->format('M d, Y @ h:i A') }}
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
                                <div class="text-xs italic text-vellum-muted bg-surface/40 p-2.5 rounded border-l-2 border-brass/30 mt-2">
                                    "{{ $log->notes }}"
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
