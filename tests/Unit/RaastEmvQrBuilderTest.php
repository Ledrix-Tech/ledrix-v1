<?php

namespace Tests\Unit;

use App\Services\Billing\RaastEmvQrBuilder;
use Tests\TestCase;

class RaastEmvQrBuilderTest extends TestCase
{
    private RaastEmvQrBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new RaastEmvQrBuilder();
    }

    public function test_builds_pakistan_raast_dynamic_payload(): void
    {
        $payload = $this->builder->buildDynamic(
            iban: 'PK36SCBL0000001123456702',
            amount: '1000.00',
            description: 'LDRX-1-TESTREF',
            expiresAt: now()->setDate(2024, 12, 31)->setTime(23, 59),
        );

        $this->assertStringStartsWith('000202010212020200', $payload);
        $this->assertStringContainsString('PK36SCBL0000001123456702', $payload);
        $this->assertStringContainsString('1000.00', $payload);
        $this->assertStringContainsString('311220242359', $payload);
        $this->assertTrue($this->builder->crcIsValid($payload));
    }

    public function test_builds_pakistan_raast_static_payload(): void
    {
        $payload = $this->builder->buildStatic('PK76MEZN0001580107677601');

        $this->assertStringStartsWith('000202010211020200', $payload);
        $this->assertStringContainsString('PK76MEZN0001580107677601', $payload);
        $this->assertTrue($this->builder->crcIsValid($payload));
    }

    public function test_rejects_invalid_iban(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->builder->buildStatic('01580107677601');
    }
}
