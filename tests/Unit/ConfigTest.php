<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit;

use Obuchmann\OdooJsonApi\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testConstructWithRequiredParams(): void
    {
        $config = new Config(
            url: 'https://odoo.example.com',
            apiKey: 'test-api-key',
        );

        $this->assertSame('https://odoo.example.com', $config->url);
        $this->assertSame('test-api-key', $config->apiKey);
        $this->assertNull($config->database);
    }

    public function testConstructWithAllParams(): void
    {
        $config = new Config(
            url: 'https://odoo.example.com',
            apiKey: 'test-api-key',
            database: 'mydb',
        );

        $this->assertSame('mydb', $config->database);
    }

    public function testGetBaseUrlStripsTrailingSlash(): void
    {
        $config = new Config(
            url: 'https://odoo.example.com/',
            apiKey: 'key',
        );

        $this->assertSame('https://odoo.example.com', $config->getBaseUrl());
    }

    public function testGetBaseUrlWithoutTrailingSlash(): void
    {
        $config = new Config(
            url: 'https://odoo.example.com',
            apiKey: 'key',
        );

        $this->assertSame('https://odoo.example.com', $config->getBaseUrl());
    }
}
