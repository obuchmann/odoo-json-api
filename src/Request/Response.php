<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

readonly class Response
{
    public function __construct(
        public int $statusCode,
        public mixed $result,
        /** @var array<string, mixed>|null */
        public ?array $error = null,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300 && $this->error === null;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        if (is_array($this->result)) {
            return $this->result;
        }

        return [$this->result];
    }
}
