<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="document-text" title="Quote Requests" subtitle="Incoming work, routed by skill to the right specialist." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card icon="document-text" label="Total" :value="$stats['total']" accent="violet" />
                <x-stat-card icon="clock" label="Pending" :value="$stats['pending']" accent="amber" />
                <x-stat-card icon="currency-dollar" label="Quoted" :value="$stats['quoted']" accent="pink" />
                <x-stat-card icon="check-circle" label="Converted" :value="$stats['converted']" accent="green" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-x-auto transition-colors duration-200">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-6 py-3">Title</th>
                            <th class="px-6 py-3">Service</th>
                            <th class="px-6 py-3">Requester</th>
                            <th class="px-6 py-3">Assigned</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($quoteRequests as $quoteRequest)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="px-6 py-4 font-medium">{{ $quoteRequest->title }}</td>
                                <td class="px-6 py-4">{{ $quoteRequest->service->name }}</td>
                                <td class="px-6 py-4">{{ $quoteRequest->client->name ?? $quoteRequest->guest_name }}</td>
                                <td class="px-6 py-4">{{ $quoteRequest->assignedSpecialist->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @class([
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' => $quoteRequest->status === 'pending',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' => $quoteRequest->status === 'quoted',
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => in_array($quoteRequest->status, ['accepted', 'converted']),
                                            'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' => $quoteRequest->status === 'rejected',
                                        ])">
                                        {{ ucfirst($quoteRequest->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <x-action-link href="{{ route('quote-requests.show', $quoteRequest) }}" icon="eye">View</x-action-link>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">No quote requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $quoteRequests->links() }}
        </div>
    </div>
</x-app-layout>
