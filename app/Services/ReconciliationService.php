<?php

namespace App\Services;

use App\Enums\BoletoStatus;
use App\Models\Boleto;
use App\Services\BankPartners\BankPartnerFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    public function __construct(
        private readonly BankPartnerFactory     $bankPartnerFactory,
        private readonly BoletoService          $boletoService,
        private readonly WebhookDeliveryService $webhookDelivery,
        private readonly AuditLogService        $auditLog,
    ) {}

    /**
     * Consulta proativamente o parceiro bancário para boletos pendentes dentro
     * da janela de busca e atualiza status conforme resposta (RF-29).
     *
     * @return array{consulted: int, paid: int, other: int, errors: int}
     */
    public function run(int $windowDays = 30, int $batchSize = 50): array
    {
        $stats = ['consulted' => 0, 'paid' => 0, 'other' => 0, 'errors' => 0];

        Boleto::with(['boletoConfig.bankPartner'])
            ->where('status', BoletoStatus::Pending->value)
            ->whereBetween('due_date', [
                now()->subDays($windowDays)->toDateString(),
                now()->toDateString(),
            ])
            ->chunkById($batchSize, function (Collection $boletos) use (&$stats): void {
                foreach ($boletos as $boleto) {
                    $this->reconcileBoleto($boleto, $stats);
                }
            });

        $this->auditLog->record(
            action:       'reconciliation.completed',
            resourceType: 'System',
            resourceId:   null,
            actorType:    'system',
            actorId:      null,
            actorLabel:   'SYSTEM/reconciliation-job',
            tenantId:     null,
            payload:      $stats,
        );

        return $stats;
    }

    private function reconcileBoleto(Boleto $boleto, array &$stats): void
    {
        $stats['consulted']++;

        if (! $boleto->bank_boleto_id) {
            $stats['errors']++;
            Log::warning("Reconciliação: boleto {$boleto->id} sem bank_boleto_id.");
            return;
        }

        $config = $boleto->boletoConfig;

        if (! $config) {
            $stats['errors']++;
            Log::warning("Reconciliação: boleto {$boleto->id} sem BoletoConfig associada.");
            return;
        }

        try {
            $adapter = $this->bankPartnerFactory->make($config->bankPartner);
            $result  = $adapter->getBoletoStatus($boleto->bank_boleto_id, $config);

            $partnerStatus = strtolower($result['status'] ?? '');

            match(true) {
                in_array($partnerStatus, ['pago', 'liquidado', 'paid']) => $this->handlePaid($boleto, $result, $stats),
                in_array($partnerStatus, ['cancelado', 'cancelled'])    => $this->handleCancelled($boleto, $stats),
                in_array($partnerStatus, ['vencido', 'expirado', 'expired']) => $this->handleExpired($boleto, $stats),
                default                                                  => $stats['other']++,
            };
        } catch (\Throwable $e) {
            $stats['errors']++;
            Log::error('Reconciliação: falha ao consultar parceiro bancário', [
                'boleto_id'    => $boleto->id,
                'external_ref' => $boleto->external_ref,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    private function handlePaid(Boleto $boleto, array $result, array &$stats): void
    {
        $paidAmountCents = isset($result['valor_pago'])
            ? (int) round((float) $result['valor_pago'] * 100)
            : $boleto->amount_cents;

        $channel = $result['forma_pagamento'] ?? 'unknown';

        $paidAt = isset($result['data_pagamento'])
            ? \Carbon\Carbon::parse($result['data_pagamento'])
            : now();

        $this->boletoService->markAsPaid($boleto, $paidAmountCents, $channel, $paidAt);

        $this->auditLog->record(
            action:       'boleto.paid_by_reconciliation',
            resourceType: 'Boleto',
            resourceId:   $boleto->id,
            actorType:    'system',
            actorId:      null,
            actorLabel:   'SYSTEM/reconciliation-job',
            tenantId:     $boleto->tenant_id,
            payload:      [
                'external_ref'      => $boleto->external_ref,
                'paid_amount_cents' => $paidAmountCents,
                'channel'           => $channel,
                'paid_at'           => $paidAt->toIso8601String(),
            ],
        );

        $this->webhookDelivery->dispatch($boleto->fresh());

        $stats['paid']++;
    }

    private function handleCancelled(Boleto $boleto, array &$stats): void
    {
        $boleto->updateQuietly([
            'status'       => BoletoStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $stats['other']++;
    }

    private function handleExpired(Boleto $boleto, array &$stats): void
    {
        $boleto->updateQuietly(['status' => BoletoStatus::Expired]);

        $stats['other']++;
    }
}
