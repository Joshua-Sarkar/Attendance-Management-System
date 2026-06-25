<x-app-layout>
    @isset($header)
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endisset

    <div class="space-y-6">
        @isset($filters)
            <div class="panel">
                {{ $filters }}
            </div>
        @endisset

        <div class="panel">
            @isset($ledgerHeader)
                <div class="panel-head">
                    {{ $ledgerHeader }}
                </div>
            @endisset

            <div class="ledger flex flex-col">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-app-layout>
