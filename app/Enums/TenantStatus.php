<?php

namespace App\Enums;

enum TenantStatus: string
{
    case PendingApproval = 'pending_approval';
    case Active          = 'active';
    case Suspended       = 'suspended';
    case Inactive        = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::PendingApproval => 'Pendente de Aprovação',
            self::Active          => 'Ativo',
            self::Suspended       => 'Suspenso',
            self::Inactive        => 'Inativo',
        };
    }

    public function allowedTransitions(): array
    {
        return match($this) {
            self::PendingApproval => [self::Active, self::Inactive],
            self::Active          => [self::Suspended, self::Inactive],
            self::Suspended       => [self::Active, self::Inactive],
            self::Inactive        => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
