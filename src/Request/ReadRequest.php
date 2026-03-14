<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;

class ReadRequest extends Request
{
    /**
     * @param list<int> $ids
     * @param list<string>|null $fields
     */
    public function __construct(
        string $model,
        private readonly array $ids,
        private readonly ?array $fields = null,
        private readonly ?string $load = null,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'read');
    }

    public function toArray(): array
    {
        $params = ['ids' => $this->ids];

        if ($this->fields !== null) {
            $params['fields'] = $this->fields;
        }
        if ($this->load !== null) {
            $params['load'] = $this->load;
        }
        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
