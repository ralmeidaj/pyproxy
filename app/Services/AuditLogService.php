<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        string  $action,
        string  $resourceType,
        ?int    $resourceId,
        string  $actorType,
        ?int    $actorId,
        string  $actorLabel,
        ?int    $tenantId = null,
        ?array  $payload = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): AuditLog {
        return AuditLog::create([
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'actor_type'    => $actorType,
            'actor_id'      => $actorId,
            'actor_label'   => $actorLabel,
            'tenant_id'     => $tenantId,
            'payload'       => $payload,
            'ip'            => $ip,
            'user_agent'    => $userAgent,
            'created_at'    => now(),
        ]);
    }

    public function recordFromRequest(
        Request $request,
        string  $action,
        string  $resourceType,
        ?int    $resourceId,
        string  $actorType,
        ?int    $actorId,
        string  $actorLabel,
        ?int    $tenantId = null,
        ?array  $payload = null,
    ): AuditLog {
        return $this->record(
            action:       $action,
            resourceType: $resourceType,
            resourceId:   $resourceId,
            actorType:    $actorType,
            actorId:      $actorId,
            actorLabel:   $actorLabel,
            tenantId:     $tenantId,
            payload:      $payload,
            ip:           $request->ip(),
            userAgent:    $request->userAgent(),
        );
    }
}
