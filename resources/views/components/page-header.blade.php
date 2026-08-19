@props(['icon' => 'bolt', 'title', 'subtitle' => null])

<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,#7c5cff,#22d3ee);box-shadow:0 4px 14px rgba(124,92,255,.35)">
            <x-icon :name="$icon" class="w-5 h-5 text-white" />
        </div>
        <div>
            <h2 class="font-space font-bold text-xl text-gray-800 dark:text-gray-100 leading-tight">{{ $title }}</h2>
            @if ($subtitle)
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
