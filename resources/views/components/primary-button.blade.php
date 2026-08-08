<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-1.5 px-4 py-2 rounded-lg font-space font-bold text-xs uppercase tracking-widest text-[#08080c] transition-all duration-150 ease-in-out hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-cyan-400/40 focus:ring-offset-2 dark:focus:ring-offset-gray-800']) }}
    style="background:linear-gradient(120deg,#7c5cff,#22d3ee);box-shadow:0 4px 16px rgba(124,92,255,.35)">
    {{ $slot }}
</button>
