<?php

namespace KiisKepri\Esign\Tests\V2;

use KiisKepri\Esign\V2\VerifyDetail;
use PHPUnit\Framework\TestCase;

class VerifyDetailTest extends TestCase
{
    public function testGetters(): void
    {
        $detail = new VerifyDetail([
            'signerName' => 'Ani',
            'fieldName' => 'Sig1',
            'signatureFormat' => 'PAdES',
            'modified' => false,
            'certificateTrusted' => true,
            'certLevelCode' => 2,
            'signatureDate' => '2024-02-01',
            'integrityValid' => true,
            'timestampInfomation' => [
                'signerName' => 'TSA-V2',
                'timestampDate' => '2024-02-01T10:00:00Z',
            ],
        ]);

        $this->assertSame('Ani', $detail->getSignerName());
        $this->assertSame('Sig1', $detail->getSignatureField());
        $this->assertSame('PAdES', $detail->getSignatureFormat());
        $this->assertFalse($detail->isDocumentModified());
        $this->assertTrue($detail->isCertificateTrusted());
        $this->assertSame(2, $detail->getCertLevelCode());
        $this->assertSame('2024-02-01', $detail->getSignatureDate());
        $this->assertTrue($detail->isIntegrityValid());
        $this->assertSame('TSA-V2', $detail->getTimestampAuthority());
        $this->assertSame('2024-02-01T10:00:00Z', $detail->getTimestampDate());
        $this->assertSame('Ani', $detail->getRaw()['signerName']);
    }

    public function testMissingFieldsDefaults(): void
    {
        $detail = new VerifyDetail([]);

        $this->assertNull($detail->getSignerName());
        $this->assertNull($detail->getSignatureField());
        $this->assertFalse($detail->isIntegrityValid());
        $this->assertFalse($detail->isDocumentModified());
        $this->assertNull($detail->getCertLevelCode());
    }
}
