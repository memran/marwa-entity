# Marwa Entity

[![Latest Version](https://img.shields.io/packagist/v/memran/marwa-entity.svg)](https://packagist.org/packages/memran/marwa-entity)
[![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-entity.svg)](https://packagist.org/packages/memran/marwa-entity)
[![License](https://img.shields.io/packagist/l/memran/marwa-entity.svg)](https://packagist.org/packages/memran/marwa-entity)
[![PHP Version](https://img.shields.io/packagist/php-v/memran/marwa-entity.svg)](https://packagist.org/packages/memran/marwa-entity)
[![CI](https://github.com/memran/marwa-entity/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-entity/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/memran/marwa-entity.svg)](https://codecov.io/gh/memran/marwa-entity)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)

Framework-agnostic entity schema, validation, sanitization, form metadata, and migration metadata for PHP 8.2+.

The package is designed around a single schema definition that can be reused across request validation, typed hydration, UI generation, and migration planning without coupling to a specific framework.

## Features

- Define fields, types, rules, sanitizers, and metadata in one place
- Reuse the same schema for validation, hydration, form rendering, and migration export
- Integrate with PSR-7 and PSR-15 request pipelines
- Extend rules and sanitizers through factories and registries
- Keep framework dependencies out of the core package

## Requirements

- PHP 8.2 or higher
- Composer
- PSR-compatible HTTP message interfaces when using the HTTP layer

## Installation

```bash
composer require memran/marwa-entity
```

For development:

```bash
composer install
```

## Usage

### Define a schema

```php
<?php

use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Support\Sanitizers;
use Marwa\Entity\Validation\Rules\Email;
use Marwa\Entity\Validation\Rules\Min;
use Marwa\Entity\Validation\Rules\Required;

$schema = EntitySchema::make('users');

$schema->string('name')
    ->label('Full Name')
    ->rule(new Required(), new Min(3))
    ->sanitize(Sanitizers::trim());

$schema->string('email')
    ->label('Email Address')
    ->rule(new Required(), new Email())
    ->sanitize(Sanitizers::trim(), Sanitizers::lower())
    ->meta('unique', true)
    ->meta('widget', 'email');

$schema->boolean('is_active')
    ->label('Active')
    ->meta('default', true);
```

### Hydrate and validate input

```php
<?php

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Validation\Validator;

$entity = new Entity($schema, new Validator());

$data = $entity->hydrate([
    'name' => '  Emran  ',
    'email' => '  TEST@EXAMPLE.COM ',
    'is_active' => 'true',
]);

/*
[
    'name' => 'Emran',
    'email' => 'test@example.com',
    'is_active' => true,
]
*/
```

### Build a schema from configuration

```php
<?php

use Marwa\Entity\Entity\SchemaFactory;
use Marwa\Entity\Support\SanitizerFactory;
use Marwa\Entity\Validation\RuleFactory;

$schema = SchemaFactory::fromArray(
    [
        'name' => 'users',
        'fields' => [
            'name' => [
                'type' => 'string',
                'rules' => [
                    ['name' => 'required'],
                    ['name' => 'min', 'params' => ['min' => 3]],
                ],
                'sanitize' => ['trim'],
            ],
        ],
    ],
    static fn (string $name, array $params = []) => RuleFactory::make($name, $params),
    static fn (string $name, array $params = []) => SanitizerFactory::make($name, $params),
);
```

### Use with PSR-7 requests

```php
<?php

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Http\FormRequest;
use Psr\Http\Message\ServerRequestInterface;

final class UserStoreRequest extends FormRequest
{
    public function __construct(ServerRequestInterface $request, private readonly Entity $entity)
    {
        parent::__construct($request);
    }

    protected function entity(): Entity
    {
        return $this->entity;
    }
}
```

### Export migration and form metadata

```php
$ui = $schema->uiSpec();
$migration = $schema->migrationSpec();
```

## Configuration Guide

Configuration is intentionally code-first. The package does not require environment variables and does not ship framework-specific config files.

- Use `SchemaFactory::fromArray()`, `fromJson()`, or `fromYaml()` when definitions come from configuration files.
- Register custom rules through `RuleFactory::register()` or `RuleRegistry`.
- Register custom sanitizers through `SanitizerFactory::register()`.
- Pass infrastructure dependencies such as containers or requests through validation context instead of coupling schema code to services.

Example custom rule registration:

```php
use Marwa\Entity\Validation\RuleFactory;
use Marwa\Entity\Validation\Rules\AbstractRule;

RuleFactory::register('uppercase', static function (): AbstractRule {
    return new class () extends AbstractRule {
        public function __construct()
        {
            $this->message = 'The :field must be uppercase.';
        }

        public function name(): string
        {
            return 'uppercase';
        }

        public function validate(mixed $value, array $context = []): bool
        {
            return $value === null || strtoupper((string) $value) === (string) $value;
        }
    };
});
```

## Testing

Run the test suite with:

```bash
composer test
```

Generate text coverage output with:

```bash
composer test:coverage
```

The current test suite covers typed hydration, validator behavior, form request integration, and schema factory configuration handling.

## Static Analysis

PHPStan is configured at max level:

```bash
composer analyse
```

Coding standards can be checked or fixed with:

```bash
composer lint
composer fix
```

## CI/CD

GitHub Actions is configured in `.github/workflows/ci.yml`.

The pipeline runs:

- `composer validate --strict`
- coding standards
- PHPStan analysis
- PHPUnit with coverage generation

The matrix targets PHP 8.2, 8.3, and 8.4.

## Security Notes

- Validation happens before type casting to avoid silently mutating invalid input into trusted values.
- JSON decoding failures and invalid scalar casts are surfaced as validation failures.
- HTTP middleware enforces safer defaults for content type and oversized payload rejection.
- The package does not manage sessions, CSRF tokens, or persistence. Those concerns should be handled by the host application or framework.

## Contributing

1. Fork the repository.
2. Install dependencies with `composer install`.
3. Run `composer ci` before opening a pull request.
4. Add or update tests for behavior changes.
5. Update examples or documentation when public APIs change.

Keep changes framework-agnostic and prefer PSR interfaces or local contracts over concrete framework services.

## License

MIT
