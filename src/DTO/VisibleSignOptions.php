<?php

namespace KiisKepri\Esign\DTO;

use KiisKepri\Esign\Exception\InvalidArgumentException;
use KiisKepri\Esign\Support\FileHelper;

final class VisibleSignOptions
{
    public const MODE_IMAGE = 'image';
    public const MODE_QR = 'qr';
    public const MODE_TAG = 'tag';

    /** @var string */
    private $mode;

    /** @var string|null */
    private $imagePath = null;

    /** @var string|null */
    private $linkQr = null;

    /** @var string|null */
    private $tagKoordinat = null;

    /** @var int|null */
    private $page = null;

    /** @var float|null */
    private $xAxis = null;

    /** @var float|null */
    private $yAxis = null;

    /** @var float|null */
    private $width = null;

    /** @var float|null */
    private $height = null;

    /** @var string|null */
    private $reason = null;

    /** @var string|null */
    private $location = null;

    /** @var string|null */
    private $text = null;

    private function __construct(string $mode)
    {
        $this->mode = $mode;
    }

    public static function withImage(
        string $imagePath,
        int $page,
        float $xAxis,
        float $yAxis,
        float $width,
        float $height
    ): self {
        FileHelper::assertReadable($imagePath);

        $opts = new self(self::MODE_IMAGE);
        $opts->imagePath = $imagePath;
        $opts->page = $page;
        $opts->xAxis = $xAxis;
        $opts->yAxis = $yAxis;
        $opts->width = $width;
        $opts->height = $height;

        return $opts;
    }

    public static function withQr(
        string $linkQr,
        int $page,
        float $xAxis,
        float $yAxis,
        float $width,
        float $height
    ): self {
        $opts = new self(self::MODE_QR);
        $opts->linkQr = $linkQr;
        $opts->page = $page;
        $opts->xAxis = $xAxis;
        $opts->yAxis = $yAxis;
        $opts->width = $width;
        $opts->height = $height;

        return $opts;
    }

    public static function withTag(string $tagKoordinat): self
    {
        $opts = new self(self::MODE_TAG);
        $opts->tagKoordinat = $tagKoordinat;

        return $opts;
    }

    public function withTagKoordinat(string $tag): self
    {
        $clone = clone $this;
        $clone->tagKoordinat = $tag;
        return $clone;
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

    public function withText(?string $text): self
    {
        $clone = clone $this;
        $clone->text = $text;
        return $clone;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    /** @return list<array<string, mixed>> */
    public function toMultipart(): array
    {
        $parts = [];

        switch ($this->mode) {
            case self::MODE_IMAGE:
                $parts[] = ['name' => 'image', 'contents' => 'true'];
                $parts[] = [
                    'name' => 'imageTTD',
                    'contents' => FileHelper::openReadStream($this->imagePath),
                    'filename' => basename($this->imagePath),
                    'headers' => ['Content-Type' => FileHelper::mimeType($this->imagePath)],
                ];
                $this->appendCoordinates($parts);
                break;

            case self::MODE_QR:
                $parts[] = ['name' => 'image', 'contents' => 'false'];
                $parts[] = ['name' => 'linkQR', 'contents' => (string) $this->linkQr];
                $this->appendCoordinates($parts);
                break;

            case self::MODE_TAG:
                if ($this->tagKoordinat === null || $this->tagKoordinat === '') {
                    throw new InvalidArgumentException('tag_koordinat is required for tag mode');
                }
                break;

            default:
                throw new InvalidArgumentException('Unknown visible sign mode: ' . $this->mode);
        }

        if ($this->tagKoordinat !== null && $this->tagKoordinat !== '') {
            $parts[] = ['name' => 'tag_koordinat', 'contents' => $this->tagKoordinat];
        }

        if ($this->reason !== null) {
            $parts[] = ['name' => 'reason', 'contents' => $this->reason];
        }

        if ($this->location !== null) {
            $parts[] = ['name' => 'location', 'contents' => $this->location];
        }

        if ($this->text !== null) {
            $parts[] = ['name' => 'text', 'contents' => $this->text];
        }

        return $parts;
    }

    /** @param list<array<string, mixed>> $parts */
    private function appendCoordinates(array &$parts): void
    {
        $parts[] = ['name' => 'page', 'contents' => (string) $this->page];
        $parts[] = ['name' => 'xAxis', 'contents' => (string) $this->xAxis];
        $parts[] = ['name' => 'yAxis', 'contents' => (string) $this->yAxis];
        $parts[] = ['name' => 'width', 'contents' => (string) $this->width];
        $parts[] = ['name' => 'height', 'contents' => (string) $this->height];
    }
}
