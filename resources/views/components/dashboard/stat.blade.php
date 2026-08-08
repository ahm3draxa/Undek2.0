@props(['label', 'bind', 'prefix' => '', 'icon' => 'bolt'])

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6 transition-colors duration-200">
    <div class="flex items-center justify-between">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,rgba(124,92,255,.14),rgba(34,211,238,.14))">
            <x-icon :name="$icon" class="w-5 h-5" style="color:#7c5cff" />
        </div>
    </div>
    <div class="mt-3 text-3xl font-space font-bold" style="background:linear-gradient(120deg,#7c5cff,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent">
        <span x-show="stats.{{ $bind }} !== undefined">{{ $prefix }}<span x-text="stats.{{ $bind }}"></span></span>
        <span x-show="stats.{{ $bind }} === undefined" class="text-gray-300 dark:text-gray-600">&mdash;</span>
    </div>
</div>
