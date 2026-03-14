<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;
use Obuchmann\OdooJsonApi\Domain;

class ReadGroupRequest extends Request
{
    /**
     * @param list<string>|null $fields
     * @param list<string> $groupBy
     */
    public function __construct(
        string $model,
        private readonly Domain $domain,
        private readonly ?array $fields = null,
        private readonly array $groupBy = [],
        private readonly int $offset = 0,
        private readonly ?int $limit = null,
        private readonly ?string $orderBy = null,
        private readonly bool $lazy = true,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'read_group');
    }

    public function toArray(): array
    {
        $params = [
            'domain' => $this->domain->toArray(),
            'groupby' => $this->groupBy,
        ];

        if ($this->fields !== null) {
            $params['fields'] = $this->fields;
        }
        if ($this->offset > 0) {
            $params['offset'] = $this->offset;
        }
        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }
        if ($this->orderBy !== null) {
            $params['orderby'] = $this->orderBy;
        }
        if (!$this->lazy) {
            $params['lazy'] = false;
        }
        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
