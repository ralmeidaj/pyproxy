<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Portal/Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'O e-mail é obrigatório.',
            'email.email'       => 'Informe um e-mail válido.',
            'password.required' => 'A senha é obrigatória.',
        ]);

        if (! Auth::guard('portal')->attempt($request->only('email', 'password'), false)) {
            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        $user = Auth::guard('portal')->user();

        if (! $user->active) {
            Auth::guard('portal')->logout();
            throw ValidationException::withMessages([
                'email' => 'Sua conta está inativa. Entre em contato com o administrador.',
            ]);
        }

        if ($user->hasTotpEnabled()) {
            Auth::guard('portal')->logout();
            $request->session()->put('portal.totp_pending_id', $user->id);

            return redirect()->route('portal.auth.totp.show');
        }

        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended(route('portal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('portal')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.auth.login.show');
    }
}
