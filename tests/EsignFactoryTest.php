<?php

namespace KiisKepri\Esign\Tests;

use KiisKepri\Esign\EsignFactory;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use KiisKepri\Esign\V1\Esign as EsignV1;
use KiisKepri\Esign\V2\Esign as EsignV2;
use PHPUnit\Framework\TestCase;

class EsignFactoryTest extends TestCase
{
    public function testCreateV1AndV2(): void
    {
        $this->assertInstanceOf(EsignV1::class, EsignFactory::v1('https://x', 'u', 'p'));
        $this->assertInstanceOf(EsignV2::class, EsignFactory::v2('https://x', 'u', 'p'));
        $this->assertInstanceOf(EsignV1::class, EsignFactory::create('v1', 'https://x', 'u', 'p'));
        $this->assertInstanceOf(EsignV2::class, EsignFactory::create('2', 'https://x', 'u', 'p'));
    }

    public function testInvalidVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EsignFactory::create('v3', 'https://x', 'u', 'p');
    }
}
