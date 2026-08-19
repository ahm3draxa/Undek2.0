<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="pencil" title="Edit Service" />
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 transition-colors duration-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                <form method="POST" action="{{ route('services.update', $service) }}" class="space-y-4">
                    @include('services._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
