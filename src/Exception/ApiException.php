<?php

namespace KiisKepri\Esign\Exception;

class ApiException extends EsignException
{
    private int $httpStatus;
    private mixed $responseBody;

    public function __construct(string $message, int $httpStatus = 0, mixed $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpStatus, $previous);
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getResponseBody(): mixed
    {
        return $this->responseBody;
    }
}
