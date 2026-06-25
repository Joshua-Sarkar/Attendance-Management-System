<x-app-layout>
    @isset($header)
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endisset
    
    <div class="space-y-9">
        {{ $slot }}
    </div>
</x-app-layout>
