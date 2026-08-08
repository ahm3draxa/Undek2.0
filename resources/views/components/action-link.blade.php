@props(['icon' => 'chevron-right'])

<a {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 hover:underline']) }}>
    <x-icon :name="$icon" class="w-4 h-4" />
    {{ $slot }}
</a>
