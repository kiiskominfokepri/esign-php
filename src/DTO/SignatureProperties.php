<?php

namespace KiisKepri\Esign\DTO;

use KiisKepri\Esign\Exception\InvalidArgumentException;
use KiisKepri\Esign\Support\FileHelper;

final class SignatureProperties
{
    public const TAMPILAN_INVISIBLE = 'INVISIBLE';
    public const TAMPILAN_VISIBLE = 'VISIBLE';

    private string $tampilan;
    private ?string $imageBase64 = null;
    private ?int $page = null;
    private ?float $originX = null;
    private ?float $originY = null;
    private ?float $width = null;
    private ?float $height = null;
    private ?string $tagKoordinat = null;
    private ?string $location = null;
    private ?string $reason = null;
    private ?string $pdfPassword = null;

    private function __construct(string $tampilan)
    {
        $this->tampilan = strtoupper($tampilan);
    }

    public static function invisible(?string $reason = null, ?string $location = null, ?string $pdfPassword = null): self
    {
        $props = new self(self::TAMPILAN_INVISIBLE);
        $props->reason = $reason ?? '';
        $props->location = $location;
        $props->pdfPassword = $pdfPassword;

        return $props;
    }

    public static function visible(
        string $imagePathOrBase64,
        int $page,
        float $originX,
        float $originY,
        float $width,
        float $height,
        bool $isFilePath = true
    ): self {
        $props = new self(self::TAMPILAN_VISIBLE);
        $props->imageBase64 = $isFilePath
            ? FileHelper::toBase64($imagePathOrBase64)
            : $imagePathOrBase64;
        $props->page = $page;
        $props->originX = $originX;
        $props->originY = $originY;
        $props->width = $width;
        $props->height = $height;

        return $props;
    }

    public static function visibleWithTag(string $tagKoordinat, ?string $imagePathOrBase64 = null, bool $isFilePath = true): self
    {
        $props = new self(self::TAMPILAN_VISIBLE);
        $props->tagKoordinat = $tagKoordinat;

        if ($imagePathOrBase64 !== null) {
            $props->imageBase64 = $isFilePath
                ? FileHelper::toBase64($imagePathOrBase64)
                : $imagePathOrBase64;
        }

        return $props;
    }

    public function withReason(?string $reason): self
    {
        $clone = clone $this;
        $clone->reason = $reason;
        return $clone;
    }

    public function withLocation(?string $location): self
    {
        $clone = clone $this;
        $clone->location = $location;
        return $clone;
    }

    public function withPdfPassword(?string $password): self
    {
        $clone = clone $this;
        $clone->pdfPassword = $password;
        return $clone;
    }

    public function withTagKoordinat(?string $tag): self
    {
        $clone = clone $this;
        $clone->tagKoordinat = $tag;
        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'tampilan' => $this->tampilan,
        ];

        if ($this->tampilan === self::TAMPILAN_VISIBLE) {
            if ($this->imageBase64 !== null) {
                $data['imageBase64'] = $this->imageBase64;
            }
            if ($this->page !== null) {
                $data['page'] = $this->page;
            }
            if ($this->originX !== null) {
                $data['originX'] = $this->originX;
            }
            if ($this->originY !== null) {
                $data['originY'] = $this->originY;
            }
            if ($this->width !== null) {
                $data['width'] = $this->width;
            }
            if ($this->height !== null) {
                $data['height'] = $this->height;
            }
        }

        if ($this->tagKoordinat !== null) {
            $data['tag_koordinat'] = $this->tagKoordinat;
        }

        $data['location'] = $this->location;
        $data['reason'] = $this->reason ?? '';

        if ($this->pdfPassword !== null && $this->pdfPassword !== '') {
            $data['pdfPassword'] = $this->pdfPassword;
        }

        return $data;
    }

    /**
     * @param list<self|array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    public static function normalizeList(array $items): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('At least one signatureProperties entry is required');
        }

        $result = [];
        foreach ($items as $item) {
            if ($item instanceof self) {
                $result[] = $item->toArray();
            } elseif (is_array($item)) {
                $result[] = $item;
            } else {
                throw new InvalidArgumentException('signatureProperties items must be SignatureProperties or array');
            }
        }

        return $result;
    }
}
