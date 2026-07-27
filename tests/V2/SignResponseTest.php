<?php

namespace KiisKepri\Esign\Tests\V2;

use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\V2\SignResponse;
use PHPUnit\Framework\TestCase;

class SignResponseTest extends TestCase
{
    public function testGetFilesBase64AndDecodedFiles(): void
    {
        $response = new SignResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'time' => '2024-01-01T00:00:00Z',
                'file' => [
                    base64_encode('FILE-A'),
                    base64_encode('FILE-B'),
                ],
            ])
        ));

        $this->assertTrue($response->isSuccess());
        $this->assertSame('2024-01-01T00:00:00Z', $response->getTimestamp());
        $this->assertCount(2, $response->getFilesBase64());
        $this->assertSame(['FILE-A', 'FILE-B'], array_values($response->getDecodedFiles()));
        $this->assertSame(base64_encode('FILE-A'), $response->getFileBase64());
        $this->assertSame('FILE-A', $response->getDecodedFile());
    }

    public function testSaveToFileIndex(): void
    {
        $response = new SignResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'file' => [
                    base64_encode('ZERO'),
                    base64_encode('ONE'),
                ],
            ])
        ));

        $out = sys_get_temp_dir() . '/esign-v2-sr-' . uniqid('', true) . '.pdf';
        $this->assertTrue($response->saveToFile($out, 1));
        $this->assertSame('ONE', file_get_contents($out));
        unlink($out);

        $this->assertFalse($response->saveToFile($out, 99));
    }

    public function testSaveAllEmptyWhenFail(): void
    {
        $response = new SignResponse(new Response(
            400,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => 'fail'])
        ));
        $response->setOutputMap([
            sys_get_temp_dir() . '/esign-v2-fail-' . uniqid('', true) . '.pdf',
        ]);

        $this->assertSame([], $response->saveAll());
        $this->assertFalse($response->saveToFile(sys_get_temp_dir() . '/x.pdf'));
    }
}
