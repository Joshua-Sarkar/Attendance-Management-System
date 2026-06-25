<x-app-layout>
    @isset($header)
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endisset

    <!-- Workflow Layout: Clean input columns & split sections -->
    <div class="max-w-[720px] mx-auto bg-surface border border-hairline rounded p-8">
        {{ $slot }}
    </div>
</x-app-layout>
