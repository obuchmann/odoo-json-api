<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request\Traits;

trait HasDomain
{
    public function where(string $field, string $operator, mixed $value): static
    {
        $this->domain->where($field, $operator, $value);

        return $this;
    }

    public function orWhere(string $field, string $operator, mixed $value): static
    {
        $this->domain->orWhere($field, $operator, $value);

        return $this;
    }

    /**
     * @param list<mixed> $values
     */
    public function whereIn(string $field, array $values): static
    {
        $this->domain->whereIn($field, $values);

        return $this;
    }

    /**
     * @param list<mixed> $values
     */
    public function whereNotIn(string $field, array $values): static
    {
        $this->domain->whereNotIn($field, $values);

        return $this;
    }
}
