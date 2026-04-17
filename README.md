# Marwa Entity

[![Latest Version](https://img.shields.io/packagist/v/memran/marwa-entity.svg)](https://packagist.org/packages/memran/marwa-entity)
[![Total Downloads](https://img.shields.io/packagist/dt/memran/marwa-entity.svg)](https://packagist.org/packages/memran/marwa-entity)
[![License](https://img.shields.io/packagist/l/memran/marwa-entity.svg)](https://packagist.org/packages/memran/marwa-entity)
[![PHP Version](https://img.shields.io/packagist/php-v/memran/marwa-entity.svg)](https://packagist.org/packages/memran/marwa-entity)
[![CI](https://github.com/memran/marwa-entity/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-entity/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/memran/marwa-entity.svg)](https://codecov.io/gh/memran/marwa-entity)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)

Framework-agnostic entity schema, validation, sanitization, and migration metadata for PHP 8.2+.

The package provides a single schema definition that can be reused across typed hydration, form rendering, and migration planning without coupling to a specific framework.

Validation and sanitization are powered by `memran/marwa-support`, keeping the package lightweight and avoiding duplicated utilities.

## Features

- Define fields, types, rules, sanitizers, and metadata in one place
- Reuse the same schema for hydration and migration export
- Type casting and validation in a single pass
- Built-in field types: string, integer, boolean, decimal, datetime, json, enum
- Built-in sanitizers: trim, lower, strip_tags (via marwa-support)

## Requirements

- PHP 8.2 or higher
- Composer

## Installation

```bash
composer require memran/marwa-entity
```

## Quick Start

```php
<?php

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Support\SanitizerFactory;
use Marwa\Entity\Validation\Validator;

$schema = EntitySchema::make('users');

$schema->string('name')
    ->label('Full Name')
    ->sanitize(SanitizerFactory::make('trim'));

$schema->string('email')
    ->label('Email Address')
    ->sanitize(SanitizerFactory::make('trim'), SanitizerFactory::make('lower'));

$schema->boolean('is_active');

$entity = new Entity($schema, new Validator());

$data = $entity->hydrate([
    'name' => '  Emran  ',
    'email' => ' TEST@EXAMPLE.COM ',
    'is_active' => 'true',
]);
```

Hydrated result:

```php
[
    'name' => 'Emran',
    'email' => 'test@example.com',
    'is_active' => true,
]
```

## Usage

### Define a schema

```php
<?php

use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Support\SanitizerFactory;

$schema = EntitySchema::make('users');

$schema->string('name')
    ->label('Full Name')
    ->sanitize(SanitizerFactory::make('trim'));

$schema->string('email')
    ->label('Email Address')
    ->sanitize(SanitizerFactory::make('trim'), SanitizerFactory::make('lower'))
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

If validation fails or a typed cast is invalid, `Entity::hydrate()` throws an `InvalidArgumentException` containing JSON-encoded field errors.

### Available field types

- `string`
- `integer`
- `boolean`
- `decimal`
- `datetime`
- `json`
- `enum`

Example:

```php
$schema->integer('age');
$schema->decimal('balance');
$schema->json('preferences');
$schema->enum('status', ['draft', 'published']);
```

### Sanitizers

Use `SanitizerFactory` to create sanitizers:

```php
use Marwa\Entity\Support\SanitizerFactory;

$schema->string('title')->sanitize(
    SanitizerFactory::make('trim'),
    SanitizerFactory::make('lower'),
    SanitizerFactory::make('strip_tags', ['allowed' => ['strong', 'em']]),
);
```

Built-in sanitizers:

- `trim` - trim whitespace
- `lower` - convert to lowercase
- `strip_tags` - strip HTML tags (supports allowed tags)

### Build a schema from configuration

```php
<?php

use Marwa\Entity\Entity\SchemaFactory;
use Marwa\Entity\Support\SanitizerFactory;

$schema = SchemaFactory::fromArray(
    [
        'name' => 'users',
        'fields' => [
            'name' => [
                'type' => 'string',
                'sanitize' => ['trim'],
            ],
        ],
    ],
    static fn (string $name, array $params = []) => throw new \RuntimeException('Rules not implemented in demo'),
    static fn (string $name, array $params = []) => SanitizerFactory::make($name, $params),
);
```

Supported sanitizer definitions:

```php
'sanitize' => [
    'trim',
    ['name' => 'strip_tags', 'params' => ['allowed' => ['strong']]],
]
```

### Export migration and form metadata

```php
$ui = $schema->uiSpec();
$migration = $schema->migrationSpec();
```

## Configuration Guide

Configuration is intentionally code-first. The package does not require environment variables.

- Use `SchemaFactory::fromArray()` when definitions come from configuration files.
- Register custom sanitizers through `SanitizerFactory::register()`.

## Testing

Run the test suite with:

```bash
composer test
```

Generate text coverage output with:

```bash
composer test:coverage
```

Run the full local CI command set with:

```bash
composer ci
```

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

The lock file is resolved against PHP `8.2.0` in Composer config so CI does not accidentally lock dependencies that require a newer runtime than the package minimum.

## Security Notes

- Validation happens before type casting to avoid silently mutating invalid input into trusted values.
- JSON decoding failures and invalid scalar casts are surfaced as validation failures.

## Contributing

1. Fork the repository.
2. Install dependencies with `composer install`.
3. Run `composer ci` before opening a pull request.
4. Add or update tests for behavior changes.
5. Update examples or documentation when public APIs change.

Keep changes framework-agnostic and prefer PSR interfaces over concrete framework services.

## License

MIT