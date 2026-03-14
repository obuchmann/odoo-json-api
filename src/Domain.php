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

    public function orWhere(string $field, string $operator, mixed $value): static
    {
        $this->conditions[] = '|';
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
