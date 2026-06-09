<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Departments
            </h2>

            <a href="{{ route('departments.create') }}"
               class="bg-blue-500 text-white px-4 py-2 rounded">
                Create Department
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
                                        Code
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Description
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">

                                @forelse($departments as $department)

                                    <tr>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $department->code }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            {{ $department->name }}
                                        </td>

                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ $department->description ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('departments.edit', $department) }}"
                                               class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('departments.destroy', $department) }}" class="inline">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                        class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Are you sure? This action cannot be undone.');">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No departments found.
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