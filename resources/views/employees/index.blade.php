<x-ledger-layout :timeline="false">
    <x-slot name="header">
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center gap-4">
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Workforce</h1>
                <x-primary-button onclick="window.location.href='{{ route('employees.create') }}'">
                    Add Workforce Member
                </x-primary-button>
            </div>
            <div class="text-[13px] text-vellum-muted tracking-wide">
                Active directory of organization members · Personnel registry
            </div>
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

    <!-- High-Density Filters Strip -->
    <x-slot name="filters">
        <form method="GET" action="{{ route('employees.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-7 gap-4 items-end p-5 bg-surface border border-hairline rounded">
            <!-- Search Box -->
            <div>
                <x-input-label for="search" value="Search Member" />
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Name, email, ID..."
                       class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm placeholder-vellum-faint focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none h-[38px]">
            </div>

            <!-- Department Filter -->
            <div>
                <x-input-label for="department_id" value="Department" />
                <select name="department_id" id="department_id"
                        class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none h-[38px]">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Manager Filter -->
            <div>
                <x-input-label for="manager_id" value="Assigned Manager" />
                <select name="manager_id" id="manager_id"
                        class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none h-[38px]">
                    <option value="">All Managers</option>
                    @foreach($managers as $mgr)
                        <option value="{{ $mgr->id }}" {{ request('manager_id') == $mgr->id ? 'selected' : '' }}>
                            {{ $mgr->name }} ({{ $mgr->employee_id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sort By -->
            <div>
                <x-input-label for="sort_by" value="Sort By" />
                <select name="sort_by" id="sort_by" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brass h-[38px]">
                    <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Name</option>
                    <option value="employee_id" {{ request('sort_by') === 'employee_id' ? 'selected' : '' }}>Employee ID</option>
                    <option value="joining_date" {{ request('sort_by') === 'joining_date' ? 'selected' : '' }}>Joining Date</option>
                    <option value="status" {{ request('sort_by') === 'status' ? 'selected' : '' }}>Status</option>
                    <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>Date Added</option>
                </select>
            </div>

            <!-- Order -->
            <div>
                <x-input-label for="sort_dir" value="Order" />
                <select name="sort_dir" id="sort_dir" class="w-full bg-surface-raised border border-hairline rounded text-vellum px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brass h-[38px]">
                    <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>ASC</option>
                    <option value="desc" {{ request('sort_dir', 'desc') === 'desc' ? 'selected' : '' }}>DESC</option>
                </select>
            </div>

            <!-- Filter Button -->
            <div>
                <x-primary-button type="submit" class="w-full !h-[38px] justify-center">
                    Filter
                </x-primary-button>
            </div>

            <!-- Reset Button -->
            <div>
                <x-secondary-button type="button" class="w-full !h-[38px] justify-center" onclick="window.location.href='{{ route('employees.index') }}'">
                    Reset
                </x-secondary-button>
            </div>
        </form>
    </x-slot>

    <x-slot name="ledgerHeader">
        <h2>All Registered Members</h2>
        <div class="meta font-mono text-[11px] text-vellum-faint">showing registry results</div>
    </x-slot>

    @php
        $headers = [
            ['label' => 'Employee ID', 'class' => ''],
            ['label' => 'Employee Name', 'class' => ''],
            ['label' => 'Department', 'class' => ''],
            ['label' => 'Assigned Manager', 'class' => ''],
            ['label' => 'Designation', 'class' => ''],
            ['label' => 'Phone', 'class' => 'hidden xl:table-cell'],
            ['label' => 'Email', 'class' => 'hidden lg:table-cell'],
            ['label' => 'Status', 'class' => ''],
            ['label' => 'Joining Date', 'class' => 'hidden md:table-cell'],
            ['label' => 'Actions', 'class' => 'text-right']
        ];
    @endphp

    <x-ledger-table :headers="$headers">
        @forelse($employees as $employee)
            <tr class="hover:bg-brass/[0.04] transition duration-150 text-[16px]">
                <!-- Employee ID -->
                <td class="py-4 px-4 font-mono text-[16px] text-brass select-all font-medium">
                    {{ $employee->employee_id ?? 'N/A' }}
                </td>
                
                <!-- Employee Name -->
                <td class="py-4 px-4 text-[18px] font-bold text-vellum">
                    <a href="{{ route('employees.show', $employee) }}" class="hover:text-brass transition-colors">{{ $employee->name }}</a>
                </td>

                <!-- Department -->
                <td class="py-4 px-4 text-[16px] text-vellum font-medium">
                    {{ $employee->department?->name ?? 'None' }}
                </td>

                <!-- Assigned Manager -->
                <td class="py-4 px-4 text-[16px] text-vellum-muted">
                    {{ $employee->manager?->name ?? 'None' }}
                </td>

                <!-- Designation -->
                <td class="py-4 px-4 text-[15px] md:text-[16px] text-vellum-muted">
                    {{ $employee->employeeProfile?->designation ?? 'None' }}
                </td>

                <!-- Phone -->
                <td class="py-4 px-4 text-[16px] text-vellum-muted font-mono select-all hidden xl:table-cell">
                    {{ $employee->phone ?? '—' }}
                </td>

                <!-- Email -->
                <td class="py-4 px-4 text-[15px] text-vellum-muted truncate max-w-[200px] select-all hidden lg:table-cell" title="{{ $employee->email }}">
                    {{ $employee->email }}
                </td>

                <!-- Status -->
                <td class="py-4 px-4">
                    <span class="tag {{ $employee->status === 'active' ? 'present' : 'absent' }} text-[11px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border
                        @if($employee->status === 'active') bg-forest-bg text-forest border-transparent
                        @else bg-burgundy-bg text-burgundy border-transparent @endif">
                        {{ $employee->status }}
                    </span>
                </td>

                <!-- Joining Date -->
                <td class="py-4 px-4 text-[16px] text-vellum-muted font-mono hidden md:table-cell">
                    {{ $employee->joining_date ? $employee->joining_date->format('Y-m-d') : '—' }}
                </td>

                <!-- Actions -->
                <td class="py-4 px-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('employees.show', $employee) }}"
                           class="px-3 py-1.5 bg-brass hover:bg-brass/90 text-canvas font-semibold rounded text-[11px] uppercase tracking-wider transition">
                            View
                        </a>

                        <a href="{{ route('employees.edit', $employee) }}"
                           class="px-3 py-1.5 bg-surface-raised hover:bg-surface-raised/80 text-vellum border border-hairline rounded text-[11px] uppercase tracking-wider transition">
                            Edit
                        </a>

                        @if(auth()->user()->role === 'admin' && auth()->user()->id !== $employee->id)
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('WARNING: This action is permanent. Deleting this employee will permanently remove their employee record, payroll profile, leave balances, salary history, external identifiers, import metadata, attendance records, leave requests, and all related audit trails. This action CANNOT be undone. Are you sure you want to permanently delete this employee?');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <x-danger-button type="submit" class="!px-3 !py-1.5 !text-[11px] uppercase tracking-wider transition">
                                    Delete
                                </x-danger-button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="py-8 text-center text-vellum-faint border border-dashed border-hairline-strong rounded mt-1 text-[13px]">
                    No workforce members match the search and filters.
                </td>
            </tr>
        @endforelse
    </x-ledger-table>

    <!-- Pagination Strip -->
    @if($employees->hasPages())
        <div class="mt-6 pt-4 border-t border-hairline">
            {{ $employees->links() }}
        </div>
    @endif
</x-ledger-layout>