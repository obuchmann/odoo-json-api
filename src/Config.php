<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi;

readonly class Config
{
    public function __construct(
        public string $url,
        public string $apiKey,
        public ?string $database = null,
    ) {
        if (trim($this->url) === '') {
            throw new \InvalidArgumentException('Config url must not be empty.');
        }

        if ($this->apiKey === '') {
            throw new \InvalidArgumentException('Config apiKey must not be empty.');
        }
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->url, '/');
    }
}
