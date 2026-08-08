<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="currency-dollar" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('Payroll') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-x-auto transition-colors duration-200">
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
