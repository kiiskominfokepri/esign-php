<?php

namespace KiisKepri\Esign\Tests\DTO;

use KiisKepri\Esign\DTO\VisibleSignOptions;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VisibleSignOptionsTest extends TestCase
{
    /** @var string */
    private $imagePath;

    protected function setUp(): void
    {
        $this->imagePath = sys_get_temp_dir() . '/esign-vso-' . uniqid('', true) . '.png';
        file_put_contents($this->imagePath, 'PNGDATA');
    }

    protected function tearDown(): void
    {
        if (is_file($this->imagePath)) {
            unlink($this->imagePath);
        }
    }

    public function testWithImageMode(): void
    {
        $opts = VisibleSignOptions::withImage($this->imagePath, 1, 10.5, 20.5, 100.0, 40.0);
        $this->assertSame(VisibleSignOptions::MODE_IMAGE, $opts->getMode());
    }

    public function testWithQrMode(): void
    {
        $opts = VisibleSignOptions::withQr('https://example.go.id/q', 2, 1.0, 2.0, 3.0, 4.0);
        $this->assertSame(VisibleSignOptions::MODE_QR, $opts->getMode());
    }

    public function testWithTagMode(): void
    {
        $opts = VisibleSignOptions::withTag('#ttd');
        $this->assertSame(VisibleSignOptions::MODE_TAG, $opts->getMode());
    }

    public function testToMultipartImageParts(): void
    {
        $parts = VisibleSignOptions::withImage($this->imagePath, 1, 10, 20, 100, 40)->toMultipart();
        $map = $this->partsToMap($parts);

        $this->assertSame('true', $map['image']);
        $this->assertArrayHasKey('imageTTD', $map);
        $this->assertSame('1', $map['page']);
        $this->assertSame('10', $map['xAxis']);
        $this->assertSame('20', $map['yAxis']);
        $this->assertSame('100', $map['width']);
        $this->assertSame('40', $map['height']);
    }

    public function testToMultipartQrParts(): void
    {
        $parts = VisibleSignOptions::withQr('https://example.go.id/verify', 3, 5, 6, 7, 8)->toMultipart();
        $map = $this->partsToMap($parts);

        $this->assertSame('false', $map['image']);
        $this->assertSame('https://example.go.id/verify', $map['linkQR']);
        $this->assertSame('3', $map['page']);
        $this->assertSame('5', $map['xAxis']);
        $this->assertArrayNotHasKey('imageTTD', $map);
    }

    public function testToMultipartTagParts(): void
    {
        $parts = VisibleSignOptions::withTag('$ttd')->toMultipart();
        $map = $this->partsToMap($parts);

        $this->assertSame('$ttd', $map['tag_koordinat']);
        $this->assertArrayNotHasKey('image', $map);
        $this->assertArrayNotHasKey('page', $map);
    }

    public function testWithReasonLocationTextChaining(): void
    {
        $parts = VisibleSignOptions::withTag('#x')
            ->withReason('Persetujuan')
            ->withLocation('Tanjungpinang')
            ->withText('Disetujui')
            ->toMultipart();
        $map = $this->partsToMap($parts);

        $this->assertSame('Persetujuan', $map['reason']);
        $this->assertSame('Tanjungpinang', $map['location']);
        $this->assertSame('Disetujui', $map['text']);
        $this->assertSame('#x', $map['tag_koordinat']);
    }

    public function testTagModeWithoutTagThrowsOnToMultipart(): void
    {
        $opts = VisibleSignOptions::withTag('#keep');
        $opts = $opts->withTagKoordinat('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tag_koordinat is required for tag mode');
        $opts->toMultipart();
    }

    private function partsToMap(array $parts): array
    {
        $map = [];
        foreach ($parts as $part) {
            $map[$part['name']] = $part['contents'];
        }

        return $map;
    }
}
