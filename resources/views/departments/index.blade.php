<x-app-layout wide>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-vellum leading-tight font-display">
                Departments
            </h2>

            <a href="{{ route('departments.create') }}"
               class="inline-flex items-center px-4 py-2 bg-brass hover:bg-brass/90 text-canvas font-bold uppercase tracking-widest rounded-md text-xs transition duration-150">
                Create Department
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full">

            @if(session('success'))
                <div class="mb-4 rounded-md bg-forest-bg border border-forest/30 text-forest px-4 py-3 shadow-sm text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="panel overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline">
                        <thead class="bg-surface-raised">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-vellum-muted uppercase tracking-wider">
                                    Code
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-vellum-muted uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-vellum-muted uppercase tracking-wider">
                                    Description
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-vellum-muted uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @forelse($departments as $department)
                                <tr class="hover:bg-surface-raised/40 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-medium text-brass">
                                        {{ $department->code }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-vellum font-semibold">
                                        {{ $department->name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-vellum-muted">
                                        {{ $department->description ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('departments.edit', $department) }}"
                                           class="text-brass hover:underline mr-4 transition">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('departments.destroy', $department) }}" class="inline">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="text-burgundy hover:underline transition"
                                                    onclick="return confirm('Are you sure? This action cannot be undone.');">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-vellum-faint">
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
</x-app-layout>