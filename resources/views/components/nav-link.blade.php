@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 pt-1 border-b-2 border-cyan-400 text-sm font-space font-medium leading-5 text-gray-900 dark:text-gray-100 focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-3 pt-1 border-b-2 border-transparent text-sm font-space font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-300 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
