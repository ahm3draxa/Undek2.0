@props(['icon' => 'bolt', 'label', 'value', 'hint' => null, 'accent' => 'violet'])

@php
$gradients = [
    'violet' => 'linear-gradient(135deg,#7c5cff,#22d3ee)',
    'green' => 'linear-gradient(135deg,#34e0a1,#22d3ee)',
    'amber' => 'linear-gradient(135deg,#ffb020,#ff5da2)',
    'pink' => 'linear-gradient(135deg,#ff5da2,#7c5cff)',
];
$gradient = $gradients[$accent] ?? $gradients['violet'];
@endphp

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-5 relative transition-colors duration-200">
    <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:{{ $gradient }}"></div>
    <div class="flex items-center justify-between">
        <div class="text-xs font-jetbrains uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ $label }}</div>
        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:{{ $gradient }}">
            <x-icon :name="$icon" class="w-4.5 h-4.5 text-white" />
        </div>
    </div>
    <div class="mt-3 text-2xl font-space font-bold" style="background:{{ $gradient }};-webkit-background-clip:text;background-clip:text;color:transparent">{{ $value }}</div>
    @if ($hint)
        <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $hint }}</div>
    @endif
</div>
