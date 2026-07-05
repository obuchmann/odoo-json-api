<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Obuchmann\OdooJsonApi\Client\HttpClient;
use Obuchmann\OdooJsonApi\Config;
use Obuchmann\OdooJsonApi\Exception\AuthenticationException;
use Obuchmann\OdooJsonApi\Exception\NotFoundException;
use Obuchmann\OdooJsonApi\Exception\OdooException;
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
            'name' => 'odoo.exceptions.AccessDenied',
            'message' => 'Invalid apikey',
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(401, [], $errorBody));

        $client = $this->createClient($mockClient);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid apikey');
        $client->request('res.partner', 'read');
    }

    public function testThrowsAuthenticationExceptionOn403(): void
    {
        $errorBody = json_encode([
            'name' => 'odoo.exceptions.AccessError',
            'message' => 'You are not allowed to access this record.',
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
        $errorBody = json_encode([
            'name' => 'odoo.exceptions.MissingError',
            'message' => 'Record does not exist or has been deleted.',
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(404, [], $errorBody));

        $client = $this->createClient($mockClient);

        $this->expectException(NotFoundException::class);
        $client->request('res.partner', 'read');
    }

    public function testThrowsValidationExceptionOn400(): void
    {
        $errorBody = json_encode([
            'name' => 'builtins.ValueError',
            'message' => 'Invalid request body',
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(400, [], $errorBody));

        $client = $this->createClient($mockClient);

        $this->expectException(ValidationException::class);
        $client->request('res.partner', 'create');
    }

    public function testThrowsValidationExceptionOn422(): void
    {
        $errorBody = json_encode([
            'name' => 'odoo.exceptions.ValidationError',
            'message' => 'The email address is not valid',
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(422, [], $errorBody));

        $client = $this->createClient($mockClient);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The email address is not valid');
        $client->request('res.partner', 'create');
    }

    public function testThrowsServerExceptionOn500(): void
    {
        $errorBody = json_encode([
            'name' => 'builtins.RuntimeError',
            'message' => 'Something went wrong',
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
            'name' => 'odoo.exceptions.UserError',
            'message' => 'Detailed error',
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
            $this->assertSame('Detailed error', $e->getMessage());
            $this->assertIsArray($e->getErrorData());
            $this->assertSame('odoo.exceptions.UserError', $e->getErrorData()['name']);
        }
    }

    public function testErrorWithoutMessageFallsBackToExceptionName(): void
    {
        $errorBody = json_encode([
            'name' => 'odoo.exceptions.UserError',
        ]);
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(422, [], $errorBody));

        $client = $this->createClient($mockClient);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('odoo.exceptions.UserError');
        $client->request('res.partner', 'create');
    }

    public function testDictResultContainingResultOrErrorKeysIsReturnedAsIs(): void
    {
        $responseData = [
            'result' => ['definition' => 'a field literally named result'],
            'error' => ['definition' => 'a field literally named error'],
        ];
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $client = $this->createClient($mockClient);
        $response = $client->request('res.partner', 'fields_get');

        $this->assertSame($responseData, $response->result);
        $this->assertTrue($response->isSuccess());
    }

    public function testInvalidJsonOnSuccessStatusThrows(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], '<html>not json</html>'));

        $client = $this->createClient($mockClient);

        $this->expectException(OdooException::class);
        $client->request('res.partner', 'read');
    }
}
