<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="currency-dollar" title="Payroll" subtitle="Every salary change, bonus, deduction, and reimbursement on record." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card icon="document-text" label="Total entries" :value="$stats['total']" accent="violet" />
                <x-stat-card icon="currency-dollar" label="This month" value="${{ number_format($stats['this_month'], 2) }}" accent="green" />
                <x-stat-card icon="sparkles" label="Bonuses" :value="$stats['bonuses']" accent="amber" />
                <x-stat-card icon="clock" label="Deductions" :value="$stats['deductions']" accent="pink" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-x-auto transition-colors duration-200">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-6 py-3">Employee</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Effective</th>
                            <th class="px-6 py-3">Recorded by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($adjustments as $adjustment)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="px-6 py-4 font-medium">
                                    <a href="{{ route('employees.show', $adjustment->user) }}" class="hover:underline">{{ $adjustment->user->name }}</a>
                                </td>
                                <td class="px-6 py-4">{{ str_replace('_', ' ', ucfirst($adjustment->type)) }}</td>
                                <td class="px-6 py-4">{{ $adjustment->currency }} {{ number_format($adjustment->amount, 2) }}</td>
                                <td class="px-6 py-4">{{ $adjustment->effective_date->format('M j, Y') }}</td>
                                <td class="px-6 py-4">{{ $adjustment->createdBy->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-gray-400">No payroll activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $adjustments->links() }}
        </div>
    </div>
</x-app-layout>
