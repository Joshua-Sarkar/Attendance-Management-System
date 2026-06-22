<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-burgundy hover:bg-burgundy/90 text-vellum border border-burgundy/30 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-burgundy/30 focus:ring-offset-2 focus:ring-offset-canvas transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
