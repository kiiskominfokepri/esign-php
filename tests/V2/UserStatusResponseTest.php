<?php

namespace KiisKepri\Esign\Tests\V2;

use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\V2\UserStatusResponse;
use PHPUnit\Framework\TestCase;

class UserStatusResponseTest extends TestCase
{
    public function testCanSignOnlyForIssue(): void
    {
        $issue = new UserStatusResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['status' => 'ISSUE'])
        ));
        $this->assertTrue($issue->canSign());
        $this->assertSame('ISSUE', $issue->getUserStatus());

        $expired = new UserStatusResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['status' => 'EXPIRED'])
        ));
        $this->assertFalse($expired->canSign());
        $this->assertSame('EXPIRED', $expired->getUserStatus());
    }

    public function testGetUserStatusNestedDataPaths(): void
    {
        $userStatus = new UserStatusResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['userStatus' => 'RENEW'])
        ));
        $this->assertSame('RENEW', $userStatus->getUserStatus());
        $this->assertFalse($userStatus->canSign());

        $nested = new UserStatusResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['data' => ['status' => 'ISSUE']])
        ));
        $this->assertSame('ISSUE', $nested->getUserStatus());
        $this->assertTrue($nested->canSign());
    }

    public function testMissingStatusReturnsNull(): void
    {
        $response = new UserStatusResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['message' => 'ok'])
        ));

        $this->assertNull($response->getUserStatus());
        $this->assertFalse($response->canSign());
    }
}
