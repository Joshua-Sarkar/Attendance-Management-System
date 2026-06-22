@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-surface-raised border border-hairline text-vellum focus:border-brass/50 focus:ring-brass/30 focus:ring-1 rounded-md shadow-sm']) }}>
