<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employees') }}
            </h2>

            <a href="{{ route('employees.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                Create Employee
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-100 border border-green-300 text-green-700 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">

                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee ID
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Department
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Manager
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">

                                @forelse($employees as $employee)

                                    <tr>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $employee->employee_id ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $employee->name }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $employee->email }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ ucfirst($employee->role) }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $employee->status === 'active'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($employee->status) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $employee->department?->name ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $employee->manager?->name ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">

                                                <a href="{{ route('employees.edit', $employee) }}"
                                                   class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">
                                                    Edit
                                                </a>

                                                <form method="POST"
                                                      action="{{ route('employees.destroy', $employee) }}"
                                                      class="inline"
                                                      onsubmit="return confirm('Delete {{ $employee->name }}? This action cannot be undone.')">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                            class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                                        Delete
                                                    </button>

                                                </form>

                                            </div>
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                            No employees found.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>