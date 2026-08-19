<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $qrCodeSvg = null;

        if ($user->two_factor_secret && ! $user->hasEnabledTwoFactorAuthentication()) {
            $qrCodeSvg = $this->qrCodeSvg($user);
        }

        return view('profile.edit', [
            'user' => $user,
            'qrCodeSvg' => $qrCodeSvg,
            'recoveryCodes' => session('recoveryCodes'),
        ]);
    }

    private function qrCodeSvg(User $user): string
    {
        $google2fa = new Google2FA;
        $qrCodeUrl = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $user->two_factor_secret);

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);
        $writer = new Writer($renderer);

        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = collect($request->validated())->except('avatar')->all();
        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            if ($request->user()->avatar_path) {
                Storage::disk('public')->delete($request->user()->avatar_path);
            }

            $request->user()->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Remove the user's profile picture.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-removed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
