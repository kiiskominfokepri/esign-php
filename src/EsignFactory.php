<?php

namespace KiisKepri\Esign;

use GuzzleHttp\ClientInterface;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use KiisKepri\Esign\V1\Esign as EsignV1;
use KiisKepri\Esign\V2\Esign as EsignV2;

final class EsignFactory
{
    public const VERSION_1 = 'v1';
    public const VERSION_2 = 'v2';

    public static function create(
        string $version,
        string $url,
        string $username,
        string $password,
        array $guzzleOptions = [],
        ?ClientInterface $httpClient = null
    ): EsignV1|EsignV2 {
        return match (strtolower($version)) {
            self::VERSION_1, '1' => new EsignV1($url, $username, $password, $guzzleOptions, $httpClient),
            self::VERSION_2, '2' => new EsignV2($url, $username, $password, $guzzleOptions, $httpClient),
            default => throw new InvalidArgumentException(
                sprintf('Unsupported API version "%s". Use v1 or v2.', $version)
            ),
        };
    }

    public static function v1(
        string $url,
        string $username,
        string $password,
        array $guzzleOptions = [],
        ?ClientInterface $httpClient = null
    ): EsignV1 {
        return new EsignV1($url, $username, $password, $guzzleOptions, $httpClient);
    }

    public static function v2(
        string $url,
        string $username,
        string $password,
        array $guzzleOptions = [],
        ?ClientInterface $httpClient = null
    ): EsignV2 {
        return new EsignV2($url, $username, $password, $guzzleOptions, $httpClient);
    }
}
