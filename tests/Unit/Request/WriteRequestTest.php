<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Request\WriteRequest;
use PHPUnit\Framework\TestCase;

class WriteRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new WriteRequest('res.partner', [1], ['name' => 'Updated']);

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('write', $request->getMethod());
    }

    public function testToArray(): void
    {
        $request = new WriteRequest('res.partner', [1, 2], ['name' => 'Updated']);

        $this->assertSame([
            'ids' => [1, 2],
            'values' => ['name' => 'Updated'],
        ], $request->toArray());
    }
}
