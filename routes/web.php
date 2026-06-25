<?php

use App\Http\Controllers\Backoffice\ApiKeyController;
use App\Http\Controllers\Backoffice\Auth\LoginController;
use App\Http\Controllers\Backoffice\Auth\TotpController;
use App\Http\Controllers\Backoffice\BoletoConfigController;
use App\Http\Controllers\Backoffice\BoletoController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\ReportController;
use App\Http\Controllers\Backoffice\SplitConfigController;
use App\Http\Controllers\Backoffice\TenantController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Portal\Auth\LoginController as PortalLoginController;
use App\Http\Controllers\Portal\Auth\TotpController as PortalTotpController;
use App\Http\Controllers\Portal\BoletoController as PortalBoletoController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use App\Http\Controllers\Portal\ReportController as PortalReportController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');

Route::get('/', fn () => redirect()->route('backoffice.dashboard'));

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

    Route::middleware('auth.backoffice')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('auth/totp/setup', [TotpController::class, 'setupShow'])->name('auth.totp.setup.show');
        Route::post('auth/totp/setup', [TotpController::class, 'setupStore'])->name('auth.totp.setup.store');

        Route::resource('tenants', TenantController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
            ->names('tenants');

        Route::patch('tenants/{tenant}/status', [TenantController::class, 'updateStatus'])
            ->name('tenants.status');

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

    Route::middleware('guest:portal')->name('auth.')->prefix('auth')->group(function () {
        Route::get('login', [PortalLoginController::class, 'show'])->name('login.show');
        Route::post('login', [PortalLoginController::class, 'store'])->name('login.store');

        Route::get('totp', [PortalTotpController::class, 'show'])->name('totp.show');
        Route::post('totp', [PortalTotpController::class, 'store'])->name('totp.store');
    });

    Route::post('auth/logout', [PortalLoginController::class, 'destroy'])
        ->name('auth.logout')
        ->middleware('auth.portal');

    Route::middleware('auth.portal')->group(function () {
        Route::get('dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');

        Route::get('auth/totp/setup', [PortalTotpController::class, 'setupShow'])->name('auth.totp.setup.show');
        Route::post('auth/totp/setup', [PortalTotpController::class, 'setupStore'])->name('auth.totp.setup.store');

        // Boletos
        Route::get('boletos', [PortalBoletoController::class, 'index'])->name('boletos.index');
        Route::get('boletos/create', [PortalBoletoController::class, 'create'])->name('boletos.create');
        Route::post('boletos', [PortalBoletoController::class, 'store'])->name('boletos.store');
        Route::get('boletos/{boleto}', [PortalBoletoController::class, 'show'])->name('boletos.show');
        Route::post('boletos/{boleto}/cancel', [PortalBoletoController::class, 'cancel'])->name('boletos.cancel');

        // Perfil
        Route::get('profile', [PortalProfileController::class, 'show'])->name('profile');
        Route::put('profile/password', [PortalProfileController::class, 'updatePassword'])->name('profile.password');

        // Relatórios
        Route::get('reports', [PortalReportController::class, 'index'])->name('reports.index');
        Route::post('reports/export', [PortalReportController::class, 'export'])->name('reports.export');
    });
});
