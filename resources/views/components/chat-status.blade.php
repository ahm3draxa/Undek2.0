{{-- Live/fast/slow indicator for <x-chat>; reads the parent chat's Alpine state. --}}
<span class="inline-flex items-center gap-1.5 text-[11px] shrink-0" :class="error ? 'text-red-500 dark:text-red-400' : 'text-gray-400 dark:text-gray-500'">
    <span class="w-1.5 h-1.5 rounded-full" :class="error ? 'bg-red-500' : (fast ? 'bg-emerald-400' : 'bg-gray-300 dark:bg-gray-600')"></span>
    <span x-text="error ? 'Connection issue — retrying' : (fast ? 'Live' : 'Checks every minute')"></span>
</span>
