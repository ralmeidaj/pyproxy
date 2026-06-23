<?php

namespace App\DTOs;

use App\Http\Requests\Backoffice\CreateApiKeyRequest;

final readonly class CreateApiKeyData
{
    public function __construct(
        public string  $name,
        public array   $scopes,
        public int     $rateLimitPerMinute,
        public ?int    $dailyLimit,
        public ?int    $monthlyLimit,
        public ?int    $maxAmountCents,
        public bool    $allowBatch,
        public ?array  $allowedMetadataTypes,
        public ?string $expiresAt,
    ) {}

    public static function fromRequest(CreateApiKeyRequest $request): self
    {
        return new self(
            name:                 $request->name,
            scopes:               $request->scopes,
            rateLimitPerMinute:   $request->rate_limit_per_minute ?? 60,
            dailyLimit:           $request->daily_limit,
            monthlyLimit:         $request->monthly_limit,
            maxAmountCents:       $request->max_amount_cents,
            allowBatch:           (bool) ($request->allow_batch ?? true),
            allowedMetadataTypes: $request->allowed_metadata_types,
            expiresAt:            $request->expires_at,
        );
    }
}
