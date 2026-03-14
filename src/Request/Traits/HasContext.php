<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request\Traits;

use Obuchmann\OdooJsonApi\Context;

trait HasContext
{
    private ?Context $context = null;

    public function context(Context $context): static
    {
        $this->context = $context;

        return $this;
    }
}
