<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit;

use Obuchmann\OdooJsonApi\Domain;
use PHPUnit\Framework\TestCase;

class DomainTest extends TestCase
{
    public function testEmptyDomain(): void
    {
        $domain = new Domain();

        $this->assertTrue($domain->isEmpty());
        $this->assertSame([], $domain->toArray());
    }

    public function testWhere(): void
    {
        $domain = new Domain();
        $result = $domain->where('name', '=', 'Test');

        $this->assertSame($domain, $result);
        $this->assertFalse($domain->isEmpty());
        $this->assertSame([['name', '=', 'Test']], $domain->toArray());
    }

    public function testMultipleWhere(): void
    {
        $domain = new Domain();
        $domain->where('name', '=', 'Test')
               ->where('active', '=', true);

        $this->assertSame([
            ['name', '=', 'Test'],
            ['active', '=', true],
        ], $domain->toArray());
    }

    public function testOrWhere(): void
    {
        $domain = new Domain();
        $domain->where('name', '=', 'Test')
               ->orWhere('email', 'ilike', 'test@');

        $this->assertSame([
            ['name', '=', 'Test'],
            '|',
            ['email', 'ilike', 'test@'],
        ], $domain->toArray());
    }

    public function testConstructWithInitialConditions(): void
    {
        $domain = new Domain([['name', '=', 'Test']]);

        $this->assertFalse($domain->isEmpty());
        $this->assertSame([['name', '=', 'Test']], $domain->toArray());
    }
}
