<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = Auth::guard('portal')->user();

        return Inertia::render('Portal/Profile', [
            'user'   => $user->only('id', 'name', 'email', 'role', 'totp_enabled', 'last_login_at', 'last_login_ip'),
            'tenant' => $user->tenant->only('id', 'name'),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.required' => 'Informe a senha atual.',
            'password.required'         => 'A nova senha é obrigatória.',
            'password.confirmed'         => 'A confirmação de senha não confere.',
            'password.min'              => 'A senha deve ter no mínimo 8 caracteres.',
        ]);

        $user = Auth::guard('portal')->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Senha atual incorreta.']);
        }

        $user->update(['password' => $request->password]);

        return back()->with('success', 'Senha alterada com sucesso.');
    }
}
