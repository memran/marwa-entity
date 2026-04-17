<?php

declare(strict_types=1);

namespace Marwa\Entity\Tests\Validation;

use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testNullableSkipsValidationForEmptyValues(): void
    {
        $schema = EntitySchema::make('users');
        $schema->string('nickname');

        $errors = (new Validator())->validate($schema, ['nickname' => '']);

        self::assertFalse($errors->hasAny());
    }

    public function testValidationWorksWithoutRules(): void
    {
        $schema = EntitySchema::make('users');
        $schema->string('nickname');

        $errors = (new Validator())->validate($schema, ['nickname' => 'ab']);

        self::assertFalse($errors->hasAny());
    }
}
