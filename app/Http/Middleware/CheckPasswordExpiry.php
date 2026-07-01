<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordExpiry
{
    public function handle(Request $request, Closure $next, string $guard = 'backoffice'): Response
    {
        $user = Auth::guard($guard)->user();

        if (! $user) {
            return $next($request);
        }

        // Não intercepta a própria rota de troca de senha (evita redirect infinito)
        $changeRoutePrefix = $guard === 'backoffice'
            ? 'backoffice.auth.password.change'
            : 'portal.auth.password.change';

        if ($request->routeIs($changeRoutePrefix . '*')) {
            return $next($request);
        }

        $changedAt = $user->password_changed_at;
        $expired   = $changedAt === null || $changedAt->lt(now()->subDays(90));

        if ($expired) {
            return redirect()
                ->route($changeRoutePrefix . '.show')
                ->with('warning', 'Sua senha expirou. Crie uma nova senha para continuar.');
        }

        return $next($request);
    }
}
