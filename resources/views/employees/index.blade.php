<x-app-layout wide>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">Workforce</h1>
                <div class="text-[12.5px] text-vellum-faint mt-1.5 tracking-wide">
                    Active directory of organization members
                </div>
            </div>

            <a href="{{ route('employees.create') }}"
               class="inline-flex items-center px-4 py-2.5 bg-brass hover:bg-brass/90 text-canvas font-semibold rounded-md text-sm transition duration-200 shadow-md">
                Add Workforce Member
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="rounded-md bg-forest-bg border border-hairline text-forest px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('success_provisioned'))
                <div class="rounded-lg bg-surface border border-brass-dim p-6 shadow-md">
                    <h3 class="text-lg font-semibold text-brass mb-2">Workforce Member Provisioned Successfully!</h3>
                    <p class="text-sm text-vellum-muted mb-4">Please copy and communicate these temporary credentials to the new workforce member. They will only be shown once.</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-surface-raised p-4 rounded-md border border-hairline">
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

            <div class="panel space-y-4">
                <div class="panel-head flex items-center justify-between mb-4.5">
                    <h2 class="font-display font-medium text-[16px]">All Registered Members</h2>
                    <div class="meta font-mono text-[11px] text-vellum-faint">workforce database</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-hairline text-vellum-muted font-semibold">
                                <th class="py-3 px-4">Employee ID</th>
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-4">Email</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4">Assigned Manager</th>
                                <th class="py-3 px-4">Assigned Admin</th>
                                <th class="py-3 px-4">Leave Balance</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $employee)
                                <tr class="border-b border-hairline/50 hover:bg-brass/[0.06] transition duration-150">
                                    <td class="py-3 px-4 font-mono font-medium text-brass">
                                        {{ $employee->employee_id ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-vellum font-medium">
                                        {{ $employee->name }}
                                    </td>
                                    <td class="py-3 px-4 text-vellum select-all">
                                        {{ $employee->email }}
                                    </td>
                                    <td class="py-3 px-4 capitalize text-vellum-muted">
                                        {{ $employee->role }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="tag {{ $employee->status === 'active' ? 'present' : 'absent' }}">
                                            {{ $employee->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-vellum-muted">
                                        {{ $employee->department?->name ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-vellum-muted">
                                        {{ $employee->manager?->name ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-vellum-muted">
                                        {{ $employee->admin?->name ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-vellum-muted font-mono">
                                        {{ $employee->role !== 'admin' ? number_format($employee->leave_balance, 2) : 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('employees.show', $employee) }}"
                                               class="px-2.5 py-1 bg-brass hover:bg-brass/90 text-canvas font-semibold rounded text-xs transition">
                                                View Profile
                                            </a>

                                            <a href="{{ route('employees.edit', $employee) }}"
                                               class="px-2.5 py-1 bg-surface-raised hover:bg-surface-raised/80 text-vellum border border-hairline rounded text-xs transition">
                                                Edit
                                            </a>

                                            <form method="POST"
                                                  action="{{ route('employees.destroy', $employee) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('Delete {{ $employee->name }}? This action cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-2.5 py-1 bg-burgundy-bg hover:bg-burgundy text-burgundy hover:text-vellum border border-burgundy/30 rounded text-xs transition">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-8 px-4 text-center text-vellum-faint border border-dashed border-hairline-strong rounded-lg mt-1 text-[12px]">
                                        No workforce members found.
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