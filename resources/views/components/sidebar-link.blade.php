@props(['active' => false])

@php
$classes = $active
    ? 'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-300 border-cyan-400'
    : 'text-gray-600 dark:text-gray-300 border-transparent hover:bg-gray-50 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-gray-100';
@endphp

<a {{ $attributes->merge(['class' => "flex items-center gap-3 px-3 py-2.5 rounded-lg border-l-4 text-sm font-space font-medium transition duration-150 ease-in-out $classes"]) }}>
    {{ $slot }}
</a>
