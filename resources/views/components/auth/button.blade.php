<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-[#7c5cff] to-[#22d3ee] px-4 py-3 font-space text-sm font-bold text-[#08080c] shadow-[0_10px_34px_rgba(124,92,255,0.35)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_16px_44px_rgba(124,92,255,0.5)] focus:outline-none focus:ring-2 focus:ring-cyan-400/40 disabled:opacity-50 disabled:hover:translate-y-0']) }}>
    {{ $slot }}
</button>
