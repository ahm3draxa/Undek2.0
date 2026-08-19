<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="cog" title="Services" subtitle="Your catalog — what clients can request a quote for.">
            <x-slot name="actions">
                <a href="{{ route('services.create') }}"><x-primary-button><x-icon name="plus" class="w-3.5 h-3.5" />Add service</x-primary-button></a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-stat-card icon="cog" label="Total services" :value="$services->count()" accent="violet" />
                <x-stat-card icon="check-circle" label="Active" :value="$services->where('is_active', true)->count()" accent="green" />
                <x-stat-card icon="document-text" label="Total requests" :value="$services->sum('quote_requests_count')" accent="amber" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse ($services as $service)
                    <div class="relative bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 flex flex-col gap-4 transition-colors duration-200 border border-transparent hover:border-cyan-400/40">
                        <div class="absolute top-0 left-0 right-0 h-1 rounded-t-xl" style="background:linear-gradient(90deg,#7c5cff,#22d3ee,transparent)"></div>

                        <div class="flex items-start justify-between">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,rgba(124,92,255,.14),rgba(34,211,238,.14))">
                                <x-icon :name="$service->icon" class="w-6 h-6" style="color:#7c5cff" />
                            </div>
                            @if ($service->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                            @endif
                        </div>

                        <div>
                            <h3 class="font-space font-semibold text-gray-900 dark:text-gray-100">{{ $service->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 line-clamp-3">{{ $service->description ?: 'No description yet.' }}</p>
                        </div>

                        <div class="mt-auto flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                            <span class="font-jetbrains text-xs text-gray-400">{{ $service->quote_requests_count }} {{ Str::plural('request', $service->quote_requests_count) }}</span>
                            <div class="flex items-center gap-3">
                                <x-action-link href="{{ route('services.edit', $service) }}" icon="pencil">Edit</x-action-link>
                                <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Remove this service?')">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex items-center gap-1 text-red-500 hover:text-red-600 hover:underline"><x-icon name="trash" class="w-4 h-4" />Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 col-span-full text-center py-10">No services yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
