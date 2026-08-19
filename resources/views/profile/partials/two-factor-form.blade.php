<section class="space-y-6">
    <header>
        <h2 class="text-lg font-space font-medium text-gray-900 dark:text-gray-100">
            {{ __('Two-Factor Authentication') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Add an extra layer of security to your account using an authenticator app like Google Authenticator or Authy.') }}
        </p>
    </header>

    @if ($user->hasEnabledTwoFactorAuthentication())
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                {{ __('Enabled') }}
            </span>
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Two-factor authentication is protecting your account.') }}</span>
        </div>

        @if ($recoveryCodes)
            <div class="rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ __('Save these recovery codes — this is the only time they will be shown.') }}</p>
                <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">{{ __('Each code can be used once to log in if you lose access to your authenticator app.') }}</p>
                <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-gray-800 dark:text-gray-200">
                    @foreach ($recoveryCodes as $code)
                        <div class="bg-white dark:bg-gray-900 rounded px-2 py-1">{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <form method="post" action="{{ route('two-factor.recovery-codes') }}">
                @csrf
                <x-secondary-button>{{ __('Regenerate recovery codes') }}</x-secondary-button>
            </form>

            <x-danger-button x-data x-on:click.prevent="$dispatch('open-modal', 'disable-two-factor')">
                {{ __('Disable') }}
            </x-danger-button>
        </div>

        <x-modal name="disable-two-factor" :show="$errors->any() && old('current_password') !== null" focusable>
            <form method="post" action="{{ route('two-factor.destroy') }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-space font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Disable two-factor authentication?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Enter your password to confirm. Your account will no longer require a second step to log in.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="disable_2fa_password" value="{{ __('Password') }}" class="sr-only" />
                    <x-text-input id="disable_2fa_password" name="current_password" type="password" class="mt-1 block w-3/4" placeholder="{{ __('Password') }}" />
                    <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ms-3">
                        {{ __('Disable') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    @elseif ($qrCodeSvg)
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Scan this QR code with your authenticator app, then enter the 6-digit code it generates to confirm setup.') }}
            </p>

            <div class="inline-block rounded-lg bg-white p-3 border border-gray-200">
                {!! $qrCodeSvg !!}
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __("Can't scan? Enter this key manually:") }}
                <code class="font-mono">{{ $user->two_factor_secret }}</code>
            </p>

            <form method="post" action="{{ route('two-factor.confirm') }}" class="space-y-4 max-w-xs">
                @csrf

                <div>
                    <x-input-label for="two_factor_code" value="{{ __('Authentication Code') }}" />
                    <x-text-input id="two_factor_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="mt-1 block w-full" placeholder="123456" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <x-primary-button>{{ __('Confirm & enable') }}</x-primary-button>
            </form>

            <form method="post" action="{{ route('two-factor.enable') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">{{ __('Generate a new QR code') }}</button>
            </form>
        </div>
    @else
        <form method="post" action="{{ route('two-factor.enable') }}">
            @csrf
            <x-primary-button>{{ __('Enable two-factor authentication') }}</x-primary-button>
        </form>
    @endif
</section>
