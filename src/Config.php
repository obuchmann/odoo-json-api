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
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->url, '/');
    }
}
