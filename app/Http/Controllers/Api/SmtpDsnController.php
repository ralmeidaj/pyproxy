<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSmtpDsnJob;
use App\Models\ArDigitalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmtpDsnController extends Controller
{
    /**
     * Recebe notificações DSN do provedor de e-mail (entrega / bounce).
     *
     * Cada provedor tem um formato diferente. O controller normaliza
     * os campos principais e delega o processamento para o job.
     *
     * Provedores suportados: Postmark, Mailgun, SendGrid, genérico.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('SMTP DSN: webhook recebido', ['payload' => $payload]);

        $normalizado = $this->normalizar($payload);

        if (! $normalizado) {
            return response()->json(['ok' => false, 'reason' => 'formato não reconhecido'], 200);
        }

        // Proteção contra ar_token recebido como array (ex.: campo duplicado em query string + body)
        if (is_array($normalizado['token'])) {
            Log::warning('SMTP DSN: ar_token recebido como array', ['raw' => $normalizado['token']]);
            return response()->json(['ok' => false, 'reason' => 'token inválido'], 200);
        }

        $token        = (string) $normalizado['token'];
        $code         = (string) $normalizado['code'];
        $smtpResponse = is_array($normalizado['response'])
            ? implode('; ', array_filter((array) $normalizado['response'], 'is_string'))
            : (string) $normalizado['response'];

        // Verifica se existe uma notificação AR com esse token
        $existe = ArDigitalNotification::where('token', $token)->exists();

        if (! $existe) {
            // Não é erro — pode ser e-mail de outra parte do sistema
            return response()->json(['ok' => true]);
        }

        ProcessSmtpDsnJob::dispatch($token, $code, $smtpResponse);

        return response()->json(['ok' => true]);
    }

    /**
     * Normaliza o payload de diferentes provedores para o formato interno.
     * Retorna null se o formato não for reconhecido.
     *
     * @return array{token: string, code: string, response: string}|null
     */
    private function normalizar(array $payload): ?array
    {
        // Postmark: DeliveryMessage / BounceMessage
        if (isset($payload['MessageID'], $payload['DeliveryMessage'])) {
            return [
                'token'    => $payload['Metadata']['ar_token'] ?? '',
                'code'     => '250',
                'response' => $payload['DeliveryMessage'],
            ];
        }

        if (isset($payload['MessageID'], $payload['Type']) && $payload['Type'] === 'HardBounce') {
            return [
                'token'    => $payload['Metadata']['ar_token'] ?? '',
                'code'     => '550',
                'response' => $payload['Description'] ?? 'Hard bounce',
            ];
        }

        // Mailgun: delivered / failed
        if (isset($payload['event'], $payload['message-headers'])) {
            $token = $payload['user-variables']['ar_token'] ?? '';
            $code  = $payload['event'] === 'delivered' ? '250' : '550';
            return ['token' => $token, 'code' => $code, 'response' => $payload['description'] ?? $payload['event']];
        }

        // SendGrid: eventos em array
        if (isset($payload[0]['event'], $payload[0]['ar_token'])) {
            $item  = $payload[0];
            $code  = in_array($item['event'], ['delivered']) ? '250' : '550';
            return ['token' => $item['ar_token'], 'code' => $code, 'response' => $item['reason'] ?? $item['event']];
        }

        // Genérico: campos diretos
        if (isset($payload['ar_token'], $payload['smtp_code'])) {
            return [
                'token'    => $payload['ar_token'],
                'code'     => (string) $payload['smtp_code'],
                'response' => $payload['smtp_response'] ?? '',
            ];
        }

        return null;
    }
}
