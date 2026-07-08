<?php

namespace App\Services;

use App\Jobs\ApplyRfc3161TimestampJob;
use App\Jobs\GenerateArEvidencePdfJob;
use App\Models\ArDigitalEvent;
use App\Models\ArDigitalNotification;
use App\Models\Boleto;
use Illuminate\Support\Facades\Log;

class ArDigitalService
{
    public function __construct(
        private readonly ArTrackingService $tracking,
    ) {}

    /**
     * Ponto de entrada: cria a notificação AR Digital para um boleto.
     * Chamado pelo BoletoService após emissão, se o tenant tiver AR Digital ativo.
     */
    public function notificar(Boleto $boleto): ?ArDigitalNotification
    {
        $boleto->loadMissing(['tenant.arDigitalConfig']);

        $config = $boleto->tenant->arDigitalConfig;

        if (! $config || ! $config->enabled) {
            return null;
        }

        // Gera hash do conteúdo do boleto para integridade do documento
        $hashDocumento = $this->tracking->hashDocumento([
            'boleto_id'       => $boleto->id,
            'external_ref'    => $boleto->external_ref,
            'amount_cents'    => $boleto->amount_cents,
            'due_date'        => $boleto->due_date?->toDateString(),
            'payer_document'  => $boleto->payer_document,
            'bank_boleto_id'  => $boleto->bank_boleto_id,
        ]);

        $token = $this->tracking->gerarToken();

        $notification = ArDigitalNotification::create([
            'boleto_id'             => $boleto->id,
            'tenant_id'             => $boleto->tenant_id,
            'destinatario_email'    => $boleto->payer_email,
            'destinatario_whatsapp' => $boleto->payer_phone,
            'cpf_hash'              => $boleto->payer_document
                ? $this->tracking->hashCpf($boleto->payer_document)
                : null,
            'hash_documento'        => $hashDocumento,
            'token'                 => $token,
            'status'                => 'enviado',
        ]);

        // Registra passo 1 (envio) — será carimbado pelo Job
        $eventoEnvio = $this->registrarEvento($notification, 'envio', 'email');

        // Dispara carimbo RFC 3161 do passo 1 assincronamente
        ApplyRfc3161TimestampJob::dispatch($eventoEnvio->id);

        // Gera o laudo com delay: aguarda o carimbo do envio ser aplicado (≈ 120s)
        GenerateArEvidencePdfJob::dispatch($notification->id)->delay(now()->addSeconds(120));

        Log::info('AR Digital: notificação criada', [
            'notification_id' => $notification->id,
            'boleto_id'       => $boleto->id,
            'tenant_id'       => $boleto->tenant_id,
            'token'           => $token,
        ]);

        return $notification;
    }

    /**
     * Processa um evento recebido (pixel, DSN SMTP, confirmação CPF, webhook WhatsApp).
     * Chamado pelos controllers e jobs de webhook.
     */
    public function processarEvento(
        ArDigitalNotification $notification,
        string $tipo,
        string $canal,
        array $dados = [],
    ): ArDigitalEvent {
        $evento = $this->registrarEvento($notification, $tipo, $canal, $dados);

        // Dispara carimbo RFC 3161 assincronamente
        ApplyRfc3161TimestampJob::dispatch($evento->id);

        // Atualiza status somente se o novo for mais avançado que o atual (sem downgrade)
        $novoStatus = $this->resolverStatus($tipo);
        if ($novoStatus && $this->statusMaisAvancado($novoStatus, $notification->status)) {
            $notification->update(['status' => $novoStatus]);

            // Ao atingir estado terminal, regenera o laudo com a cadeia completa de evidências
            if (in_array($novoStatus, ['confirmado', 'bounce'], true)) {
                GenerateArEvidencePdfJob::dispatch($notification->id);
            }
        }

        return $evento;
    }

    /**
     * Registra um evento na tabela ar_digital_events.
     */
    public function registrarEvento(
        ArDigitalNotification $notification,
        string $tipo,
        string $canal,
        array $dados = [],
    ): ArDigitalEvent {
        return ArDigitalEvent::create([
            'notification_id' => $notification->id,
            'tipo'            => $tipo,
            'canal'           => $canal,
            'ip'              => $dados['ip'] ?? null,
            'user_agent'      => $dados['user_agent'] ?? null,
            'geolocation'     => $dados['geolocation'] ?? null,
            'smtp_code'       => $dados['smtp_code'] ?? null,
            'smtp_response'   => $dados['smtp_response'] ?? null,
            'ocorrido_em'     => now(),
        ]);
    }

    /**
     * Mapeia tipo de evento para o status da notificação.
     */
    private function resolverStatus(string $tipo): ?string
    {
        return match ($tipo) {
            'entrega_provedor' => 'entregue',
            'abertura'         => 'lido',
            'confirmacao_cpf'  => 'confirmado',
            'bounce'           => 'bounce',
            default            => null,
        };
    }

    /**
     * Retorna true somente se $novo é mais avançado que $atual na progressão de status.
     * Bounce é terminal e não regride — mas pode entrar se ainda estiver em enviado.
     */
    private function statusMaisAvancado(string $novo, string $atual): bool
    {
        $ordem = ['enviado' => 0, 'entregue' => 1, 'lido' => 2, 'confirmado' => 3];

        // bounce entra somente se o status atual ainda for enviado
        if ($novo === 'bounce') {
            return $atual === 'enviado';
        }

        return ($ordem[$novo] ?? -1) > ($ordem[$atual] ?? -1);
    }
}
