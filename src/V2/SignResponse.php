<?php

namespace KiisKepri\Esign\V2;

use KiisKepri\Esign\BaseResponse;

class SignResponse extends BaseResponse
{
    /** @var list<string> */
    protected array $outputMap = [];

    public function setOutputMap(array $map): static
    {
        $this->outputMap = array_values($map);
        return $this;
    }

    public function getTimestamp(): ?string
    {
        return $this->data['time'] ?? null;
    }

    public function getFilesBase64(): array
    {
        $files = $this->data['file'] ?? [];
        return is_array($files) ? $files : [];
    }

    public function getDecodedFiles(): array
    {
        $decoded = [];
        foreach ($this->getFilesBase64() as $idx => $base64) {
            $decoded[$idx] = base64_decode((string) $base64, true) ?: '';
        }
        return $decoded;
    }

    public function getFileBase64(): ?string
    {
        $files = $this->getFilesBase64();
        return isset($files[0]) ? (string) $files[0] : null;
    }

    public function getDecodedFile(): ?string
    {
        $base64 = $this->getFileBase64();
        if ($base64 === null) {
            return null;
        }

        $decoded = base64_decode($base64, true);
        return $decoded === false ? null : $decoded;
    }

    public function saveToFile(string $savePath, int $index = 0): bool
    {
        if (!$this->isSuccess()) {
            return false;
        }

        $files = $this->getDecodedFiles();
        if (!isset($files[$index])) {
            return false;
        }

        $dir = dirname($savePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        return file_put_contents($savePath, $files[$index]) !== false;
    }

    public function saveAll(): array
    {
        if (!$this->isSuccess()) {
            return [];
        }

        $files = $this->getDecodedFiles();
        $saved = [];

        foreach ($this->outputMap as $idx => $targetPath) {
            if (!isset($files[$idx])) {
                continue;
            }

            $dir = dirname($targetPath);
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                continue;
            }

            if (file_put_contents($targetPath, $files[$idx]) !== false) {
                $saved[] = $targetPath;
            }
        }

        return $saved;
    }
}
