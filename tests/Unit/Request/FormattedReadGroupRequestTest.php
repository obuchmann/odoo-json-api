<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Domain;
use Obuchmann\OdooJsonApi\Request\FormattedReadGroupRequest;
use PHPUnit\Framework\TestCase;

class FormattedReadGroupRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new FormattedReadGroupRequest('res.partner', new Domain());

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('formatted_read_group', $request->getMethod());
    }

    public function testToArrayMinimal(): void
    {
        $request = new FormattedReadGroupRequest('res.partner', new Domain());

        $this->assertSame([
            'domain' => [],
            'groupby' => [],
        ], $request->toArray());
    }

    public function testToArrayWithAllParams(): void
    {
        $domain = new Domain();
        $domain->where('active', '=', true);

        $request = new FormattedReadGroupRequest(
            model: 'res.partner',
            domain: $domain,
            groupBy: ['country_id'],
            aggregates: ['__count', 'credit_limit:sum'],
            having: [['__count', '>', 1]],
            offset: 5,
            limit: 10,
            order: 'country_id asc',
        );

        $this->assertSame([
            'domain' => [['active', '=', true]],
            'groupby' => ['country_id'],
            'aggregates' => ['__count', 'credit_limit:sum'],
            'having' => [['__count', '>', 1]],
            'offset' => 5,
            'limit' => 10,
            'order' => 'country_id asc',
        ], $request->toArray());
    }

    public function testEmptyAggregatesAndHavingOmitted(): void
    {
        $request = new FormattedReadGroupRequest('res.partner', new Domain(), groupBy: ['is_company']);

        $params = $request->toArray();
        $this->assertArrayNotHasKey('aggregates', $params);
        $this->assertArrayNotHasKey('having', $params);
    }
}
