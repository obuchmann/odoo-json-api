<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi;

class Domain
{
    /** @var list<mixed> */
    private array $conditions = [];

    /**
     * @param list<mixed> $conditions
     */
    public function __construct(array $conditions = [])
    {
        $this->conditions = $conditions;
    }

    public function where(string $field, string $operator, mixed $value): static
    {
        $this->conditions[] = [$field, $operator, $value];

        return $this;
    }

    /**
     * @param list<mixed> $values
     */
    public function whereIn(string $field, array $values): static
    {
        return $this->where($field, 'in', $values);
    }

    /**
     * @param list<mixed> $values
     */
    public function whereNotIn(string $field, array $values): static
    {
        return $this->where($field, 'not in', $values);
    }

    /**
     * OR the given condition with the previously added condition.
     *
     * Odoo domains use prefix notation, so the '|' operator is inserted
     * before the last condition: where(A)->orWhere(B) yields ['|', A, B],
     * and where(A)->where(B)->orWhere(C) yields [A, '|', B, C], i.e. A AND (B OR C).
     */
    public function orWhere(string $field, string $operator, mixed $value): static
    {
        if ($this->conditions === []) {
            return $this->where($field, $operator, $value);
        }

        array_splice($this->conditions, count($this->conditions) - 1, 0, ['|']);
        $this->conditions[] = [$field, $operator, $value];

        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this->conditions) === 0;
    }

    /**
     * @return list<mixed>
     */
    public function toArray(): array
    {
        return $this->conditions;
    }
}
