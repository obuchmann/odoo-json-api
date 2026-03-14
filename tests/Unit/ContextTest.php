<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit;

use Obuchmann\OdooJsonApi\Context;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    public function testEmptyContext(): void
    {
        $context = new Context();

        $this->assertSame([], $context->toArray());
    }

    public function testConstructWithValues(): void
    {
        $context = new Context(['lang' => 'en_US']);

        $this->assertSame(['lang' => 'en_US'], $context->toArray());
    }

    public function testSet(): void
    {
        $context = new Context();
        $result = $context->set('lang', 'en_US');

        $this->assertSame($context, $result);
        $this->assertSame(['lang' => 'en_US'], $context->toArray());
    }

    public function testMerge(): void
    {
        $context = new Context(['lang' => 'en_US']);
        $context->merge(['tz' => 'UTC', 'lang' => 'fr_FR']);

        $this->assertSame(['lang' => 'fr_FR', 'tz' => 'UTC'], $context->toArray());
    }
}
