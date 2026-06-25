<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-burgundy-bg hover:bg-burgundy/10 text-burgundy border border-burgundy rounded font-bold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-burgundy/30 focus:ring-offset-2 focus:ring-offset-canvas transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
