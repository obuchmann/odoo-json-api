<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Obuchmann\OdooJsonApi\Client\HttpClient;
use Obuchmann\OdooJsonApi\Config;
use Obuchmann\OdooJsonApi\Exception\AuthenticationException;
use Obuchmann\OdooJsonApi\Exception\NotFoundException;
use Obuchmann\OdooJsonApi\Exception\ServerException;
use Obuchmann\OdooJsonApi\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class ClientTest extends TestCase
{
    private function createClient(
        ClientInterface $httpClient,
        ?string $database = null,
    ): HttpClient {
        $config = new Config(
            url: 'https://odoo.example.com',
            apiKey: 'test-api-key',
            database: $database,
        );

        $factory = new HttpFactory();

        return new HttpClient($config, $httpClient, $factory, $factory);
    }

    public function testRequestBuildsCorrectUrl(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return (string) $request->getUri() === 'https://odoo.example.com/json/2/res.partner/search_read';
            }))
            ->willReturn(new Response(200, [], json_encode(['id' => 1])));

        $client = $this->createClient($mockClient);
        $client->request('res.partner', 'search_read');
    }

    public function testRequestSetsAuthorizationHeader(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getHeaderLine('Authorization') === 'Bearer test-api-key';
            }))
            ->willReturn(new Response(200, [], json_encode([])));

        $client = $this->createClient($mockClient);
        $client->request('res.partner', 'read');
    }

    public function testRequestSetsContentTypeHeader(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getHeaderLine('Content-Type') === 'application/json; charset=utf-8';
            }))
            ->willReturn(new Response(200, [], json_encode([])));

        $client = $this->createClient($mockClient);
        $client->request('res.partner', 'read');
    }

    public function testRequestSetsDatabaseHeader(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getHeaderLine('X-Odoo-Database') === 'mydb';
            }))
            ->willReturn(new Response(200, [], json_encode([])));

        $client = $this->createClient($mockClient, database: 'mydb');
        $client->request('res.partner', 'read');
    }

    public function testRequestOmitsDatabaseHeaderWhenNull(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return !$request->hasHeader('X-Odoo-Database');
            }))
            ->willReturn(new Response(200, [], json_encode([])));

        $client = $this->createClient($mockClient);
        $client->request('res.partner', 'read');
    }

    public function testRequestSendsJsonBody(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body = json_decode((string) $request->getBody(), true);
                return $body === ['domain' => [['name', '=', 'Test']]];
            }))
            ->willReturn(new Response(200, [], json_encode([])));

        $client = $this->createClient($mockClient);
        $client->request('res.partner', 'search', ['domain' => [['name', '=', 'Test']]]);
    }

    public function testSuccessfulResponseReturnsResult(): void
    {
        $responseData = [['id' => 1, 'name' => 'Test Partner']];
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $client = $this->createClient($mockClient);
        $response = $client->request('res.partner', 'search_read');

        $this->assertSame($responseData, $response->result);
        $this->assertTrue($response->isSuccess());
    }

    public function testThrowsAuthenticationExceptionOn401(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Unauthorized',
                'data' => ['message' => 'Invalid API key'],
            ],
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(401, [], $errorBody));

        $client = $this->createClient($mockClient);

        $this->expectException(AuthenticationException::class);
        $client->request('res.partner', 'read');
    }

    public function testThrowsAuthenticationExceptionOn403(): void
    {
        $errorBody = json_encode([
            'error' => ['message' => 'Forbidden'],
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(403, [], $errorBody));

        $client = $this->createClient($mockClient);

        $this->expectException(AuthenticationException::class);
        $client->request('res.partner', 'read');
    }

    public function testThrowsNotFoundExceptionOn404(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(404, [], json_encode(['error' => ['message' => 'Not found']])));

        $client = $this->createClient($mockClient);

        $this->expectException(NotFoundException::class);
        $client->request('res.partner', 'read');
    }

    public function testThrowsValidationExceptionOn400(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(400, [], json_encode(['error' => ['message' => 'Bad request']])));

        $client = $this->createClient($mockClient);

        $this->expectException(ValidationException::class);
        $client->request('res.partner', 'create');
    }

    public function testThrowsServerExceptionOn500(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Internal Server Error',
                'data' => ['message' => 'Something went wrong'],
            ],
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(500, [], $errorBody));

        $client = $this->createClient($mockClient);

        $this->expectException(ServerException::class);
        $client->request('res.partner', 'read');
    }

    public function testExceptionContainsStatusCodeAndErrorData(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Server Error',
                'data' => ['message' => 'Detailed error'],
            ],
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(500, [], $errorBody));

        $client = $this->createClient($mockClient);

        try {
            $client->request('res.partner', 'read');
            $this->fail('Expected ServerException');
        } catch (ServerException $e) {
            $this->assertSame(500, $e->getHttpStatusCode());
            $this->assertIsArray($e->getErrorData());
        }
    }
}
