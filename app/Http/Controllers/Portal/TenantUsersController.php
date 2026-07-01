<?php

namespace App\Http\Controllers\Portal;

use App\DTOs\InviteTenantUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\InviteTenantUserRequest;
use App\Models\TenantUser;
use App\Services\TenantUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TenantUsersController extends Controller
{
    public function __construct(private readonly TenantUserService $service) {}

    public function index(): Response
    {
        $actor  = Auth::guard('portal')->user();
        $tenant = $actor->tenant;

        abort_if(! $actor->hasRole('admin'), 403, 'Apenas administradores podem gerenciar usuários.');

        $users = $this->service->list($tenant)->map(fn (TenantUser $u) => [
            'id'                => $u->id,
            'name'              => $u->name,
            'email'             => $u->email,
            'role'              => $u->role,
            'active'            => $u->active,
            'pending_invite'    => ! is_null($u->invite_token),
            'invite_expires_at' => $u->invite_expires_at,
            'last_login_at'     => $u->last_login_at,
            'created_at'        => $u->created_at,
        ]);

        return Inertia::render('Portal/Users/Index', [
            'users'    => $users,
            'is_me'    => $actor->id,
        ]);
    }

    public function create(): Response
    {
        $actor = Auth::guard('portal')->user();
        abort_if(! $actor->hasRole('admin'), 403);

        return Inertia::render('Portal/Users/Create');
    }

    public function store(InviteTenantUserRequest $request): RedirectResponse
    {
        $actor  = Auth::guard('portal')->user();
        $tenant = $actor->tenant;

        abort_if(! $actor->hasRole('admin'), 403);

        $this->service->invite(
            $tenant,
            InviteTenantUserData::fromRequest($request),
            $actor,
            $request->ip(),
        );

        return redirect()->route('portal.users.index')
            ->with('success', "Convite enviado para {$request->email}.");
    }

    public function show(TenantUser $tenantUser): Response
    {
        $actor = Auth::guard('portal')->user();
        abort_if(! $actor->hasRole('admin'), 403);
        abort_if($tenantUser->tenant_id !== $actor->tenant_id, 404);

        return Inertia::render('Portal/Users/Show', [
            'member' => [
                'id'                => $tenantUser->id,
                'name'              => $tenantUser->name,
                'email'             => $tenantUser->email,
                'role'              => $tenantUser->role,
                'active'            => $tenantUser->active,
                'pending_invite'    => ! is_null($tenantUser->invite_token),
                'invite_expires_at' => $tenantUser->invite_expires_at,
                'last_login_at'     => $tenantUser->last_login_at,
                'last_login_ip'     => $tenantUser->last_login_ip,
                'created_at'        => $tenantUser->created_at,
            ],
            'is_me' => $actor->id === $tenantUser->id,
        ]);
    }

    public function toggleActive(Request $request, TenantUser $tenantUser): RedirectResponse
    {
        $actor = Auth::guard('portal')->user();
        abort_if(! $actor->hasRole('admin'), 403);
        abort_if($tenantUser->tenant_id !== $actor->tenant_id, 404);

        if ($tenantUser->id === $actor->id) {
            return back()->with('error', 'Você não pode alterar o status da sua própria conta.');
        }

        $this->service->toggleActive($tenantUser, ! $tenantUser->active, $actor, $request->ip());

        $label = $tenantUser->fresh()->active ? 'ativado' : 'desativado';
        return back()->with('success', "Usuário {$label} com sucesso.");
    }

    public function resendInvite(Request $request, TenantUser $tenantUser): RedirectResponse
    {
        $actor = Auth::guard('portal')->user();
        abort_if(! $actor->hasRole('admin'), 403);
        abort_if($tenantUser->tenant_id !== $actor->tenant_id, 404);

        if ($tenantUser->active) {
            return back()->with('error', 'Este usuário já está ativo. Não é necessário reenviar convite.');
        }

        $this->service->resendInvite($tenantUser, $actor, $request->ip());

        return back()->with('success', "Convite reenviado para {$tenantUser->email}.");
    }
}
