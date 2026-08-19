@props(['status' => null])

@php $status = $status ?? session('status'); @endphp

@if ($status)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
        class="flex items-center gap-3 rounded-xl border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm shadow-sm">
        <span class="w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,#34e0a1,#22d3ee)">
            <x-icon name="check-circle" class="w-4 h-4 text-white" />
        </span>
        <span class="font-medium">{{ $status }}</span>
    </div>
@endif
