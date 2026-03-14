<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Request\ReadRequest;
use PHPUnit\Framework\TestCase;

class ReadRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new ReadRequest('res.partner', [1]);

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('read', $request->getMethod());
    }

    public function testToArrayMinimal(): void
    {
        $request = new ReadRequest('res.partner', [1, 2]);

        $this->assertSame(['ids' => [1, 2]], $request->toArray());
    }

    public function testToArrayWithFields(): void
    {
        $request = new ReadRequest('res.partner', [1], fields: ['name', 'email']);

        $this->assertSame([
            'ids' => [1],
            'fields' => ['name', 'email'],
        ], $request->toArray());
    }

    public function testToArrayWithLoad(): void
    {
        $request = new ReadRequest('res.partner', [1], load: '_classic_read');

        $this->assertSame([
            'ids' => [1],
            'load' => '_classic_read',
        ], $request->toArray());
    }
}
