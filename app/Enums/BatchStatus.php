<?php

namespace App\Enums;

enum BatchStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Partial    = 'partial';
    case Failed     = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Aguardando processamento',
            self::Processing => 'Processando',
            self::Completed  => 'Concluído',
            self::Partial    => 'Parcialmente concluído',
            self::Failed     => 'Falhou',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Partial, self::Failed]);
    }
}
