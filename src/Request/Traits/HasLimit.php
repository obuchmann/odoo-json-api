<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request\Traits;

trait HasLimit
{
    private ?int $limit = null;

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }
}
