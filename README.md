# Odoo JSON API

PHP client for the Odoo JSON-2 API (Odoo 19+).

This library provides a clean, type-safe wrapper around Odoo's new JSON-2 external API, which replaces the legacy XML-RPC and JSON-RPC interfaces.

## Requirements

- PHP 8.3+
- A PSR-18 HTTP client (e.g., Guzzle 7.5+)

## Installation

```bash
composer require obuchmann/odoo-json-api
```

If you don't already have a PSR-18 HTTP client installed:

```bash
composer require guzzlehttp/guzzle
```

## Quick Start

```php
use Obuchmann\OdooJsonApi\Config;
use Obuchmann\OdooJsonApi\Odoo;

$config = new Config(
    url: 'https://your-odoo-instance.com',
    apiKey: 'your-api-key',
    database: 'your-database', // optional if dbfilter is configured
);

$odoo = new Odoo($config);

// Search and read partners
$partners = $odoo->searchRead('res.partner', fields: ['name', 'email'], limit: 10);
```

## Usage

### Configuration

```php
use Obuchmann\OdooJsonApi\Config;

$config = new Config(
    url: 'https://your-odoo-instance.com',
    apiKey: 'your-api-key',
    database: 'mydb',       // optional
);
```

### Direct Methods

```php
use Obuchmann\OdooJsonApi\Domain;

// Search and read
$partners = $odoo->searchRead('res.partner', fields: ['name', 'email'], limit: 10);

// Search with domain filter
$domain = new Domain();
$domain->where('is_company', '=', true)
       ->where('country_id', '=', 14);

$companies = $odoo->searchRead('res.partner', $domain, fields: ['name']);

// Search for IDs only
$ids = $odoo->search('res.partner', $domain);

// Read by IDs
$records = $odoo->read('res.partner', [1, 2, 3], fields: ['name', 'email']);

// Count
$count = $odoo->count('res.partner', $domain);

// Create
$id = $odoo->create('res.partner', [
    'name' => 'New Partner',
    'email' => 'partner@example.com',
]);

// Update
$odoo->write('res.partner', [$id], ['name' => 'Updated Name']);

// Delete
$odoo->unlink('res.partner', [$id]);

// Get field definitions
$fields = $odoo->fieldsGet('res.partner', ['string', 'type']);

// Grouped/aggregated data (formatted_read_group)
$groups = $odoo->readGroup(
    'res.partner',
    groupBy: ['country_id'],
    aggregates: ['__count'],
);

// Call any method (all arguments are named; record methods take an 'ids' key)
$result = $odoo->execute('res.partner', 'custom_method', ['param' => 'value']);
$result = $odoo->execute('res.partner', 'action_archive', ['ids' => [1, 2]]);
```

### Fluent Request Builder

```php
// Fluent interface scoped to a model
$partners = $odoo->model('res.partner')
    ->where('is_company', '=', true)
    ->fields(['name', 'email', 'phone'])
    ->orderBy('name asc')
    ->limit(20)
    ->offset(0)
    ->get();

// Get first matching record
$partner = $odoo->model('res.partner')
    ->where('email', '=', 'user@example.com')
    ->fields(['name', 'email'])
    ->first();

// Find by ID
$partner = $odoo->model('res.partner')
    ->fields(['name', 'email'])
    ->find(42);

// Count
$count = $odoo->model('res.partner')
    ->where('active', '=', true)
    ->count();

// CRUD via builder
$id = $odoo->model('res.partner')->create(['name' => 'New']);
$odoo->model('res.partner')->update([1, 2], ['active' => false]);
$odoo->model('res.partner')->delete([1, 2]);
```

### Domain Filters

```php
use Obuchmann\OdooJsonApi\Domain;

$domain = new Domain();
$domain->where('name', 'ilike', 'test')
       ->where('active', '=', true)
       ->orWhere('email', '!=', false);
```

`orWhere()` combines with the *previous* condition (Odoo prefix notation):
`where(A)->orWhere(B)` produces `['|', A, B]` (A OR B), and
`where(A)->where(B)->orWhere(C)` produces `[A, '|', B, C]` (A AND (B OR C)).

### Grouping / Aggregation

`readGroup()` wraps Odoo's `formatted_read_group` (the JSON-2 replacement for
the deprecated `read_group`):

```php
$groups = $odoo->model('res.partner')
    ->where('active', '=', true)
    ->readGroup(['country_id'], ['__count', 'credit_limit:sum']);
```

### Error Handling

Errors are raised as exceptions mapped from the HTTP status code:

| Status | Exception |
|---|---|
| 401 / 403 | `AuthenticationException` |
| 404 | `NotFoundException` |
| 400 / 422 | `ValidationException` (Odoo `UserError`/`ValidationError`) |
| 5xx | `ServerException` |
| other | `OdooException` (base class of all of the above) |

```php
use Obuchmann\OdooJsonApi\Exception\OdooException;

try {
    $odoo->create('res.partner', ['email' => 'not-an-email']);
} catch (OdooException $e) {
    $e->getMessage();        // Odoo's error message
    $e->getHttpStatusCode(); // e.g. 422
    $e->getErrorData();      // full JSON-2 error object, e.g. ['name' => 'odoo.exceptions.ValidationError', ...]
}
```

### Context

```php
use Obuchmann\OdooJsonApi\Context;

$context = new Context(['lang' => 'fr_FR', 'tz' => 'Europe/Paris']);

$partners = $odoo->searchRead('res.partner', context: $context);
```

### Custom HTTP Client

You can provide your own PSR-18 client:

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;

$httpClient = new GuzzleClient(['timeout' => 30]);
$factory = new HttpFactory();

$odoo = new Odoo($config, $httpClient, $factory, $factory);
```

## Testing

```bash
# Unit tests
composer test:unit

# Static analysis
composer analyse
```

### Integration Tests

Integration tests require a running Odoo 19 instance with an API key:

```bash
# Start Odoo via Docker
cd docker && docker compose up -d

# Run integration tests
ODOO_HOST=http://localhost:8069 ODOO_API_KEY=your-key ODOO_DATABASE=odoo composer test:integration
```

## License

MIT
