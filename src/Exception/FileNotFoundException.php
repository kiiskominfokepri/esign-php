<?php

namespace KiisKepri\Esign\Exception;

class FileNotFoundException extends EsignException
{
    public static function forPath(string $path): self
    {
        return new self(sprintf('File not found: %s', $path));
    }
}
