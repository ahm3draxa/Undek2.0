<x-guest-layout :title="__('Sign up')">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Create your account</h1>
        <p class="text-sm text-gray-400 mt-1.5">Join undek to request quotes, track milestones and manage your projects.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <x-auth.label for="name" :value="__('Name')" />
            <x-auth.input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Jane Doe" />
            <x-auth.error :messages="$errors->get('name')" />
        </div>

        <!-- Email Address -->
        <div>
            <x-auth.label for="email" :value="__('Email')" />
            <x-auth.input id="email" type="email" name="email" :value="old('email', request('email'))" required autocomplete="username" placeholder="you@company.com" />
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
            {{ __('Create account') }} &rarr;
        </x-auth.button>
    </form>

    <p class="text-center text-sm text-gray-400 mt-6">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="text-cyan-300 hover:text-cyan-200 font-semibold transition">{{ __('Log in') }}</a>
    </p>
</x-guest-layout>
