<?php

namespace App\Providers;

use App\Contracts\BankPartnerInterface;
use App\Models\Boleto;
use App\Observers\BoletoObserver;
use App\Services\ApiKeyService;
use App\Services\ArDigitalService;
use App\Services\ArEvidencePdfService;
use App\Services\ArTrackingService;
use App\Services\AuditLogService;
use App\Services\BankPartners\BankPartnerFactory;
use App\Services\BoletoConfigService;
use App\Services\BoletoService;
use App\Services\CryptoService;
use App\Services\NotificationService;
use App\Services\Rfc3161TimestampService;
use App\Services\SanitizationService;
use App\Services\SplitService;
use App\Services\TenantService;
use App\Services\WebhookDeliveryService;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CryptoService::class);
        $this->app->singleton(SanitizationService::class);
        $this->app->singleton(AuditLogService::class);
        $this->app->singleton(Google2FA::class);
        $this->app->singleton(TenantService::class);
        $this->app->singleton(ApiKeyService::class);
        $this->app->singleton(BankPartnerFactory::class);
        $this->app->singleton(SplitService::class);
        $this->app->singleton(BoletoConfigService::class);
        $this->app->singleton(BoletoService::class);
        $this->app->singleton(WebhookDeliveryService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(ArTrackingService::class);
        $this->app->singleton(Rfc3161TimestampService::class);
        $this->app->singleton(ArDigitalService::class);
        $this->app->singleton(ArEvidencePdfService::class);
    }

    public function boot(): void
    {
        Boleto::observe(BoletoObserver::class);
    }
}
