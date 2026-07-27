<?php

namespace KiisKepri\Esign\Tests;

use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\Response\JsonResponse;
use PHPUnit\Framework\TestCase;

class BaseResponseTest extends TestCase
{
    public function testSuccessJsonSetsDataAndClearsErrors(): void
    {
        $response = new JsonResponse(new Response(200, ['X-Test' => 'yes'], '{"ok":true,"value":1}'));

        $this->assertSame(200, $response->getStatus());
        $this->assertTrue($response->isSuccess());
        $this->assertNull($response->getErrors());
        $this->assertSame(['ok' => true, 'value' => 1], $response->getData());
        $this->assertSame(['ok' => true, 'value' => 1], $response->getRaw());
        $this->assertSame('{"ok":true,"value":1}', $response->getRawBody());
        $this->assertSame('yes', $response->getHeader('X-Test'));
        $this->assertNull($response->getHeader('Missing'));
        $this->assertSame(200, $response->getResponse()->getStatusCode());
    }

    public function testHttp200WithErrorFieldIsNotSuccess(): void
    {
        $response = new JsonResponse(new Response(200, [], '{"error":"passphrase salah"}'));

        $this->assertFalse($response->isSuccess());
        $this->assertSame('passphrase salah', $response->getErrors());
        $this->assertNull($response->getData());
        $this->assertSame(['error' => 'passphrase salah'], $response->getRaw());
    }

    public function testNon200PrefersErrorThenMessageThenStatus(): void
    {
        $withError = new JsonResponse(new Response(400, [], '{"error":"bad request"}'));
        $this->assertFalse($withError->isSuccess());
        $this->assertSame('bad request', $withError->getErrors());

        $withMessage = new JsonResponse(new Response(401, [], '{"message":"unauthorized"}'));
        $this->assertSame('unauthorized', $withMessage->getErrors());

        $withStatus = new JsonResponse(new Response(403, [], '{"status":"forbidden"}'));
        $this->assertSame('forbidden', $withStatus->getErrors());
    }

    public function testNon200NonJsonUsesRawBodyOrUnknown(): void
    {
        $withBody = new JsonResponse(new Response(500, [], 'plain failure'));
        $this->assertFalse($withBody->isSuccess());
        $this->assertSame('plain failure', $withBody->getErrors());
        $this->assertNull($withBody->getRaw());

        $empty = new JsonResponse(new Response(502, [], ''));
        $this->assertSame('Unknown error', $empty->getErrors());
    }

    public function testInvalidJsonBodyDoesNotDecode(): void
    {
        $response = new JsonResponse(new Response(200, [], '{not-json'));

        $this->assertTrue($response->isSuccess());
        $this->assertNull($response->getRaw());
        $this->assertNull($response->getData());
        $this->assertSame('{not-json', $response->getRawBody());
    }
}
