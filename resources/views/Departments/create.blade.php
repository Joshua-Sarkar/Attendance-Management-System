<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Department
        </h2>
    </x-slot>

    <div class="py-6 px-6">

        <form method="POST"
              action="{{ route('departments.store') }}">

            @csrf

            <div class="mb-4">
                <label>Name</label>

                <input
                    type="text"
                    name="name"
                    class="border p-2 w-full">
            </div>

            <div class="mb-4">
                <label>Code</label>

                <input
                    type="text"
                    name="code"
                    class="border p-2 w-full">
            </div>

            <div class="mb-4">
                <label>Description</label>

                <textarea
                    name="description"
                    class="border p-2 w-full"></textarea>
            </div>

            <button
                type="submit"
                class="bg-green-500 text-white px-4 py-2 rounded">
                Save Department
            </button>

        </form>

    </div>
</x-app-layout>