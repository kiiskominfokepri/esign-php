<?php

namespace KiisKepri\Esign\Tests\DTO;

use KiisKepri\Esign\DTO\SignatureProperties;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SignaturePropertiesTest extends TestCase
{
    /** @var string */
    private $imagePath;

    protected function setUp(): void
    {
        $this->imagePath = sys_get_temp_dir() . '/esign-sp-' . uniqid('', true) . '.png';
        file_put_contents($this->imagePath, 'IMG');
    }

    protected function tearDown(): void
    {
        if (is_file($this->imagePath)) {
            unlink($this->imagePath);
        }
    }

    public function testInvisibleToArrayKeys(): void
    {
        $data = SignatureProperties::invisible('reason-a', 'loc-a', 'pdf-pass')->toArray();

        $this->assertSame('INVISIBLE', $data['tampilan']);
        $this->assertSame('reason-a', $data['reason']);
        $this->assertSame('loc-a', $data['location']);
        $this->assertSame('pdf-pass', $data['pdfPassword']);
        $this->assertArrayNotHasKey('imageBase64', $data);
        $this->assertArrayNotHasKey('page', $data);
    }

    public function testVisibleFromFilePath(): void
    {
        $data = SignatureProperties::visible($this->imagePath, 2, 11.0, 22.0, 100.0, 50.0)->toArray();

        $this->assertSame('VISIBLE', $data['tampilan']);
        $this->assertSame(base64_encode('IMG'), $data['imageBase64']);
        $this->assertSame(2, $data['page']);
        $this->assertEquals(11.0, $data['originX']);
        $this->assertEquals(22.0, $data['originY']);
        $this->assertEquals(100.0, $data['width']);
        $this->assertEquals(50.0, $data['height']);
    }

    public function testVisibleFromBase64(): void
    {
        $b64 = base64_encode('raw-image');
        $data = SignatureProperties::visible($b64, 1, 0, 0, 10, 10, false)->toArray();

        $this->assertSame('VISIBLE', $data['tampilan']);
        $this->assertSame($b64, $data['imageBase64']);
    }

    public function testVisibleWithTag(): void
    {
        $data = SignatureProperties::visibleWithTag('#ttd', 'aW1hZ2U=', false)->toArray();

        $this->assertSame('VISIBLE', $data['tampilan']);
        $this->assertSame('#ttd', $data['tag_koordinat']);
        $this->assertSame('aW1hZ2U=', $data['imageBase64']);
    }

    public function testWithReasonLocationPdfPasswordTagKoordinat(): void
    {
        $data = SignatureProperties::invisible()
            ->withReason('R')
            ->withLocation('L')
            ->withPdfPassword('P')
            ->withTagKoordinat('#y')
            ->toArray();

        $this->assertSame('R', $data['reason']);
        $this->assertSame('L', $data['location']);
        $this->assertSame('P', $data['pdfPassword']);
        $this->assertSame('#y', $data['tag_koordinat']);
    }

    public function testNormalizeListWithObjectsAndArrays(): void
    {
        $list = SignatureProperties::normalizeList([
            SignatureProperties::invisible('obj'),
            ['tampilan' => 'INVISIBLE', 'reason' => 'arr'],
        ]);

        $this->assertCount(2, $list);
        $this->assertSame('INVISIBLE', $list[0]['tampilan']);
        $this->assertSame('obj', $list[0]['reason']);
        $this->assertSame('arr', $list[1]['reason']);
    }

    public function testNormalizeListEmptyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one signatureProperties entry is required');
        SignatureProperties::normalizeList([]);
    }

    public function testNormalizeListInvalidTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('signatureProperties items must be SignatureProperties or array');
        SignatureProperties::normalizeList(['not-valid']);
    }
}
