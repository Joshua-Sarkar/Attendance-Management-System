@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-medium text-vellum-muted uppercase tracking-wider mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
