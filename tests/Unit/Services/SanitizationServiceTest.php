<?php

namespace Tests\Unit\Services;

use App\Services\SanitizationService;
use Tests\TestCase;

class SanitizationServiceTest extends TestCase
{
    private SanitizationService $sanitization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitization = new SanitizationService();
    }

    // ─── CPF ───────────────────────────────────────────────────────────────

    public function test_validate_cpf_returns_digits_for_valid_cpf(): void
    {
        // Known valid CPF
        $this->assertEquals('52998224725', $this->sanitization->validateCpf('529.982.247-25'));
    }

    public function test_validate_cpf_accepts_unformatted_digits(): void
    {
        $this->assertEquals('52998224725', $this->sanitization->validateCpf('52998224725'));
    }

    public function test_validate_cpf_returns_null_for_invalid_cpf(): void
    {
        $this->assertNull($this->sanitization->validateCpf('111.111.111-11'));
        $this->assertNull($this->sanitization->validateCpf('000.000.000-00'));
        $this->assertNull($this->sanitization->validateCpf('123.456.789-00'));
    }

    public function test_validate_cpf_returns_null_for_wrong_length(): void
    {
        $this->assertNull($this->sanitization->validateCpf('1234567890'));
        $this->assertNull($this->sanitization->validateCpf('123456789012'));
    }

    // ─── CNPJ ──────────────────────────────────────────────────────────────

    public function test_validate_cnpj_returns_digits_for_valid_cnpj(): void
    {
        // Caixa Econômica Federal — CNPJ público válido
        $this->assertEquals('00360305000104', $this->sanitization->validateCnpj('00.360.305/0001-04'));
    }

    public function test_validate_cnpj_accepts_unformatted_digits(): void
    {
        $this->assertEquals('00360305000104', $this->sanitization->validateCnpj('00360305000104'));
    }

    public function test_validate_cnpj_returns_null_for_invalid_cnpj(): void
    {
        $this->assertNull($this->sanitization->validateCnpj('11.111.111/1111-11'));
        $this->assertNull($this->sanitization->validateCnpj('00.000.000/0000-00'));
        $this->assertNull($this->sanitization->validateCnpj('12.345.678/0001-00'));
    }

    public function test_validate_cnpj_returns_null_for_wrong_length(): void
    {
        $this->assertNull($this->sanitization->validateCnpj('1234567890123'));
        $this->assertNull($this->sanitization->validateCnpj('123456789012345'));
    }

    // ─── Document (auto-detect) ─────────────────────────────────────────────

    public function test_validate_document_detects_cpf(): void
    {
        $this->assertEquals('52998224725', $this->sanitization->validateDocument('529.982.247-25'));
    }

    public function test_validate_document_detects_cnpj(): void
    {
        $this->assertEquals('00360305000104', $this->sanitization->validateDocument('00.360.305/0001-04'));
    }

    public function test_validate_document_returns_null_for_unknown_length(): void
    {
        $this->assertNull($this->sanitization->validateDocument('12345'));
        $this->assertNull($this->sanitization->validateDocument(''));
    }

    // ─── CEP format ────────────────────────────────────────────────────────

    public function test_format_cep_applies_mask(): void
    {
        $this->assertEquals('41810-001', $this->sanitization->formatCep('41810001'));
        $this->assertEquals('41810-001', $this->sanitization->formatCep('41810-001'));
    }

    public function test_format_cep_returns_original_when_not_8_digits(): void
    {
        $this->assertEquals('1234567', $this->sanitization->formatCep('1234567'));
    }
}
