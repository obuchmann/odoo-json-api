<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;

class FieldsGetRequest extends Request
{
    /**
     * @param list<string>|null $attributes
     */
    public function __construct(
        string $model,
        private readonly ?array $attributes = null,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'fields_get');
    }

    public function toArray(): array
    {
        $params = [];

        if ($this->attributes !== null) {
            $params['attributes'] = $this->attributes;
        }
        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
