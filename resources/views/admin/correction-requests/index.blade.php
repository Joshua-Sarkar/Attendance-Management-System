<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-on-surface dark:text-on-surface leading-tight">
            {{ __('Workforce Profile Correction Requests') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        @if(session('success'))
            <div class="rounded-md bg-green-100 border border-green-300 text-green-700 px-4 py-3 shadow-sm text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="glass-panel p-6 rounded-lg border border-primary/10">
            <h3 class="text-lg font-bold text-on-surface mb-4">All Correction Requests</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="border-b border-outline-variant/30 text-on-surface-variant font-semibold bg-gray-50 dark:bg-gray-800">
                            <th class="py-3 px-4">Employee Details</th>
                            <th class="py-3 px-4">Field Being Corrected</th>
                            <th class="py-3 px-4">Request Message</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Submitted Date</th>
                            <th class="py-3 px-4">Resolution Note / Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($requests as $req)
                            <tr class="hover:bg-surface-container-high/30 transition duration-150">
                                <!-- Employee Details -->
                                <td class="py-4 px-4 align-top">
                                    <div class="font-semibold text-on-surface">
                                        <a href="{{ route('employees.show', $req->requester) }}" class="text-primary hover:underline">
                                            {{ $req->requester->name }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-on-surface-variant font-mono mt-0.5">
                                        ID: {{ $req->requester->employee_id ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-on-surface-variant mt-0.5">
                                        Dept: {{ $req->requester->department?->name ?? 'N/A' }}
                                    </div>
                                </td>

                                <!-- Field -->
                                <td class="py-4 px-4 align-top">
                                    <span class="inline-block px-2.5 py-0.5 rounded bg-indigo-100 text-indigo-800 text-[11px] font-bold">
                                        {{ $req->field }}
                                    </span>
                                </td>

                                <!-- Message -->
                                <td class="py-4 px-4 align-top max-w-xs whitespace-pre-line text-on-surface">
                                    {{ $req->message }}
                                </td>

                                <!-- Status -->
                                <td class="py-4 px-4 align-top text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold capitalize
                                        @if($req->status === 'pending')
                                            bg-amber-100 text-amber-800
                                        @else
                                            bg-green-100 text-green-800
                                        @endif
                                    ">
                                        {{ $req->status }}
                                    </span>
                                </td>

                                <!-- Submitted Date -->
                                <td class="py-4 px-4 align-top text-on-surface-variant font-mono">
                                    {{ $req->created_at->format('Y-m-d h:i A') }}
                                </td>

                                <!-- Resolution / Form -->
                                <td class="py-4 px-4 align-top">
                                    @if($req->status === 'pending')
                                        <form method="POST" action="{{ route('admin.corrections.resolve', $req) }}" class="space-y-2">
                                            @csrf
                                            <textarea name="admin_note" rows="2" placeholder="Add resolution note..."
                                                      class="w-full text-xs bg-surface-container border border-outline-variant/30 rounded px-2 py-1 text-on-surface focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs py-1 px-3 rounded shadow transition duration-150">
                                                Resolve & Mark Complete
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-xs text-on-surface-variant bg-gray-50 dark:bg-gray-900/40 p-2.5 rounded border border-outline-variant/20">
                                            <div class="font-semibold text-gray-700 dark:text-gray-300">Resolution details:</div>
                                            <div class="italic text-gray-600 dark:text-gray-400 mt-1">"{{ $req->admin_note ?? 'No notes provided' }}"</div>
                                            <div class="mt-2 text-[10px] text-gray-500 border-t border-dashed border-outline-variant/20 pt-1">
                                                By {{ $req->resolver?->name ?? 'Admin' }} on {{ $req->resolved_at?->format('Y-m-d h:i A') }}
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-on-surface-variant">
                                    No profile correction requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
