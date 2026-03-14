<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request\Traits;

trait HasOffset
{
    private int $offset = 0;

    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }
}
