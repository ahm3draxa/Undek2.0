@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-jetbrains text-[11px] uppercase tracking-widest text-gray-400 mb-2']) }}>
    {{ $value ?? $slot }}
</label>
