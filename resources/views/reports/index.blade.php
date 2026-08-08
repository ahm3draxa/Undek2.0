<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="currency-dollar" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('Financial Reports') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="GET" action="{{ route('reports.index') }}" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 flex flex-wrap items-end gap-4 transition-colors duration-200">
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
                @php
                    $tiles = [
                        ['Revenue (accepted quotes)', '$' . number_format($totalRevenue, 2)],
                        ['Payroll paid out', '$' . number_format($totalPayroll, 2)],
                        ['Net (revenue − payroll)', '$' . number_format($totalRevenue - $totalPayroll, 2)],
                        ['Delivered projects', $deliveredProjects],
                    ];
                @endphp
                @foreach ($tiles as [$label, $value])
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 transition-colors duration-200">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        <div class="mt-3 text-2xl font-space font-bold" style="background:linear-gradient(120deg,#7c5cff,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Revenue by service</h3>
                    <span class="text-xs text-gray-400">{{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }}</span>
                </div>
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
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Payroll by type</h3>
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
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 transition-colors duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Standing fixed-salary run-rate</h3>
                <p class="text-xs text-gray-400 mb-3">Sum of <code class="font-jetbrains">base_salary</code> across all active (no end date) fixed-salary employees — a rough always-on cost floor. Hourly and percentage-based compensation isn't included since it depends on hours/project value actually logged.</p>
                <div class="text-2xl font-space font-bold text-gray-800 dark:text-gray-100">${{ number_format($activeCompensationRunRate, 2) }}</div>
            </div>

        </div>
    </div>
</x-app-layout>
