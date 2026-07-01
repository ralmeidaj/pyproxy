<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

// Sobrescreve o endpoint de auth do broadcast para aceitar os guards backoffice e portal
Broadcast::routes(['middleware' => ['web', 'auth:backoffice,portal']]);

// Canal do backoffice — qualquer admin/operador autenticado
Broadcast::channel('dashboard.backoffice', function () {
    if (! Auth::guard('backoffice')->check()) {
        return false;
    }
    return in_array(Auth::guard('backoffice')->user()->role, ['super_admin', 'admin', 'operator']);
});

// Canal por tenant — apenas o tenant_user do tenant correspondente
Broadcast::channel('dashboard.{tenantId}', function ($user, int $tenantId) {
    if (! Auth::guard('portal')->check()) {
        return false;
    }
    return (int) Auth::guard('portal')->user()->tenant_id === $tenantId;
});
