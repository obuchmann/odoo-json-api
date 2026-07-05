<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;
use Obuchmann\OdooJsonApi\Domain;

class SearchCountRequest extends Request
{
    public function __construct(
        string $model,
        private readonly Domain $domain,
        private readonly ?int $limit = null,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'search_count');
    }

    public function toArray(): array
    {
        $params = ['domain' => $this->domain->toArray()];

        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }
        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
