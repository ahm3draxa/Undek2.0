<x-guest-layout :title="__('Two-factor challenge')">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Two-factor verification</h1>
        <p class="text-sm text-gray-400 mt-1.5">Enter the 6-digit code from your authenticator app, or use one of your recovery codes.</p>
    </div>

    <x-auth.status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-5" x-data="{ useRecovery: false }">
        @csrf

        <div x-show="!useRecovery">
            <x-auth.label for="code" value="Authentication code" />
            <x-auth.input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus placeholder="123456" />
            <x-auth.error :messages="$errors->get('code')" />
        </div>

        <div x-show="useRecovery" x-cloak>
            <x-auth.label for="recovery_code" value="Recovery code" />
            <x-auth.input id="recovery_code" type="text" name="recovery_code" autocomplete="off" placeholder="xxxxxxxxxx-xxxxxxxxxx" />
        </div>

        <x-auth.button>
            {{ __('Verify') }} &rarr;
        </x-auth.button>

        <button type="button" class="w-full text-center text-sm text-gray-400 hover:text-cyan-300 transition" x-on:click="useRecovery = !useRecovery">
            <span x-show="!useRecovery">Use a recovery code instead</span>
            <span x-show="useRecovery" x-cloak>Use an authentication code instead</span>
        </button>
    </form>
</x-guest-layout>
