<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\ClaimsGuestQuoteRequests;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    use ClaimsGuestQuoteRequests;

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // dns: the domain must actually accept mail (MX/A record), which
            // catches typos like gmial.com before a verification email is sent
            // into the void. Proof of ownership comes from the emailed link.
            'email' => ['required', 'string', 'lowercase', 'email:rfc,dns', 'max:255', 'unique:'.User::class],
            // max:72 matches bcrypt's actual effective input limit — bytes past that
            // are silently ignored by the hasher, so without a cap here two different
            // long passwords sharing the same first 72 bytes would hash identically.
            'password' => ['required', 'confirmed', 'max:72', Rules\Password::defaults()],
            'website' => ['prohibited'], // honeypot, see components/honeypot
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole('client');
        $this->claimGuestQuoteRequests($user);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
