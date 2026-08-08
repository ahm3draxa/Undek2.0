<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? __('Welcome') }} — {{ config('app.name', 'undek') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Manrope:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @keyframes riseIn { from { opacity:0; transform:translateY(24px) } to { opacity:1; transform:none } }
            @keyframes pulseDot { 0%,100% { opacity:1; transform:scale(1) } 50% { opacity:.35; transform:scale(.8) } }
            .auth-hud { position:fixed; width:16px; height:16px; pointer-events:none; z-index:5; opacity:.25; }
            .auth-hud::before,.auth-hud::after { content:''; position:absolute; background:#22d3ee; }
            .auth-hud::before { width:2px; height:100%; top:0; left:0; }
            .auth-hud::after { width:100%; height:2px; top:0; left:0; }
            .auth-hud-tl { top:20px; left:20px; }
            .auth-hud-tr { top:20px; right:20px; transform:scaleX(-1); }
            .auth-hud-bl { bottom:20px; left:20px; transform:scaleY(-1); }
            .auth-hud-br { bottom:20px; right:20px; transform:scale(-1,-1); }
            .auth-card { animation: riseIn .6s cubic-bezier(.16,1,.3,1) both; }
        </style>
    </head>
    <body class="font-sans antialiased bg-[#08080c] text-[#f5f5fa]">

        <div class="auth-hud auth-hud-tl"></div>
        <div class="auth-hud auth-hud-tr"></div>
        <div class="auth-hud auth-hud-bl"></div>
        <div class="auth-hud auth-hud-br"></div>

        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-12 overflow-hidden">
            <!-- Background grid + glow, matches the marketing site -->
            <div class="pointer-events-none absolute inset-0 z-0"
                 style="background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:64px 64px;mask-image:radial-gradient(ellipse 70% 60% at 50% 30%,#000 20%,transparent 75%);-webkit-mask-image:radial-gradient(ellipse 70% 60% at 50% 30%,#000 20%,transparent 75%)"></div>
            <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 w-[560px] h-[560px] rounded-full opacity-40 blur-3xl z-0"
                 style="background:radial-gradient(circle,rgba(124,92,255,.35),transparent 70%)"></div>

            <div class="relative z-10 w-full max-w-md">
                <!-- Logo -->
                <div class="flex justify-center mb-8" style="animation: riseIn .6s ease both">
                    <a href="/" class="inline-flex items-center gap-2.5">
                        <span class="block w-7 h-7 rounded-lg rotate-12" style="background:linear-gradient(135deg,#7c5cff,#22d3ee);box-shadow:0 0 20px rgba(124,92,255,.65)"></span>
                        <span class="font-space font-bold text-xl tracking-tight text-[#f5f5fa]">undek</span>
                    </a>
                </div>

                <div class="flex items-center justify-center gap-2 mb-6 font-jetbrains text-[11px] tracking-widest text-gray-500" style="animation: riseIn .6s ease both; animation-delay:.05s">
                    <span class="w-1.5 h-1.5 rounded-full" style="background:#34e0a1;box-shadow:0 0 8px #34e0a1;animation:pulseDot 2s infinite"></span>
                    SYS::AUTH &nbsp;■&nbsp; SECURE ACCESS
                </div>

                <!-- Card -->
                <div class="auth-card rounded-2xl border border-white/10 bg-white/[0.03] backdrop-blur-sm shadow-[0_10px_50px_rgba(0,0,0,.4)] p-8">
                    {{ $slot }}
                </div>

                <p class="text-center text-xs text-gray-500 mt-6 font-jetbrains">
                    <a href="/" class="text-gray-400 hover:text-cyan-300 transition">&larr; back to undek.com</a>
                </p>
            </div>
        </div>
    </body>
</html>
