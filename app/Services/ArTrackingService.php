<?php

namespace App\Services;

use Illuminate\Http\Request;

class ArTrackingService
{
    /**
     * Gera token UUID v4 único para a notificação.
     */
    public function gerarToken(): string
    {
        return (string) \Illuminate\Support\Str::uuid();
    }

    /**
     * Gera hash SHA-256 do CPF para armazenamento LGPD-compliant.
     */
    public function hashCpf(string $cpf): string
    {
        $digits = preg_replace('/\D/', '', $cpf);
        return hash('sha256', $digits);
    }

    /**
     * Valida se o CPF informado corresponde ao hash armazenado.
     */
    public function validarCpf(string $cpfInformado, string $hashArmazenado): bool
    {
        return hash_equals($hashArmazenado, $this->hashCpf($cpfInformado));
    }

    /**
     * Gera hash SHA-256 do conteúdo do boleto para integridade do documento.
     */
    public function hashDocumento(array $dadosBoleto): string
    {
        ksort($dadosBoleto);
        return hash('sha256', json_encode($dadosBoleto));
    }

    /**
     * Detecta se a requisição é de um bot (preview do WhatsApp, scanners, etc.)
     * e não de um ser humano abrindo o e-mail.
     */
    public function isBot(Request $request): bool
    {
        $ua = strtolower($request->userAgent() ?? '');

        $bots = [
            'whatsapp',
            'facebookexternalhit',
            'twitterbot',
            'linkedinbot',
            'googlebot',
            'bingbot',
            'slackbot',
            'telegrambot',
            'preview',
        ];

        foreach ($bots as $bot) {
            if (str_contains($ua, $bot)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Monta a URL do pixel de rastreamento para o e-mail.
     */
    public function urlPixel(string $token): string
    {
        return url("/ar/pixel/{$token}");
    }

    /**
     * Monta a URL da landing page de acesso/confirmação do boleto.
     */
    public function urlBoleto(string $token): string
    {
        return url("/ar/boleto/{$token}");
    }

    /**
     * Retorna o GIF 1×1 transparente em bytes (padrão da indústria para pixel tracking).
     */
    public function gifTransparente(): string
    {
        return base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }
}
