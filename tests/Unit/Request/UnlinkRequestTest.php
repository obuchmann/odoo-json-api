<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use Obuchmann\OdooJsonApi\Request\UnlinkRequest;
use PHPUnit\Framework\TestCase;

class UnlinkRequestTest extends TestCase
{
    public function testModelAndMethod(): void
    {
        $request = new UnlinkRequest('res.partner', [1]);

        $this->assertSame('res.partner', $request->getModel());
        $this->assertSame('unlink', $request->getMethod());
    }

    public function testToArray(): void
    {
        $request = new UnlinkRequest('res.partner', [1, 2, 3]);

        $this->assertSame(['ids' => [1, 2, 3]], $request->toArray());
    }
}
