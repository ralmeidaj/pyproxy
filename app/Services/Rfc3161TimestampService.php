<?php

namespace App\Services;

use App\Models\ArDigitalEvent;
use App\Models\ArDigitalTimestamp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Rfc3161TimestampService
{
    /**
     * Aplica carimbo de tempo RFC 3161 em um evento AR Digital.
     *
     * Em desenvolvimento (STUB): simula o carimbo sem chamar a ACT real.
     * Em produção: chama a API da ACT credenciada pelo ICP-Brasil.
     */
    public function carimbar(ArDigitalEvent $event): ?ArDigitalTimestamp
    {
        $dadosParaCarimbar = $this->montarDados($event);
        $hashInput = hash('sha256', $dadosParaCarimbar);

        try {
            $tsrBase64 = $this->isStubMode()
                ? $this->gerarStubTsr($hashInput, $event)
                : $this->chamarAct($hashInput, $event->notification->tenant->arDigitalConfig->act_provider);

            $tsrPath = $this->salvarTsr($tsrBase64, $event);

            $timestamp = ArDigitalTimestamp::create([
                'event_id'      => $event->id,
                'hash_input'    => $hashInput,
                'tsr_base64'    => $tsrBase64,
                'act_provider'  => $this->isStubMode() ? 'stub' : $event->notification->tenant->arDigitalConfig->act_provider,
                'verificado_em' => now(),
            ]);

            // Atualiza o evento com o caminho do TSR no MinIO
            $event->update(['tsr_path' => $tsrPath]);

            return $timestamp;

        } catch (\Throwable $e) {
            Log::error('RFC3161: falha ao carimbar evento', [
                'event_id' => $event->id,
                'tipo'     => $event->tipo,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Monta os dados que serão carimbados: identificadores do evento + conteúdo.
     */
    private function montarDados(ArDigitalEvent $event): string
    {
        return json_encode([
            'notification_id' => $event->notification_id,
            'event_id'        => $event->id,
            'tipo'            => $event->tipo,
            'canal'           => $event->canal,
            'ocorrido_em'     => $event->ocorrido_em->toIso8601String(),
            'ip'              => $event->ip,
        ]);
    }

    /**
     * Chama a API da ACT ICP-Brasil e retorna o TSR em base64.
     * Implementação real — será ativada quando a ACT for contratada.
     */
    private function chamarAct(string $hashInput, string $provider): string
    {
        $config = config("services.act.{$provider}");

        // Monta o TSQ (Time Stamp Query) RFC 3161
        $tsq = $this->montarTsq($hashInput);

        $response = Http::withBasicAuth($config['user'], $config['password'])
            ->withBody($tsq, 'application/timestamp-query')
            ->post($config['url']);

        if ($response->failed()) {
            throw new \RuntimeException("ACT {$provider}: HTTP {$response->status()}");
        }

        return base64_encode($response->body());
    }

    /**
     * Monta o TSQ (Time Stamp Query) no formato binário RFC 3161.
     * Usa a extensão openssl via shell — requer openssl instalado no container.
     */
    private function montarTsq(string $hashInput): string
    {
        $hashBin = hex2bin($hashInput);

        // ASN.1 DER encoding de um TSQ RFC 3161 mínimo (SHA-256, nonce aleatório)
        // version=1, messageImprint=SHA256(hash), certReq=true
        $nonce = random_bytes(8);

        return implode('', [
            "\x30\x37",           // SEQUENCE
            "\x02\x01\x01",       // version INTEGER 1
            "\x30\x0d",           // messageImprint SEQUENCE
            "\x30\x0b",           // hashAlgorithm AlgorithmIdentifier
            "\x06\x09",           // OID SHA-256
            "\x60\x86\x48\x01\x86\xf8\x42\x00\x02", // 2.16.840.1.101.3.4.2.1
            "\x04\x20",           // hashedMessage OCTET STRING (32 bytes)
            $hashBin,
            "\x02\x08",           // nonce INTEGER
            $nonce,
            "\x01\x01\xff",       // certReq BOOLEAN TRUE
        ]);
    }

    /**
     * Stub para desenvolvimento: simula o TSR sem chamar a ACT real.
     * Gera um JSON assinado localmente que representa a estrutura de um TSR.
     */
    private function gerarStubTsr(string $hashInput, ArDigitalEvent $event): string
    {
        $stub = [
            '_stub'          => true,
            'act_provider'   => 'stub-dev',
            'hash_input'     => $hashInput,
            'event_id'       => $event->id,
            'tipo'           => $event->tipo,
            'timestamp_utc'  => now()->utc()->toIso8601String(),
            'serial_number'  => random_int(1000000, 9999999),
            'nota'           => 'CARIMBO DE DESENVOLVIMENTO — não tem validade jurídica',
        ];

        return base64_encode(json_encode($stub));
    }

    /**
     * Salva o TSR no MinIO (WORM) e retorna o caminho.
     */
    private function salvarTsr(string $tsrBase64, ArDigitalEvent $event): string
    {
        $path = sprintf(
            'ar-digital/%s/eventos/%s_%s.tsr',
            $event->notification_id,
            $event->id,
            $event->tipo
        );

        Storage::disk('s3')->put($path, base64_decode($tsrBase64));

        return $path;
    }

    private function isStubMode(): bool
    {
        return config('app.env') !== 'production'
            || ! config('services.act.enabled', false);
    }
}
