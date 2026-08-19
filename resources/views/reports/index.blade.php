<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="currency-dollar" title="Financial Reports" subtitle="Revenue, payroll, and run-rate — filterable by date range." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="GET" action="{{ route('reports.index') }}" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5 flex flex-wrap items-end gap-4 transition-colors duration-200">
                <div>
                    <x-input-label for="from" value="From" />
                    <x-text-input id="from" name="from" type="date" class="block mt-1" :value="request('from', $from->toDateString())" />
                </div>
                <div>
                    <x-input-label for="to" value="To" />
                    <x-text-input id="to" name="to" type="date" class="block mt-1" :value="request('to', $to->toDateString())" />
                </div>
                <x-secondary-button>Apply range</x-secondary-button>
                <div class="ml-auto flex gap-2">
                    <x-action-link href="{{ route('reports.revenue.csv', request()->query()) }}" icon="document-text" class="text-sm">Export revenue CSV</x-action-link>
                    <x-action-link href="{{ route('reports.payroll.csv', request()->query()) }}" icon="document-text" class="text-sm">Export payroll CSV</x-action-link>
                </div>
            </form>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card icon="document-text" label="Revenue (accepted quotes)" value="${{ number_format($totalRevenue, 2) }}" accent="violet" />
                <x-stat-card icon="currency-dollar" label="Payroll paid out" value="${{ number_format($totalPayroll, 2) }}" accent="amber" />
                <x-stat-card icon="check-circle" label="Net (revenue − payroll)" value="${{ number_format($totalRevenue - $totalPayroll, 2) }}" accent="green" />
                <x-stat-card icon="briefcase" label="Delivered projects" :value="$deliveredProjects" accent="pink" />
            </div>

            <x-panel icon="document-text" title="Revenue by service" accent="violet" :subtitle="$from->format('M j, Y') . ' – ' . $to->format('M j, Y')">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="py-2">Service</th>
                            <th class="py-2">Deals</th>
                            <th class="py-2 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($revenueByService as $row)
                            <tr>
                                <td class="py-2 text-gray-800 dark:text-gray-100">{{ $row->service }}</td>
                                <td class="py-2 text-gray-500 dark:text-gray-400">{{ $row->deals }}</td>
                                <td class="py-2 text-right font-medium text-gray-800 dark:text-gray-100">${{ number_format($row->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-gray-400">No accepted quotations in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-panel>

            <x-panel icon="currency-dollar" title="Payroll by type" accent="amber">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="py-2">Type</th>
                            <th class="py-2">Entries</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($payrollByType as $row)
                            <tr>
                                <td class="py-2 text-gray-800 dark:text-gray-100">{{ str_replace('_', ' ', ucfirst($row->type)) }}</td>
                                <td class="py-2 text-gray-500 dark:text-gray-400">{{ $row->entries }}</td>
                                <td class="py-2 text-right font-medium text-gray-800 dark:text-gray-100">${{ number_format($row->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-gray-400">No payroll adjustments in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-panel>

            <x-panel icon="bolt" title="Standing fixed-salary run-rate" accent="pink"
                subtitle="Sum of base_salary across all active (no end date) fixed-salary employees — a rough always-on cost floor. Hourly and percentage-based compensation isn't included since it depends on hours/project value actually logged.">
                <div class="text-2xl font-space font-bold text-gray-800 dark:text-gray-100">${{ number_format($activeCompensationRunRate, 2) }}</div>
            </x-panel>

        </div>
    </div>
</x-app-layout>
