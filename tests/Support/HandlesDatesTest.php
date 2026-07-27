<?php

namespace KiisKepri\Esign\Tests\Support;

use KiisKepri\Esign\Support\HandlesDates;
use PHPUnit\Framework\TestCase;

class HandlesDatesTest extends TestCase
{
    private $harness;

    protected function setUp(): void
    {
        $this->harness = new HandlesDatesHarness();
    }

    public function testNullAndEmptyReturnNull(): void
    {
        $this->assertNull($this->harness->expose(null));
        $this->assertNull($this->harness->expose(''));
    }

    public function testIsoWithTParses(): void
    {
        $dt = $this->harness->expose('2024-06-15T10:30:00+07:00');

        $this->assertInstanceOf(\DateTimeImmutable::class, $dt);
        $this->assertSame('2024-06-15', $dt->format('Y-m-d'));
    }

    public function testInvalidIsoWithTReturnsNull(): void
    {
        $this->assertNull($this->harness->expose('not-a-dateTxx'));
    }

    public function testV1FormatWithTimezone(): void
    {
        $dt = $this->harness->expose('2024-01-02 10:11:12.000000', 'Y-m-d H:i:s.u');

        $this->assertInstanceOf(\DateTimeImmutable::class, $dt);
        $this->assertSame('Asia/Jakarta', $dt->getTimezone()->getName());
        $this->assertSame('2024-01-02 10:11:12', $dt->format('Y-m-d H:i:s'));
    }

    public function testInvalidV1FormatReturnsNull(): void
    {
        $this->assertNull($this->harness->expose('02-01-2024', 'Y-m-d H:i:s.u'));
    }

    public function testGenericParseWithoutFormat(): void
    {
        $dt = $this->harness->expose('2024-12-01');

        $this->assertInstanceOf(\DateTimeImmutable::class, $dt);
        $this->assertSame('2024-12-01', $dt->format('Y-m-d'));
    }
}

class HandlesDatesHarness
{
    use HandlesDates;

    public function expose(?string $date, ?string $formatV1 = null): ?\DateTimeImmutable
    {
        return $this->parseDate($date, $formatV1);
    }
}
