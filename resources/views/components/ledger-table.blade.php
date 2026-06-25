@props(['headers' => []])

<div class="w-full overflow-x-auto lg:overflow-x-visible">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-surface-raised/85 border-b-2 border-hairline-strong">
                @foreach($headers as $header)
                    <th class="py-3.5 px-4 font-sans text-[13px] md:text-[14px] font-bold uppercase tracking-wider text-vellum {{ $header['class'] ?? '' }}">
                        {{ $header['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-hairline">
            {{ $slot }}
        </tbody>
    </table>
</div>
