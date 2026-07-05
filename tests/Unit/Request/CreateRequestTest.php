<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Context;
use Obuchmann\OdooJsonApi\Request\CreateRequest;
use PHPUnit\Framework\TestCase;

class CreateRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new CreateRequest('res.partner', [['name' => 'Test']]);

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('create', $request->getMethod());
    }

    public function testToArray(): void
    {
        $request = new CreateRequest('res.partner', [['name' => 'Test', 'email' => 'test@example.com']]);

        $this->assertSame([
            'vals_list' => [['name' => 'Test', 'email' => 'test@example.com']],
        ], $request->toArray());
    }

    public function testToArrayWithMultipleRecords(): void
    {
        $request = new CreateRequest('res.partner', [
            ['name' => 'One'],
            ['name' => 'Two'],
        ]);

        $this->assertSame([
            'vals_list' => [['name' => 'One'], ['name' => 'Two']],
        ], $request->toArray());
    }

    public function testWithContext(): void
    {
        $context = new Context(['lang' => 'fr_FR']);
        $request = new CreateRequest('res.partner', [['name' => 'Test']], $context);

        $result = $request->toArray();
        $this->assertSame(['lang' => 'fr_FR'], $result['context']);
    }
}
