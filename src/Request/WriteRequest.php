<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;

class WriteRequest extends Request
{
    /**
     * @param list<int> $ids
     * @param array<string, mixed> $values
     */
    public function __construct(
        string $model,
        private readonly array $ids,
        private readonly array $values,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'write');
    }

    public function toArray(): array
    {
        $params = [
            'ids' => $this->ids,
            'values' => $this->values,
        ];

        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
