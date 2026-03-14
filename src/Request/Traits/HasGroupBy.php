<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request\Traits;

trait HasGroupBy
{
    /** @var list<string> */
    private array $groupBy = [];

    /**
     * @param list<string> $groupBy
     */
    public function groupBy(array $groupBy): static
    {
        $this->groupBy = $groupBy;

        return $this;
    }
}
