<x-guest-layout :title="__('Confirm password')">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Confirm your password</h1>
        <p class="text-sm text-gray-400 mt-1.5">{{ __('This is a secure area. Please confirm your password before continuing.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <x-auth.label for="password" :value="__('Password')" />
            <x-auth.input id="password" type="password" name="password" required autocomplete="current-password" autofocus placeholder="••••••••" />
            <x-auth.error :messages="$errors->get('password')" />
        </div>

        <x-auth.button>
            {{ __('Confirm') }}
        </x-auth.button>
    </form>
</x-guest-layout>
