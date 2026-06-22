@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-vellum-muted']) }}>
    {{ $value ?? $slot }}
</label>
