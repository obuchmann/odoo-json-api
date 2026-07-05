<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;

class CreateRequest extends Request
{
    /**
     * @param list<array<string, mixed>> $valsList
     */
    public function __construct(
        string $model,
        private readonly array $valsList,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'create');
    }

    public function toArray(): array
    {
        $params = ['vals_list' => $this->valsList];

        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
