<?php

namespace KiisKepri\Esign\Tests\Exception;

use KiisKepri\Esign\Exception\EsignException;
use KiisKepri\Esign\Exception\FileNotFoundException;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ExceptionHierarchyTest extends TestCase
{
    public function testEsignExceptionExtendsBaseException(): void
    {
        $e = new EsignException('boom', 42);

        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertSame('boom', $e->getMessage());
        $this->assertSame(42, $e->getCode());
    }

    public function testInvalidArgumentExceptionExtendsEsignException(): void
    {
        $e = new InvalidArgumentException('bad arg');

        $this->assertInstanceOf(EsignException::class, $e);
        $this->assertSame('bad arg', $e->getMessage());
    }

    public function testFileNotFoundForPathFactory(): void
    {
        $e = FileNotFoundException::forPath('/tmp/missing.pdf');

        $this->assertInstanceOf(EsignException::class, $e);
        $this->assertInstanceOf(FileNotFoundException::class, $e);
        $this->assertSame('File not found: /tmp/missing.pdf', $e->getMessage());
    }
}
