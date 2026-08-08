<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="plus" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('New Service') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <form method="POST" action="{{ route('services.store') }}" class="space-y-4">
                    @include('services._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
