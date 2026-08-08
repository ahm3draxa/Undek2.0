<x-guest-layout :title="__('Reset password')">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Reset your password</h1>
        <p class="text-sm text-gray-400 mt-1.5">{{ __('Enter your email and we will send you a link to choose a new one.') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth.status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-auth.label for="email" :value="__('Email')" />
            <x-auth.input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="you@company.com" />
            <x-auth.error :messages="$errors->get('email')" />
        </div>

        <x-auth.button>
            {{ __('Email Password Reset Link') }}
        </x-auth.button>
    </form>

    <p class="text-center text-sm text-gray-400 mt-6">
        <a href="{{ route('login') }}" class="text-cyan-300 hover:text-cyan-200 font-semibold transition">&larr; {{ __('Back to login') }}</a>
    </p>
</x-guest-layout>
