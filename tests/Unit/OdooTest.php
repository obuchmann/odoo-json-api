<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Obuchmann\OdooJsonApi\Config;
use Obuchmann\OdooJsonApi\Domain;
use Obuchmann\OdooJsonApi\Odoo;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class OdooTest extends TestCase
{
    private function createOdoo(ClientInterface $httpClient): Odoo
    {
        $config = new Config(
            url: 'https://odoo.example.com',
            apiKey: 'test-key',
            database: 'testdb',
        );

        $factory = new HttpFactory();

        return new Odoo($config, $httpClient, $factory, $factory);
    }

    public function testSearchRead(): void
    {
        $data = [['id' => 1, 'name' => 'Test']];
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode($data)));

        $odoo = $this->createOdoo($mockClient);
        $result = $odoo->searchRead('res.partner');

        $this->assertSame($data, $result);
    }

    public function testCreate(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);
                return $body === ['vals_list' => [['name' => 'New Partner']]];
            }))
            ->willReturn(new Response(200, [], json_encode([42])));

        $odoo = $this->createOdoo($mockClient);
        $id = $odoo->create('res.partner', ['name' => 'New Partner']);

        $this->assertSame(42, $id);
    }

    public function testCreateMany(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);
                return $body === ['vals_list' => [['name' => 'One'], ['name' => 'Two']]];
            }))
            ->willReturn(new Response(200, [], json_encode([42, 43])));

        $odoo = $this->createOdoo($mockClient);
        $ids = $odoo->createMany('res.partner', [['name' => 'One'], ['name' => 'Two']]);

        $this->assertSame([42, 43], $ids);
    }

    public function testCountWithLimit(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);
                return $body === ['domain' => [], 'limit' => 10];
            }))
            ->willReturn(new Response(200, [], json_encode(10)));

        $odoo = $this->createOdoo($mockClient);

        $this->assertSame(10, $odoo->count('res.partner', limit: 10));
    }

    public function testWrite(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode(true)));

        $odoo = $this->createOdoo($mockClient);
        $result = $odoo->write('res.partner', [1], ['name' => 'Updated']);

        $this->assertTrue($result);
    }

    public function testUnlink(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode(true)));

        $odoo = $this->createOdoo($mockClient);
        $result = $odoo->unlink('res.partner', [1]);

        $this->assertTrue($result);
    }

    public function testCount(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode(15)));

        $odoo = $this->createOdoo($mockClient);
        $count = $odoo->count('res.partner');

        $this->assertSame(15, $count);
    }

    public function testModelReturnsRequestBuilder(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $odoo = $this->createOdoo($mockClient);

        $builder = $odoo->model('res.partner');

        $this->assertInstanceOf(\Obuchmann\OdooJsonApi\Request\RequestBuilder::class, $builder);
    }

    public function testSearchReadWithDomain(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);
                return $body['domain'] === [['name', 'ilike', 'Test']];
            }))
            ->willReturn(new Response(200, [], json_encode([])));

        $odoo = $this->createOdoo($mockClient);
        $domain = new Domain();
        $domain->where('name', 'ilike', 'Test');
        $odoo->searchRead('res.partner', $domain);
    }

    public function testExecute(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return str_contains((string) $request->getUri(), '/json/2/res.partner/custom_action');
            }))
            ->willReturn(new Response(200, [], json_encode('done')));

        $odoo = $this->createOdoo($mockClient);
        $result = $odoo->execute('res.partner', 'custom_action', ['param' => 'value']);

        $this->assertSame('done', $result);
    }
}
