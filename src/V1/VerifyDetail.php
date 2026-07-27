<?php

namespace KiisKepri\Esign\V1;

use KiisKepri\Esign\Support\HandlesDates;

class VerifyDetail
{
    use HandlesDates;

    /** @var array<string, mixed> */
    private $detail;

    public function __construct(array $detail)
    {
        $this->detail = $detail;
    }

    public function getTimestampAuthority(): ?string
    {
        return $this->detail['info_tsa']['name'] ?? null;
    }

    public function getTsaCertValidity(): ?string
    {
        return $this->detail['info_tsa']['tsa_cert_validity'] ?? null;
    }

    public function getSignerName(): ?string
    {
        return $this->detail['info_signer']['signer_name'] ?? null;
    }

    public function getSignerCertValidity(): ?string
    {
        return $this->detail['info_signer']['signer_cert_validity'] ?? null;
    }

    public function getDocumentIntegrity(): bool
    {
        return (bool) ($this->detail['signature_document']['document_integrity'] ?? false);
    }

    public function getSignedIn(): ?string
    {
        $raw = $this->detail['signature_document']['signed_in'] ?? null;
        $dt = $this->parseDate($raw, 'Y-m-d H:i:s.u');
        if ($dt === null) {
            return null;
        }

        return $dt->setTimezone(new \DateTimeZone('UTC'))->format(\DateTimeInterface::ATOM);
    }

    public function getSignatureField(): ?string
    {
        return $this->detail['signature_field'] ?? null;
    }

    public function getRaw(): array
    {
        return $this->detail;
    }
}
