<?php

namespace KiisKepri\Esign\Support;

use KiisKepri\Esign\Exception\FileNotFoundException;
use KiisKepri\Esign\Exception\InvalidArgumentException;

final class FileHelper
{
    public static function assertReadable(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw FileNotFoundException::forPath($path);
        }
    }

    public static function readBinary(string $path): string
    {
        self::assertReadable($path);

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InvalidArgumentException(sprintf('Unable to read file: %s', $path));
        }

        return $contents;
    }

    public static function toBase64(string $path): string
    {
        return base64_encode(self::readBinary($path));
    }

    public static function basename(string $path, ?string $override = null): string
    {
        return $override ?? basename($path);
    }

    public static function mimeType(string $path): string
    {
        self::assertReadable($path);

        $mime = mime_content_type($path);
        if ($mime === false) {
            return 'application/octet-stream';
        }

        return $mime;
    }

    /** @return resource */
    public static function openReadStream(string $path)
    {
        self::assertReadable($path);

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException(sprintf('Unable to open file: %s', $path));
        }

        return $handle;
    }
}
