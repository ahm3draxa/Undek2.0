@props(['links'])

<div class="flex flex-wrap gap-3">
    @foreach ($links as $link)
        @if (\Illuminate\Support\Facades\Route::has($link['route']))
            <a href="{{ route($link['route']) }}"
               class="group inline-flex items-center gap-2.5 pl-2 pr-4 py-2 rounded-full text-sm font-space font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:border-transparent hover:shadow-md transition-all duration-150">
                <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 transition-transform group-hover:scale-110" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">
                    <x-icon :name="$link['icon'] ?? 'arrow-right'" class="w-3.5 h-3.5 text-white" />
                </span>
                {{ $link['label'] }}
            </a>
        @endif
    @endforeach
</div>
