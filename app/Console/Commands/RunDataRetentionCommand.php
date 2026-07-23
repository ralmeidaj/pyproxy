<?php

namespace App\Console\Commands;

use App\Services\AuditLogService;
use App\Services\DataRetentionService;
use Illuminate\Console\Command;

class RunDataRetentionCommand extends Command
{
    protected $signature = 'data:retention {--dry-run : Simula o expurgo sem alterar dados}';
    protected $description = 'Executa a política de retenção e expurgo de dados pessoais (LGPD)';

    public function __construct(
        private readonly DataRetentionService $service,
        private readonly AuditLogService      $auditLog,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN — nenhum dado será alterado.');
        }

        $results = [
            'boletos_anonimizados'       => $this->service->anonymizeBoletos($dryRun),
            'ar_events_anonimizados'     => $this->service->anonymizeArEvents($dryRun),
            'notif_logs_anonimizados'    => $this->service->purgeNotificationLogs($dryRun),
            'audit_logs_excluidos'       => $this->service->purgeAuditLogs($dryRun),
            'api_keys_excluidas'         => $this->service->purgeRevokedApiKeys($dryRun),
        ];

        $this->table(
            ['Operação', 'Registros afetados'],
            [
                ['Boletos anonimizados (>5 anos)',              $results['boletos_anonimizados']],
                ['Notificações AR anonimizadas (>5 anos)',      $results['ar_events_anonimizados']],
                ['Logs de notificação anonimizados (>5 anos)',  $results['notif_logs_anonimizados']],
                ['Audit logs excluídos (>2 anos)',              $results['audit_logs_excluidos']],
                ['API Keys revogadas excluídas (>90d)',         $results['api_keys_excluidas']],
            ]
        );

        if (! $dryRun) {
            $this->auditLog->record(
                action:       'data_retention.executed',
                resourceType: 'System',
                resourceId:   null,
                actorType:    'system',
                actorId:      null,
                actorLabel:   'scheduler',
                payload:      $results,
            );
        }

        $this->info($dryRun ? 'Simulação concluída.' : 'Expurgo concluído.');

        return Command::SUCCESS;
    }
}
