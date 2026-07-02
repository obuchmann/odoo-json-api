<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi;

use Obuchmann\OdooJsonApi\Client\HttpClient;
use Obuchmann\OdooJsonApi\Client\HttpClientFactory;
use Obuchmann\OdooJsonApi\Request\RequestBuilder;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Odoo
{
    private readonly HttpClient $client;

    public function __construct(
        Config $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        if ($httpClient !== null && $requestFactory !== null && $streamFactory !== null) {
            $this->client = new HttpClient($config, $httpClient, $requestFactory, $streamFactory);
        } else {
            $this->client = HttpClientFactory::create($config, $httpClient, $requestFactory, $streamFactory);
        }
    }

    /**
     * Get a fluent request builder for a model.
     */
    public function model(string $model): RequestBuilder
    {
        return new RequestBuilder($this->client, $model);
    }

    /**
     * Search and read records.
     *
     * @param list<string>|null $fields
     * @return list<array<string, mixed>>
     */
    public function searchRead(
        string $model,
        ?Domain $domain = null,
        ?array $fields = null,
        int $offset = 0,
        ?int $limit = null,
        ?string $order = null,
        ?Context $context = null,
    ): array {
        $request = new Request\SearchReadRequest(
            model: $model,
            domain: $domain ?? new Domain(),
            fields: $fields,
            offset: $offset,
            limit: $limit,
            order: $order,
            context: $context,
        );

        return $this->call($request);
    }

    /**
     * Search for record IDs.
     *
     * @return list<int>
     */
    public function search(
        string $model,
        ?Domain $domain = null,
        int $offset = 0,
        ?int $limit = null,
        ?string $order = null,
        ?Context $context = null,
    ): array {
        $request = new Request\SearchRequest(
            model: $model,
            domain: $domain ?? new Domain(),
            offset: $offset,
            limit: $limit,
            order: $order,
            context: $context,
        );

        return $this->call($request);
    }

    /**
     * Read records by IDs.
     *
     * @param list<int> $ids
     * @param list<string>|null $fields
     * @return list<array<string, mixed>>
     */
    public function read(
        string $model,
        array $ids,
        ?array $fields = null,
        ?string $load = null,
        ?Context $context = null,
    ): array {
        $request = new Request\ReadRequest(
            model: $model,
            ids: $ids,
            fields: $fields,
            load: $load,
            context: $context,
        );

        return $this->call($request);
    }

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $values
     */
    public function create(string $model, array $values, ?Context $context = null): int
    {
        $request = new Request\CreateRequest(
            model: $model,
            values: $values,
            context: $context,
        );

        $result = $this->call($request);

        // JSON2 API returns an array of IDs for create (vals_list); extract the first one.
        if (is_array($result)) {
            return (int) ($result[0] ?? 0);
        }

        return (int) $result;
    }

    /**
     * Update records.
     *
     * @param list<int> $ids
     * @param array<string, mixed> $values
     */
    public function write(string $model, array $ids, array $values, ?Context $context = null): bool
    {
        $request = new Request\WriteRequest(
            model: $model,
            ids: $ids,
            values: $values,
            context: $context,
        );

        return (bool) $this->call($request);
    }

    /**
     * Delete records.
     *
     * @param list<int> $ids
     */
    public function unlink(string $model, array $ids, ?Context $context = null): bool
    {
        $request = new Request\UnlinkRequest(
            model: $model,
            ids: $ids,
            context: $context,
        );

        return (bool) $this->call($request);
    }

    /**
     * Count records matching the domain.
     */
    public function count(string $model, ?Domain $domain = null, ?Context $context = null): int
    {
        $request = new Request\SearchCountRequest(
            model: $model,
            domain: $domain ?? new Domain(),
            context: $context,
        );

        return (int) $this->call($request);
    }

    /**
     * Get model field definitions.
     *
     * @param list<string>|null $attributes
     * @return array<string, mixed>
     */
    public function fieldsGet(string $model, ?array $attributes = null, ?Context $context = null): array
    {
        $request = new Request\FieldsGetRequest(
            model: $model,
            attributes: $attributes,
            context: $context,
        );

        return $this->call($request);
    }

    /**
     * Read grouped/aggregated data via formatted_read_group.
     *
     * @param list<string> $groupBy
     * @param list<string> $aggregates e.g. ['amount_total:sum', '__count']
     * @return list<array<string, mixed>>
     */
    public function readGroup(
        string $model,
        ?Domain $domain = null,
        array $groupBy = [],
        array $aggregates = [],
        ?Context $context = null,
    ): array {
        $request = new Request\FormattedReadGroupRequest(
            model: $model,
            domain: $domain ?? new Domain(),
            groupBy: $groupBy,
            aggregates: $aggregates,
            context: $context,
        );

        return $this->call($request);
    }

    /**
     * Execute an arbitrary method on a model.
     *
     * @param array<string, mixed> $params
     */
    public function execute(string $model, string $method, array $params = [], ?Context $context = null): mixed
    {
        $request = new Request\ExecuteRequest(
            model: $model,
            method: $method,
            params: $params,
            context: $context,
        );

        return $this->call($request);
    }

    /**
     * Get the underlying HTTP client.
     */
    public function getClient(): HttpClient
    {
        return $this->client;
    }

    private function call(Request\Request $request): mixed
    {
        $response = $this->client->request(
            $request->getModel(),
            $request->getMethod(),
            $request->toArray(),
        );

        return $response->result;
    }
}
