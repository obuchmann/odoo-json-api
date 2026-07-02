<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Client;

use Obuchmann\OdooJsonApi\Config;
use Obuchmann\OdooJsonApi\Exception\AuthenticationException;
use Obuchmann\OdooJsonApi\Exception\NotFoundException;
use Obuchmann\OdooJsonApi\Exception\OdooException;
use Obuchmann\OdooJsonApi\Exception\ServerException;
use Obuchmann\OdooJsonApi\Exception\ValidationException;
use Obuchmann\OdooJsonApi\Request\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class HttpClient
{
    public function __construct(
        private readonly Config $config,
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function request(string $model, string $method, array $params = []): Response
    {
        $url = sprintf('%s/json/2/%s/%s', $this->config->getBaseUrl(), $model, $method);

        $request = $this->requestFactory->createRequest('POST', $url);
        $request = $request
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Authorization', 'Bearer ' . $this->config->apiKey);

        if ($this->config->database !== null) {
            $request = $request->withHeader('X-Odoo-Database', $this->config->database);
        }

        $body = $this->streamFactory->createStream(json_encode($params, JSON_THROW_ON_ERROR));
        $request = $request->withBody($body);

        try {
            $httpResponse = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new OdooException(
                message: 'HTTP request failed: ' . $e->getMessage(),
                previous: $e,
            );
        }

        $statusCode = $httpResponse->getStatusCode();
        $responseBody = (string) $httpResponse->getBody();
        $decoded = json_decode($responseBody, true);

        if ($statusCode >= 400) {
            // JSON-2 errors carry the error object directly as the body,
            // e.g. {"name": "odoo.exceptions.ValidationError", "message": "..."}.
            $this->throwForStatus($statusCode, new Response(
                statusCode: $statusCode,
                result: null,
                error: is_array($decoded) ? $decoded : null,
            ));
        }

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new OdooException(
                message: 'Invalid JSON response from Odoo: ' . json_last_error_msg(),
                httpStatusCode: $statusCode,
            );
        }

        // JSON-2 success: the body is the bare JSON-serialized return value.
        return new Response(
            statusCode: $statusCode,
            result: $decoded,
        );
    }

    /**
     * Send a GET request to a specific URL path.
     *
     * @return array<string, mixed>
     */
    public function get(string $path): array
    {
        $url = sprintf('%s%s', $this->config->getBaseUrl(), $path);

        $request = $this->requestFactory->createRequest('GET', $url);
        $request = $request
            ->withHeader('Authorization', 'Bearer ' . $this->config->apiKey);

        if ($this->config->database !== null) {
            $request = $request->withHeader('X-Odoo-Database', $this->config->database);
        }

        try {
            $httpResponse = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new OdooException(
                message: 'HTTP request failed: ' . $e->getMessage(),
                previous: $e,
            );
        }

        $statusCode = $httpResponse->getStatusCode();
        $responseBody = (string) $httpResponse->getBody();
        $decoded = json_decode($responseBody, true);

        if ($statusCode >= 400) {
            $this->throwForStatus($statusCode, new Response(
                statusCode: $statusCode,
                result: null,
                error: is_array($decoded) ? $decoded : null,
            ));
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function throwForStatus(int $statusCode, Response $response): never
    {
        $message = $this->extractErrorMessage($response);

        $exceptionClass = match (true) {
            $statusCode === 401, $statusCode === 403 => AuthenticationException::class,
            $statusCode === 404 => NotFoundException::class,
            // Odoo raises UserError/ValidationError as 422 Unprocessable Entity.
            $statusCode === 400, $statusCode === 422 => ValidationException::class,
            $statusCode >= 500 => ServerException::class,
            default => OdooException::class,
        };

        throw new $exceptionClass(
            message: $message,
            code: $statusCode,
            httpStatusCode: $statusCode,
            errorData: $response->error,
        );
    }

    private function extractErrorMessage(Response $response): string
    {
        if ($response->error !== null) {
            if (isset($response->error['message']) && is_string($response->error['message']) && $response->error['message'] !== '') {
                return $response->error['message'];
            }

            if (isset($response->error['name']) && is_string($response->error['name']) && $response->error['name'] !== '') {
                return $response->error['name'];
            }
        }

        return 'Odoo API error (HTTP ' . $response->statusCode . ')';
    }
}
