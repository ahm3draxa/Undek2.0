<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="shield-check" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('Roles & Permissions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Each role's checked permissions control what that role can see and do across the portal.
                Assign a role to a person from <a href="{{ route('users.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">Users</a>.
            </p>

            @foreach ($roles as $role)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 transition-colors duration-200">
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

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 border border-dashed border-gray-300 dark:border-gray-600 transition-colors duration-200">
                <h3 class="font-space font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    <x-icon name="plus" class="w-4 h-4" style="color:#7c5cff" />
                    Create a custom role
                </h3>
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
            </div>
        </div>
    </div>
</x-app-layout>
