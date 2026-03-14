<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi;

class Context
{
    /** @var array<string, mixed> */
    private array $values;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function set(string $key, mixed $value): static
    {
        $this->values[$key] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function merge(array $values): static
    {
        $this->values = array_merge($this->values, $values);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
