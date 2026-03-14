<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit\Request;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Obuchmann\OdooJsonApi\Client\HttpClient;
use Obuchmann\OdooJsonApi\Config;
use Obuchmann\OdooJsonApi\Request\RequestBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class RequestBuilderTest extends TestCase
{
    private function createBuilder(ClientInterface $httpClient): RequestBuilder
    {
        $config = new Config(
            url: 'https://odoo.example.com',
            apiKey: 'key',
        );
        $factory = new HttpFactory();
        $client = new HttpClient($config, $httpClient, $factory, $factory);

        return new RequestBuilder($client, 'res.partner');
    }

    public function testGetSendsSearchRead(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return str_contains((string) $request->getUri(), '/json/2/res.partner/search_read');
            }))
            ->willReturn(new Response(200, [], json_encode([['id' => 1]])));

        $builder = $this->createBuilder($mockClient);
        $result = $builder->get();

        $this->assertSame([['id' => 1]], $result);
    }

    public function testFluentWhereAndFields(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);
                return $body['domain'] === [['active', '=', true]]
                    && $body['fields'] === ['name', 'email']
                    && $body['limit'] === 5;
            }))
            ->willReturn(new Response(200, [], json_encode([])));

        $builder = $this->createBuilder($mockClient);
        $builder->where('active', '=', true)
                ->fields(['name', 'email'])
                ->limit(5)
                ->get();
    }

    public function testFirst(): void
    {
        $record = ['id' => 1, 'name' => 'Test'];
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode([$record])));

        $builder = $this->createBuilder($mockClient);
        $result = $builder->first();

        $this->assertSame($record, $result);
    }

    public function testFirstReturnsNullWhenEmpty(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode([])));

        $builder = $this->createBuilder($mockClient);
        $result = $builder->first();

        $this->assertNull($result);
    }

    public function testCount(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return str_contains((string) $request->getUri(), '/search_count');
            }))
            ->willReturn(new Response(200, [], json_encode(42)));

        $builder = $this->createBuilder($mockClient);
        $this->assertSame(42, $builder->count());
    }

    public function testCreate(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode(99)));

        $builder = $this->createBuilder($mockClient);
        $this->assertSame(99, $builder->create(['name' => 'New']));
    }

    public function testUpdate(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode(true)));

        $builder = $this->createBuilder($mockClient);
        $this->assertTrue($builder->update([1], ['name' => 'Updated']));
    }

    public function testDelete(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode(true)));

        $builder = $this->createBuilder($mockClient);
        $this->assertTrue($builder->delete([1, 2]));
    }
}
