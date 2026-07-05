<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;

class ExecuteRequest extends Request
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        string $model,
        string $method,
        private readonly array $params = [],
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, $method);
    }

    public function toArray(): array
    {
        $params = $this->params;

        if ($this->context !== null && !isset($params['context'])) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
