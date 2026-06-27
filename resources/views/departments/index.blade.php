<x-ledger-layout :timeline="false">
    <x-slot name="header">
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center gap-4">
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Departments</h1>
                <x-primary-button onclick="window.location.href='{{ route('departments.create') }}'">
                    Create Department
                </x-primary-button>
            </div>
            <div class="text-[13px] text-vellum-muted tracking-wide">
                Manage organizational groups and department codes
            </div>
        </div>
    </x-slot>

    <!-- Session Notifications -->
    @if(session('success'))
        <div class="rounded bg-forest-bg border border-hairline text-forest px-4 py-3 text-sm mb-6">
            {{ session('success') }}
        </div>
    @endif

    <x-slot name="ledgerHeader">
        <h2>Organization Departments</h2>
        <div class="meta">departments registry</div>
    </x-slot>

    @php
        $headers = [
            ['label' => 'Department', 'class' => ''],
            ['label' => 'Code', 'class' => ''],
            ['label' => 'Manager', 'class' => ''],
            ['label' => 'Employees', 'class' => ''],
            ['label' => 'Description', 'class' => ''],
            ['label' => 'Actions', 'class' => 'text-right']
        ];
    @endphp

    <x-ledger-table :headers="$headers">
        @forelse($departments as $department)
            <tr class="hover:bg-brass/[0.04] transition duration-150 text-[16px]">
                <!-- Department (Name) -->
                <td class="py-4 px-4 text-[16px] font-semibold text-vellum">
                    <a href="{{ route('departments.show', $department) }}" class="hover:text-brass transition-colors">{{ $department->name }}</a>
                </td>

                <!-- Code -->
                <td class="py-4 px-4 font-mono text-[16px] text-brass font-medium">
                    {{ $department->code }}
                </td>

                <!-- Manager -->
                <td class="py-4 px-4 text-[16px] text-vellum font-medium">
                    @php
                        $manager = $department->users->whereIn('role', ['manager', 'admin'])->first()
                            ?? $department->users->whereNotNull('manager_id')->first()?->manager;
                    @endphp
                    {{ $manager?->name ?? 'None' }}
                </td>

                <!-- Employees Count -->
                <td class="py-4 px-4 text-[16px] text-vellum font-mono font-medium">
                    {{ $department->users->count() }}
                </td>

                <!-- Description -->
                <td class="py-4 px-4 text-[16px] text-vellum-muted truncate max-w-[300px]" title="{{ $department->description }}">
                    {{ $department->description ?? '—' }}
                </td>

                <!-- Actions -->
                <td class="py-4 px-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('departments.edit', $department) }}"
                           class="px-3 py-1.5 bg-surface-raised hover:bg-surface-raised/80 text-vellum border border-hairline rounded text-[11px] uppercase tracking-wider transition">
                            Edit
                        </a>

                        <form method="POST"
                              action="{{ route('departments.destroy', $department) }}"
                              class="inline"
                              onsubmit="return confirm('Delete department {{ $department->name }}? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3 py-1.5 bg-burgundy-bg hover:bg-burgundy text-burgundy hover:text-canvas border border-burgundy/20 rounded text-[11px] uppercase tracking-wider transition">
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                    No departments found.
                </td>
            </tr>
        @endforelse
    </x-ledger-table>
</x-ledger-layout>