<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}?v=2">

        <!-- Dark mode: applied before first paint to avoid a flash of the wrong theme -->
        <script>
            (function () {
                const stored = localStorage.getItem('theme');
                const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        {{-- Space Grotesk backs the `font-space` utility used throughout the app (headings, the
             logo) — without loading it here, every font-space element silently fell back to a
             generic sans on every authenticated page. --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
            @include('layouts.navigation')

            <div class="lg:pl-64">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white dark:bg-gray-800 shadow transition-colors duration-200">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            @unless (request()->routeIs('dashboard'))
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-jetbrains uppercase tracking-widest text-gray-400 dark:text-gray-500 hover:text-cyan-600 dark:hover:text-cyan-300 mb-3 transition">
                                    <x-icon name="arrow-right" class="w-3.5 h-3.5 rotate-180" />
                                    Back to dashboard
                                </a>
                            @endunless
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
