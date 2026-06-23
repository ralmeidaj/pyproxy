<?php

namespace App\Services;

use RuntimeException;

class CryptoService
{
    private const CIPHER = 'aes-256-gcm';
    private const TAG_LENGTH = 16;
    private const IV_LENGTH = 12;

    private string $key;

    public function __construct()
    {
        $raw = config('app.crypto_key');

        if (empty($raw)) {
            throw new RuntimeException('CRYPTO_KEY não configurada. Execute: php artisan payproxy:generate-crypto-key');
        }

        $decoded = base64_decode($raw, strict: true);

        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('CRYPTO_KEY inválida. Deve ser 32 bytes codificados em base64.');
        }

        $this->key = $decoded;
    }

    /**
     * Criptografa um valor com AES-256-GCM.
     * Retorna string base64 no formato: iv.tag.ciphertext
     */
    public function encrypt(string $value): string
    {
        $iv  = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $value,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH,
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Falha na criptografia: ' . openssl_error_string());
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Descriptografa um valor cifrado com encrypt().
     */
    public function decrypt(string $encrypted): string
    {
        $raw = base64_decode($encrypted, strict: true);

        if ($raw === false) {
            throw new RuntimeException('Valor criptografado inválido (base64).');
        }

        $minLength = self::IV_LENGTH + self::TAG_LENGTH;

        if (strlen($raw) <= $minLength) {
            throw new RuntimeException('Valor criptografado muito curto.');
        }

        $iv         = substr($raw, 0, self::IV_LENGTH);
        $tag        = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, $minLength);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($plaintext === false) {
            throw new RuntimeException('Falha na descriptografia (tag inválida ou dado corrompido).');
        }

        return $plaintext;
    }

    /**
     * Gera HMAC-SHA256 de um payload usando um segredo externo.
     * Usado para assinatura de webhooks.
     */
    public function hmac(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verifica HMAC-SHA256 de forma timing-safe.
     */
    public function verifyHmac(string $payload, string $secret, string $signature): bool
    {
        return hash_equals($this->hmac($payload, $secret), $signature);
    }

    /**
     * Gera hash SHA-256 de uma API key para armazenamento seguro.
     */
    public function hashApiKey(string $key): string
    {
        return hash('sha256', $key);
    }
}
