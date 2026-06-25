<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-surface-raised hover:bg-surface-raised/80 text-vellum border border-hairline rounded font-bold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-brass/30 focus:ring-offset-2 focus:ring-offset-canvas disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
