<?php

declare(strict_types=1);

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Support\Sanitizers;
use Marwa\Entity\Validation\Rules\Min;
use Marwa\Entity\Validation\Rules\Required;
use Marwa\Entity\Validation\Validator;

require __DIR__ . '/../vendor/autoload.php';

$schema = EntitySchema::make('users');
$schema->string('name')
    ->label('Full Name')
    ->rule(new Required(), new Min(3))
    ->sanitize(Sanitizers::trim());

$schema->string('email')
    ->label('Email')
    ->rule(new Required())
    ->sanitize(Sanitizers::trim(), Sanitizers::lower());

$schema->boolean('is_active')
    ->label('Active');

$entity = new Entity($schema, new Validator());

$input = [
    'name' => $_GET['name'] ?? null,
    'email' => $_GET['email'] ?? null,
    'is_active' => $_GET['is_active'] ?? null,
];

header('Content-Type: application/json; charset=UTF-8');

try {
    $data = $entity->hydrate($input);

    echo json_encode(
        [
            'ok' => true,
            'input' => $input,
            'data' => $data,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    );
} catch (Throwable $e) {
    http_response_code(422);

    echo json_encode(
        [
            'ok' => false,
            'input' => $input,
            'error' => $e->getMessage(),
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    );
}
