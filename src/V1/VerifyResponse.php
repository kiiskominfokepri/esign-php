<?php

namespace KiisKepri\Esign\V1;

use KiisKepri\Esign\BaseResponse;

class VerifyResponse extends BaseResponse
{
    public function getDocumentName(): ?string
    {
        return $this->data['nama_dokumen'] ?? null;
    }

    public function getSignatureCounts(): ?int
    {
        return isset($this->data['jumlah_signature']) ? (int) $this->data['jumlah_signature'] : null;
    }

    /**
     * @return mixed
     */
    public function getNotes()
    {
        return $this->data['notes'] ?? null;
    }

    /** @return VerifyDetail[] */
    public function getDetails(): array
    {
        $details = $this->data['details'] ?? [];
        if (!is_array($details)) {
            return [];
        }

        return array_map(static function ($detail) {
            return new VerifyDetail(is_array($detail) ? $detail : []);
        }, $details);
    }

    public function getSummary(): ?string
    {
        return $this->data['summary'] ?? null;
    }

    public function getSigners(): array
    {
        return array_map(static function (VerifyDetail $detail) {
            return $detail->getSignerName();
        }, $this->getDetails());
    }
}
