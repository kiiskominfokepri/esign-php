<?php

namespace KiisKepri\Esign;

use Psr\Http\Message\ResponseInterface;

abstract class BaseResponse
{
    protected int $status;
    protected mixed $errors = null;
    protected mixed $data = null;
    protected mixed $decodedBody = null;
    protected ResponseInterface $response;
    protected string $rawBody = '';

    protected const STATUS_OK = 200;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        $this->setStatus();
        $this->decodeBody();
        $this->setErrors();
        $this->setData();
    }

    protected function setStatus(): void
    {
        $this->status = $this->response->getStatusCode();
    }

    protected function decodeBody(): void
    {
        $this->rawBody = (string) $this->response->getBody();
        $decoded = json_decode($this->rawBody, true);
        $this->decodedBody = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isSuccess(): bool
    {
        return $this->status === static::STATUS_OK && $this->errors === null;
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
                ?? $this->decodedBody['status']
                ?? $this->rawBody
                ?: 'Unknown error';
            return;
        }

        $this->errors = $this->rawBody !== '' ? $this->rawBody : 'Unknown error';
    }

    public function getErrors(): mixed
    {
        return $this->errors;
    }

    protected function setData(): void
    {
        if ($this->isSuccess()) {
            $this->data = $this->decodedBody;
        }
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getRaw(): mixed
    {
        return $this->decodedBody;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getHeader(string $name): ?string
    {
        $values = $this->response->getHeader($name);
        return $values[0] ?? null;
    }
}
