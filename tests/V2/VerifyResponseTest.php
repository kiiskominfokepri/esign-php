<?php

namespace KiisKepri\Esign\Tests\V2;

use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\V2\VerifyDetail;
use KiisKepri\Esign\V2\VerifyResponse;
use PHPUnit\Framework\TestCase;

class VerifyResponseTest extends TestCase
{
    public function testGettersFromCamelCasePayload(): void
    {
        $payload = [
            'documentName' => 'a.pdf',
            'signatureCount' => 1,
            'description' => 'verified',
            'conclusion' => 'VALID',
            'certificateDetails' => ['issuer' => 'BSrE'],
            'signatureInformations' => [
                [
                    'signerName' => 'Ani',
                    'integrityValid' => true,
                ],
            ],
        ];

        $response = new VerifyResponse(new Response(200, [], json_encode($payload)));

        $this->assertTrue($response->isSuccess());
        $this->assertSame('a.pdf', $response->getDocumentName());
        $this->assertSame(1, $response->getSignatureCounts());
        $this->assertSame('verified', $response->getNotes());
        $this->assertSame('VALID', $response->getSummary());
        $this->assertSame(['issuer' => 'BSrE'], $response->getCertificateDetails());
        $this->assertSame(['Ani'], $response->getSigners());

        $details = $response->getDetails();
        $this->assertCount(1, $details);
        $this->assertInstanceOf(VerifyDetail::class, $details[0]);
        $this->assertTrue($details[0]->isIntegrityValid());
    }

    public function testMissingFieldsAndInvalidSignatureInformations(): void
    {
        $response = new VerifyResponse(new Response(200, [], json_encode([
            'signatureInformations' => false,
        ])));

        $this->assertNull($response->getDocumentName());
        $this->assertNull($response->getSignatureCounts());
        $this->assertNull($response->getNotes());
        $this->assertNull($response->getSummary());
        $this->assertNull($response->getCertificateDetails());
        $this->assertSame([], $response->getDetails());
        $this->assertSame([], $response->getSigners());
    }
}
