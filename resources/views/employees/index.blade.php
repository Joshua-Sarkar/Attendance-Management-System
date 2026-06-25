<x-ledger-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Workforce</h1>
                <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
                    Active directory of organization members · Personnel registry
                </div>
            </div>

            <x-primary-button onclick="window.location.href='{{ route('employees.create') }}'">
                Add Workforce Member
            </x-primary-button>
        </div>
    </x-slot>

    <!-- Session Notifications -->
    @if(session('success'))
        <div class="rounded bg-forest-bg border border-hairline text-forest px-4 py-3 text-sm mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('success_provisioned'))
        <div class="rounded bg-surface border border-brass p-6 shadow-md mb-6">
            <h3 class="font-display font-medium text-[18px] text-brass mb-2">Workforce Member Provisioned Successfully!</h3>
            <p class="text-sm text-vellum-muted mb-4">Please copy and communicate these temporary credentials to the new workforce member. They will only be shown once.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-surface-raised p-4 rounded border border-hairline">
                <div>
                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Name</span>
                    <p class="text-sm font-medium text-vellum mt-1">{{ session('success_provisioned')['name'] }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Employee ID (Username)</span>
                    <p class="text-sm font-medium text-brass mt-1 select-all font-mono">{{ session('success_provisioned')['employee_id'] }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Temporary Password</span>
                    <p class="text-sm font-medium text-brass mt-1 select-all font-mono">{{ session('success_provisioned')['password'] }}</p>
                </div>
            </div>
        </div>
    @endif

    <x-slot name="ledgerHeader">
        <h2>All Registered Members</h2>
        <div class="meta">workforce database</div>
    </x-slot>

    @forelse($employees as $employee)
        <div class="ledger-row grid grid-cols-[100px_2fr_1.8fr_1fr_100px_100px_180px] items-center py-4 px-2 border-b border-hairline last:border-none hover:bg-brass/[0.04] transition duration-150">
            <!-- ID -->
            <span class="font-mono text-[13px] font-medium text-brass">{{ $employee->employee_id ?? 'N/A' }}</span>
            
            <!-- Identity -->
            <div class="flex flex-col gap-0.5">
                <span class="text-[14.5px] font-semibold text-vellum">
                    <a href="{{ route('employees.show', $employee) }}" class="hover:text-brass transition-colors">{{ $employee->name }}</a>
                </span>
                <span class="text-[12px] text-vellum-muted select-all truncate max-w-[200px]">{{ $employee->email }}</span>
            </div>

            <!-- Dept / Manager -->
            <div class="flex flex-col gap-0.5">
                <span class="text-[13px] text-vellum font-medium">{{ $employee->department?->name ?? 'No Department' }}</span>
                <span class="text-[11.5px] text-vellum-faint">Mgr: {{ $employee->manager?->name ?? 'None' }}</span>
            </div>

            <!-- Role -->
            <span class="text-[13px] capitalize text-vellum-muted">{{ $employee->role }}</span>

            <!-- Status -->
            <div>
                <span class="tag {{ $employee->status === 'active' ? 'present' : 'absent' }} text-[10.5px] font-mono uppercase tracking-[0.8px] px-2 py-0.5 rounded border
                    @if($employee->status === 'active') bg-forest-bg text-forest border-transparent
                    @else bg-burgundy-bg text-burgundy border-transparent @endif">
                    {{ $employee->status }}
                </span>
            </div>

            <!-- Leave Balance -->
            <span class="font-mono text-[13px] text-vellum-muted text-right pr-4">
                {{ $employee->role !== 'admin' ? number_format($employee->leave_balance, 1) : '—' }}
            </span>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('employees.show', $employee) }}"
                   class="px-2.5 py-1 bg-brass hover:bg-brass/90 text-canvas font-semibold rounded text-[11px] uppercase tracking-wider transition">
                    View
                </a>

                <a href="{{ route('employees.edit', $employee) }}"
                   class="px-2.5 py-1 bg-surface-raised hover:bg-surface-raised/80 text-vellum border border-hairline rounded text-[11px] uppercase tracking-wider transition">
                    Edit
                </a>

                <form method="POST"
                      action="{{ route('employees.destroy', $employee) }}"
                      class="inline"
                      onsubmit="return confirm('Delete {{ $employee->name }}? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-2.5 py-1 bg-burgundy-bg hover:bg-burgundy text-burgundy hover:text-canvas border border-burgundy/20 rounded text-[11px] uppercase tracking-wider transition">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="empty-cta py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[12px]">
            No workforce members found.
        </div>
    @endforelse
</x-ledger-layout>