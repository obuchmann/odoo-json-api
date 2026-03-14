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

        $error = null;
        $result = $decoded;

        if (is_array($decoded)) {
            $error = $decoded['error'] ?? null;
            $result = $decoded['result'] ?? $decoded;
        }

        $response = new Response(
            statusCode: $statusCode,
            result: $result,
            error: is_array($error) ? $error : null,
        );

        if (!$response->isSuccess()) {
            $this->throwForStatus($statusCode, $response);
        }

        return $response;
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
                result: $decoded,
                error: is_array($decoded) ? ($decoded['error'] ?? null) : null,
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
            $statusCode === 400 => ValidationException::class,
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
            $data = $response->error['data'] ?? [];
            if (is_array($data) && isset($data['message']) && is_string($data['message'])) {
                return $data['message'];
            }

            if (isset($response->error['message']) && is_string($response->error['message'])) {
                return $response->error['message'];
            }
        }

        return 'Odoo API error (HTTP ' . $response->statusCode . ')';
    }
}
