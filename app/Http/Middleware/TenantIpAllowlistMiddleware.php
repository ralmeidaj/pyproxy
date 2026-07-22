<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantIpAllowlistMiddleware
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->attributes->get('tenant');

        if (! $tenant || empty($tenant->allowed_ips)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        foreach ($tenant->allowed_ips as $entry) {
            if ($this->ipMatches($clientIp, trim($entry))) {
                return $next($request);
            }
        }

        $apiKey = $request->attributes->get('api_key');

        $this->auditLogService->record(
            action:       'api.ip_blocked',
            resourceType: 'tenant',
            resourceId:   $tenant->id,
            actorType:    'api_key',
            actorId:      $apiKey?->id,
            actorLabel:   $apiKey?->name ?? 'desconhecido',
            tenantId:     $tenant->id,
            payload:      ['ip' => $clientIp, 'allowed_ips' => $tenant->allowed_ips],
            ip:           $clientIp,
            userAgent:    $request->userAgent(),
        );

        return response()->json(['message' => 'Acesso negado: IP não autorizado.'], 403);
    }

    private function ipMatches(string $clientIp, string $entry): bool
    {
        if (! str_contains($entry, '/')) {
            return $clientIp === $entry;
        }

        [$subnet, $bits] = explode('/', $entry, 2);
        $bits = (int) $bits;

        if ($bits < 0 || $bits > 32) {
            return false;
        }

        $clientLong = ip2long($clientIp);
        $subnetLong = ip2long($subnet);

        if ($clientLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));

        return ($clientLong & $mask) === ($subnetLong & $mask);
    }
}
