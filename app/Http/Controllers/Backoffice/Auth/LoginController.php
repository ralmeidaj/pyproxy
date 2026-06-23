<?php

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\BackofficeLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Backoffice/Auth/Login');
    }

    public function store(BackofficeLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::guard('backoffice')->attempt($credentials, false)) {
            return back()->withErrors([
                'email' => 'Credenciais inválidas.',
            ])->onlyInput('email');
        }

        $user = Auth::guard('backoffice')->user();

        if ($user->hasTotpEnabled()) {
            // Store user id in session and logout — full auth happens after TOTP
            Auth::guard('backoffice')->logout();
            $request->session()->put('backoffice.totp_pending_id', $user->id);

            return redirect()->route('backoffice.auth.totp.show');
        }

        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended(route('backoffice.dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('backoffice')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('backoffice.auth.login.show');
    }
}
