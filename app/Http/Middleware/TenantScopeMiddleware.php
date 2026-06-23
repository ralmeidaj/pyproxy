<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Tenant already bound by ApiKeyMiddleware; just ensure it's present
        if (! $request->attributes->has('tenant')) {
            return response()->json(['message' => 'Tenant não identificado.'], 401);
        }

        return $next($request);
    }
}
