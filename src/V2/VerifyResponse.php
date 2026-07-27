<?php

namespace KiisKepri\Esign\V2;

use KiisKepri\Esign\BaseResponse;

class VerifyResponse extends BaseResponse
{
    public function getDocumentName(): ?string
    {
        return $this->data['documentName'] ?? null;
    }

    public function getSignatureCounts(): ?int
    {
        return isset($this->data['signatureCount']) ? (int) $this->data['signatureCount'] : null;
    }

    /**
     * @return mixed
     */
    public function getNotes()
    {
        return $this->data['description'] ?? null;
    }

    /** @return VerifyDetail[] */
    public function getDetails(): array
    {
        $details = $this->data['signatureInformations'] ?? [];
        if (!is_array($details)) {
            return [];
        }

        return array_map(static function ($detail) {
            return new VerifyDetail(is_array($detail) ? $detail : []);
        }, $details);
    }

    public function getSummary(): ?string
    {
        return $this->data['conclusion'] ?? null;
    }

    public function getSigners(): array
    {
        return array_map(static function (VerifyDetail $detail) {
            return $detail->getSignerName();
        }, $this->getDetails());
    }

    /**
     * @return mixed
     */
    public function getCertificateDetails()
    {
        return $this->data['certificateDetails'] ?? null;
    }
}
