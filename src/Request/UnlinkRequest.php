<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Context;

class UnlinkRequest extends Request
{
    /**
     * @param list<int> $ids
     */
    public function __construct(
        string $model,
        private readonly array $ids,
        private readonly ?Context $context = null,
    ) {
        parent::__construct($model, 'unlink');
    }

    public function toArray(): array
    {
        $params = ['ids' => $this->ids];

        if ($this->context !== null) {
            $params['context'] = $this->context->toArray();
        }

        return $params;
    }
}
