<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;

class BackofficeAuthenticate extends Authenticate
{
    protected function redirectTo(\Illuminate\Http\Request $request): ?string
    {
        return $request->expectsJson()
            ? null
            : route('backoffice.auth.login.show');
    }

    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, ['backoffice']);
    }
}
