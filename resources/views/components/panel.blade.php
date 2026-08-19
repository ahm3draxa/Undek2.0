@props(['icon' => null, 'title' => null, 'subtitle' => null, 'accent' => 'violet'])

@php
$gradients = [
    'violet' => 'linear-gradient(135deg,#7c5cff,#22d3ee)',
    'green' => 'linear-gradient(135deg,#34e0a1,#22d3ee)',
    'amber' => 'linear-gradient(135deg,#ffb020,#ff5da2)',
    'pink' => 'linear-gradient(135deg,#ff5da2,#7c5cff)',
];
$gradient = $gradients[$accent] ?? $gradients['violet'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 transition-colors duration-200 relative overflow-hidden']) }}>
    <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:{{ $gradient }}"></div>
    @if ($title)
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    @if ($icon)
                        <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background:{{ $gradient }}">
                            <x-icon :name="$icon" class="w-3.5 h-3.5 text-white" />
                        </span>
                    @endif
                    {{ $title }}
                </h3>
                @if ($subtitle)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</div>
