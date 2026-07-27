<?php

namespace KiisKepri\Esign\Tests\Support;

use KiisKepri\Esign\Exception\FileNotFoundException;
use KiisKepri\Esign\Support\FileHelper;
use PHPUnit\Framework\TestCase;

class FileHelperTest extends TestCase
{
    /** @var string */
    private $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/esign-fh-' . uniqid('', true) . '.bin';
        file_put_contents($this->path, 'hello-bytes');
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testAssertReadableThrowsFileNotFoundException(): void
    {
        $missing = '/tmp/esign-missing-' . uniqid('', true) . '.pdf';

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File not found:');
        FileHelper::assertReadable($missing);
    }

    public function testReadBinary(): void
    {
        $this->assertSame('hello-bytes', FileHelper::readBinary($this->path));
    }

    public function testToBase64(): void
    {
        $this->assertSame(base64_encode('hello-bytes'), FileHelper::toBase64($this->path));
    }

    public function testBasenameOverride(): void
    {
        $this->assertSame(basename($this->path), FileHelper::basename($this->path));
        $this->assertSame('custom.pdf', FileHelper::basename($this->path, 'custom.pdf'));
    }

    public function testMimeType(): void
    {
        $mime = FileHelper::mimeType($this->path);
        $this->assertNotSame('', $mime);
        $this->assertIsString($mime);
    }

    public function testOpenReadStream(): void
    {
        $stream = FileHelper::openReadStream($this->path);
        $this->assertTrue(is_resource($stream));
        $this->assertSame('hello-bytes', stream_get_contents($stream));
        fclose($stream);
    }
}
