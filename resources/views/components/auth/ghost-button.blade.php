<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 px-4 py-3 font-space text-sm font-semibold text-gray-200 transition-all duration-200 hover:border-white/30 hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-white/20']) }}>
    {{ $slot }}
</button>
