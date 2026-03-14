<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request\Traits;

trait HasOrder
{
    private ?string $order = null;

    public function orderBy(string $order): static
    {
        $this->order = $order;

        return $this;
    }
}
