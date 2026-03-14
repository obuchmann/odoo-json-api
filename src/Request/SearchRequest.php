<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;
use Obuchmann\OdooJsonApi\Domain;

class SearchRequest extends Request
{
    public function __construct(
        string $model,
        private readonly Domain $domain,
        private readonly int $offset = 0,
        private readonly ?int $limit = null,
        private readonly ?string $order = null,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'search');
    }

    public function toArray(): array
    {
        $params = ['domain' => $this->domain->toArray()];

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
