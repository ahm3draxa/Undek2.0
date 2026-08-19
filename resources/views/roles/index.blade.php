<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="shield-check" title="Roles & Permissions" subtitle="What each role can see and do across the portal." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="grid grid-cols-3 gap-4">
                <x-stat-card icon="shield-check" label="Roles" :value="$roles->count()" accent="violet" />
                <x-stat-card icon="check-circle" label="Permissions" :value="$permissions->count()" accent="green" />
                <x-stat-card icon="users" label="Assigned people" :value="$roles->sum('users_count')" accent="pink" />
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Each role's checked permissions control what that role can see and do across the portal.
                Assign a role to a person from <a href="{{ route('users.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">Users</a>.
            </p>

            @foreach ($roles as $role)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 transition-colors duration-200 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <h3 class="font-space font-semibold text-gray-900 dark:text-gray-100"><x-role-label :name="$role->name" /></h3>
                            <span class="text-xs font-jetbrains text-gray-400">{{ $role->users_count }} {{ Str::plural('person', $role->users_count) }}</span>
                        </div>

                        @if ($role->name === 'ceo')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                <x-icon name="shield-check" class="w-3.5 h-3.5" />
                                Always full access
                            </span>
                        @elseif (! in_array($role->name, ['admin', 'specialist', 'expert', 'employee', 'intern', 'client']) && $role->users_count === 0)
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-1 text-red-500 hover:text-red-600 hover:underline text-sm"><x-icon name="trash" class="w-4 h-4" />Delete</button>
                            </form>
                        @endif
                    </div>

                    @if ($role->name === 'ceo')
                        <p class="text-sm text-gray-400">The CEO role automatically holds every permission and can't be edited, so there's always at least one account that can't be locked out.</p>
                    @else
                        <form method="POST" action="{{ route('roles.update', $role) }}">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2 mb-4">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                            @checked($role->permissions->contains('name', $permission->name))
                                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                                        {{ ucfirst($permission->name) }}
                                    </label>
                                @endforeach
                            </div>
                            <x-secondary-button><x-icon name="check-circle" class="w-3.5 h-3.5" />Save permissions</x-secondary-button>
                        </form>
                    @endif
                </div>
            @endforeach

            <x-panel icon="plus" title="Create a custom role" accent="amber">
                <form method="POST" action="{{ route('roles.store') }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="name" value="Role name" />
                        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full max-w-xs" placeholder="e.g. finance_manager" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2 mb-4">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                                {{ ucfirst($permission->name) }}
                            </label>
                        @endforeach
                    </div>
                    <x-primary-button><x-icon name="plus" class="w-3.5 h-3.5" />Create role</x-primary-button>
                </form>
            </x-panel>
        </div>
    </div>
</x-app-layout>
