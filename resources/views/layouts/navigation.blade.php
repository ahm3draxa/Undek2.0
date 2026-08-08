<div x-data="{ open: false }">

    <!-- Desktop sidebar -->
    <aside class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:w-64 bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 transition-colors duration-200 z-30">
        <div class="h-16 flex items-center gap-2.5 px-5 border-b border-gray-100 dark:border-gray-700">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="block w-7 h-7 rounded-lg rotate-12" style="background:linear-gradient(135deg,#7c5cff,#22d3ee);box-shadow:0 0 14px rgba(124,92,255,.5)"></span>
                <span class="font-space font-bold text-lg tracking-tight text-gray-900 dark:text-gray-100">undek</span>
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @include('layouts.nav-items')
        </nav>

        <div class="border-t border-gray-100 dark:border-gray-700 p-3 space-y-1">
            <button
                @click="$store.theme.toggle()"
                type="button"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-space font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition"
            >
                <svg x-show="!$store.theme.dark" x-cloak class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36 6.36l-.71-.71M6.34 6.34l-.71-.71m12.02 0l-.71.71M6.34 17.66l-.71.71M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <span x-text="$store.theme.dark ? 'Light mode' : 'Dark mode'"></span>
            </button>

            <x-dropdown align="left" width="56">
                <x-slot name="trigger">
                    <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-space font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                        @if (Auth::user()->avatarUrl())
                            <img src="{{ Auth::user()->avatarUrl() }}" alt="{{ Auth::user()->name }}" class="w-5 h-5 rounded-full object-cover shrink-0">
                        @else
                            <x-icon name="user-circle" class="w-5 h-5 shrink-0" />
                        @endif
                        <span class="truncate">{{ Auth::user()->name }}</span>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">
                        <x-icon name="user-circle" class="w-4 h-4 me-1.5 inline -mt-0.5" />{{ __('Profile') }}
                    </x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            <x-icon name="logout" class="w-4 h-4 me-1.5 inline -mt-0.5" />{{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </aside>

    <!-- Mobile top bar -->
    <div class="lg:hidden flex items-center justify-between h-16 px-4 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 transition-colors duration-200">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <span class="block w-7 h-7 rounded-lg rotate-12" style="background:linear-gradient(135deg,#7c5cff,#22d3ee);box-shadow:0 0 14px rgba(124,92,255,.5)"></span>
            <span class="font-space font-bold text-lg tracking-tight text-gray-900 dark:text-gray-100">undek</span>
        </a>
        <div class="flex items-center gap-1">
            <button
                @click="$store.theme.toggle()"
                type="button"
                class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out"
                aria-label="Toggle dark mode"
            >
                <svg x-show="!$store.theme.dark" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36 6.36l-.71-.71M6.34 6.34l-.71-.71m12.02 0l-.71.71M6.34 17.66l-.71.71M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
            <button @click="open = true" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile drawer -->
    <div x-show="open" x-cloak class="lg:hidden fixed inset-0 z-40">
        <div x-show="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div x-show="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-white dark:bg-gray-800 shadow-xl flex flex-col transition-colors duration-200">
            <div class="h-16 flex items-center justify-between px-5 border-b border-gray-100 dark:border-gray-700">
                <span class="font-space font-bold text-lg tracking-tight text-gray-900 dark:text-gray-100">undek</span>
                <button @click="open = false" class="p-2 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" @click="open = false">
                @include('layouts.nav-items')
            </nav>

            <div class="border-t border-gray-100 dark:border-gray-700 p-3 space-y-1">
                <div class="px-3 py-2">
                    <div class="font-medium text-sm text-gray-800 dark:text-gray-100">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
                </div>
                <x-sidebar-link :href="route('profile.edit')">
                    <x-icon name="user-circle" class="w-5 h-5 shrink-0" />{{ __('Profile') }}
                </x-sidebar-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg border-l-4 border-transparent text-sm font-space font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">
                        <x-icon name="logout" class="w-5 h-5 shrink-0" />{{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
