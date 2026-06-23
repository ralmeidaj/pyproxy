<?php

namespace App\Services;

use App\DTOs\CreateApiKeyData;
use App\Models\ApiKey;
use App\Models\BackofficeUser;
use App\Models\Tenant;
use Illuminate\Support\Str;

class ApiKeyService
{
    public function __construct(
        private readonly CryptoService   $crypto,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * Gera uma nova API key. Retorna o valor completo apenas neste momento.
     *
     * @return array{api_key: ApiKey, plain_key: string}
     */
    public function generate(Tenant $tenant, CreateApiKeyData $data): array
    {
        $plainKey = 'ppx_' . Str::random(40);
        $prefix   = substr($plainKey, 0, 12);
        $hash     = $this->crypto->hashApiKey($plainKey);

        $apiKey = ApiKey::create([
            'tenant_id'              => $tenant->id,
            'name'                   => $data->name,
            'key_prefix'             => $prefix,
            'key_hash'               => $hash,
            'scopes'                 => $data->scopes,
            'rate_limit_per_minute'  => $data->rateLimitPerMinute,
            'daily_limit'            => $data->dailyLimit,
            'monthly_limit'          => $data->monthlyLimit,
            'max_amount_cents'       => $data->maxAmountCents,
            'allow_batch'            => $data->allowBatch,
            'allowed_metadata_types' => $data->allowedMetadataTypes,
            'expires_at'             => $data->expiresAt,
        ]);

        $this->auditLog->record(
            action:       'api_key.created',
            resourceType: 'ApiKey',
            resourceId:   $apiKey->id,
            actorType:    'system',
            actorId:      null,
            actorLabel:   'backoffice',
            tenantId:     $tenant->id,
            payload:      ['name' => $data->name, 'scopes' => $data->scopes],
        );

        return ['api_key' => $apiKey, 'plain_key' => $plainKey];
    }

    public function revoke(ApiKey $apiKey, BackofficeUser $actor, string $ip): ApiKey
    {
        $apiKey->update([
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
        ]);

        $this->auditLog->record(
            action:       'api_key.revoked',
            resourceType: 'ApiKey',
            resourceId:   $apiKey->id,
            actorType:    'backoffice_user',
            actorId:      $actor->id,
            actorLabel:   $actor->email,
            tenantId:     $apiKey->tenant_id,
            payload:      ['name' => $apiKey->name],
            ip:           $ip,
        );

        return $apiKey->fresh();
    }

    public function findByPlainKey(string $plainKey): ?ApiKey
    {
        $hash = $this->crypto->hashApiKey($plainKey);

        return ApiKey::where('key_hash', $hash)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('tenant')
            ->first();
    }
}
