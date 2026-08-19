<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="shield-check" title="Users & Roles" subtitle="Every account on the platform and what they're allowed to do." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-stat-card icon="users" label="Total users" :value="$stats['total']" accent="violet" />
                <x-stat-card icon="check-circle" label="Active" :value="$stats['active']" accent="green" />
                <x-stat-card icon="x-circle" label="Deactivated" :value="$stats['deactivated']" accent="pink" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-x-auto transition-colors duration-200">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Role</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($users as $user)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="px-6 py-4 font-medium">{{ $user->name }}</td>
                                <td class="px-6 py-4">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="{{ route('users.role', $user) }}">
                                        @csrf @method('PATCH')
                                        <select name="role" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ $role->name === 'ceo' ? 'CEO' : \Illuminate\Support\Str::of($role->name)->replace('_', ' ')->title() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($user->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Deactivated</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-4">
                                        <form method="POST" action="{{ route('users.reset-password', $user) }}" onsubmit="return confirm('Reset {{ $user->name }}\'s password? Their current password stops working immediately.')">
                                            @csrf
                                            <button class="inline-flex items-center gap-1 text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 hover:underline">
                                                <x-icon name="key" class="w-4 h-4" />
                                                Reset password
                                            </button>
                                        </form>
                                        @unless ($user->id === auth()->id())
                                            <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                                @csrf @method('PATCH')
                                                <button class="inline-flex items-center gap-1 text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 hover:underline">
                                                    <x-icon :name="$user->is_active ? 'x-circle' : 'check-circle'" class="w-4 h-4" />
                                                    {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
