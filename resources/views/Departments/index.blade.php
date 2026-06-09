<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Departments
        </h2>
    </x-slot>

    <div class="py-6 px-6">

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 rounded">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('departments.create') }}"
           class="bg-blue-500 text-white px-4 py-2 rounded">
            Create Department
        </a>

        <div class="mt-6">

            @forelse($departments as $department)

                <div class="border p-3 mb-2 rounded">

                    <h3>{{ $department->name }}</h3>

                    <p>{{ $department->code }}</p>

                    <p>{{ $department->description }}</p>

                </div>

            @empty

                <p>No departments found.</p>

            @endforelse

        </div>

    </div>
</x-app-layout>