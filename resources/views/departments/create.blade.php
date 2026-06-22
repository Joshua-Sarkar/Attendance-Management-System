<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vellum leading-tight font-display">
            Create Department
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel overflow-hidden shadow-sm border border-hairline">
                <form method="POST" action="{{ route('departments.store') }}">
                    @csrf

                    <div class="mb-6">
                        <label for="name" class="block text-sm font-semibold text-vellum-muted mb-1">
                            Name
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 rounded-md shadow-sm px-3 py-2"
                            required>
                        @error('name')
                            <p class="mt-2 text-sm text-burgundy">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="code" class="block text-sm font-semibold text-vellum-muted mb-1">
                            Code
                        </label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            value="{{ old('code') }}"
                            class="mt-1 block w-full bg-surface-raised border border-hairline text-brass font-mono focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 rounded-md shadow-sm px-3 py-2"
                            required>
                        @error('code')
                            <p class="mt-2 text-sm text-burgundy">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-semibold text-vellum-muted mb-1">
                            Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 rounded-md shadow-sm px-3 py-2">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-burgundy">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-4">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-brass hover:bg-brass/90 text-canvas font-bold uppercase tracking-widest rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brass/30 focus:ring-offset-2 focus:ring-offset-canvas transition duration-150">
                            Create Department
                        </button>

                        <a href="{{ route('departments.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-surface-raised hover:bg-surface-raised/80 text-vellum border border-hairline rounded-md font-semibold text-xs uppercase tracking-widest transition duration-150">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>