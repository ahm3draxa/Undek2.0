<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                <x-icon name="users" class="w-5 h-5" style="color:#7c5cff" />
                {{ __('Employees') }}
            </h2>
            <a href="{{ route('employees.create') }}"><x-primary-button><x-icon name="plus" class="w-3.5 h-3.5" />Add employee</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-x-auto transition-colors duration-200">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Position</th>
                            <th class="px-6 py-3">Department</th>
                            <th class="px-6 py-3">Reports to</th>
                            <th class="px-6 py-3">Role</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($employees as $employee)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="px-6 py-4 font-medium">{{ $employee->name }}</td>
                                <td class="px-6 py-4">{{ $employee->employeeProfile->position->name ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $employee->employeeProfile->department->name ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $employee->employeeProfile->manager->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @foreach ($employee->roles as $role)
                                        <x-role-label :name="$role->name" />@if (! $loop->last), @endif
                                    @endforeach
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <x-action-link href="{{ route('employees.show', $employee) }}" icon="eye">View</x-action-link>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No employee profiles yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
