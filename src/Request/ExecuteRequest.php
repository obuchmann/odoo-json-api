<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

class ExecuteRequest extends Request
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        string $model,
        string $method,
        private readonly array $params = [],
    ) {
        parent::__construct($model, $method);
    }

    public function toArray(): array
    {
        return $this->params;
    }
}
