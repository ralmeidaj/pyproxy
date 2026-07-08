<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateArEvidencePdfJob;
use App\Models\ArDigitalNotification;
use App\Services\ArDigitalService;
use App\Services\ArTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class ArTrackingController extends Controller
{
    public function __construct(
        private readonly ArTrackingService $tracking,
        private readonly ArDigitalService  $arDigital,
    ) {}

    /**
     * Passo 3 — pixel de rastreamento.
     * Retorna GIF 1×1 transparente e registra evento de abertura se for humano.
     */
    public function pixel(Request $request, string $token): Response
    {
        if (! $this->tracking->isBot($request)) {
            $notification = ArDigitalNotification::where('token', $token)
                ->whereNotIn('status', ['lido', 'confirmado'])
                ->first();

            if ($notification) {
                $this->arDigital->processarEvento($notification, 'abertura', 'email', [
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'geolocation' => $this->resolveGeo($request->ip()),
                ]);
            }
        }

        return response($this->tracking->gifTransparente(), 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * Passo 4 (opcional) — landing page de acesso ao boleto.
     * Exibe formulário de confirmação de CPF se o tenant tiver cpf_confirmation ativo.
     */
    public function exibirBoleto(string $token): mixed
    {
        $notification = ArDigitalNotification::with(['boleto.tenant.arDigitalConfig'])
            ->where('token', $token)
            ->firstOrFail();

        $config  = $notification->boleto->tenant->arDigitalConfig;
        $boleto  = $notification->boleto;

        return Inertia::render('ArDigital/ConfirmarRecebimento', [
            'token'            => $token,
            'valor'            => number_format($boleto->amount_cents / 100, 2, ',', '.'),
            'vencimento'       => $boleto->due_date->format('d/m/Y'),
            'tenant_nome'      => $boleto->tenant->name,
            'cpf_confirmation' => $config?->cpf_confirmation ?? false,
            'link_boleto'      => $boleto->pdf_url,
            'status'           => $notification->status,
        ]);
    }

    /**
     * Passo 4 (opcional) — confirmação de CPF pelo contribuinte.
     * Valida CPF, registra evento e retorna link do boleto.
     */
    public function confirmarRecebimento(Request $request, string $token): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'cpf' => ['required', 'string'],
        ]);

        $notification = ArDigitalNotification::with(['boleto.tenant.arDigitalConfig'])
            ->where('token', $token)
            ->firstOrFail();

        // Valida CPF contra o hash armazenado (LGPD — não guardamos o CPF em claro)
        if (! $this->tracking->validarCpf($request->cpf, $notification->cpf_hash)) {
            return response()->json(['message' => 'CPF não corresponde ao titular do boleto.'], 422);
        }

        // Idempotência — já confirmado
        if ($notification->status === 'confirmado') {
            return response()->json(['link_boleto' => $notification->boleto->pdf_url]);
        }

        $this->arDigital->processarEvento($notification, 'confirmacao_cpf', 'email', [
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'geolocation' => $this->resolveGeo($request->ip()),
        ]);

        // Dispara geração do laudo PDF após confirmação
        GenerateArEvidencePdfJob::dispatch($notification->id)->delay(now()->addSeconds(5));

        return response()->json(['link_boleto' => $notification->boleto->pdf_url]);
    }

    private function resolveGeo(string $ip): ?string
    {
        // IPs locais e de loopback não têm geolocalização
        if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'local';
        }

        // Em produção: integrar com ip-api.com ou MaxMind GeoIP
        return null;
    }
}
