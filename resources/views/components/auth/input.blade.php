@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 outline-none transition focus:border-cyan-400/60 focus:ring-2 focus:ring-cyan-400/20 disabled:opacity-40']) }}>
