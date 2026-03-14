<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Exception;

class OdooException extends \RuntimeException
{
    /**
     * @param array<string, mixed>|null $errorData
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected ?int $httpStatusCode = null,
        protected ?array $errorData = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }
}
