<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\TenantUser;
use App\Rules\PasswordPolicy;
use App\Services\TenantUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInviteController extends Controller
{
    public function __construct(private readonly TenantUserService $service) {}

    public function show(string $token): Response
    {
        $user = $this->findByToken($token);

        if (! $user) {
            return Inertia::render('Portal/Auth/AcceptInvite', [
                'valid'   => false,
                'message' => 'Este link de convite é inválido ou expirou. Solicite um novo convite ao administrador.',
            ]);
        }

        $user->load('tenant');

        return Inertia::render('Portal/Auth/AcceptInvite', [
            'valid'  => true,
            'token'  => $token,
            'name'   => $user->name,
            'email'  => $user->email,
            'tenant' => $user->tenant->name,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        if (! $this->findByToken($token)) {
            return redirect()->route('portal.auth.login.show')
                ->with('error', 'Link de convite inválido ou expirado. Solicite um novo convite.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', new PasswordPolicy()],
        ], [
            'password.required'  => 'A senha é obrigatória.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ]);

        $this->service->acceptInvite($token, $request->password);

        return redirect()->route('portal.auth.login.show')
            ->with('success', 'Conta ativada com sucesso! Faça login com seu e-mail e a senha que você criou.');
    }

    private function findByToken(string $token): ?TenantUser
    {
        return TenantUser::where('invite_token', hash('sha256', $token))
            ->where('invite_expires_at', '>', now())
            ->first();
    }
}
