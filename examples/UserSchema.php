<?php

declare(strict_types=1);

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Support\SanitizerFactory;
use Marwa\Entity\Validation\Validator;

require __DIR__ . '/../vendor/autoload.php';

// Define schema (single source of truth)
$schema = EntitySchema::make('users');
$schema->string('name')
    ->label('Full Name')
    ->sanitize(SanitizerFactory::make('trim'));

$schema->string('email')
    ->label('Email')
    ->meta('unique', true)
    ->sanitize(SanitizerFactory::make('trim'), SanitizerFactory::make('lower'));

$schema->boolean('is_active')
    ->label('Active?')
    ->meta('default', true);

$entity = new Entity($schema, new Validator());

// Hydrate + validate
$input = ['name' => '  MY Name  ', 'email' => ' TEST@EXAMPLE.com ', 'is_active' => '1'];
$data  = $entity->hydrate($input);

var_dump($data);  // $data is sanitized + validated and cast
print_r($schema->migrationSpec());
