<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;

class CreateRequest extends Request
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        string $model,
        private readonly array $values,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'create');
    }

    public function toArray(): array
    {
        $params = ['values' => $this->values];

        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
