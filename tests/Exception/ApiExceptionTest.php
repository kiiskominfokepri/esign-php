<?php

namespace KiisKepri\Esign\Tests\Exception;

use KiisKepri\Esign\Exception\ApiException;
use PHPUnit\Framework\TestCase;

class ApiExceptionTest extends TestCase
{
    public function testGetHttpStatusAndResponseBody(): void
    {
        $ex = new ApiException('boom', 503, ['error' => 'unavailable']);

        $this->assertSame(503, $ex->getHttpStatus());
        $this->assertSame(['error' => 'unavailable'], $ex->getResponseBody());
        $this->assertSame('boom', $ex->getMessage());
        $this->assertSame(503, $ex->getCode());
    }

    public function testDefaults(): void
    {
        $ex = new ApiException('transport failed');

        $this->assertSame(0, $ex->getHttpStatus());
        $this->assertNull($ex->getResponseBody());
    }
}
