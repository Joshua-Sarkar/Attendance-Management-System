<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-brass hover:bg-brass/90 text-canvas font-bold uppercase tracking-widest rounded-md focus:outline-none focus:ring-2 focus:ring-brass/30 focus:ring-offset-2 focus:ring-offset-canvas transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
