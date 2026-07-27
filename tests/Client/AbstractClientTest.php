<?php

namespace KiisKepri\Esign\Tests\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\Exception\ApiException;
use KiisKepri\Esign\V1\Esign;
use PHPUnit\Framework\TestCase;

class AbstractClientTest extends TestCase
{
    public function testSetAndGetNikEmailAndBaseUrl(): void
    {
        $esign = new Esign('https://esign.test/base/', 'user', 'pass');

        $this->assertSame('https://esign.test/base', $esign->getBaseUrl());
        $this->assertNull($esign->getNIK());
        $this->assertNull($esign->getEmail());

        $esign->setNIK('3200123456789012')->setEmail('a@b.go.id');
        $this->assertSame('3200123456789012', $esign->getNIK());
        $this->assertSame('a@b.go.id', $esign->getEmail());

        $esign->setNIK(null)->setEmail(null);
        $this->assertNull($esign->getNIK());
        $this->assertNull($esign->getEmail());
    }

    public function testApiExceptionWhenGuzzleThrows(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Connection refused',
                new Request('GET', 'https://esign.test/api/user/status/x')
            ),
        ]);
        $http = new Client([
            'handler' => HandlerStack::create($mock),
            'http_errors' => false,
        ]);
        $esign = new Esign('https://esign.test', 'user', 'pass', [], $http);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP request failed:');
        $esign->checkUserStatus('3200123456789012');
    }

    public function testGetHttpClientReturnsInjectedClient(): void
    {
        $http = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, [], '{}'),
            ])),
            'http_errors' => false,
        ]);
        $esign = new Esign('https://esign.test', 'user', 'pass', [], $http);

        $this->assertSame($http, $esign->getHttpClient());
    }
}
