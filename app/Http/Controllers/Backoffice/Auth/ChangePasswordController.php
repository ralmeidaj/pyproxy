<?php

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use App\Rules\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ChangePasswordController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Backoffice/Auth/ChangePassword', [
            'warning' => session('warning'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', new PasswordPolicy()],
        ], [
            'current_password.required' => 'Informe a senha atual.',
            'password.required'         => 'A nova senha é obrigatória.',
            'password.confirmed'         => 'A confirmação de senha não confere.',
        ]);

        $user = Auth::guard('backoffice')->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Senha atual incorreta.']);
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'A nova senha não pode ser igual à senha atual.']);
        }

        $user->update([
            'password'            => $request->password,
            'password_changed_at' => now(),
        ]);

        return redirect()->route('backoffice.dashboard')
            ->with('success', 'Senha alterada com sucesso.');
    }
}
