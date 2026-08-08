<x-guest-layout :title="__('Log in')">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Welcome back</h1>
        <p class="text-sm text-gray-400 mt-1.5">Log in to your undek account to pick up where you left off.</p>
    </div>

    <!-- Session Status -->
    <x-auth.status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-auth.label for="email" :value="__('Email')" />
            <x-auth.input id="email" type="email" name="email" :value="old('email', request('email'))" required autofocus autocomplete="username" placeholder="you@company.com" />
            <x-auth.error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <x-auth.label for="password" :value="__('Password')" class="!mb-0" />
                @if (Route::has('password.request'))
                    <a class="font-jetbrains text-[11px] text-gray-500 hover:text-cyan-300 transition" href="{{ route('password.request') }}">
                        {{ __('Forgot?') }}
                    </a>
                @endif
            </div>
            <x-auth.input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-auth.error :messages="$errors->get('password')" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="flex items-center gap-2.5 select-none cursor-pointer">
            <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 rounded border-white/20 bg-white/5" style="accent-color:#22d3ee">
            <span class="text-sm text-gray-400">{{ __('Remember me') }}</span>
        </label>

        <x-auth.button>
            {{ __('Log in') }} &rarr;
        </x-auth.button>
    </form>

    <p class="text-center text-sm text-gray-400 mt-6">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="text-cyan-300 hover:text-cyan-200 font-semibold transition">{{ __('Sign up') }}</a>
    </p>
</x-guest-layout>
