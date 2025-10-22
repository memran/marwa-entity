<?php

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Validation\Validator;
use Marwa\Entity\Validation\Rules\{Required, StringRule, Min, Unique};
use Marwa\Entity\Support\Sanitizers;

require __DIR__ . '/../vendor/autoload.php';

// Define schema (single source of truth)
$schema = EntitySchema::make('users');
$schema->string('name')
    ->label('Full Name')
    ->rule(new Required(), new StringRule(), new Min(3))
    ->sanitize(Sanitizers::trim());

$schema->string('email')
    ->label('Email')
    ->meta('unique', true)
    ->rule(new Required(), new StringRule(), new Unique(function (string $v, array $ctx): bool {
        // user-provided callable that checks DB via container/ORM
        // return true if unique; false if duplicate
        //$db = $ctx['container']->get('db'); // example
        //return !$db->users()->where('email', $v)->exists();
        return true; // for demo purposes
    }))
    ->sanitize(Sanitizers::trim(), Sanitizers::lower());

$schema->boolean('is_active')
    ->label('Active?')
    ->meta('default', true);

$entity = new Entity($schema, new Validator());

// Hydrate + validate
$input = ['name' => '  MY Name  ', 'email' => ' TEST@EXAMPLE.com ', 'is_active' => '1'];
$data  = $entity->hydrate($input);

var_dump($data);  // $data is sanitized + validated and cast
print_r($schema->migrationSpec());
