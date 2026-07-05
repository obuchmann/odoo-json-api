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
     * Iterate over all matching records, fetching them in chunks.
     *
     * Honors offset() as the starting position and limit() as the total
     * maximum number of records.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function lazy(int $chunkSize = 100): \Generator
    {
        if ($chunkSize < 1) {
            throw new \InvalidArgumentException('Chunk size must be at least 1.');
        }

        $offset = $this->offset;
        $remaining = $this->limit;

        while ($remaining === null || $remaining > 0) {
            $size = $remaining !== null ? min($chunkSize, $remaining) : $chunkSize;

            $request = new SearchReadRequest(
                model: $this->model,
                domain: $this->domain,
                fields: $this->fields,
                offset: $offset,
                limit: $size,
                order: $this->order,
                context: $this->context,
            );

            /** @var list<array<string, mixed>> $records */
            $records = $this->execute($request);

            foreach ($records as $record) {
                yield $record;
            }

            if (count($records) < $size) {
                return;
            }

            $offset += $size;
            if ($remaining !== null) {
                $remaining -= $size;
            }
        }
    }

    /**
     * Count records matching the domain, honoring limit() as an upper bound.
     */
    public function count(): int
    {
        $request = new SearchCountRequest(
            model: $this->model,
            domain: $this->domain,
            limit: $this->limit,
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
        return $this->createMany([$values])[0] ?? 0;
    }

    /**
     * Create multiple records in a single call.
     *
     * @param list<array<string, mixed>> $valsList
     * @return list<int> the created record IDs
     */
    public function createMany(array $valsList): array
    {
        $request = new CreateRequest(
            model: $this->model,
            valsList: $valsList,
            context: $this->context,
        );

        $result = $this->execute($request);

        if (!is_array($result)) {
            return [(int) $result];
        }

        return array_values(array_map(intval(...), $result));
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
     * @param list<string>|null $allFields field names to describe; all fields when null
     * @return array<string, mixed>
     */
    public function fieldsGet(?array $attributes = null, ?array $allFields = null): array
    {
        $request = new FieldsGetRequest(
            model: $this->model,
            attributes: $attributes,
            allFields: $allFields,
            context: $this->context,
        );

        return $this->execute($request);
    }

    /**
     * Read grouped/aggregated records via formatted_read_group.
     *
     * Falls back to the groupBy() value when $groupBy is omitted.
     *
     * @param list<string>|null $groupBy
     * @param list<string> $aggregates e.g. ['amount_total:sum', '__count']
     * @param list<mixed> $having
     * @return list<array<string, mixed>>
     */
    public function readGroup(?array $groupBy = null, array $aggregates = [], array $having = []): array
    {
        $request = new FormattedReadGroupRequest(
            model: $this->model,
            domain: $this->domain,
            groupBy: $groupBy ?? $this->groupBy,
            aggregates: $aggregates,
            having: $having,
            offset: $this->offset,
            limit: $this->limit,
            order: $this->order,
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
            context: $this->context,
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
