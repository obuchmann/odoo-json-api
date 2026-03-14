<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Request;

use Obuchmann\OdooJsonApi\Client\HttpClient;
use Obuchmann\OdooJsonApi\Context;
use Obuchmann\OdooJsonApi\Domain;
use Obuchmann\OdooJsonApi\Request\Traits\HasContext;
use Obuchmann\OdooJsonApi\Request\Traits\HasDomain;
use Obuchmann\OdooJsonApi\Request\Traits\HasFields;
use Obuchmann\OdooJsonApi\Request\Traits\HasGroupBy;
use Obuchmann\OdooJsonApi\Request\Traits\HasLimit;
use Obuchmann\OdooJsonApi\Request\Traits\HasOffset;
use Obuchmann\OdooJsonApi\Request\Traits\HasOrder;

class RequestBuilder
{
    use HasDomain;
    use HasFields;
    use HasLimit;
    use HasOffset;
    use HasOrder;
    use HasGroupBy;
    use HasContext;

    private Domain $domain;

    public function __construct(
        private readonly HttpClient $client,
        private readonly string $model,
        ?Domain $domain = null,
    ) {
        $this->domain = $domain ?? new Domain();
    }

    /**
     * Search and read records matching the domain.
     *
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        $request = new SearchReadRequest(
            model: $this->model,
            domain: $this->domain,
            fields: $this->fields,
            offset: $this->offset,
            limit: $this->limit,
            order: $this->order,
            context: $this->context,
        );

        return $this->execute($request);
    }

    /**
     * Search for record IDs matching the domain.
     *
     * @return list<int>
     */
    public function search(): array
    {
        $request = new SearchRequest(
            model: $this->model,
            domain: $this->domain,
            offset: $this->offset,
            limit: $this->limit,
            order: $this->order,
            context: $this->context,
        );

        return $this->execute($request);
    }

    /**
     * Get the first record matching the domain.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $request = new SearchReadRequest(
            model: $this->model,
            domain: $this->domain,
            fields: $this->fields,
            offset: $this->offset,
            limit: 1,
            order: $this->order,
            context: $this->context,
        );

        $result = $this->execute($request);

        return $result[0] ?? null;
    }

    /**
     * Read a single record by ID.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $request = new ReadRequest(
            model: $this->model,
            ids: [$id],
            fields: $this->fields,
            context: $this->context,
        );

        $result = $this->execute($request);

        return $result[0] ?? null;
    }

    /**
     * Read records by IDs.
     *
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function read(array $ids): array
    {
        $request = new ReadRequest(
            model: $this->model,
            ids: $ids,
            fields: $this->fields,
            context: $this->context,
        );

        return $this->execute($request);
    }

    /**
     * Count records matching the domain.
     */
    public function count(): int
    {
        $request = new SearchCountRequest(
            model: $this->model,
            domain: $this->domain,
            context: $this->context,
        );

        return (int) $this->execute($request);
    }

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $values
     */
    public function create(array $values): int
    {
        $request = new CreateRequest(
            model: $this->model,
            values: $values,
            context: $this->context,
        );

        return (int) $this->execute($request);
    }

    /**
     * Update records by IDs.
     *
     * @param list<int> $ids
     * @param array<string, mixed> $values
     */
    public function update(array $ids, array $values): bool
    {
        $request = new WriteRequest(
            model: $this->model,
            ids: $ids,
            values: $values,
            context: $this->context,
        );

        return (bool) $this->execute($request);
    }

    /**
     * Delete records by IDs.
     *
     * @param list<int> $ids
     */
    public function delete(array $ids): bool
    {
        $request = new UnlinkRequest(
            model: $this->model,
            ids: $ids,
            context: $this->context,
        );

        return (bool) $this->execute($request);
    }

    /**
     * Get model field definitions.
     *
     * @param list<string>|null $attributes
     * @return array<string, mixed>
     */
    public function fieldsGet(?array $attributes = null): array
    {
        $request = new FieldsGetRequest(
            model: $this->model,
            attributes: $attributes,
            context: $this->context,
        );

        return $this->execute($request);
    }

    /**
     * Read grouped records.
     *
     * @param list<string> $groupBy
     * @return list<array<string, mixed>>
     */
    public function readGroup(array $groupBy, bool $lazy = true): array
    {
        $request = new ReadGroupRequest(
            model: $this->model,
            domain: $this->domain,
            fields: $this->fields,
            groupBy: $groupBy,
            offset: $this->offset,
            limit: $this->limit,
            orderBy: $this->order,
            lazy: $lazy,
            context: $this->context,
        );

        return $this->execute($request);
    }

    /**
     * Execute an arbitrary method on the model.
     *
     * @param array<string, mixed> $params
     */
    public function call(string $method, array $params = []): mixed
    {
        $request = new ExecuteRequest(
            model: $this->model,
            method: $method,
            params: $params,
        );

        return $this->execute($request);
    }

    private function execute(Request $request): mixed
    {
        $response = $this->client->request(
            $request->getModel(),
            $request->getMethod(),
            $request->toArray(),
        );

        return $response->result;
    }
}
