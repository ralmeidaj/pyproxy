<?php

use App\Http\Controllers\Backoffice\ApiKeyController;
use App\Http\Controllers\Backoffice\Auth\ChangePasswordController as BackofficeChangePasswordController;
use App\Http\Controllers\Backoffice\Auth\LoginController;
use App\Http\Controllers\Backoffice\Auth\TotpController;
use App\Http\Controllers\Backoffice\BoletoConfigController;
use App\Http\Controllers\Backoffice\BoletoController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\ReportController;
use App\Http\Controllers\Backoffice\SplitConfigController;
use App\Http\Controllers\Backoffice\TenantController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Portal\Auth\AcceptInviteController;
use App\Http\Controllers\Portal\Auth\ChangePasswordController as PortalChangePasswordController;
use App\Http\Controllers\Portal\Auth\LoginController as PortalLoginController;
use App\Http\Controllers\Portal\Auth\TotpController as PortalTotpController;
use App\Http\Controllers\Portal\BoletoController as PortalBoletoController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use App\Http\Controllers\Portal\ReportController as PortalReportController;
use App\Http\Controllers\Portal\TenantUsersController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');

Route::get('/', fn () => redirect()->route('backoffice.dashboard'));

// ─────────────────────────────────────────────
// Debug — apenas em ambientes não-produção
// ─────────────────────────────────────────────
if (! app()->isProduction()) {
    Route::get('/debug/pjbank-payload', function (\Illuminate\Http\Request $request) {
        $captured = null;

        \Illuminate\Support\Facades\Http::fake(function (\Illuminate\Http\Client\Request $req) use (&$captured) {
            $captured = [
                'url'          => $req->url(),
                'method'       => $req->method(),
                'content_type' => $req->header('Content-Type')[0] ?? null,
                'body'         => $req->data(),
            ];
            return \Illuminate\Support\Facades\Http::response(['nossonumero' => 'DEBUG-PREVIEW'], 200);
        });

        $tenant = \App\Models\Tenant::where('status', 'active')
            ->with(['boletoConfigs.bankPartner', 'boletoConfigs.splitConfigs'])
            ->firstOrFail();

        $config = $tenant->boletoConfigs
            ->where('status', 'active')
            ->first();

        if (! $config) {
            return response()->json(['error' => 'Nenhum BoletoConfig ativo para este tenant.'], 404);
        }

        $amountCents = (int) round((float) $request->query('valor', '100.00') * 100);

        $splits = app(\App\Services\SplitService::class)->calculate($config, $amountCents);

        $data = new \App\DTOs\IssueBoletoData(
            externalRef:   'DEBUG-PREVIEW-' . now()->format('YmdHis'),
            amountCents:   $amountCents,
            dueDate:       now()->addDays(3)->toDateString(),
            payerName:     'Pagador Teste',
            payerDocument: '000.000.000-00',
            payerEmail:    'teste@debug.com',
            payerPhone:    null,
            payerAddress:  [
                'logradouro'  => 'Rua das Flores',
                'numero'      => '100',
                'complemento' => '',
                'bairro'      => 'Centro',
                'cidade'      => 'Salvador',
                'estado'      => 'BA',
                'cep'         => '40000-000',
            ],
        );

        app(\App\Services\BankPartners\PJBankService::class)->issueBoleto($data, $config, $splits);

        return response()->json($captured, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    })->name('debug.pjbank-payload');
}

// ─────────────────────────────────────────────
// Backoffice — admins Ciberian
// ─────────────────────────────────────────────
Route::prefix('backoffice')->name('backoffice.')->group(function () {

    Route::middleware('guest:backoffice')->name('auth.')->prefix('auth')->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login.show');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');

        Route::get('totp', [TotpController::class, 'show'])->name('totp.show');
        Route::post('totp', [TotpController::class, 'store'])->name('totp.store');
    });

    Route::post('auth/logout', [LoginController::class, 'destroy'])
        ->name('auth.logout')
        ->middleware('auth.backoffice');

    Route::middleware(['auth.backoffice', 'check.password.expiry:backoffice'])->group(function () {
        // Troca de senha obrigatória (excluída do check pelo próprio middleware)
        Route::get('auth/password/change', [BackofficeChangePasswordController::class, 'show'])->name('auth.password.change.show');
        Route::post('auth/password/change', [BackofficeChangePasswordController::class, 'store'])->name('auth.password.change.store');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('auth/totp/setup', [TotpController::class, 'setupShow'])->name('auth.totp.setup.show');
        Route::post('auth/totp/setup', [TotpController::class, 'setupStore'])->name('auth.totp.setup.store');

        Route::resource('tenants', TenantController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
            ->names('tenants');

        Route::patch('tenants/{tenant}/status', [TenantController::class, 'updateStatus'])
            ->name('tenants.status');

        Route::post('tenants/{tenant}/approve', [TenantController::class, 'approve'])
            ->name('tenants.approve');

        Route::get('tenants/{tenant}/api-keys/create', [ApiKeyController::class, 'create'])
            ->name('tenants.api-keys.create');
        Route::post('tenants/{tenant}/api-keys', [ApiKeyController::class, 'store'])
            ->name('tenants.api-keys.store');
        Route::delete('tenants/{tenant}/api-keys/{apiKey}', [ApiKeyController::class, 'revoke'])
            ->name('tenants.api-keys.revoke');

        Route::get('tenants/{tenant}/boleto-configs/create', [BoletoConfigController::class, 'create'])
            ->name('tenants.boleto-configs.create');
        Route::post('tenants/{tenant}/boleto-configs', [BoletoConfigController::class, 'store'])
            ->name('tenants.boleto-configs.store');
        Route::get('tenants/{tenant}/boleto-configs/{boletoConfig}/edit', [BoletoConfigController::class, 'edit'])
            ->name('tenants.boleto-configs.edit');
        Route::put('tenants/{tenant}/boleto-configs/{boletoConfig}', [BoletoConfigController::class, 'update'])
            ->name('tenants.boleto-configs.update');

        // Split configs (favorecidos do split por BoletoConfig)
        Route::post('tenants/{tenant}/boleto-configs/{boletoConfig}/split-configs', [SplitConfigController::class, 'store'])
            ->name('tenants.boleto-configs.split-configs.store');
        Route::put('tenants/{tenant}/boleto-configs/{boletoConfig}/split-configs/{splitConfig}', [SplitConfigController::class, 'update'])
            ->name('tenants.boleto-configs.split-configs.update');
        Route::delete('tenants/{tenant}/boleto-configs/{boletoConfig}/split-configs/{splitConfig}', [SplitConfigController::class, 'destroy'])
            ->name('tenants.boleto-configs.split-configs.destroy');

        Route::get('tenants/{tenant}/boletos', [BoletoController::class, 'index'])
            ->name('tenants.boletos.index');
        Route::get('tenants/{tenant}/boletos/{boleto}', [BoletoController::class, 'show'])
            ->name('tenants.boletos.show');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/export', [ReportController::class, 'export'])->name('reports.export');
    });
});

