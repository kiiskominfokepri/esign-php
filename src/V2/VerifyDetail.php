<?php

namespace KiisKepri\Esign\V2;

class VerifyDetail
{
    private array $detail;

    public function __construct(array $detail)
    {
        $this->detail = $detail;
    }

    public function getSignerName(): ?string
    {
        return $this->detail['signerName'] ?? null;
    }

    public function getSignatureField(): ?string
    {
        return $this->detail['fieldName'] ?? null;
    }

    public function getSignatureFormat(): ?string
    {
        return $this->detail['signatureFormat'] ?? null;
    }

    public function getTimestampAuthority(): ?string
    {
        return $this->detail['timestampInfomation']['signerName'] ?? null;
    }

    public function getTimestampDate(): ?string
    {
        return $this->detail['timestampInfomation']['timestampDate'] ?? null;
    }

    public function isDocumentModified(): bool
    {
        return (bool) ($this->detail['modified'] ?? false);
    }

    public function isCertificateTrusted(): bool
    {
        return (bool) ($this->detail['certificateTrusted'] ?? false);
    }

    public function getCertLevelCode(): ?int
    {
        return isset($this->detail['certLevelCode']) ? (int) $this->detail['certLevelCode'] : null;
    }

    public function getSignatureDate(): ?string
    {
        return $this->detail['signatureDate'] ?? null;
    }

    public function isIntegrityValid(): bool
    {
        return (bool) ($this->detail['integrityValid'] ?? false);
    }

    public function getRaw(): array
    {
        return $this->detail;
    }
}
