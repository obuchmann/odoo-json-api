<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

abstract class Request
{
    public function __construct(
        protected string $model,
        protected string $method,
    ) {
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Serialize to named parameters for JSON-2 API body.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
