<?php

namespace Tests\Unit\Services;

use App\Enums\SplitType;
use App\Exceptions\InvalidSplitException;
use App\Models\BoletoConfig;
use App\Models\SplitConfig;
use App\Services\SplitService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SplitServiceTest extends TestCase
{
    private SplitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SplitService();
    }

    // --- validate() ---

    public function test_validates_percentage_splits_within_100(): void
    {
        $splits = $this->makeSplits([
            ['type' => SplitType::Percentage, 'value' => 30],
            ['type' => SplitType::Percentage, 'value' => 50],
        ]);

        // Should not throw
        $this->service->validate($splits, 10000);
        $this->addToAssertionCount(1);
    }

    public function test_throws_when_percentage_exceeds_100(): void
    {
        $splits = $this->makeSplits([
            ['type' => SplitType::Percentage, 'value' => 60],
            ['type' => SplitType::Percentage, 'value' => 50],
        ]);

        $this->expectException(InvalidSplitException::class);
        $this->expectExceptionMessageMatches('/100%/');

        $this->service->validate($splits, 10000);
    }

    public function test_throws_when_fixed_splits_exceed_amount(): void
    {
        $splits = $this->makeSplits([
            ['type' => SplitType::FixedAmount, 'value' => 6000],
            ['type' => SplitType::FixedAmount, 'value' => 5000],
        ]);

        $this->expectException(InvalidSplitException::class);
        $this->expectExceptionMessageMatches('/valor fixo/');

        $this->service->validate($splits, 10000);
    }

    public function test_throws_when_combined_splits_leave_negative_residual(): void
    {
        $splits = $this->makeSplits([
            ['type' => SplitType::Percentage, 'value' => 80],
            ['type' => SplitType::FixedAmount, 'value' => 3000],
        ]);

        $this->expectException(InvalidSplitException::class);

        // 80% of 10000 = 8000, fixed = 3000, total = 11000 > 10000
        $this->service->validate($splits, 10000);
    }

    public function test_validates_mixed_splits_within_bounds(): void
    {
        $splits = $this->makeSplits([
            ['type' => SplitType::Percentage, 'value' => 20],
            ['type' => SplitType::FixedAmount, 'value' => 1000],
        ]);

        // 20% of 10000 = 2000, fixed = 1000, total = 3000 < 10000
        $this->service->validate($splits, 10000);
        $this->addToAssertionCount(1);
    }

    public function test_validates_empty_splits(): void
    {
        $this->service->validate(collect(), 10000);
        $this->addToAssertionCount(1);
    }

    // --- calculate() ---

    public function test_calculate_returns_empty_when_no_splits(): void
    {
        $config = $this->mockConfigWithSplits([]);

        $result = $this->service->calculate($config, 10000);

        $this->assertSame([], $result);
    }

    public function test_calculate_percentage_split_correctly(): void
    {
        $config = $this->mockConfigWithSplits([
            ['name' => 'SEFAZ', 'type' => SplitType::Percentage, 'value' => 30, 'bank_partner_payee_id' => 'abc'],
        ]);

        $result = $this->service->calculate($config, 10000);

        $this->assertCount(1, $result);
        $this->assertSame(3000, $result[0]['amount_cents']); // 30% of 10000
        $this->assertSame('percentage', $result[0]['type']);
        $this->assertSame('SEFAZ', $result[0]['name']);
    }

    public function test_calculate_fixed_split_correctly(): void
    {
        $config = $this->mockConfigWithSplits([
            ['name' => 'Taxa', 'type' => SplitType::FixedAmount, 'value' => 150, 'bank_partner_payee_id' => 'xyz'],
        ]);

        $result = $this->service->calculate($config, 10000);

        $this->assertCount(1, $result);
        $this->assertSame(150, $result[0]['amount_cents']); // fixed 150 cents
        $this->assertSame('fixed_amount', $result[0]['type']);
    }

    public function test_calculate_rounds_percentage_correctly(): void
    {
        $config = $this->mockConfigWithSplits([
            ['name' => 'Split', 'type' => SplitType::Percentage, 'value' => 33, 'bank_partner_payee_id' => 'abc'],
        ]);

        $result = $this->service->calculate($config, 10000);

        // 33% of 10000 = 3300
        $this->assertSame(3300, $result[0]['amount_cents']);
    }

    public function test_calculate_multiple_splits(): void
    {
        $config = $this->mockConfigWithSplits([
            ['name' => 'Parceiro A', 'type' => SplitType::Percentage, 'value' => 20, 'bank_partner_payee_id' => 'a'],
            ['name' => 'Taxa fixa',  'type' => SplitType::FixedAmount, 'value' => 500, 'bank_partner_payee_id' => 'b'],
        ]);

        $result = $this->service->calculate($config, 50000);

        $this->assertCount(2, $result);
        $this->assertSame(10000, $result[0]['amount_cents']); // 20% of 50000
        $this->assertSame(500,   $result[1]['amount_cents']); // fixed 500
    }

    // --- helpers ---

    private function makeSplits(array $configs): Collection
    {
        return collect($configs)->map(function ($c) {
            $split = new SplitConfig();
            $split->forceFill([
                'type'                  => $c['type'],
                'value'                 => $c['value'],
                'name'                  => $c['name'] ?? 'Split',
                'bank_partner_payee_id' => $c['bank_partner_payee_id'] ?? 'id',
            ]);
            return $split;
        });
    }

    private function mockConfigWithSplits(array $configs): BoletoConfig
    {
        $splits = $this->makeSplits($configs);

        $config = $this->getMockBuilder(BoletoConfig::class)
            ->onlyMethods(['splitConfigs'])
            ->getMock();

        $relation = $this->getMockBuilder(\Illuminate\Database\Eloquent\Relations\HasMany::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $relation->method('get')->willReturn($splits);
        $config->method('splitConfigs')->willReturn($relation);

        return $config;
    }
}
