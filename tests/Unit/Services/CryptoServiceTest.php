<?php

namespace Tests\Unit\Services;

use App\Services\CryptoService;
use RuntimeException;
use Tests\TestCase;

class CryptoServiceTest extends TestCase
{
    private CryptoService $crypto;

    protected function setUp(): void
    {
        parent::setUp();

        // Set a valid 32-byte base64-encoded key for tests
        config(['app.crypto_key' => base64_encode(str_repeat('A', 32))]);

        $this->crypto = new CryptoService();
    }

    public function test_encrypt_returns_non_empty_string(): void
    {
        $encrypted = $this->crypto->encrypt('hello world');

        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals('hello world', $encrypted);
    }

    public function test_decrypt_recovers_original_value(): void
    {
        $original  = 'sensitive-data-123';
        $encrypted = $this->crypto->encrypt($original);

        $this->assertEquals($original, $this->crypto->decrypt($encrypted));
    }

    public function test_encrypted_values_are_unique_due_to_random_iv(): void
    {
        $a = $this->crypto->encrypt('same value');
        $b = $this->crypto->encrypt('same value');

        $this->assertNotEquals($a, $b);
    }

    public function test_decrypt_different_values_correctly(): void
    {
        $values = ['CPF: 12345678901', 'CNPJ: 00000000000191', 'secret-api-key'];

        foreach ($values as $value) {
            $this->assertEquals($value, $this->crypto->decrypt($this->crypto->encrypt($value)));
        }
    }

    public function test_decrypt_throws_on_tampered_ciphertext(): void
    {
        $encrypted = $this->crypto->encrypt('data');
        $tampered  = base64_encode('tampered-junk-that-is-definitely-wrong-length-padding');

        $this->expectException(RuntimeException::class);
        $this->crypto->decrypt($tampered);
    }

    public function test_hmac_returns_hex_string(): void
    {
        $mac = $this->crypto->hmac('payload', 'secret');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $mac);
    }

    public function test_hmac_is_deterministic(): void
    {
        $a = $this->crypto->hmac('payload', 'secret');
        $b = $this->crypto->hmac('payload', 'secret');

        $this->assertEquals($a, $b);
    }

    public function test_hmac_differs_with_different_secret(): void
    {
        $a = $this->crypto->hmac('payload', 'secret-a');
        $b = $this->crypto->hmac('payload', 'secret-b');

        $this->assertNotEquals($a, $b);
    }

    public function test_verify_hmac_returns_true_for_valid_signature(): void
    {
        $mac = $this->crypto->hmac('payload', 'secret');

        $this->assertTrue($this->crypto->verifyHmac('payload', 'secret', $mac));
    }

    public function test_verify_hmac_returns_false_for_invalid_signature(): void
    {
        $this->assertFalse($this->crypto->verifyHmac('payload', 'secret', 'wrong-signature'));
    }

    public function test_hash_api_key_returns_64_char_hex(): void
    {
        $hash = $this->crypto->hashApiKey('my-api-key');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function test_hash_api_key_is_deterministic(): void
    {
        $this->assertEquals(
            $this->crypto->hashApiKey('key'),
            $this->crypto->hashApiKey('key'),
        );
    }

    public function test_hash_api_key_differs_for_different_keys(): void
    {
        $this->assertNotEquals(
            $this->crypto->hashApiKey('key-a'),
            $this->crypto->hashApiKey('key-b'),
        );
    }

    public function test_throws_when_crypto_key_is_missing(): void
    {
        config(['app.crypto_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/CRYPTO_KEY/');

        new CryptoService();
    }
}
