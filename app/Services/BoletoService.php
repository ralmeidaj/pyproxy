<?php

namespace App\Services;

use App\DTOs\IssueBoletoData;
use App\Enums\BoletoStatus;
use App\Exceptions\BoletoCannotBeCancelledException;
use App\Exceptions\BoletoConfigNotFoundException;
use App\Exceptions\BankPartnerException;
use App\Models\Boleto;
use App\Models\BoletoSplit;
use App\Models\Tenant;
use App\Services\BankPartners\BankPartnerFactory;
use Illuminate\Support\Facades\DB;

class BoletoService
{
    public function __construct(
        private readonly BankPartnerFactory $bankPartnerFactory,
        private readonly SplitService       $splitService,
        private readonly AuditLogService    $auditLog,
    ) {}

    public function issue(Tenant $tenant, IssueBoletoData $data, ?int $configId = null): Boleto
    {
        // RN-01: Idempotência — retorna boleto existente se válido
        $existing = Boleto::where('tenant_id', $tenant->id)
            ->where('external_ref', $data->externalRef)
            ->whereNotIn('status', [BoletoStatus::Cancelled->value, BoletoStatus::Expired->value])
            ->first();

        if ($existing) {
            return $existing;
        }

        $query = $tenant->boletoConfigs()
            ->where('status', 'active')
            ->with(['bankPartner', 'splitConfigs']);

        $config = $configId
            ? $query->find($configId)
            : $query->where('is_default', true)->first();

        if (! $config) {
            throw new BoletoConfigNotFoundException('Tenant não possui configuração de boleto ativa.');
        }

        // RF-PART-06: valida se parceiro suporta split (quando houver splits configurados)
        if ($config->splitConfigs->isNotEmpty() && ! $config->bankPartner->supports('split')) {
            throw new BankPartnerException('O parceiro bancário selecionado não suporta split de pagamento.');
        }

        // Calcula splits (RN-02, RN-08)
        $splits = $this->splitService->calculate($config, $data->amountCents);

        // Snapshot da configuração (RN-06)
        $configSnapshot = [
            'bank_partner'               => $config->bankPartner->slug,
            'prazo_vencimento_dias'      => $config->prazo_vencimento_dias,
            'multa_percentual'           => $config->multa_percentual,
            'juros_percentual_mes'       => $config->juros_percentual_mes,
            'desconto_percentual'        => $config->desconto_percentual,
            'desconto_antecedencia_dias' => $config->desconto_antecedencia_dias,
            'instrucoes'                 => $config->instrucoes,
        ];

        // Emite no parceiro bancário (passa splits calculados para o adapter)
        $adapter = $this->bankPartnerFactory->make($config->bankPartner);
        $result  = $adapter->issueBoleto($data, $config, $splits);

        $boleto = DB::transaction(function () use ($tenant, $data, $config, $result, $splits, $configSnapshot): Boleto {
            $boleto = Boleto::create([
                'tenant_id'        => $tenant->id,
                'boleto_config_id' => $config->id,
                'bank_partner_id'  => $config->bank_partner_id,
                'external_ref'     => $data->externalRef,
                'status'           => BoletoStatus::Pending,
                'amount_cents'     => $data->amountCents,
                'due_date'         => $data->dueDate,
                'payer_name'       => $data->payerName,
                'payer_document'   => $data->payerDocument,
                'payer_email'      => $data->payerEmail,
                'payer_phone'      => $data->payerPhone,
                'payer_address'    => $data->payerAddress,
                'bank_boleto_id'        => $result->bankBoletoId,
                'token_facilitador'     => $result->tokenFacilitador,
                'barcode'               => $result->barcode,
                'digitable_line'   => $result->digitableLine,
                'pix_qr_code'      => $result->pixQrCode,
                'pdf_url'          => $result->pdfUrl,
                'dda_registered'   => $result->ddaRegistered,
                'config_snapshot'  => $configSnapshot,
                'splits_snapshot'  => $splits,
                'metadata'         => $data->metadata,
            ]);

            // Persiste splits individuais para rastreabilidade
            foreach ($splits as $split) {
                BoletoSplit::create(array_merge(['boleto_id' => $boleto->id], $split, ['created_at' => now()]));
            }

            $this->auditLog->record(
                action:       'boleto.issued',
                resourceType: 'Boleto',
                resourceId:   $boleto->id,
                actorType:    'tenant',
                actorId:      $tenant->id,
                actorLabel:   $tenant->name,
                tenantId:     $tenant->id,
                payload:      ['external_ref' => $data->externalRef, 'amount_cents' => $data->amountCents],
            );

            return $boleto;
        });

        return $boleto;
    }

    public function cancel(Boleto $boleto, Tenant $tenant): Boleto
    {
        // RN-03: só cancela boletos pendentes
        if (! $boleto->status->canCancel()) {
            throw new BoletoCannotBeCancelledException(
                "Boleto com status '{$boleto->status->label()}' não pode ser cancelado."
            );
        }

        $config  = $boleto->boletoConfig()->with('bankPartner')->first();
        $adapter = $this->bankPartnerFactory->make($boleto->bankPartner);
        $adapter->cancelBoleto($boleto->bank_boleto_id, $config);

        DB::transaction(function () use ($boleto, $tenant): void {
            $boleto->update([
                'status'       => BoletoStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $this->auditLog->record(
                action:       'boleto.cancelled',
                resourceType: 'Boleto',
                resourceId:   $boleto->id,
                actorType:    'tenant',
                actorId:      $tenant->id,
                actorLabel:   $tenant->name,
                tenantId:     $tenant->id,
                payload:      ['external_ref' => $boleto->external_ref],
            );
        });

        return $boleto->fresh();
    }

    public function markAsPaid(Boleto $boleto, int $paidAmountCents, string $channel, \DateTimeInterface $paidAt): Boleto
    {
        if ($boleto->status->isFinal()) {
            return $boleto; // RN-07 / RF-26: idempotência
        }

        $boleto->update([
            'status'           => BoletoStatus::Paid,
            'paid_at'          => $paidAt,
            'paid_amount_cents' => $paidAmountCents,
            'paid_channel'     => $channel,
        ]);

        return $boleto->fresh();
    }

    public function paginate(Tenant $tenant, int $perPage = 20, ?string $status = null, ?string $search = null)
    {
        return Boleto::where('tenant_id', $tenant->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where(fn ($q) =>
                $q->where('external_ref', 'ilike', "%{$search}%")
                  ->orWhere('payer_name', 'ilike', "%{$search}%")
                  ->orWhere('payer_document', 'like', "%{$search}%")
            ))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
