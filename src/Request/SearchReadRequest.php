<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;
use Obuchmann\OdooJsonApi\Domain;

class SearchReadRequest extends Request
{
    /**
     * @param list<string>|null $fields
     */
    public function __construct(
        string $model,
        private readonly Domain $domain,
        private readonly ?array $fields = null,
        private readonly int $offset = 0,
        private readonly ?int $limit = null,
        private readonly ?string $order = null,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'search_read');
    }

    public function toArray(): array
    {
        $params = ['domain' => $this->domain->toArray()];

        if ($this->fields !== null) {
            $params['fields'] = $this->fields;
        }
        if ($this->offset > 0) {
            $params['offset'] = $this->offset;
        }
        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }
        if ($this->order !== null) {
            $params['order'] = $this->order;
        }
        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
