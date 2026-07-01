<?php

namespace App\Http\Middleware;

use App\Models\Boleto;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HmacWebhookMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $payload  = $request->all();
        $rawBody  = $request->getContent();

        // 1. Valida HMAC-SHA256 se o secret estiver configurado no ambiente
        $hmacSecret = config('services.pjbank.webhook_secret');

        if ($hmacSecret) {
            $receivedSig  = $request->header('X-PJBank-Hmac-Sha256', '');
            $expectedSig  = 'sha256=' . hash_hmac('sha256', $rawBody, $hmacSecret);

            if (! hash_equals($expectedSig, $receivedSig)) {
                Log::warning('PJBank webhook: assinatura HMAC inválida', [
                    'ip'       => $request->ip(),
                    'sig_recv' => substr($receivedSig, 0, 16) . '...',
                ]);
                return response()->json(['error' => 'Assinatura inválida.'], 401);
            }
        }

        // 2. Localiza o boleto pelo identificador enviado pelo PJBank
        $bankBoletoId = $payload['nosso_numero'] ?? $payload['token_transaction'] ?? null;

        if (! $bankBoletoId) {
            Log::warning('PJBank webhook: payload sem nosso_numero/token_transaction', [
                'ip' => $request->ip(),
            ]);
            // Retorna 200 para evitar retry infinito do PJBank
            return response()->json(['ok' => true]);
        }

        $boleto = Boleto::where('bank_boleto_id', $bankBoletoId)
            ->with('boletoConfig')
            ->first();

        if (! $boleto) {
            Log::warning("PJBank webhook: boleto não encontrado — bank_boleto_id={$bankBoletoId}", [
                'ip' => $request->ip(),
            ]);
            return response()->json(['ok' => true]);
        }

        // 3. Valida credencial — fail-closed (rejeita se ausente ou não confere)
        $incomingCredencial = $payload['credencial'] ?? null;
        $config             = $boleto->boletoConfig;

        if ($config && $config->credentials_encrypted) {
            try {
                $storedCredencial = $config->getCredentials()['api_key'] ?? null;

                if (! $incomingCredencial || ! $storedCredencial || ! hash_equals($storedCredencial, $incomingCredencial)) {
                    Log::warning('PJBank webhook: credencial inválida ou ausente', [
                        'bank_boleto_id'      => $bankBoletoId,
                        'credencial_presente' => ! is_null($incomingCredencial),
                        'ip'                  => $request->ip(),
                    ]);
                    return response()->json(['error' => 'Credencial inválida.'], 401);
                }
            } catch (\Throwable $e) {
                Log::error('PJBank webhook: falha ao descriptografar credencial', [
                    'bank_boleto_id' => $bankBoletoId,
                    'error'          => $e->getMessage(),
                ]);
                // Falha na cripto — retorna 200 para não bloquear pagamento legítimo
                return response()->json(['ok' => true]);
            }
        }

        // Compartilha o boleto já carregado com o controller (evita segunda query)
        $request->attributes->set('resolved_boleto', $boleto);

        return $next($request);
    }
}
