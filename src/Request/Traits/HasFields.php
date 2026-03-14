<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request\Traits;

trait HasFields
{
    /** @var list<string>|null */
    private ?array $fields = null;

    /**
     * @param list<string> $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }
}
