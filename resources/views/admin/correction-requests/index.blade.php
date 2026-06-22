<x-app-layout wide>
    <x-slot name="header">
        <div>
            <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">Correction Requests</h1>
            <div class="text-[12.5px] text-vellum-faint mt-1.5 tracking-wide">
                Review and resolve workforce profile correction requests
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-6">

            @if(session('success'))
                <div class="rounded-md bg-forest-bg border border-hairline text-forest px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="panel">
                <div class="panel-head flex items-center justify-between mb-4.5">
                    <h2 class="font-display font-medium text-[16px]">All Correction Requests</h2>
                    <div class="meta font-mono text-[11px] text-vellum-faint">requests log</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-hairline text-vellum-muted font-semibold">
                                <th class="py-3 px-4">Employee Details</th>
                                <th class="py-3 px-4">Field Being Corrected</th>
                                <th class="py-3 px-4">Request Message</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4">Submitted Date</th>
                                <th class="py-3 px-4">Resolution Note / Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                                <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150">
                                    <!-- Employee Details -->
                                    <td class="py-4 px-4 align-top">
                                        <div class="font-semibold text-vellum">
                                            <a href="{{ route('employees.show', $req->requester) }}" class="text-brass hover:underline">
                                                {{ $req->requester->name }}
                                            </a>
                                        </div>
                                        <div class="text-xs text-vellum-faint font-mono mt-0.5">
                                            ID: {{ $req->requester->employee_id ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-vellum-faint mt-0.5">
                                            Dept: {{ $req->requester->department?->name ?? 'N/A' }}
                                        </div>
                                    </td>

                                    <!-- Field -->
                                    <td class="py-4 px-4 align-top">
                                        <span class="tag leave">
                                            {{ $req->field }}
                                        </span>
                                    </td>

                                    <!-- Message -->
                                    <td class="py-4 px-4 align-top max-w-xs whitespace-pre-line text-vellum-muted">
                                        {{ $req->message }}
                                    </td>

                                    <!-- Status -->
                                    <td class="py-4 px-4 align-top text-center">
                                        @if($req->status === 'pending')
                                            <span class="tag late">Pending</span>
                                        @else
                                            <span class="tag present">Resolved</span>
                                        @endif
                                    </td>

                                    <!-- Submitted Date -->
                                    <td class="py-4 px-4 align-top text-vellum-faint font-mono">
                                        {{ $req->created_at->format('Y-m-d h:i A') }}
                                    </td>

                                    <!-- Resolution / Form -->
                                    <td class="py-4 px-4 align-top">
                                        @if($req->status === 'pending')
                                            <form method="POST" action="{{ route('admin.corrections.resolve', $req) }}" class="space-y-2">
                                                @csrf
                                                <textarea name="admin_note" rows="2" placeholder="Add resolution note..."
                                                          class="w-full text-xs bg-surface-raised border border-hairline rounded px-2.5 py-2 text-vellum placeholder-vellum-faint focus:outline-none focus:border-brass/50"></textarea>
                                                <button type="submit" class="bg-brass hover:bg-brass/90 text-canvas font-semibold text-xs py-1.5 px-3.5 rounded transition duration-150">
                                                    Resolve & Mark Complete
                                                </button>
                                            </form>
                                        @else
                                            <div class="text-xs text-vellum-muted bg-surface-raised p-2.5 rounded border border-hairline">
                                                <div class="font-semibold text-vellum">Resolution details:</div>
                                                <div class="italic text-vellum-muted mt-1">"{{ $req->admin_note ?? 'No notes provided' }}"</div>
                                                <div class="mt-2 text-[10px] text-vellum-faint border-t border-dashed border-hairline pt-1">
                                                    By {{ $req->resolver?->name ?? 'Admin' }} on {{ $req->resolved_at?->format('Y-m-d h:i A') }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-vellum-faint">
                                        No profile correction requests found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
