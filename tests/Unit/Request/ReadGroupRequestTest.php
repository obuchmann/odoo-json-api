<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Domain;
use Obuchmann\OdooJsonApi\Request\ReadGroupRequest;
use PHPUnit\Framework\TestCase;

class ReadGroupRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new ReadGroupRequest('res.partner', new Domain());

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('read_group', $request->getMethod());
    }

    public function testToArrayMinimal(): void
    {
        $request = new ReadGroupRequest('res.partner', new Domain());

        $this->assertSame([
            'domain' => [],
            'groupby' => [],
        ], $request->toArray());
    }

    public function testToArrayWithAllParams(): void
    {
        $domain = new Domain();
        $domain->where('active', '=', true);

        $request = new ReadGroupRequest(
            model: 'res.partner',
            domain: $domain,
            fields: ['name', 'country_id'],
            groupBy: ['country_id'],
            offset: 5,
            limit: 10,
            orderBy: 'country_id asc',
            lazy: false,
        );

        $this->assertSame([
            'domain' => [['active', '=', true]],
            'groupby' => ['country_id'],
            'fields' => ['name', 'country_id'],
            'offset' => 5,
            'limit' => 10,
            'orderby' => 'country_id asc',
            'lazy' => false,
        ], $request->toArray());
    }

    public function testLazyTrueOmitted(): void
    {
        $request = new ReadGroupRequest('res.partner', new Domain(), lazy: true);

        $this->assertArrayNotHasKey('lazy', $request->toArray());
    }
}
