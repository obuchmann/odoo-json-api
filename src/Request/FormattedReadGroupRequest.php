<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;
use Obuchmann\OdooJsonApi\Domain;

class FormattedReadGroupRequest extends Request
{
    /**
     * @param list<string> $groupBy
     * @param list<string> $aggregates e.g. ['amount_total:sum', '__count']
     * @param list<mixed> $having
     */
    public function __construct(
        string $model,
        private readonly Domain $domain,
        private readonly array $groupBy = [],
        private readonly array $aggregates = [],
        private readonly array $having = [],
        private readonly int $offset = 0,
        private readonly ?int $limit = null,
        private readonly ?string $order = null,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'formatted_read_group');
    }

    public function toArray(): array
    {
        $params = [
            'domain' => $this->domain->toArray(),
            'groupby' => $this->groupBy,
        ];

        if ($this->aggregates !== []) {
            $params['aggregates'] = $this->aggregates;
        }
        if ($this->having !== []) {
            $params['having'] = $this->having;
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
