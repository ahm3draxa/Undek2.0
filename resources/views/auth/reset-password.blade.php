<x-guest-layout :title="__('Reset password')">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Choose a new password</h1>
        <p class="text-sm text-gray-400 mt-1.5">Make it something you haven't used before.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-auth.label for="email" :value="__('Email')" />
            <x-auth.input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" placeholder="you@company.com" />
            <x-auth.error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div>
            <x-auth.label for="password" :value="__('Password')" />
            <x-auth.input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-auth.error :messages="$errors->get('password')" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-auth.label for="password_confirmation" :value="__('Confirm Password')" />
            <x-auth.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-auth.error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-auth.button>
            {{ __('Reset Password') }}
        </x-auth.button>
    </form>
</x-guest-layout>
