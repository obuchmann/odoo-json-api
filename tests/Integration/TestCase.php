<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Integration;

use Obuchmann\OdooJsonApi\Config;
use Obuchmann\OdooJsonApi\Odoo;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Odoo $odoo;

    protected function setUp(): void
    {
        parent::setUp();

        $host = getenv('ODOO_HOST') ?: 'http://localhost:8069';
        $apiKey = getenv('ODOO_API_KEY') ?: '';
        $database = getenv('ODOO_DATABASE') ?: null;

        if ($apiKey === '') {
            $this->markTestSkipped('ODOO_API_KEY environment variable is required for integration tests.');
        }

        $config = new Config(
            url: $host,
            apiKey: $apiKey,
            database: $database ?: null,
        );

        $this->odoo = new Odoo($config);
    }
}
