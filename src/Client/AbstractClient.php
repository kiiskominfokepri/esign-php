<?php

namespace KiisKepri\Esign\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use KiisKepri\Esign\Exception\ApiException;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractClient
{
    protected ClientInterface $httpClient;
    protected string $baseUrl;
    protected ?string $nik = null;
    protected ?string $email = null;

    /**
     * @param array<string, mixed> $guzzleOptions Extra Guzzle client options
     */
    public function __construct(
        string $url,
        string $username,
        string $password,
        array $guzzleOptions = [],
        ?ClientInterface $httpClient = null
    ) {
        $this->baseUrl = rtrim($url, '/');

        if ($httpClient !== null) {
            $this->httpClient = $httpClient;
            return;
        }

        $this->httpClient = new Client(array_merge([
            'auth' => [$username, $password],
            'timeout' => 120.0,
            'http_errors' => false,
            'connect_timeout' => 30.0,
        ], $guzzleOptions));
    }

    public function setNIK(?string $nik): static
    {
        $this->nik = $nik;
        return $this;
    }

    public function getNIK(): ?string
    {
        return $this->nik;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws ApiException
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $url = str_starts_with($uri, 'http') ? $uri : $this->baseUrl . '/' . ltrim($uri, '/');

        try {
            return $this->httpClient->request($method, $url, $options);
        } catch (GuzzleException $e) {
            throw new ApiException(
                'HTTP request failed: ' . $e->getMessage(),
                0,
                null,
                $e
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws ApiException
     */
    protected function post(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws ApiException
     */
    protected function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }
}
