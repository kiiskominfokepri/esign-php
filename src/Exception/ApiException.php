<?php

namespace KiisKepri\Esign\Exception;

class ApiException extends EsignException
{
    /** @var int */
    private $httpStatus;

    /** @var mixed */
    private $responseBody;

    /**
     * @param mixed $responseBody
     */
    public function __construct(string $message, int $httpStatus = 0, $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpStatus, $previous);
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * @return mixed
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
