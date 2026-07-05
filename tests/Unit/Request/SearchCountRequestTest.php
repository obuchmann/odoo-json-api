<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Domain;
use Obuchmann\OdooJsonApi\Request\SearchCountRequest;
use PHPUnit\Framework\TestCase;

class SearchCountRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new SearchCountRequest('res.partner', new Domain());

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('search_count', $request->getMethod());
    }

    public function testToArray(): void
    {
        $domain = new Domain();
        $domain->where('active', '=', true);

        $request = new SearchCountRequest('res.partner', $domain);

        $this->assertSame([
            'domain' => [['active', '=', true]],
        ], $request->toArray());
    }

    public function testToArrayWithLimit(): void
    {
        $request = new SearchCountRequest('res.partner', new Domain(), limit: 100);

        $this->assertSame([
            'domain' => [],
            'limit' => 100,
        ], $request->toArray());
    }
}
