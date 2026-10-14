<x-guest-layout :title="__('Verify email')">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Verify your email</h1>
        <p class="text-sm text-gray-400 mt-1.5">
            {{ __("Thanks for signing up! Click the link we emailed you to verify your address. Didn't get it? We'll gladly send another.") }}
        </p>
        <p class="text-sm text-gray-400 mt-3">
            Sent to <span class="font-medium text-gray-200">{{ auth()->user()->email }}</span>.
            Check your spam folder too. Wrong address? Log out and sign up again with the right one.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-auth.status class="mb-5" :status="__('A new verification link has been sent to the email address you provided during registration.')" />
    @endif

    <div class="flex flex-col sm:flex-row items-center gap-3">
        <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto sm:flex-1">
            @csrf
            <x-auth.button>
                {{ __('Resend Verification Email') }}
            </x-auth.button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
            @csrf
            <x-auth.ghost-button type="submit" class="w-full">
                {{ __('Log Out') }}
            </x-auth.ghost-button>
        </form>
    </div>
</x-guest-layout>
