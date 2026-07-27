<?php

namespace KiisKepri\Esign\Tests\V1;

use KiisKepri\Esign\V1\VerifyDetail;
use PHPUnit\Framework\TestCase;

class VerifyDetailTest extends TestCase
{
    public function testGetters(): void
    {
        $detail = new VerifyDetail([
            'signature_field' => 'Signature1',
            'info_signer' => [
                'signer_name' => 'Budi',
                'signer_cert_validity' => '2025-01-01',
            ],
            'info_tsa' => [
                'name' => 'TSA-1',
                'tsa_cert_validity' => '2026-01-01',
            ],
            'signature_document' => [
                'document_integrity' => true,
                'signed_in' => '2024-01-02 10:11:12.000000',
            ],
        ]);

        $this->assertSame('Signature1', $detail->getSignatureField());
        $this->assertSame('Budi', $detail->getSignerName());
        $this->assertSame('2025-01-01', $detail->getSignerCertValidity());
        $this->assertSame('TSA-1', $detail->getTimestampAuthority());
        $this->assertSame('2026-01-01', $detail->getTsaCertValidity());
        $this->assertTrue($detail->getDocumentIntegrity());
        $this->assertNotNull($detail->getSignedIn());
        $this->assertStringContainsString('2024-01-02', $detail->getSignedIn());
        $this->assertSame('Signature1', $detail->getRaw()['signature_field']);
    }

    public function testMissingFieldsDefaults(): void
    {
        $detail = new VerifyDetail([]);

        $this->assertNull($detail->getSignerName());
        $this->assertNull($detail->getSignatureField());
        $this->assertNull($detail->getTimestampAuthority());
        $this->assertFalse($detail->getDocumentIntegrity());
        $this->assertNull($detail->getSignedIn());
    }
}
