<?php

namespace KiisKepri\Esign\Tests\V1;

use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\V1\VerifyDetail;
use KiisKepri\Esign\V1\VerifyResponse;
use PHPUnit\Framework\TestCase;

class VerifyResponseTest extends TestCase
{
    public function testGettersFromSnakeCasePayload(): void
    {
        $payload = [
            'nama_dokumen' => 'doc.pdf',
            'jumlah_signature' => 2,
            'notes' => ['ok'],
            'summary' => 'VALID',
            'details' => [
                [
                    'info_signer' => ['signer_name' => 'Budi'],
                    'signature_document' => ['document_integrity' => true],
                ],
                [
                    'info_signer' => ['signer_name' => 'Ani'],
                ],
            ],
        ];

        $response = new VerifyResponse(new Response(200, [], json_encode($payload)));

        $this->assertTrue($response->isSuccess());
        $this->assertSame('doc.pdf', $response->getDocumentName());
        $this->assertSame(2, $response->getSignatureCounts());
        $this->assertSame(['ok'], $response->getNotes());
        $this->assertSame('VALID', $response->getSummary());
        $this->assertSame(['Budi', 'Ani'], $response->getSigners());

        $details = $response->getDetails();
        $this->assertCount(2, $details);
        $this->assertInstanceOf(VerifyDetail::class, $details[0]);
        $this->assertTrue($details[0]->getDocumentIntegrity());
    }

    public function testMissingAndInvalidDetailsDefaultSafely(): void
    {
        $response = new VerifyResponse(new Response(200, [], json_encode([
            'details' => 'not-an-array',
        ])));

        $this->assertNull($response->getDocumentName());
        $this->assertNull($response->getSignatureCounts());
        $this->assertNull($response->getNotes());
        $this->assertNull($response->getSummary());
        $this->assertSame([], $response->getDetails());
        $this->assertSame([], $response->getSigners());
    }

    public function testNonArrayDetailEntriesBecomeEmptyVerifyDetail(): void
    {
        $response = new VerifyResponse(new Response(200, [], json_encode([
            'details' => [null, 'x'],
        ])));

        $details = $response->getDetails();
        $this->assertCount(2, $details);
        $this->assertNull($details[0]->getSignerName());
        $this->assertNull($details[1]->getSignerName());
    }
}
