@props(['links'])

<div class="flex flex-wrap gap-3">
    @foreach ($links as $link)
        @if (\Illuminate\Support\Facades\Route::has($link['route']))
            <a href="{{ route($link['route']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-space font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:border-cyan-400/60 hover:text-cyan-600 dark:hover:text-cyan-300 transition">
                <x-icon :name="$link['icon'] ?? 'arrow-right'" class="w-4 h-4" />
                {{ $link['label'] }}
            </a>
        @endif
    @endforeach
</div>
