<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Context;
use Obuchmann\OdooJsonApi\Domain;
use Obuchmann\OdooJsonApi\Request\SearchReadRequest;
use PHPUnit\Framework\TestCase;

class SearchReadRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new SearchReadRequest('res.partner', new Domain());

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('search_read', $request->getMethod());
    }

    public function testMinimalParams(): void
    {
        $request = new SearchReadRequest('res.partner', new Domain());

        $this->assertSame(['domain' => []], $request->toArray());
    }

    public function testAllParams(): void
    {
        $domain = new Domain();
        $domain->where('name', 'ilike', 'Test');

        $context = new Context(['lang' => 'en_US']);

        $request = new SearchReadRequest(
            model: 'res.partner',
            domain: $domain,
            fields: ['name', 'email'],
            offset: 10,
            limit: 5,
            order: 'name asc',
            context: $context,
        );

        $this->assertSame([
            'domain' => [['name', 'ilike', 'Test']],
            'fields' => ['name', 'email'],
            'offset' => 10,
            'limit' => 5,
            'order' => 'name asc',
            'context' => ['lang' => 'en_US'],
        ], $request->toArray());
    }

    public function testZeroOffsetOmitted(): void
    {
        $request = new SearchReadRequest('res.partner', new Domain(), offset: 0);

        $this->assertArrayNotHasKey('offset', $request->toArray());
    }
}
