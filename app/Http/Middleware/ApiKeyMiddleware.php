<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Models\ApiKeyUsageDaily;
use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function __construct(private readonly ApiKeyService $apiKeyService) {}

    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        $raw = $request->header('X-API-Key') ?? $request->bearerToken();

        if (! $raw) {
            return $this->unauthorized('API key não informada.');
        }

        $apiKey = $this->apiKeyService->findByPlainKey($raw);

        if (! $apiKey) {
            return $this->unauthorized('API key inválida ou revogada.');
        }

        $tenant = $apiKey->tenant;

        if ($tenant->status !== TenantStatus::Active) {
            return response()->json([
                'message' => 'Acesso negado. A aplicação está ' . $tenant->status->label() . '.',
            ], 403);
        }

        // Scope check
        foreach ($requiredScopes as $scope) {
            if (! $apiKey->hasScope($scope)) {
                return response()->json([
                    'message' => 'Esta API key não possui permissão para realizar esta operação.',
                ], 403);
            }
        }

        // Rate limiting per minute (Redis)
        $rateLimitKey = "api_key:{$apiKey->id}:rate";
        $maxPerMinute = $apiKey->rate_limit_per_minute;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxPerMinute)) {
            return response()->json(['message' => 'Limite de requisições por minuto atingido.'], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Daily usage counter + limit check
        $today = now()->toDateString();
        $usage = ApiKeyUsageDaily::firstOrCreate(
            ['api_key_id' => $apiKey->id, 'date' => $today],
            ['count' => 0],
        );

        if ($apiKey->daily_limit && $usage->count >= $apiKey->daily_limit) {
            return response()->json(['message' => 'Limite diário de operações atingido.'], 429);
        }

        $usage->increment('count');

        // Bind to request for downstream use
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json(['message' => $message], 401);
    }
}
