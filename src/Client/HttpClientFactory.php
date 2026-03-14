<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Client;

use Obuchmann\OdooJsonApi\Config;
use Obuchmann\OdooJsonApi\Exception\OdooException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class HttpClientFactory
{
    public static function create(
        Config $config,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ): HttpClient {
        $client ??= self::discoverClient();
        $requestFactory ??= self::discoverRequestFactory();
        $streamFactory ??= self::discoverStreamFactory();

        return new HttpClient($config, $client, $requestFactory, $streamFactory);
    }

    private static function discoverClient(): ClientInterface
    {
        if (class_exists(\GuzzleHttp\Client::class)) {
            return new \GuzzleHttp\Client();
        }

        throw new OdooException(
            'No PSR-18 HTTP client found. Install guzzlehttp/guzzle (^7.5) or any PSR-18 compatible client.',
        );
    }

    private static function discoverRequestFactory(): RequestFactoryInterface
    {
        if (class_exists(\GuzzleHttp\Psr7\HttpFactory::class)) {
            return new \GuzzleHttp\Psr7\HttpFactory();
        }

        $nyholmClass = 'Nyholm\\Psr7\\Factory\\Psr17Factory';
        if (class_exists($nyholmClass)) {
            /** @var RequestFactoryInterface */
            return new $nyholmClass();        }

        throw new OdooException(
            'No PSR-17 request factory found. Install guzzlehttp/guzzle (^7.5) or nyholm/psr7.',
        );
    }

    private static function discoverStreamFactory(): StreamFactoryInterface
    {
        if (class_exists(\GuzzleHttp\Psr7\HttpFactory::class)) {
            return new \GuzzleHttp\Psr7\HttpFactory();
        }

        $nyholmClass = 'Nyholm\\Psr7\\Factory\\Psr17Factory';
        if (class_exists($nyholmClass)) {
            /** @var StreamFactoryInterface */
            return new $nyholmClass();        }

        throw new OdooException(
            'No PSR-17 stream factory found. Install guzzlehttp/guzzle (^7.5) or nyholm/psr7.',
        );
    }
}
