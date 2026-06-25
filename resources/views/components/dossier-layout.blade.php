<x-app-layout>
    @isset($header)
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endisset

    <!-- Dossier Layout: Sidebar folder tab navigation + main details content -->
    <div class="flex flex-col lg:flex-row gap-9">
        <!-- Sidebar folder tabs (180px wide) -->
        @isset($tabs)
            <aside class="w-full lg:w-[180px] shrink-0 flex flex-row lg:flex-col gap-1 overflow-x-auto lg:overflow-x-visible">
                {{ $tabs }}
            </aside>
        @endisset

        <!-- Main details pane -->
        <div class="flex-1 bg-surface border border-hairline rounded p-7">
            {{ $slot }}
        </div>
    </div>
</x-app-layout>
