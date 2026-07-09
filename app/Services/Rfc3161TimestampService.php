<?php

namespace App\Services;

use App\Models\ArDigitalEvent;
use App\Models\ArDigitalTimestamp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Rfc3161TimestampService
{
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

    private function chamarAct(string $hashInput, string $provider): string
    {
        $config = config("services.act.{$provider}");

        $tsq = $this->montarTsq($hashInput);

        $http = Http::withBody($tsq, 'application/timestamp-query');
        if (! empty($config['user'])) {
            $http = $http->withBasicAuth($config['user'], $config['password']);
        }
        $response = $http->post($config['url']);

        if ($response->failed()) {
            throw new \RuntimeException("ACT {$provider}: HTTP {$response->status()}");
        }

        return base64_encode($response->body());
    }

    private function montarTsq(string $hashInput): string
    {
        $hashBin = hex2bin($hashInput);

        $nonceBin    = random_bytes(8);
        $nonceBin[0] = chr(ord($nonceBin[0]) & 0x7f);

        return implode('', [
            "\x30\x43",
            "\x02\x01\x01",
            "\x30\x31",
            "\x30\x0d",
            "\x06\x09",
            "\x60\x86\x48\x01\x65\x03\x04\x02\x01",
            "\x05\x00",
            "\x04\x20",
            $hashBin,
            "\x02\x08",
            $nonceBin,
            "\x01\x01\xff",
        ]);
    }

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
        ];

        return base64_encode(json_encode($stub));
    }

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
        return ! config('services.act.enabled', false);
    }
}