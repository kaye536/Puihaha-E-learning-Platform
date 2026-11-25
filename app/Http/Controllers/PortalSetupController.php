<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Lets a member choose their password from the emailed invitation link.
 * The route is protected by Laravel's signed-URL middleware (tamper-proof, expires),
 * and the link stops working as soon as a password has been chosen.
 */
class PortalSetupController extends Controller
{
    public function show(User $user): View|RedirectResponse
    {
        if ($blocked = $this->blocked($user)) {
            return $blocked;
        }

        return view('portal.setup', ['member' => $user]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        if ($blocked = $this->blocked($user)) {
            return $blocked;
        }

        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(8)]]);

        $user->forceFill([
            'password' => $data['password'],
            'portal_activated_at' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.index')->with('success', 'Welcome! Your password is set and your portal is ready.');
    }

    private function blocked(User $user): ?RedirectResponse
    {
        if (! $user->isMember() || ! $user->is_active) {
            abort(404);
        }

        if ($user->portal_activated_at) {
            return redirect()->route('login')->withErrors(['email' => 'This invitation was already used. Sign in with your password, or use "Forgot password".']);
        }

        return null;
    }
}
