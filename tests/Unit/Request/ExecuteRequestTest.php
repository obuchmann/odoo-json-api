<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Request\ExecuteRequest;
use PHPUnit\Framework\TestCase;

class ExecuteRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new ExecuteRequest('res.partner', 'custom_method');

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('custom_method', $request->getMethod());
    }

    public function testToArrayEmpty(): void
    {
        $request = new ExecuteRequest('res.partner', 'custom_method');

        $this->assertSame([], $request->toArray());
    }

    public function testToArrayWithParams(): void
    {
        $request = new ExecuteRequest('res.partner', 'custom_method', ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $request->toArray());
    }
}
