@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-surface-raised border border-hairline text-vellum focus:border-brass focus:ring-brass focus:ring-1 rounded shadow-sm']) }}>
