<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Domain;
use Obuchmann\OdooJsonApi\Request\SearchRequest;
use PHPUnit\Framework\TestCase;

class SearchRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new SearchRequest('res.partner', new Domain());

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('search', $request->getMethod());
    }

    public function testToArrayWithDomain(): void
    {
        $domain = new Domain();
        $domain->where('active', '=', true);

        $request = new SearchRequest('res.partner', $domain, limit: 10, order: 'id desc');

        $this->assertSame([
            'domain' => [['active', '=', true]],
            'limit' => 10,
            'order' => 'id desc',
        ], $request->toArray());
    }
}