// ─────────────────────────────────────────────
// Portal do Tenant — /portal/*
// ─────────────────────────────────────────────
Route::prefix('portal')->name('portal.')->group(function () {

    // Aceitar convite — público (sem autenticação)
    Route::get('invite/{token}', [AcceptInviteController::class, 'show'])->name('invite.show');
    Route::post('invite/{token}', [AcceptInviteController::class, 'store'])->name('invite.store');

    Route::middleware('guest:portal')->name('auth.')->prefix('auth')->group(function () {
        Route::get('login', [PortalLoginController::class, 'show'])->name('login.show');
        Route::post('login', [PortalLoginController::class, 'store'])->name('login.store');

        Route::get('totp', [PortalTotpController::class, 'show'])->name('totp.show');
        Route::post('totp', [PortalTotpController::class, 'store'])->name('totp.store');
    });

    Route::post('auth/logout', [PortalLoginController::class, 'destroy'])
        ->name('auth.logout')
        ->middleware('auth.portal');

    Route::middleware(['auth.portal', 'check.password.expiry:portal'])->group(function () {
        // Troca de senha obrigatória (excluída do check pelo próprio middleware)
        Route::get('auth/password/change', [PortalChangePasswordController::class, 'show'])->name('auth.password.change.show');
        Route::post('auth/password/change', [PortalChangePasswordController::class, 'store'])->name('auth.password.change.store');

        Route::get('dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');

        Route::get('auth/totp/setup', [PortalTotpController::class, 'setupShow'])->name('auth.totp.setup.show');
        Route::post('auth/totp/setup', [PortalTotpController::class, 'setupStore'])->name('auth.totp.setup.store');

        // Boletos
        Route::get('boletos', [PortalBoletoController::class, 'index'])->name('boletos.index');
        Route::get('boletos/create', [PortalBoletoController::class, 'create'])->name('boletos.create');
        Route::post('boletos', [PortalBoletoController::class, 'store'])->name('boletos.store');
        Route::get('boletos/{boleto}', [PortalBoletoController::class, 'show'])->name('boletos.show');
        Route::post('boletos/{boleto}/cancel', [PortalBoletoController::class, 'cancel'])->name('boletos.cancel');
        Route::post('boletos/{boleto}/resend', [PortalBoletoController::class, 'resend'])->name('boletos.resend');

        // Perfil
        Route::get('profile', [PortalProfileController::class, 'show'])->name('profile');
        Route::put('profile/password', [PortalProfileController::class, 'updatePassword'])->name('profile.password');

        // Usuários do tenant (apenas admin)
        Route::get('users', [TenantUsersController::class, 'index'])->name('users.index');
        Route::get('users/create', [TenantUsersController::class, 'create'])->name('users.create');
        Route::post('users', [TenantUsersController::class, 'store'])->name('users.store');
        Route::get('users/{tenantUser}', [TenantUsersController::class, 'show'])->name('users.show');
        Route::patch('users/{tenantUser}/toggle-active', [TenantUsersController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('users/{tenantUser}/resend-invite', [TenantUsersController::class, 'resendInvite'])->name('users.resend-invite');

        // Relatórios
        Route::get('reports', [PortalReportController::class, 'index'])->name('reports.index');
        Route::post('reports/export', [PortalReportController::class, 'export'])->name('reports.export');
    });
});
