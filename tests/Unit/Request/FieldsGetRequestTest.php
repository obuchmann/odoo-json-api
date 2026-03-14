<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Request\FieldsGetRequest;
use PHPUnit\Framework\TestCase;

class FieldsGetRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new FieldsGetRequest('res.partner');

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('fields_get', $request->getMethod());
    }

    public function testToArrayEmpty(): void
    {
        $request = new FieldsGetRequest('res.partner');

        $this->assertSame([], $request->toArray());
    }

    public function testToArrayWithAttributes(): void
    {
        $request = new FieldsGetRequest('res.partner', attributes: ['string', 'type', 'help']);

        $this->assertSame([
            'attributes' => ['string', 'type', 'help'],
        ], $request->toArray());
    }
}
