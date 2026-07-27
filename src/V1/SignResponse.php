<?php

namespace KiisKepri\Esign\V1;

use KiisKepri\Esign\BaseResponse;

class SignResponse extends BaseResponse
{
    /** @var string|null */
    private $documentId = null;

    /** @var string */
    private $binaryBody = '';

    protected function decodeBody(): void
    {
        $this->rawBody = (string) $this->response->getBody();
        $this->binaryBody = $this->rawBody;

        $contentType = strtolower($this->response->getHeaderLine('Content-Type'));
        $looksLikePdf = str_starts_with($this->rawBody, '%PDF');
        $looksLikeJson = str_starts_with(ltrim($this->rawBody), '{') || str_starts_with(ltrim($this->rawBody), '[');

        if ($looksLikeJson || (str_contains($contentType, 'json') && !$looksLikePdf)) {
            $decoded = json_decode($this->rawBody, true);
            $this->decodedBody = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            return;
        }

        $this->decodedBody = null;
    }

    protected function setErrors(): void
    {
        if ($this->status === static::STATUS_OK) {
            if (is_array($this->decodedBody) && isset($this->decodedBody['error'])) {
                $this->errors = $this->decodedBody['error'];
            }
            return;
        }

        if (is_array($this->decodedBody)) {
            $this->errors = $this->decodedBody['error']
                ?? $this->decodedBody['message']
                ?? 'Unknown error';
            return;
        }

        $this->errors = $this->rawBody !== '' ? $this->rawBody : 'Unknown error';
    }

    protected function setData(): void
    {
        if (!$this->isSuccess()) {
            return;
        }

        $this->data = $this->binaryBody;
        $this->documentId = $this->extractDocumentId();
    }

    private function extractDocumentId(): ?string
    {
        foreach (['id_dokumen', 'id-dokumen', 'Id_Dokumen', 'ID_DOKUMEN'] as $header) {
            $value = $this->getHeader($header);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function getBinary(): string
    {
        return $this->isSuccess() ? $this->binaryBody : '';
    }

    public function saveToFile(string $savePath): bool
    {
        if (!$this->isSuccess()) {
            return false;
        }

        $dir = dirname($savePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        return file_put_contents($savePath, $this->binaryBody) !== false;
    }
}
