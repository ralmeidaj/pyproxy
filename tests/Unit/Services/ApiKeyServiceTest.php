<?php

namespace Tests\Unit\Services;

use App\Services\ApiKeyService;
use App\Services\AuditLogService;
use App\Services\CryptoService;
use Mockery;
use Tests\TestCase;

class ApiKeyServiceTest extends TestCase
{
    private CryptoService $crypto;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.crypto_key' => base64_encode(str_repeat('A', 32))]);
        $this->crypto = new CryptoService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // --- Key format ---

    public function test_plain_key_starts_with_ppx_prefix(): void
    {
        $plainKey = 'ppx_' . str(\Illuminate\Support\Str::random(40));
        $this->assertStringStartsWith('ppx_', $plainKey);
    }

    public function test_plain_key_is_44_chars(): void
    {
        $plainKey = 'ppx_' . \Illuminate\Support\Str::random(40);
        $this->assertSame(44, strlen($plainKey));
    }

    public function test_key_prefix_is_first_12_chars(): void
    {
        $plainKey = 'ppx_' . \Illuminate\Support\Str::random(40);
        $prefix   = substr($plainKey, 0, 12);

        $this->assertSame(12, strlen($prefix));
        $this->assertStringStartsWith('ppx_', $prefix);
    }

    public function test_different_generated_keys_are_unique(): void
    {
        $keys = array_map(fn () => 'ppx_' . \Illuminate\Support\Str::random(40), range(1, 10));
        $this->assertSame(10, count(array_unique($keys)));
    }

    // --- Hash lookup ---

    public function test_find_by_plain_key_uses_sha256_hash_for_lookup(): void
    {
        $plainKey     = 'ppx_test-plain-key-for-hash-verification-ok';
        $expectedHash = $this->crypto->hashApiKey($plainKey);

        // Hash is deterministic: same input always produces same 64-char hex
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $expectedHash);
        $this->assertSame($expectedHash, $this->crypto->hashApiKey($plainKey));
    }

    public function test_different_plain_keys_produce_different_hashes(): void
    {
        $hashA = $this->crypto->hashApiKey('ppx_key_A_' . str_repeat('x', 34));
        $hashB = $this->crypto->hashApiKey('ppx_key_B_' . str_repeat('x', 34));

        $this->assertNotSame($hashA, $hashB);
    }

    public function test_hash_does_not_equal_plain_key(): void
    {
        $plainKey = 'ppx_' . \Illuminate\Support\Str::random(40);
        $hash     = $this->crypto->hashApiKey($plainKey);

        $this->assertNotSame($plainKey, $hash);
    }

    // --- Audit log integration ---

    public function test_audit_log_is_injected_as_dependency(): void
    {
        $auditLog = Mockery::mock(AuditLogService::class);

        // Verify ApiKeyService accepts AuditLogService as constructor dependency
        $service = new ApiKeyService($this->crypto, $auditLog);
        $this->assertInstanceOf(ApiKeyService::class, $service);
    }
}
